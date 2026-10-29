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

<div class="dash" id="dash"><aside><div class="brand">BuildPro</div><span id="who"></span><button class="btn ghost" data-t="dash"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>Dashboard</button><button class="btn ghost" data-t="cats"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>Categories</button><button class="btn ghost" data-t="prods"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.5 4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.27 6.96 8.73 5.05 8.73-5.05"/><path d="M12 22.08V12"/></svg>Products</button><button class="btn ghost" data-t="about"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>About Us</button><button class="btn ghost" data-t="contact"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>Contact Us</button>
<button class="btn ghost" data-t="users" id="ub" hidden><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Users</button><button class="btn ghost" id="out"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>Logout</button></aside><main id="m"></main></div>

<script src="../assets/app.js"></script><script>
const me = <?= json_encode($me, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
let cats = [], catsStale = true;
const catOpts = () => cats; const refreshCats = async () => { cats = await api('categories'); catsStale = false; };

$('#who').textContent = me.name;
const canEdit = ['admin', 'subadmin'].includes(me.role); $('#ub').hidden = me.role !== 'admin';
const views = {
  dash: { dash: 1 },
  cats: { title: 'Categories', res: 'categories', edit: canEdit, onChange: () => catsStale = true, cols: [{ k: 'image', label: 'Image', img: 1 }, { k: 'name', label: 'Name' }],
    fields: [{ k: 'name', label: 'Name' }, { k: 'image', label: 'Image', type: 'file', req: false }] },
  prods: { title: 'Products', res: 'products', edit: canEdit, filter: { k: 'category_id', options: catOpts },
    cols: [{ k: 'name', label: 'Title' }, { k: 'category_id', label: 'Category', pill: 1, map: v => cats.find(c => c.id == v)?.name }, { k: 'description', label: 'Description', plain: 70 }, { k: 'min_qty', label: 'Min. Qty' }, { k: 'price', label: 'Price', cls: 'price' }],
    fields: [{ k: 'name', label: 'Title' }, { k: 'category_id', label: 'Category', type: 'select', options: catOpts },
      { k: 'description', label: 'Description', type: 'html', req: false },
      { k: 'min_qty', label: 'Min. Quantity (e.g. 10 TON, Booking, 50 Kg)', req: false, attr: 'maxlength="100"' }, { k: 'price', label: 'Price (e.g. ₹85 / Kg)' }] },
  about: { page: 'about', title: 'About Us' },
  contact: { page: 'contact', title: 'Contact Us' },
  users: { title: 'Users', res: 'users', edit: true, cols: [{ k: 'name', label: 'Name' }, { k: 'username', label: 'Username' }, { k: 'mobile', label: 'Mobile' }, { k: 'email', label: 'Email' }, { k: 'gst', label: 'GST' }, { k: 'role', label: 'Role', pill: 1 }],
    fields: [{ k: 'name', label: 'Name' }, { k: 'username', label: 'Username (used to log in)', attr: 'pattern="[A-Za-z][A-Za-z0-9_.]{2,29}" minlength="3" maxlength="30" autocomplete="off"' },
      { k: 'mobile', label: 'Mobile (10 digits)', attr: 'pattern="\\d{10}" maxlength="10"' }, { k: 'email', label: 'Email', type: 'email' },
      { k: 'gst', label: 'GST (15 chars)', attr: 'minlength="15" maxlength="15"' }, { k: 'pin', label: '4-digit PIN', type: 'password', attr: 'pattern="\\d{4}" maxlength="4" autocomplete="new-password"' },
      { k: 'role', label: 'Role', type: 'select', options: () => [{ id: 'subadmin', name: 'Subadmin' }, { id: 'user', name: 'User' }] }] }
};
const dashboard = async () => {
  const root = $('#m');
  try {
    const [c, p, u] = await Promise.all([api('categories'), api('products'), me.role === 'admin' ? api('users') : null]);
    cats = c; catsStale = false;
    const card = (go, n, label, cls) => `<button class="stat ${cls}" data-go="${go}"><b>${n}</b><span>${label}</span></button>`;
    const catName = id => c.find(x => x.id == id)?.name ?? '';
    root.innerHTML = `<div class="bar"><h2>Welcome, ${esc(me.name)}</h2></div>
      <div class="stats">${card('cats', c.length, 'Categories', 'amber')}${card('prods', p.length, 'Products', 'blue')}${u ? card('users', u.length, 'Users', 'green') : ''}</div>
      <h3 class="sub">Recently added products</h3>
      <table><tr><th>Title</th><th>Category</th><th>Min. Qty</th><th>Price</th></tr>
      ${p.slice(0, 5).map(r => `<tr><td>${esc(r.name)}</td><td><span class="pill">${esc(catName(r.category_id))}</span></td><td>${esc(r.min_qty) || '<span class="dim">—</span>'}</td><td class="price">${esc(r.price)}</td></tr>`).join('') || '<tr><td class="empty" colspan="4">No products yet</td></tr>'}</table>`;
    root.querySelectorAll('[data-go]').forEach(b => b.onclick = () => show(b.dataset.go));
  } catch (e) { root.innerHTML = `<p style="color:#ff5470">${esc(e.message)}</p>`; }
};
const show = async k => {
  document.querySelectorAll('[data-t]').forEach(x => x.classList.toggle('active', x.dataset.t === k));
  $('#m').innerHTML = '<p>Loading…</p>';
  try { if (k === 'prods' && catsStale) await refreshCats(); } catch (e) { return $('#m').innerHTML = `<p style="color:#ff5470">${esc(e.message)}</p>`; }
  const v = views[k];
  if (v.dash) dashboard(); else if (v.page) pageEditor($('#m'), v.page, v.title, canEdit); else crud($('#m'), v);
};
document.querySelectorAll('[data-t]').forEach(b => b.onclick = () => show(b.dataset.t));
$('#out').onclick = async () => { await api('auth/logout', 'POST'); location.reload(); };
show('dash');
</script></body></html>
