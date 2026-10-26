<?php
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);
session_start();
require __DIR__.'/../includes/helpers.php';
restore_session();
$p = explode('/', trim($_GET['path'] ?? '', '/'));
$res = $p[0]; $id = (int)($p[1] ?? 0); $sub = $p[1] ?? '';
$m = $_SERVER['REQUEST_METHOD'];
if ($m==='POST' && isset($_POST['_method'])) $m = strtoupper($_POST['_method']);
if ($m==='GET') session_write_close(); // reads don't write the session; don't block parallel requests

$dispatch = function () use ($res, $id, $sub, $m) {
  switch ($res) {
    case 'auth':
      if ($sub==='login' && $m==='POST') {
        $i = input(); need($i, ['login','secret']); usleep(300000);
        $login = trim((string)$i['login']); $secret = (string)$i['secret'];
        if (hash_equals((string)env('ADMIN_USER'), $login) && hash_equals((string)env('ADMIN_PASS'), $secret)) $a = ['id'=>0,'name'=>'Admin','role'=>'admin'];
        else {
          // subadmin / user can log in with their username OR their 10-digit mobile number
          $q = db()->prepare('SELECT id,name,role,pin_hash FROM users WHERE username=? OR mobile=? LIMIT 1'); $q->execute([$login, $login]); $u = $q->fetch();
          if (!$u || !password_verify($secret, $u['pin_hash'])) fail('Invalid credentials', 401);
          $a = ['id'=>(int)$u['id'],'name'=>$u['name'],'role'=>$u['role']];
        }
        session_regenerate_id(true); $_SESSION['auth'] = $a; remember_set($a); ok($a);
      }
      if ($sub==='logout') { remember_clear(); session_destroy(); ok(); }
      if ($sub==='me') ok(me() ?? fail('Not logged in', 401));
      fail('Not found', 404);

    case 'users':
      require_role('admin'); // users data (mobile/email/GST) is admin-only, even for reading
      resource('users', ['admin'], 'id,name,username,mobile,email,gst,role,created_at', function ($in, $u) {
        $d = clean($in, $u, ['name','username','mobile','email','gst','pin','role']);
        if (isset($d['username'])) {
          if (!preg_match('/^[A-Za-z][A-Za-z0-9_.]{2,29}$/', $d['username'])) fail('Username must be 3-30 chars, start with a letter, only letters/numbers/_ .', 422);
          if (strcasecmp($d['username'], (string)env('ADMIN_USER')) === 0) fail('This username is reserved', 422);
        }
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
      if ($m !== 'GET') ensure_products_schema();
      resource('products', ['admin','subadmin'], 'id,category_id,name,description,price,created_at', function ($in, $u) {
        $d = clean($in, $u, ['category_id','name']);
        // description (rich HTML, sanitized) and price (free text) may be sent blank to clear them
        if (array_key_exists('description', $in)) $d['description'] = sanitize_html($in['description']);
        if (array_key_exists('price', $in)) $d['price'] = trim((string)$in['price']);
        if (!$u && !isset($d['price'])) fail('price is required', 422);
        if (isset($d['price']) && preg_match_all('/./us', $d['price']) > 100) fail('Price must be at most 100 characters', 422);
        return $d;
      }, ['category_id'], $id, $m);

    default: fail('Unknown resource', 404);
  }
};

try {
  try { $dispatch(); }
  catch (PDOException $e) {
    // Older database without users.username: add it once, then retry the same request
    if ($e->getCode()==='42S22' && stripos($e->getMessage(), 'username') !== false) {
      db()->exec('ALTER TABLE users ADD COLUMN username VARCHAR(30) NULL UNIQUE AFTER name');
      $dispatch();
    } else throw $e;
  }
} catch (PDOException $e) {
  fail($e->getCode()==='23000' ? ($res==='users' ? 'Username or mobile already exists' : 'Duplicate value or invalid category') : 'Database error: '.$e->getMessage(), 422);
}
