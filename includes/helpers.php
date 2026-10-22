<?php
// ---------- Reusable helpers ----------
const TABLES = [
  'users'      => ['name','mobile','email','gst','pin_hash','role'],
  'categories' => ['name','image'],
  'products'   => ['category_id','name','description','price','unit','image'],
];
function env($k, $d = null) {
  static $e;
  if ($e === null) { $e = []; $f = __DIR__.'/../.env';
    if (is_file($f)) foreach (file($f, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l) {
      if ($l[0]==='#' || !str_contains($l,'=')) continue; [$a,$b] = explode('=',$l,2); $e[trim($a)] = trim($b); } }
  return $e[$k] ?? $d;
}
function db() {
  static $p;
  if (!$p) $p = new PDO('mysql:host='.env('DB_HOST','localhost').';dbname='.env('DB_NAME').';charset=utf8mb4',
    env('DB_USER','root'), env('DB_PASS',''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  return $p;
}
function respond($d, $code=200) { http_response_code($code); header('Content-Type: application/json'); echo json_encode($d); exit; }
function ok($data=null, $code=200) { respond(['success'=>true,'data'=>$data], $code); }
function fail($msg, $code=400) { respond(['success'=>false,'error'=>$msg], $code); }
function input() { static $i; if ($i===null) { $j = json_decode(file_get_contents('php://input'), true); $i = is_array($j) ? $j : $_POST; } return $i; }
function need($a, $keys) { foreach ($keys as $k) if (!isset($a[$k]) || trim((string)$a[$k])==='') fail("$k is required", 422); }
function clean($in, $upd, $req, $opt=[]) {
  if (!$upd) need($in, $req); $o = [];
  foreach (array_merge($req,$opt) as $k) if (isset($in[$k]) && trim((string)$in[$k])!=='') $o[$k] = trim((string)$in[$k]);
  return $o;
}
function me() { return $_SESSION['auth'] ?? null; }
function require_role(...$roles) { $m = me(); if (!$m) fail('Login required', 401); if (!in_array($m['role'],$roles)) fail('Forbidden', 403); return $m; }
function upload_image($field, $old=null) {
  if (empty($_FILES[$field]) || $_FILES[$field]['error']===UPLOAD_ERR_NO_FILE) return $old;
  $x = $_FILES[$field];
  if ($x['error']!==0 || $x['size']>3*1024*1024) fail('Image upload failed (max 3MB)', 422);
  $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][mime_content_type($x['tmp_name'])] ?? fail('Only JPG/PNG/WEBP allowed', 422);
  $n = 'uploads/'.bin2hex(random_bytes(8)).".$ext";
  move_uploaded_file($x['tmp_name'], __DIR__.'/../'.$n);
  delete_file($old);
  return $n;
}
function delete_file($p) { if ($p && is_file($f = __DIR__.'/../'.$p)) @unlink($f); }

// ---------- Generic CRUD (any table in TABLES) ----------
function crud_all($t, $sel='*', $where=[]) {
  $where = array_intersect_key($where, array_flip(TABLES[$t])); $s = "SELECT $sel FROM $t";
  if ($where) $s .= ' WHERE '.implode(' AND ', array_map(fn($k)=>"$k=?", array_keys($where)));
  $q = db()->prepare("$s ORDER BY id DESC"); $q->execute(array_values($where)); return $q->fetchAll();
}
function crud_find($t, $id, $sel='*') { $q = db()->prepare("SELECT $sel FROM $t WHERE id=?"); $q->execute([$id]); return $q->fetch() ?: null; }
function crud_insert($t, $d) {
  $d = array_intersect_key($d, array_flip(TABLES[$t]));
  db()->prepare("INSERT INTO $t(".implode(',',array_keys($d)).') VALUES('.implode(',',array_fill(0,count($d),'?')).')')->execute(array_values($d));
  return (int)db()->lastInsertId();
}
function crud_update($t, $id, $d) {
  $d = array_intersect_key($d, array_flip(TABLES[$t])); if (!$d) return;
  db()->prepare("UPDATE $t SET ".implode(',',array_map(fn($k)=>"$k=?",array_keys($d)))." WHERE id=?")->execute([...array_values($d), $id]);
}
function crud_delete($t, $id) { db()->prepare("DELETE FROM $t WHERE id=?")->execute([$id]); }

// One handler for every resource: list / get / create / update / delete
function resource($t, $write, $sel, $validate, $filters, $id, $m) {
  if ($m==='GET') {
    if ($id) ok(crud_find($t,$id,$sel) ?? fail('Not found',404));
    $w = []; foreach ($filters as $f) if (!empty($_GET[$f])) $w[$f] = $_GET[$f];
    ok(crud_all($t,$sel,$w));
  }
  require_role(...$write);
  if ($m==='DELETE') {
    $id || fail('id required'); $r = crud_find($t,$id) ?? fail('Not found',404);
    crud_delete($t,$id); delete_file($r['image'] ?? null); ok();
  }
  if (!in_array($m,['POST','PUT'])) fail('Method not allowed', 405);
  $old = $id ? (crud_find($t,$id) ?? fail('Not found',404)) : null;
  $d = $validate(input(), (bool)$id);
  if (in_array('image', TABLES[$t])) $d['image'] = upload_image('image', $old['image'] ?? null);
  if ($id) { crud_update($t,$id,$d); ok(crud_find($t,$id,$sel)); }
  ok(crud_find($t, crud_insert($t,$d), $sel), 201);
}
