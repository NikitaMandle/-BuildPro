<?php
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);
session_start();
require __DIR__.'/../includes/helpers.php';
$p = explode('/', trim($_GET['path'] ?? '', '/'));
$res = $p[0]; $id = (int)($p[1] ?? 0); $sub = $p[1] ?? '';
$m = $_SERVER['REQUEST_METHOD'];
if ($m==='POST' && isset($_POST['_method'])) $m = strtoupper($_POST['_method']);

try {
  switch ($res) {
    case 'auth':
      if ($sub==='login' && $m==='POST') {
        $i = input(); need($i, ['login','secret']); usleep(300000);
        if (hash_equals(env('ADMIN_USER'), $i['login']) && hash_equals(env('ADMIN_PASS'), $i['secret'])) $a = ['id'=>0,'name'=>'Admin','role'=>'admin'];
        else {
          $q = db()->prepare('SELECT id,name,role,pin_hash FROM users WHERE mobile=?'); $q->execute([$i['login']]); $u = $q->fetch();
          if (!$u || !password_verify($i['secret'], $u['pin_hash'])) fail('Invalid credentials', 401);
          $a = ['id'=>(int)$u['id'],'name'=>$u['name'],'role'=>$u['role']];
        }
        session_regenerate_id(true); $_SESSION['auth'] = $a; ok($a);
      }
      if ($sub==='logout') { session_destroy(); ok(); }
      if ($sub==='me') ok(me() ?? fail('Not logged in', 401));
      fail('Not found', 404);

    case 'users':
      resource('users', ['admin'], 'id,name,mobile,email,gst,role,created_at', function ($in, $u) {
        $d = clean($in, $u, ['name','mobile','email','gst','pin','role']);
        if (isset($d['mobile']) && !preg_match('/^\d{10}$/', $d['mobile'])) fail('Mobile must be 10 digits', 422);
        if (isset($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) fail('Invalid email', 422);
        if (isset($d['gst']) && !preg_match('/^[0-9A-Za-z]{15}$/', $d['gst'])) fail('GST must be 15 characters', 422);
        if (isset($d['role']) && !in_array($d['role'], ['subadmin','user'])) fail('Role must be subadmin or user', 422);
        if (isset($d['pin'])) { if (!preg_match('/^\d{4}$/', $d['pin'])) fail('PIN must be 4 digits', 422); $d['pin_hash'] = password_hash($d['pin'], PASSWORD_DEFAULT); unset($d['pin']); }
        return $d;
      }, [], $id, $m);

    case 'categories':
      resource('categories', ['admin','subadmin'], '*', fn($in,$u) => clean($in,$u,['name']), [], $id, $m);

    case 'products':
      resource('products', ['admin','subadmin'], '*', function ($in, $u) {
        $d = clean($in, $u, ['category_id','name'], ['description','price','unit']);
        if (isset($d['price']) && !is_numeric($d['price'])) fail('Price must be a number', 422);
        return $d;
      }, ['category_id'], $id, $m);

    default: fail('Unknown resource', 404);
  }
} catch (PDOException $e) {
  fail($e->getCode()==='23000' ? 'Duplicate value or invalid category' : 'Database error: '.$e->getMessage(), 422);
}
