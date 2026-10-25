<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BuildPro Admin</title><link rel="stylesheet" href="../assets/style.css"></head><body>

<!-- Login (shown when not logged in) -->
<form class="card" id="f" hidden><h2>BuildPro Login</h2>
<label>Username / Mobile number<input name="login" autocomplete="username" required></label>
<label>Password / 4-digit PIN<input name="secret" type="password" autocomplete="current-password" required></label>
<button class="btn">Login</button></form>

<!-- Dashboard (shown after login) -->
<div class="dash" id="dash" hidden><aside><b id="who"></b><button class="btn ghost" data-t="cats">Categories</button><button class="btn ghost" data-t="prods">Products</button>
<button class="btn ghost" data-t="users" id="ub" hidden>Users</button><button class="btn" id="out">Logout</button></aside><main id="m"></main></div>

<script src="../assets/app.js"></script><script>
let cats = [], catsStale = true;
const catOpts = () => cats; const refreshCats = async () => { cats = await api('categories'); catsStale = false; };

function showDashboard(me) {
  $('#f').hidden = true; $('#dash').hidden = false;
  $('#who').textContent = `${me.name} (${me.role})`;
  const canEdit = ['admin', 'subadmin'].includes(me.role); $('#ub').hidden = me.role !== 'admin';
  const views = {
    cats: { title: 'Categories', res: 'categories', edit: canEdit, onChange: () => catsStale = true, cols: [{ k: 'image', label: 'Image', img: 1 }, { k: 'name', label: 'Name' }],
      fields: [{ k: 'name', label: 'Name' }, { k: 'image', label: 'Image', type: 'file', req: false }] },
    prods: { title: 'Products', res: 'products', edit: canEdit, filter: { k: 'category_id', options: catOpts },
      cols: [{ k: 'image', label: 'Image', img: 1 }, { k: 'name', label: 'Name' }, { k: 'category_id', label: 'Category', map: v => cats.find(c => c.id == v)?.name }, { k: 'price', label: 'Price ₹' }, { k: 'unit', label: 'Unit' }],
      fields: [{ k: 'category_id', label: 'Category', type: 'select', options: catOpts }, { k: 'name', label: 'Name' }, { k: 'description', label: 'Description', req: false },
        { k: 'price', label: 'Price', type: 'number', attr: 'step="0.01" min="0"' }, { k: 'unit', label: 'Unit (piece, kg, meter)' }, { k: 'image', label: 'Image', type: 'file', req: false }] },
    users: { title: 'Users', res: 'users', edit: true, cols: [{ k: 'name', label: 'Name' }, { k: 'username', label: 'Username' }, { k: 'mobile', label: 'Mobile' }, { k: 'email', label: 'Email' }, { k: 'gst', label: 'GST' }, { k: 'role', label: 'Role' }],
      fields: [{ k: 'name', label: 'Name' }, { k: 'username', label: 'Username (used to log in)', attr: 'pattern="[A-Za-z][A-Za-z0-9_.]{2,29}" minlength="3" maxlength="30" autocomplete="off"' },
        { k: 'mobile', label: 'Mobile (10 digits)', attr: 'pattern="\\d{10}" maxlength="10"' }, { k: 'email', label: 'Email', type: 'email' },
        { k: 'gst', label: 'GST (15 chars)', attr: 'minlength="15" maxlength="15"' }, { k: 'pin', label: '4-digit PIN', type: 'password', attr: 'pattern="\\d{4}" maxlength="4" autocomplete="new-password"' },
        { k: 'role', label: 'Role', type: 'select', options: () => [{ id: 'subadmin', name: 'Subadmin' }, { id: 'user', name: 'User' }] }] }
  };
  const show = async k => {
    $('#m').innerHTML = '<p>Loading…</p>';
    try { if (k === 'prods' && catsStale) await refreshCats(); } catch (e) { return $('#m').innerHTML = `<p style="color:#ff5470">${esc(e.message)}</p>`; }
    crud($('#m'), views[k]);
  };
  document.querySelectorAll('[data-t]').forEach(b => b.onclick = () => show(b.dataset.t));
  $('#out').onclick = async () => { await api('auth/logout', 'POST'); showLogin(); };
  show('cats');
}

function showLogin() { $('#dash').hidden = true; $('#m').innerHTML = ''; $('#f').reset(); $('#f').hidden = false; }

$('#f').onsubmit = async e => { e.preventDefault();
  try { showDashboard(await api('auth/login', 'POST', Object.fromEntries(new FormData(e.target)))); } catch (x) { toast(x.message, 1); } };

(async () => { try { showDashboard(await api('auth/me')); } catch { showLogin(); } })();
</script></body></html>
