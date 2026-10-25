<?php
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);
session_start();
require __DIR__.'/includes/helpers.php'; restore_session();
// Already logged in? Skip the login form and go straight to the dashboard.
if (!empty($_SESSION['auth'])) { header('Location: admin/', true, 302); exit; }
session_write_close();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BuildPro Login</title><link rel="stylesheet" href="assets/style.css"></head><body>
<?php include __DIR__ . '/includes/scene.php'; ?>
<form class="card" id="f"><h2>BuildPro Login</h2>
<label>Username / Mobile number<input name="login" autocomplete="username" required></label>
<label>Password / 4-digit PIN<input name="secret" type="password" autocomplete="current-password" required></label>
<button class="btn">Login</button></form>
<script src="assets/app.js"></script><script>
$('#f').onsubmit = async e => { e.preventDefault();
  try { await api('auth/login', 'POST', Object.fromEntries(new FormData(e.target))); location.href = ROOT + 'admin/'; }
  catch (x) { toast(x.message, 1); } };
</script></body></html>
