<?php
// ---------- Reusable helpers ----------
const TABLES = [
  'users'      => ['name','username','mobile','email','gst','pin_hash','role'],
  'categories' => ['name','image'],
  'products'   => ['category_id','name','description','price'],
];

function env($k, $d = null) {
    $value = getenv($k);

    if ($value !== false) {
        return $value;
    }

    static $e = null;

    if ($e === null) {
        $e = [];
        $f = __DIR__ . '/../.env';

        if (is_file($f)) {
            foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $val] = explode('=', $line, 2);
                $e[trim($key)] = trim($val);
            }
        }
    }

    return $e[$k] ?? $d;
}


function db() {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST');
    $port = env('DB_PORT', '3306');
    $name = env('DB_NAME');
    $user = env('DB_USER');
    $pass = env('DB_PASS');

    if (!$host || !$name || !$user || $pass === null || $pass === '') {
        throw new RuntimeException('Database environment variables are missing.');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    return $pdo;
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


// ---------- "Stay logged in" (signed cookie, 30 days; survives server restarts) ----------
function remember_key() { return hash('sha256', 'bp|'.env('ADMIN_USER').'|'.env('ADMIN_PASS').'|'.env('DB_PASS'), true); }
function remember_set($a) {
  $p = base64_encode(json_encode(['a'=>$a,'e'=>time()+30*86400]));
  $https = !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')==='https';
  setcookie('bp_remember', $p.'.'.hash_hmac('sha256',$p,remember_key()), ['expires'=>time()+30*86400,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>$https]);
}
function remember_clear() { setcookie('bp_remember','',['expires'=>1,'path'=>'/']); }
function restore_session() {
  if (!empty($_SESSION['auth']) || empty($_COOKIE['bp_remember'])) return;
  try {
    [$p,$sig] = array_pad(explode('.', $_COOKIE['bp_remember'], 2), 2, '');
    if (!hash_equals(hash_hmac('sha256',$p,remember_key()), $sig)) return;
    $d = json_decode(base64_decode($p), true);
    if (!is_array($d) || ($d['e'] ?? 0) < time()) return;
    $a = $d['a'];
    if ($a['id'] > 0) { // subadmin/user: must still exist; take latest name/role
      $q = db()->prepare('SELECT id,name,role FROM users WHERE id=?'); $q->execute([$a['id']]); $u = $q->fetch();
      if (!$u) return; $a = ['id'=>(int)$u['id'],'name'=>$u['name'],'role'=>$u['role']];
    }
    $_SESSION['auth'] = $a;
  } catch (Throwable $e) { /* ignore, user just logs in again */ }
}

// ---------- Rich-text (HTML editor) support ----------
// Keeps only a small safe set of tags; strips every attribute (except a safe link href), scripts, styles, iframes etc.
function sanitize_nodes($nodes) {
  $keep = ['p','b','strong','i','em','u','s','ul','ol','li','h2','h3','h4','blockquote'];
  $o = '';
  foreach ($nodes as $n) {
    if ($n->nodeType === XML_TEXT_NODE) { $o .= htmlspecialchars($n->nodeValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); continue; }
    if ($n->nodeType !== XML_ELEMENT_NODE) continue;
    $t = strtolower($n->nodeName);
    if (in_array($t, ['script','style','iframe','object','embed','noscript','template','svg','math'])) continue;
    $inner = sanitize_nodes($n->childNodes);
    if ($t === 'br') $o .= '<br>';
    elseif ($t === 'div') $o .= "<p>$inner</p>";
    elseif ($t === 'a') {
      $h = trim($n->getAttribute('href'));
      $o .= preg_match('~^(https?://|mailto:|tel:)~i', $h)
        ? '<a href="'.htmlspecialchars($h, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener noreferrer">'.$inner.'</a>' : $inner;
    }
    elseif (in_array($t, $keep)) $o .= "<$t>$inner</$t>";
    else $o .= $inner;
  }
  return $o;
}
function sanitize_html($html) {
  $html = trim((string)$html);
  if ($html === '') return '';
  $doc = new DOMDocument(); libxml_use_internal_errors(true);
  $doc->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>'); libxml_clear_errors();
  $body = $doc->getElementsByTagName('body')->item(0);
  $out = $body ? trim(sanitize_nodes($body->childNodes)) : '';
  // nothing but empty tags / spaces -> treat as empty
  return preg_replace('/[\s\x{00A0}]+/u', '', html_entity_decode(strip_tags($out), ENT_QUOTES, 'UTF-8')) === '' ? '' : $out;
}

// One-time, automatic upgrade of an older products table: price becomes free text (e.g. "₹85 / Kg").
function ensure_products_schema() {
  static $done = false; if ($done) return; $done = true;
  $q = db()->query("SELECT DATA_TYPE AS t FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price'");
  $t = strtolower((string)$q->fetchColumn());
  if ($t !== '' && !in_array($t, ['varchar','text'])) db()->exec("ALTER TABLE products MODIFY price VARCHAR(100) NOT NULL DEFAULT ''");
}
