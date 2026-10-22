<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BuildPro Construction Materials</title><link rel="stylesheet" href="assets/style.css"></head><body>
<?php include 'includes/scene.php'; ?>
<nav><b>🏗️ Build<span style="color:var(--acc)">Pro</span></b><a class="btn" href="login.php">Login</a></nav>
<div class="hero"><h1>Strong Materials.<br>Solid Foundations.</h1><p>Pipes, rods, cement &amp; more for your construction projects.</p></div>
<section><h2>Categories</h2><div class="grid" id="cats"></div></section>
<section><h2 id="ptitle">All Products</h2><div class="grid" id="prods"></div></section>
<script src="assets/app.js"></script><script>
const card = (img, h, s, extra = '') => `<div class="card reveal" data-tilt ${extra}>${img ? `<img src="${esc(img)}">` : '<img>'}<h3>${esc(h)}</h3><small>${s}</small></div>`;
async function products(cid, name) {
  $('#ptitle').textContent = name || 'All Products';
  const l = await api('products' + (cid ? '?category_id=' + cid : ''));
  $('#prods').innerHTML = l.map(p => card(p.image, p.name, `<span class="price">₹${esc(p.price)}</span> / ${esc(p.unit)}<br>${esc(p.description)}`)).join('') || '<p>No products yet.</p>';
  animateIn($('#prods'));
}
(async () => {
  const c = await api('categories');
  $('#cats').innerHTML = c.map(x => card(x.image, x.name, 'View products →', `data-id="${x.id}" data-n="${esc(x.name)}"`)).join('') || '<p>No categories yet.</p>';
  $('#cats').onclick = e => { const el = e.target.closest('[data-id]'); if (el) products(el.dataset.id, el.dataset.n); };
  animateIn($('#cats')); products();
})();
</script></body></html>
