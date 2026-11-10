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
<form class="card" id="f"><img class="login-logo" src="../assets/logo-light.png" alt="LeonX Steel Private Limited"><h2>Login</h2>
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

<div class="dash" id="dash"><aside><div class="brand"><img src="../assets/logo-light.png" alt="LeonX Steel Private Limited"></div><span id="who"></span><button class="btn ghost" data-t="dash"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>Dashboard</button><button class="btn ghost" data-t="cats"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>Categories</button><button class="btn ghost" data-t="subs"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>Sub Categories</button><button class="btn ghost" data-t="prods"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.5 4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.27 6.96 8.73 5.05 8.73-5.05"/><path d="M12 22.08V12"/></svg>Products</button><button class="btn ghost" data-t="popups"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>Popup</button><button class="btn ghost" data-t="about"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>About Us</button><button class="btn ghost" data-t="contact"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>Contact Us</button>
<button class="btn ghost" data-t="users" id="ub" hidden><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Users</button><button class="btn ghost" id="out"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>Logout</button></aside><main id="m"></main></div>

<script src="../assets/app.js"></script><script>
const me = <?= json_encode($me, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
let cats = [], subs = [], catsStale = true;
// Min. Quantity suggestions: a few defaults + every value already used on a product (anything else can still be typed)
const qtySeen = new Set(), QTY_DEFAULT = ['Booking', 'Booking Price', '1 TON', '5 TON', '10 TON', '9 m.t.', '50 Kg', '100 Kg'];
const qtyOpts = () => [...new Set([...QTY_DEFAULT, ...qtySeen])];
const rememberQty = l => l.forEach(r => r.min_qty && qtySeen.add(r.min_qty));
const catOpts = () => cats; const refreshCats = async () => { [cats, subs] = await Promise.all([api('categories'), api('subcategories')]); catsStale = false; };
const catName = id => cats.find(c => c.id == id)?.name;
// Product form dropdown: every sub category, shown as "Category › Sub category"
const subOpts = () => subs.map(s => ({ id: s.id, name: `${catName(s.category_id) ?? '?'} › ${s.name}` })).sort((a, b) => a.name.localeCompare(b.name));

$('#who').textContent = me.name;
const canEdit = ['admin', 'subadmin'].includes(me.role); $('#ub').hidden = me.role !== 'admin';
const views = {
  dash: { dash: 1 },
  cats: { title: 'Categories', one: 'Category', res: 'categories', edit: canEdit, delMsg: 'Delete this category? Its sub categories and products will also be deleted.', onChange: () => catsStale = true, cols: [{ k: 'image', label: 'Image', img: 1 }, { k: 'name', label: 'Name' }],
    fields: [{ k: 'name', label: 'Name' }, { k: 'image', label: 'Image', type: 'file', req: false }] },
  subs: { title: 'Sub Categories', one: 'Sub Category', res: 'subcategories', edit: canEdit, delMsg: 'Delete this sub category? Its products stay, but will have no sub category until you pick one again.', onChange: () => catsStale = true, filter: { k: 'category_id', options: catOpts },
    cols: [{ k: 'name', label: 'Sub Category' }, { k: 'category_id', label: 'Category', pill: 1, map: catName }],
    fields: [{ k: 'category_id', label: 'Category', type: 'select', options: catOpts, ph: 'Select category…', lock: 1 }, { k: 'name', label: 'Sub Category name' }] },
  prods: { title: 'Products', one: 'Product', res: 'products', edit: canEdit, onLoad: rememberQty, filter: { k: 'category_id', options: catOpts },
    cols: [{ k: 'name', label: 'Title' }, { k: 'category_id', label: 'Category', pill: 1, map: catName }, { k: 'subcategory_id', label: 'Sub Category', pill: 1, map: v => subs.find(s => s.id == v)?.name }, { k: 'description', label: 'Description', plain: 70 }, { k: 'min_qty', label: 'Min. Qty' }, { k: 'price', label: 'Price', cls: 'price' }],
    fields: [{ k: 'name', label: 'Title' }, { k: 'subcategory_id', label: 'Sub Category', type: 'select', options: subOpts, ph: 'Select sub category…' },
      { k: 'description', label: 'Description', type: 'html', req: false },
      { k: 'min_qty', label: 'Min. Quantity', type: 'combo', options: qtyOpts, req: false, attr: 'maxlength="100"' }, { k: 'price', label: 'Price (e.g. ₹85 / Kg)' }] },
  popups: { popups: 1 },
  about: { page: 'about', title: 'About Us' },
  contact: { pages: [{ slug: 'contact_address', label: 'Address', ph: 'Write address…' }, { slug: 'contact_phone', label: 'Phone Number', ph: 'Write phone number(s)…' }, { slug: 'contact_email', label: 'Email ID', ph: 'Write email id(s)…' }], title: 'Contact Us' },
  users: { title: 'Users', one: 'User', res: 'users', edit: true, cols: [{ k: 'name', label: 'Name' }, { k: 'username', label: 'Username' }, { k: 'mobile', label: 'Mobile' }, { k: 'email', label: 'Email' }, { k: 'gst', label: 'GST' }, { k: 'role', label: 'Role', pill: 1 }],
    fields: [{ k: 'name', label: 'Name' }, { k: 'username', label: 'Username (used to log in)', attr: 'pattern="[A-Za-z][A-Za-z0-9_.]{2,29}" minlength="3" maxlength="30" autocomplete="off"' },
      { k: 'mobile', label: 'Mobile (10 digits)', attr: 'pattern="\\d{10}" maxlength="10"' }, { k: 'email', label: 'Email', type: 'email' },
      { k: 'gst', label: 'GST (15 chars)', attr: 'minlength="15" maxlength="15"' }, { k: 'pin', label: '4-digit PIN', type: 'password', attr: 'pattern="\\d{4}" maxlength="4" autocomplete="new-password"' },
      { k: 'role', label: 'Role', type: 'select', options: () => [{ id: 'subadmin', name: 'Subadmin' }, { id: 'user', name: 'User' }] }] }
};
const dashboard = async () => {
  const root = $('#m');
  try {
    const [c, sc, p, u] = await Promise.all([api('categories'), api('subcategories'), api('products'), me.role === 'admin' ? api('users') : null]);
    cats = c; subs = sc; catsStale = false; rememberQty(p);
    const card = (go, n, label, cls) => `<button class="stat ${cls}" data-go="${go}"><b>${n}</b><span>${label}</span></button>`;
    root.innerHTML = `<div class="bar"><h2>Welcome, ${esc(me.name)}</h2></div>
      <div class="stats">${card('cats', c.length, 'Categories', 'amber')}${card('subs', sc.length, 'Sub Categories', 'amber')}${card('prods', p.length, 'Products', 'blue')}${u ? card('users', u.length, 'Users', 'green') : ''}</div>
      <h3 class="sub">Recently added products</h3>
      <table><tr><th>Title</th><th>Category</th><th>Sub Category</th><th>Min. Qty</th><th>Price</th></tr>
      ${p.slice(0, 5).map(r => `<tr><td>${esc(r.name)}</td><td><span class="pill">${esc(catName(r.category_id) ?? '')}</span></td><td>${r.subcategory_id ? `<span class="pill">${esc(subs.find(s => s.id == r.subcategory_id)?.name ?? '')}</span>` : '<span class="dim">—</span>'}</td><td>${esc(r.min_qty) || '<span class="dim">—</span>'}</td><td class="price">${esc(r.price)}</td></tr>`).join('') || '<tr><td class="empty" colspan="5">No products yet</td></tr>'}</table>`;
    root.querySelectorAll('[data-go]').forEach(b => b.onclick = () => show(b.dataset.go));
  } catch (e) { root.innerHTML = `<p style="color:#ff5470">${esc(e.message)}</p>`; }
};
const show = async k => {
  document.querySelectorAll('[data-t]').forEach(x => x.classList.toggle('active', x.dataset.t === k));
  $('#m').innerHTML = '<p>Loading…</p>';
  try { if ((k === 'prods' || k === 'subs') && catsStale) await refreshCats(); } catch (e) { return $('#m').innerHTML = `<p style="color:#ff5470">${esc(e.message)}</p>`; }
  const v = views[k];
  if (v.dash) dashboard(); else if (v.popups) popupManager($('#m')); else if (v.pages) multiPageEditor($('#m'), v.title, v.pages, canEdit); else if (v.page) pageEditor($('#m'), v.page, v.title, canEdit); else crud($('#m'), v);
};
document.querySelectorAll('[data-t]').forEach(b => b.onclick = () => show(b.dataset.t));
// ---------- Popup tab: image cards + Add / Edit / Delete (only an image and a product dropdown) ----------
const prodLabel = p => `${p.name}${p.category_name ? ` (${p.category_name}${p.subcategory_name ? ' › ' + p.subcategory_name : ''})` : ''}`;
async function popupManager(root) {
  let list, prods;
  try { [list, prods] = await Promise.all([api('popups'), api('products')]); }
  catch (e) { root.innerHTML = `<h2>Popup</h2><p style="color:#ff5470">${esc(e.message)}</p>`; return; }
  const live = list.find(r => r.active == 1);        // the dashboard shows the newest active popup
  const reload = async () => { try { list = await api('popups'); } catch (e) { return toast(e.message, 1); } draw(); };
  function draw() {
    const lv = list.find(r => r.active == 1);
    root.innerHTML = `<div class="bar"><h2>Popup <span class="count">${list.length}</span></h2>${canEdit ? '<button class="btn" id="add">+ Add Popup</button>' : ''}</div>
      ${list.length ? `<div class="pgrid">${list.map(r => `<div class="pcard"><div class="pimg">${r.image ? `<img src="${esc(asset(r.image))}" alt="">` : '<span>No image</span>'}</div>
        <div class="pbody"><div><h4>${esc(r.product_name || 'No product linked')}</h4>${lv && lv.id === r.id ? '<span class="pill live">Showing on dashboard</span>' : ''}</div>
        ${canEdit ? `<div class="row"><button class="btn sm ghost" data-e="${r.id}">Edit</button><button class="btn sm" data-d="${r.id}">Delete</button></div>` : ''}</div></div>`).join('')}</div>`
        : '<div class="pempty">No popup yet. Click <b>+ Add Popup</b> to add an image and a product.</div>'}`;
    if (!canEdit) return;
    $('#add', root).onclick = () => form();
    root.querySelectorAll('[data-e]').forEach(b => b.onclick = () => form(list.find(r => r.id == b.dataset.e)));
    root.querySelectorAll('[data-d]').forEach(b => b.onclick = async () => { if (!confirm('Delete this popup?')) return;
      try { await api(`popups/${b.dataset.d}`, 'DELETE'); toast('Deleted'); reload(); } catch (e) { toast(e.message, 1); } });
  }
  function form(row = {}) {
    const m = document.createElement('div'); m.className = 'modal';
    m.innerHTML = `<form class="card"><h3>${row.id ? 'Edit' : 'Add'} Popup</h3>
      <div class="upl"><div class="prev">${row.image ? `<img src="${esc(asset(row.image))}" alt="">` : '<span>No image selected</span>'}</div>
        <label class="btn ghost">${row.image ? 'Change image' : 'Choose image'}<input type="file" name="image" accept="image/png,image/jpeg,image/webp" hidden></label>
        <small>JPG / PNG / WEBP, max 3 MB</small></div>
      <label class="field">Product<select name="product_id"><option value="">— No product —</option>${prods.map(x => `<option value="${x.id}" ${x.id == row.product_id ? 'selected' : ''}>${esc(prodLabel(x))}</option>`).join('')}</select></label>
      <div class="row"><button class="btn">Save</button><button type="button" class="btn ghost" id="x">Cancel</button></div></form>`;
    document.body.append(m); $('#x', m).onclick = () => m.remove();
    const inp = $('input[type=file]', m);
    inp.onchange = () => { const f = inp.files[0]; if (!f) return; $('.prev', m).innerHTML = `<img src="${URL.createObjectURL(f)}" alt="">`; };
    $('form', m).onsubmit = async e => { e.preventDefault();
      const fd = new FormData(e.target); if (!fd.get('image')?.size) fd.delete('image');
      if (!row.id && !fd.has('image')) return toast('Please choose an image', 1);
      try { await api(row.id ? `popups/${row.id}` : 'popups', row.id ? 'PUT' : 'POST', fd); m.remove(); toast('Saved'); reload(); }
      catch (err) { toast(err.message, 1); } };
  }
  draw();
}
// ---------- Dashboard popup: shows the newest ACTIVE popup once when the dashboard opens (read-only) ----------
function showPopup(p) {
  const m = document.createElement('div'); m.className = 'modal'; const pr = p.product;
  m.innerHTML = `<div class="card pop"><button type="button" class="pop-x" aria-label="Close">×</button>
    ${p.image ? `<img src="${esc(asset(p.image))}" alt="">` : ''}
    <div class="pop-body">${pr ? `<h3>${esc(pr.name)}</h3>
        <div class="pop-meta">${pr.category_name ? `<span class="pill">${esc(pr.category_name)}</span>` : ''}${pr.subcategory_name ? `<span class="pill">${esc(pr.subcategory_name)}</span>` : ''}${pr.min_qty ? `<span class="pop-qty">Min. Qty: ${esc(pr.min_qty)}</span>` : ''}</div>
        ${pr.price ? `<div class="pop-price">${esc(pr.price)}</div>` : ''}
        ${plainText(pr.description, 140) ? `<p>${esc(plainText(pr.description, 140))}</p>` : ''}` : ''}
      <div class="row">${pr ? '<button type="button" class="btn" id="pgo">View Products</button>' : ''}<button type="button" class="btn ghost" id="pcl">Close</button></div></div></div>`;
  const onKey = e => { if (e.key === 'Escape') close(); };
  const close = () => { m.remove(); document.removeEventListener('keydown', onKey); };
  document.addEventListener('keydown', onKey);
  m.onclick = e => { if (e.target === m) close(); };
  document.body.append(m); $('.pop-x', m).onclick = close; $('#pcl', m).onclick = close;
  if ($('#pgo', m)) $('#pgo', m).onclick = () => { close(); show('prods'); };
}
let popupChecked = false;
const startPopup = async () => {          // once, when the dashboard first opens
  if (popupChecked) return; popupChecked = true;
  try { const p = await api('popup'); if (p.active && (p.image || p.product)) showPopup(p); }
  catch (e) { /* popup is optional */ }
};
$('#out').onclick = async () => { await api('auth/logout', 'POST'); location.reload(); };
show('dash'); startPopup();
</script></body></html>
