<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard</title><link rel="stylesheet" href="assets/style.css"></head><body>
<?php include 'includes/scene.php'; ?>
<div class="dash"><aside><b id="who"></b><button class="btn ghost" data-t="cats">Categories</button><button class="btn ghost" data-t="prods">Products</button>
<button class="btn ghost" data-t="users" id="ub" hidden>Users</button><button class="btn" id="out">Logout</button></aside><main id="m"></main></div>
<script src="assets/app.js"></script><script>
let cats = [];
(async () => {
  let me; try { me = await api('auth/me'); } catch { return location = 'login.php'; }
  $('#who').textContent = `${me.name} (${me.role})`; const canEdit = ['admin', 'subadmin'].includes(me.role); if (me.role === 'admin') $('#ub').hidden = false;
  const catOpts = () => cats; const refreshCats = async () => cats = await api('categories');
  const views = {
    cats: { title: 'Categories', res: 'categories', edit: canEdit, cols: [{ k: 'image', label: 'Image', img: 1 }, { k: 'name', label: 'Name' }],
      fields: [{ k: 'name', label: 'Name' }, { k: 'image', label: 'Image', type: 'file', req: false }] },
    prods: { title: 'Products', res: 'products', edit: canEdit, filter: { k: 'category_id', options: catOpts },
      cols: [{ k: 'image', label: 'Image', img: 1 }, { k: 'name', label: 'Name' }, { k: 'category_id', label: 'Category', map: v => cats.find(c => c.id == v)?.name }, { k: 'price', label: 'Price ₹' }, { k: 'unit', label: 'Unit' }],
      fields: [{ k: 'category_id', label: 'Category', type: 'select', options: catOpts }, { k: 'name', label: 'Name' }, { k: 'description', label: 'Description', req: false },
        { k: 'price', label: 'Price', type: 'number', attr: 'step="0.01" min="0"' }, { k: 'unit', label: 'Unit (piece, kg, meter)' }, { k: 'image', label: 'Image', type: 'file', req: false }] },
    users: { title: 'Users', res: 'users', edit: true, cols: [{ k: 'name', label: 'Name' }, { k: 'mobile', label: 'Mobile' }, { k: 'email', label: 'Email' }, { k: 'gst', label: 'GST' }, { k: 'role', label: 'Role' }],
      fields: [{ k: 'name', label: 'Name' }, { k: 'mobile', label: 'Mobile (10 digits)', attr: 'pattern="\\d{10}" maxlength="10"' }, { k: 'email', label: 'Email', type: 'email' },
        { k: 'gst', label: 'GST (15 chars)', attr: 'minlength="15" maxlength="15"' }, { k: 'pin', label: '4-digit PIN', type: 'password', attr: 'pattern="\\d{4}" maxlength="4"' },
        { k: 'role', label: 'Role', type: 'select', options: () => [{ id: 'subadmin', name: 'Subadmin' }, { id: 'user', name: 'User' }] }] }
  };
  const show = async k => { await refreshCats(); crud($('#m'), views[k]); };
  document.querySelectorAll('[data-t]').forEach(b => b.onclick = () => show(b.dataset.t));
  $('#out').onclick = async () => { await api('auth/logout', 'POST'); location = 'login.php'; };
  show('cats');
})();
</script></body></html>
