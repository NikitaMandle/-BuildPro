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
// ---------- Small HTML (rich text) editor ----------
const SPEC_LINES = '<p><b>Edge :</b> </p><p><b>Width :</b> </p><p><b>Finish :</b> </p><p><b>Payment Condition :</b> </p>';
function plainText(h, max) {   // HTML -> short plain text for table previews (DOMParser = nothing is executed or loaded)
  const t = (new DOMParser().parseFromString(String(h ?? '').replace(/<\/(p|li|h\d|blockquote)>|<br\s*\/?>/gi, ' '), 'text/html').body.textContent || '').replace(/\s+/g, ' ').trim();
  return max && t.length > max ? t.slice(0, max) + '…' : t;
}
function htmlField(f, row) {
  const v = row[f.k] || '', init = /<[a-z][\s\S]*>/i.test(v) ? v : esc(v).replace(/\n/g, '<br>');
  const b = (cmd, label, title, val = '') => `<button type="button" data-cmd="${cmd}" data-val="${val}" title="${title}">${label}</button>`;
  return `<div class="field"><span>${f.label}</span><div class="ed${f.tall ? ' tall' : ''}"><div class="ed-bar">
    ${b('bold', '<b>B</b>', 'Bold')}${b('italic', '<i>I</i>', 'Italic')}${b('underline', '<u>U</u>', 'Underline')}${b('formatBlock', 'H', 'Heading', 'h3')}
    ${b('insertUnorderedList', '• List', 'Bullet list')}${b('insertOrderedList', '1. List', 'Numbered list')}${b('createLink', 'Link', 'Add link')}
    ${b('removeFormat', 'Clear', 'Clear formatting')}${f.noSpecs ? '' : b('specs', '+ Specs', 'Insert Edge / Width / Finish / Payment Condition lines')}</div>
    <div class="ed-body" contenteditable="true" data-ph="${f.ph || 'Write description…'}">${init}</div><input type="hidden" name="${f.k}"></div></div>`;
}
function initEditor(ed) {
  const body = $('.ed-body', ed); document.execCommand('defaultParagraphSeparator', false, 'p');
  ed.querySelectorAll('[data-cmd]').forEach(btn => {
    btn.onmousedown = e => e.preventDefault();          // keep the text selection while clicking toolbar buttons
    btn.onclick = () => { body.focus(); const cmd = btn.dataset.cmd;
      if (cmd === 'specs') document.execCommand('insertHTML', false, SPEC_LINES);
      else if (cmd === 'createLink') { const u = prompt('Link URL (https://…)'); if (u) document.execCommand('createLink', false, u); }
      else if (cmd === 'formatBlock') document.execCommand('formatBlock', false, document.queryCommandValue('formatBlock') === btn.dataset.val ? 'p' : btn.dataset.val);
      else document.execCommand(cmd, false, null); };
  });
  body.addEventListener('paste', e => { e.preventDefault(); document.execCommand('insertText', false, (e.clipboardData || window.clipboardData).getData('text')); });
}
function syncEditor(ed) {      // copy the editor's HTML into its hidden input before the form is sent
  const b = $('.ed-body', ed); $('input', ed).value = (b.textContent.trim() || b.querySelector('li')) ? b.innerHTML : '';
}
// Single-page HTML editor (About Us / Contact Us). Content is stored as sanitized HTML via api 'pages/<slug>'.
async function pageEditor(root, slug, title, canEdit) {
  root.innerHTML = `<div class="bar"><h2>${esc(title)}</h2></div><p>Loading…</p>`;
  let row;
  try { row = await api(`pages/${slug}`); }
  catch (e) { root.innerHTML = `<h2>${esc(title)}</h2><p style="color:#ff5470">${esc(e.message)}</p>`; return; }
  if (!canEdit) { root.innerHTML = `<div class="bar"><h2>${esc(title)}</h2></div><div class="ed-body page-view">${row.content || '<p>Nothing added yet.</p>'}</div>`; return; }
  root.innerHTML = `<div class="bar"><h2>${esc(title)}</h2></div><form class="page-form">${htmlField({ k: 'content', label: 'Content', ph: 'Write content…', noSpecs: 1, tall: 1 }, row)}<div class="row"><button class="btn">Save</button></div></form>`;
  initEditor($('.ed', root));
  $('form', root).onsubmit = async e => { e.preventDefault(); syncEditor($('.ed', root));
    try { await api(`pages/${slug}`, 'PUT', new FormData(e.target)); toast('Saved'); }
    catch (err) { toast(err.message, 1); } };
}
// Generic CRUD table + modal form. c = {title,res,cols,fields,edit,filter}
function crud(root, c) {
  let list = [], fv = '';
  const load = async () => {
    try { list = await api(c.res + (fv ? `?${c.filter.k}=${fv}` : '')); c.onLoad?.(list); draw(); }
    catch (e) { root.innerHTML = `<h2>${c.title}</h2><p style="color:#ff5470">${esc(e.message)}</p>`; }
  };
  const cell = (x, r) => {   // one table cell: image / plain-text preview / coloured pill / text
    const v = x.map ? x.map(r[x.k]) : r[x.k];
    const h = x.img ? (r[x.k] ? `<img src="${esc(asset(r[x.k]))}">` : '')
      : x.plain ? esc(plainText(r[x.k], x.plain))
      : x.pill ? (v ? `<span class="pill p-${String(r[x.k]).replace(/\W/g, '')}">${esc(v)}</span>` : '')
      : esc(v);
    return `<td${x.cls ? ` class="${x.cls}"` : ''}>${h || '<span class="dim">—</span>'}</td>`;
  };
  const rowText = r => c.cols.map(x => x.plain ? plainText(r[x.k], 999) : x.map ? x.map(r[x.k]) : r[x.k]).join(' ').toLowerCase();
  function draw() {
    root.innerHTML = `<div class="bar"><h2>${c.title} <span class="count">${list.length}</span></h2>
      <input id="q" class="search" type="search" placeholder="Search…" autocomplete="off">
      ${c.filter ? `<select id="f"><option value="">All</option>${c.filter.options().map(o => `<option value="${o.id}" ${o.id == fv ? 'selected' : ''}>${esc(o.name)}</option>`).join('')}</select>` : ''}
      ${c.edit ? '<button class="btn" id="add">+ Add</button>' : ''}</div>
      <table><tr>${c.cols.map(x => `<th>${x.label}</th>`).join('')}${c.edit ? '<th class="act"></th>' : ''}</tr>
      ${list.map(r => `<tr data-s="${esc(rowText(r))}">${c.cols.map(x => cell(x, r)).join('')}
      ${c.edit ? `<td class="act"><button class="btn sm ghost" data-e="${r.id}">Edit</button> <button class="btn sm" data-d="${r.id}">Delete</button></td>` : ''}</tr>`).join('')}
      ${list.length ? '' : `<tr><td class="empty" colspan="${c.cols.length + (c.edit ? 1 : 0)}">Nothing added yet</td></tr>`}</table>`;
    $('#q', root).oninput = e => { const q = e.target.value.trim().toLowerCase(); root.querySelectorAll('tr[data-s]').forEach(tr => tr.hidden = !tr.dataset.s.includes(q)); };
    if (c.filter) $('#f', root).onchange = e => { fv = e.target.value; load(); };
    if (c.edit) { $('#add', root).onclick = () => form();
      root.querySelectorAll('[data-e]').forEach(b => b.onclick = () => form(list.find(r => r.id == b.dataset.e)));
      root.querySelectorAll('[data-d]').forEach(b => b.onclick = async () => { if (!confirm('Delete?')) return;
        try { await api(`${c.res}/${b.dataset.d}`, 'DELETE'); toast('Deleted'); c.onChange?.(); load(); } catch (e) { toast(e.message, 1); } }); }
  }
  function form(row = {}) {
    const m = document.createElement('div'); m.className = 'modal';
    m.innerHTML = `<form class="card${c.fields.length > 2 ? ' wide cols' : ''}"><h3>${row.id ? 'Edit' : 'Add'} ${c.one || c.title}</h3>${c.fields.map(f => f.type === 'html' ? htmlField(f, row) : `<label>${f.label}${
      f.type === 'select' ? `<select name="${f.k}" required>${f.options().map(o => `<option value="${o.id}" ${o.id == row[f.k] ? 'selected' : ''}>${esc(o.name)}</option>`).join('')}</select>`
      : f.type === 'file' ? `<input type="file" name="${f.k}" accept="image/*">`
      : f.type === 'combo' ? `<input name="${f.k}" list="dl-${f.k}" ${f.attr || ''} autocomplete="off" placeholder="Select or type…" value="${esc(row[f.k])}" ${f.req === false ? '' : 'required'}><datalist id="dl-${f.k}">${f.options().map(o => `<option value="${esc(o)}">`).join('')}</datalist>`
      : `<input name="${f.k}" type="${f.type || 'text'}" ${f.attr || ''} value="${f.type === 'password' ? '' : esc(row[f.k])}" ${f.req === false || (row.id && f.type === 'password') ? '' : 'required'}>`}</label>`).join('')}
      <div class="row"><button class="btn">Save</button><button type="button" class="btn ghost" id="x">Cancel</button></div></form>`;
    document.body.append(m); m.querySelectorAll('.ed').forEach(initEditor); $('#x', m).onclick = () => m.remove();
    $('form', m).onsubmit = async e => { e.preventDefault();
      m.querySelectorAll('.ed').forEach(syncEditor);
      try { await api(row.id ? `${c.res}/${row.id}` : c.res, row.id ? 'PUT' : 'POST', new FormData(e.target)); m.remove(); toast('Saved'); c.onChange?.(); load(); }
      catch (err) { toast(err.message, 1); } };
  }
  load(); return { load };
}
