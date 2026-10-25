// ---------- Reusable utilities ----------
const ROOT = new URL('../', document.currentScript.src).href;   // project root, independent of page URL
const asset = p => ROOT + p;                                     // e.g. asset('uploads/x.jpg')
const $ = (s, r = document) => r.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
async function api(path, method = 'GET', data = null) {
  const [p, q] = path.split('?'); const o = { method, credentials: 'same-origin' };
  if (data instanceof FormData) { if (method !== 'POST') { data.append('_method', method); o.method = 'POST'; } o.body = data; }
  else if (data) { o.headers = { 'Content-Type': 'application/json' }; o.body = JSON.stringify(data); }
  const j = await (await fetch(`${ROOT}api/index.php?path=${p}${q ? '&' + q : ''}`, o)).json();
  if (!j.success) throw new Error(j.error); return j.data;
}
function toast(msg, err) { const t = document.createElement('div'); t.className = 'toast' + (err ? ' err' : ''); t.textContent = msg; document.body.append(t); setTimeout(() => t.remove(), 2800); }
// Generic CRUD table + modal form. c = {title,res,cols,fields,edit,filter}
function crud(root, c) {
  let list = [], fv = '';
  const load = async () => {
    try { list = await api(c.res + (fv ? `?${c.filter.k}=${fv}` : '')); draw(); }
    catch (e) { root.innerHTML = `<h2>${c.title}</h2><p style="color:#ff5470">${esc(e.message)}</p>`; }
  };
  function draw() {
    root.innerHTML = `<div class="bar"><h2>${c.title}</h2>
      ${c.filter ? `<select id="f"><option value="">All</option>${c.filter.options().map(o => `<option value="${o.id}" ${o.id == fv ? 'selected' : ''}>${esc(o.name)}</option>`).join('')}</select>` : ''}
      ${c.edit ? '<button class="btn" id="add">+ Add</button>' : ''}</div>
      <table><tr>${c.cols.map(x => `<th>${x.label}</th>`).join('')}${c.edit ? '<th></th>' : ''}</tr>
      ${list.map(r => `<tr>${c.cols.map(x => `<td>${x.img ? (r[x.k] ? `<img src="${esc(asset(r[x.k]))}">` : '') : esc(x.map ? x.map(r[x.k]) : r[x.k])}</td>`).join('')}
      ${c.edit ? `<td><button class="btn sm ghost" data-e="${r.id}">Edit</button> <button class="btn sm" data-d="${r.id}">Delete</button></td>` : ''}</tr>`).join('')}</table>`;
    if (c.filter) $('#f', root).onchange = e => { fv = e.target.value; load(); };
    if (c.edit) { $('#add', root).onclick = () => form();
      root.querySelectorAll('[data-e]').forEach(b => b.onclick = () => form(list.find(r => r.id == b.dataset.e)));
      root.querySelectorAll('[data-d]').forEach(b => b.onclick = async () => { if (!confirm('Delete?')) return;
        try { await api(`${c.res}/${b.dataset.d}`, 'DELETE'); toast('Deleted'); c.onChange?.(); load(); } catch (e) { toast(e.message, 1); } }); }
  }
  function form(row = {}) {
    const m = document.createElement('div'); m.className = 'modal';
    m.innerHTML = `<form class="card"><h3>${row.id ? 'Edit' : 'Add'} ${c.title}</h3>${c.fields.map(f => `<label>${f.label}${
      f.type === 'select' ? `<select name="${f.k}" required>${f.options().map(o => `<option value="${o.id}" ${o.id == row[f.k] ? 'selected' : ''}>${esc(o.name)}</option>`).join('')}</select>`
      : f.type === 'file' ? `<input type="file" name="${f.k}" accept="image/*">`
      : `<input name="${f.k}" type="${f.type || 'text'}" ${f.attr || ''} value="${f.type === 'password' ? '' : esc(row[f.k])}" ${f.req === false || (row.id && f.type === 'password') ? '' : 'required'}>`}</label>`).join('')}
      <div class="row"><button class="btn">Save</button><button type="button" class="btn ghost" id="x">Cancel</button></div></form>`;
    document.body.append(m); $('#x', m).onclick = () => m.remove();
    $('form', m).onsubmit = async e => { e.preventDefault();
      try { await api(row.id ? `${c.res}/${row.id}` : c.res, row.id ? 'PUT' : 'POST', new FormData(e.target)); m.remove(); toast('Saved'); c.onChange?.(); load(); }
      catch (err) { toast(err.message, 1); } };
  }
  load(); return { load };
}
