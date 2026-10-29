<?php
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);
session_start();
require __DIR__.'/../includes/helpers.php'; restore_session();
// Not logged in -> show the login form right here on /admin (URL stays the same).
$me = me();
session_write_close();
if (!$me):
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BuildPro Login</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<form class="card" id="f"><h2>BuildPro Login</h2>
<label>Username<input name="login" autocomplete="username" required></label>
<label>Password<input name="secret" type="password" autocomplete="current-password" required></label>
<button class="btn">Login</button></form>
<script src="../assets/app.js"></script><script>
$('#f').onsubmit = async e => { e.preventDefault();
  try { await api('auth/login', 'POST', Object.fromEntries(new FormData(e.target))); location.reload(); }
  catch (x) { toast(x.message, 1); } };
</script></body></html>
<?php exit; endif; ?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BuildPro Admin</title><link rel="stylesheet" href="../assets/style.css"></head><body>

<div class="dash" id="dash"><aside><b id="who"></b><button class="btn ghost" data-t="cats">Categories</button><button class="btn ghost" data-t="prods">Products</button><button class="btn ghost" data-t="about">About Us</button><button class="btn ghost" data-t="contact">Contact Us</button>
<button class="btn ghost" data-t="users" id="ub" hidden>Users</button><button class="btn" id="out">Logout</button></aside><main id="m"></main></div>

<script src="../assets/app.js"></script><script>
const me = <?= json_encode($me, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
let cats = [], catsStale = true;
const catOpts = () => cats; const refreshCats = async () => { cats = await api('categories'); catsStale = false; };

$('#who').textContent = me.name;
const canEdit = ['admin', 'subadmin'].includes(me.role); $('#ub').hidden = me.role !== 'admin';
const views = {
  cats: { title: 'Categories', res: 'categories', edit: canEdit, onChange: () => catsStale = true, cols: [{ k: 'image', label: 'Image', img: 1 }, { k: 'name', label: 'Name' }],
    fields: [{ k: 'name', label: 'Name' }, { k: 'image', label: 'Image', type: 'file', req: false }] },
  prods: { title: 'Products', res: 'products', edit: canEdit, filter: { k: 'category_id', options: catOpts },
    cols: [{ k: 'name', label: 'Title' }, { k: 'category_id', label: 'Category', map: v => cats.find(c => c.id == v)?.name }, { k: 'description', label: 'Description', plain: 70 }, { k: 'min_qty', label: 'Min. Qty' }, { k: 'price', label: 'Price' }],
    fields: [{ k: 'name', label: 'Title' }, { k: 'category_id', label: 'Category', type: 'select', options: catOpts },
      { k: 'description', label: 'Description', type: 'html', req: false },
      { k: 'min_qty', label: 'Min. Quantity (e.g. 10 TON, Booking, 50 Kg)', req: false, attr: 'maxlength="100"' }, { k: 'price', label: 'Price (e.g. ₹85 / Kg)' }] },
  about: { page: 'about', title: 'About Us' },
  contact: { page: 'contact', title: 'Contact Us' },
  users: { title: 'Users', res: 'users', edit: true, cols: [{ k: 'name', label: 'Name' }, { k: 'username', label: 'Username' }, { k: 'mobile', label: 'Mobile' }, { k: 'email', label: 'Email' }, { k: 'gst', label: 'GST' }, { k: 'role', label: 'Role' }],
    fields: [{ k: 'name', label: 'Name' }, { k: 'username', label: 'Username (used to log in)', attr: 'pattern="[A-Za-z][A-Za-z0-9_.]{2,29}" minlength="3" maxlength="30" autocomplete="off"' },
      { k: 'mobile', label: 'Mobile (10 digits)', attr: 'pattern="\\d{10}" maxlength="10"' }, { k: 'email', label: 'Email', type: 'email' },
      { k: 'gst', label: 'GST (15 chars)', attr: 'minlength="15" maxlength="15"' }, { k: 'pin', label: '4-digit PIN', type: 'password', attr: 'pattern="\\d{4}" maxlength="4" autocomplete="new-password"' },
      { k: 'role', label: 'Role', type: 'select', options: () => [{ id: 'subadmin', name: 'Subadmin' }, { id: 'user', name: 'User' }] }] }
};
const show = async k => {
  document.querySelectorAll('[data-t]').forEach(x => x.classList.toggle('active', x.dataset.t === k));
  $('#m').innerHTML = '<p>Loading…</p>';
  try { if (k === 'prods' && catsStale) await refreshCats(); } catch (e) { return $('#m').innerHTML = `<p style="color:#ff5470">${esc(e.message)}</p>`; }
  const v = views[k];
  if (v.page) pageEditor($('#m'), v.page, v.title, canEdit); else crud($('#m'), v);
};
document.querySelectorAll('[data-t]').forEach(b => b.onclick = () => show(b.dataset.t));
$('#out').onclick = async () => { await api('auth/logout', 'POST'); location.reload(); };
show('cats');
</script></body></html>
