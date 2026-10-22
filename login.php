<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login</title><link rel="stylesheet" href="assets/style.css"></head><body>
<?php include 'includes/scene.php'; ?>
<form class="card" id="f" data-tilt><h2>Login</h2>
<label>Admin username / Mobile number<input name="login" required></label>
<label>Admin password / 4-digit PIN<input name="secret" type="password" required></label>
<button class="btn">Login</button><a href="index.php">← Back to site</a></form>
<script src="assets/app.js"></script><script>
animateIn();
$('#f').onsubmit = async e => { e.preventDefault();
  try { await api('auth/login', 'POST', Object.fromEntries(new FormData(e.target))); location = 'admin.php'; } catch (x) { toast(x.message, 1); } };
</script></body></html>
