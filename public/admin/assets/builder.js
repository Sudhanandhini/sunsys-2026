// Post content builder: rows → columns → widgets. The state is plain JSON posted in the hidden "blocks" field;
// api/_lib/blocks.php cleans it on save, so the editor only needs to produce sensible structure.
(() => {
  const root = document.getElementById('builder');
  if (!root) return;
  const form = root.closest('form');
  const hidden = document.getElementById('blocks');
  const csrf = form.querySelector('input[name="csrf"]').value;
  const state = JSON.parse(document.getElementById('blocks-data').textContent || '{}');
  if (!state.rows?.length) state.rows = [{ cols: [{ widgets: [] }] }];
  const MAX_COLS = 4;
  // Uploads are stored as /uploads/...; when the site sits in a subfolder, previews need that folder in front
  const siteBase = location.pathname.replace(/\/admin\/[^/]*$/, '');
  const preview = (src) => (src.startsWith('/') && !src.startsWith('//') ? siteBase + src : src);

  const TYPES = {
    heading: { label: 'Heading', make: () => ({ type: 'heading', level: 2, html: '' }) },
    paragraph: { label: 'Paragraph', make: () => ({ type: 'paragraph', html: '' }) },
    list: { label: 'List', make: () => ({ type: 'list', ordered: false, items: [''] }) },
    quote: { label: 'Quote', make: () => ({ type: 'quote', html: '' }) },
    image: { label: 'Image', make: () => ({ type: 'image', src: '', alt: '', caption: '', href: '' }) },
    table: { label: 'Table', make: () => ({ type: 'table', header: true, rows: [['', '', ''], ['', '', ''], ['', '', '']] }) },
    button: { label: 'Button / link', make: () => ({ type: 'button', text: '', href: '', style: 'solid', newTab: false }) },
  };

  // ---------- helpers ----------
  const h = (tag, props = {}, ...kids) => {
    const el = document.createElement(tag);
    for (const [k, v] of Object.entries(props)) {
      if (k === 'class') el.className = v;
      else if (k === 'html') el.innerHTML = v;
      else if (k.startsWith('on')) el.addEventListener(k.slice(2), v);
      else if (k === 'value') el.value = v;
      else if (v === true) el.setAttribute(k, '');
      else if (v !== false && v != null) el.setAttribute(k, v);
    }
    el.append(...kids.flat().filter((c) => c != null && c !== false));
    return el;
  };
  const btn = (label, title, onclick, cls = 'pb-ib') =>
    h('button', { type: 'button', class: cls, title, 'aria-label': title, onmousedown: (e) => e.preventDefault(), onclick }, label);
  const move = (arr, i, d) => {
    const j = i + d;
    if (j < 0 || j >= arr.length) return;
    [arr[i], arr[j]] = [arr[j], arr[i]];
    render();
  };
  const hasContent = (w) => Object.entries(w).some(([k, v]) => k !== 'type' && k !== 'level' && k !== 'style' && (Array.isArray(v) ? v.join('').trim() : typeof v === 'string' && v.replace(/<[^>]*>/g, '').trim()));
  const TABLE_MAX_COLS = 12; // same limit as BLOCK_TABLE_MAX_COLS in api/_lib/blocks.php
  const escapeHtml = (s) => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

  let dirty = false;
  let focusTarget = null; // widget to focus after the next render
  root.addEventListener('input', () => (dirty = true));
  window.addEventListener('beforeunload', (e) => { if (dirty) e.preventDefault(); });
  form.addEventListener('submit', () => { hidden.value = JSON.stringify(state); dirty = false; });

  // ---------- uploads ----------
  const upload = async (data) => {
    const fd = new FormData();
    fd.append('csrf', csrf);
    for (const [k, v] of Object.entries(data)) fd.append(k, v);
    const r = await fetch('upload-image.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const d = await r.json().catch(() => ({}));
    if (!r.ok || d.error || !d.src) throw new Error(d.error || 'Upload failed.');
    return d.src;
  };
  // Pasted images (data: or https links, which can expire) are copied to the website's own uploads
  const copyImage = async (w) => {
    const wasData = w.src.startsWith('data:');
    w._status = 'Copying the image to the website…';
    try {
      const data = wasData ? { image: new File([await (await fetch(w.src)).blob()], 'pasted-image') } : { url: w.src };
      w.src = await upload(data);
      w._status = '';
    } catch (err) {
      if (wasData) w.src = '';
      w._status = w.src ? `Could not copy the image (${err.message}) It will load from the original address.` : `${err.message} Upload the image again.`;
    }
    dirty = true;
    render();
  };

  // ---------- paste: Google Docs, Word, web pages → widgets ----------
  const SKIP = /^(SCRIPT|STYLE|META|LINK|TITLE|TEMPLATE|SVG|IFRAME|OBJECT|NOSCRIPT|HEAD|BUTTON|INPUT|SELECT|TEXTAREA)$/;
  const BLOCK = /^(P|DIV|H[1-6]|UL|OL|LI|TABLE|BLOCKQUOTE|FIGURE|FIGCAPTION|SECTION|ARTICLE|HEADER|FOOTER|MAIN|ASIDE|CENTER|PRE|DL|DT|DD|ADDRESS)$/;
  const HAS_BLOCK = 'p,div,h1,h2,h3,h4,h5,h6,ul,ol,table,blockquote,img,figure,pre';
  const textOf = (s) => s.replace(/<[^>]*>/g, '').trim();
  const stripEnds = (s) => s.replace(/^(\s|<br>)+|(\s|<br>)+$/g, '');
  const tidy = (s) => (textOf(stripEnds(s)) ? stripEnds(s) : '');
  // Inline content with only bold, italic, underline, links and line breaks (Docs/Word styles become tags)
  const inline = (node) => {
    let out = '';
    for (const n of node.childNodes) {
      if (n.nodeType === 3) { out += escapeHtml(n.nodeValue.replace(/[\s ]+/g, ' ')); continue; }
      if (n.nodeType !== 1 || SKIP.test(n.tagName)) continue;
      const tag = n.tagName;
      const style = n.getAttribute('style') || '';
      if (tag === 'BR') { out += '<br>'; continue; }
      if (/mso-list\s*:\s*ignore/i.test(style)) continue; // Word's typed bullet or number
      let inner = inline(n);
      if (BLOCK.test(tag) && textOf(out) && textOf(inner)) inner = '<br>' + inner;
      if (!textOf(inner.replace(/<br>/g, ''))) { out += inner; continue; }
      const st = n.style || {};
      const fw = st.fontWeight || '';
      const bold = /^(B|STRONG)$/.test(tag) ? !/^(normal|[1-5]00)$/.test(fw) : fw === 'bold' || +fw >= 600;
      const italic = /^(I|EM)$/.test(tag) ? st.fontStyle !== 'normal' : st.fontStyle === 'italic';
      const under = (tag === 'U' || /underline/.test(st.textDecoration || '')) && !n.closest('a');
      if (tag === 'A') {
        const href = (n.getAttribute('href') || '').trim();
        if (/^(https?:|mailto:|tel:|\/(?!\/)|#)/i.test(href)) inner = `<a href="${escapeHtml(href)}">${inner}</a>`;
      }
      if (under) inner = `<u>${inner}</u>`;
      if (italic) inner = `<i>${inner}</i>`;
      if (bold) inner = `<b>${inner}</b>`;
      out += inner;
    }
    return out;
  };
  const unbold = (s) => s.replace(/<\/?b>/g, '');
  const wholeBold = (s) => /^<b>[\s\S]*<\/b>$/.test(s) && !/<\/b>[\s\S]*<b>/.test(s);
  // rows of cell HTML → table widget (same width for every row; header given, or a first row bold all the way across)
  const makeTable = (rows, header = false) => {
    rows = rows.filter((r) => textOf(r.join('')));
    if (!rows.length) return null;
    const n = Math.min(TABLE_MAX_COLS, Math.max(...rows.map((r) => r.length)));
    rows = rows.map((r) => [...r, ...Array(n).fill('')].slice(0, n));
    const first = rows[0].filter(Boolean);
    header = header || (rows.length > 1 && first.length > 0 && first.every(wholeBold));
    if (header) rows[0] = rows[0].map(unbold);
    return { type: 'table', header, rows };
  };
  const ROLE_CELL = '[role="cell"],[role="gridcell"],[role="columnheader"],[role="rowheader"]';
  const tableFrom = (t) => {
    if (t.tagName !== 'TABLE') { // <div role="table"> grids (some web apps and doc viewers)
      const rows = [...t.querySelectorAll('[role="row"]')].map((r) => [...r.querySelectorAll(ROLE_CELL)]);
      return makeTable(rows.map((r) => r.map((c) => tidy(inline(c)))), !!rows[0]?.some((c) => c.getAttribute('role') === 'columnheader'));
    }
    const rows = [...t.rows].map((tr) => [...tr.cells].flatMap((td) => [tidy(inline(td)), ...Array(Math.max(0, (td.colSpan || 1) - 1)).fill('')]));
    return makeTable(rows, !!(t.tHead || t.rows[0]?.querySelector('th')));
  };
  // Plain text copied from a table or spreadsheet: one row per line, cells separated by tabs
  const textTable = (text) => {
    const lines = (text || '').split(/\r?\n/).filter((l) => l.trim());
    if (lines.length < 2 || !lines.every((l) => l.includes('\t'))) return null;
    return makeTable(lines.map((l) => l.split('\t').map((c) => escapeHtml(c.trim()))), true);
  };
  const htmlToWidgets = (html) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const out = [];
    let run = '';
    let wordList = null; // Word pastes list items as separate <p class="MsoListParagraph">
    const para = (h) => {
      h = tidy(h);
      if (!h) return;
      const last = out[out.length - 1];
      if (last?.type === 'paragraph') last.html += `<p>${h}</p>`; // consecutive paragraphs share one widget
      else out.push({ type: 'paragraph', html: `<p>${h}</p>` });
    };
    const flush = () => { para(run); run = ''; };
    const endList = () => { if (wordList?.items.length) out.push(wordList); wordList = null; };
    const walk = (parent) => {
      for (const n of [...parent.childNodes]) {
        if (n.nodeType === 3) {
          if (n.nodeValue.trim()) { endList(); run += escapeHtml(n.nodeValue.replace(/[\s ]+/g, ' ')); }
          continue;
        }
        if (n.nodeType !== 1 || SKIP.test(n.tagName)) continue;
        const tag = n.tagName;
        if (tag === 'P' && (/MsoList/i.test(n.className) || /mso-list/i.test(n.getAttribute('style') || ''))) {
          flush();
          const mark = [...n.querySelectorAll('[style]')].find((x) => /mso-list\s*:\s*ignore/i.test(x.getAttribute('style')))?.textContent.trim() || '';
          const item = tidy(inline(n));
          if (!wordList) wordList = { type: 'list', ordered: /^(\d+|[a-z]{1,4})[.)]/i.test(mark), items: [] };
          if (item) wordList.items.push(item);
          continue;
        }
        endList();
        if (/^H[1-6]$/.test(tag)) {
          flush();
          const h = unbold(tidy(inline(n)));
          if (h) out.push({ type: 'heading', level: Math.min(4, Math.max(2, +tag[1])), html: h });
        } else if (tag === 'UL' || tag === 'OL') {
          flush();
          const items = [...n.querySelectorAll('li')].map((li) => {
            const c = li.cloneNode(true);
            c.querySelectorAll('ul,ol').forEach((x) => x.remove()); // nested lists become their own items
            return tidy(inline(c));
          }).filter(Boolean);
          if (items.length) out.push({ type: 'list', ordered: tag === 'OL', items });
        } else if (tag === 'TABLE' || /^(table|grid|treegrid)$/.test(n.getAttribute('role') || '')) {
          flush();
          const t = tableFrom(n);
          if (t) out.push(t);
        } else if (tag === 'BLOCKQUOTE' && !n.querySelector('table,h1,h2,h3,h4,ul,ol,img')) {
          flush();
          const ps = [...n.querySelectorAll('p')];
          const q = (ps.length ? ps : [n]).map((p) => tidy(inline(p))).filter(Boolean).map((p) => `<p>${p}</p>`).join('');
          if (q) out.push({ type: 'quote', html: q });
        } else if (tag === 'IMG') {
          flush();
          const src = n.getAttribute('src') || '';
          if (/^(https:\/\/|data:image\/)/i.test(src)) out.push({ type: 'image', src, alt: (n.getAttribute('alt') || '').slice(0, 300), caption: '', href: '' });
        } else if (tag === 'HR') {
          flush();
        } else if (n.querySelector(HAS_BLOCK)) {
          if (BLOCK.test(tag)) flush();
          walk(n); // wrappers (Google Docs <b id="docs-internal-guid…">, layout divs): look inside
          if (BLOCK.test(tag)) flush();
        } else if (BLOCK.test(tag)) {
          flush();
          para(inline(n));
        } else {
          run += inline({ childNodes: [n] });
        }
      }
    };
    walk(doc.body);
    flush();
    endList();
    return out;
  };
  // Put pasted widgets after `cur` (or in its place while it is still empty), in the same column
  const insertWidgets = (cur, ws) => {
    for (const row of state.rows) for (const col of row.cols) {
      const i = col.widgets.indexOf(cur);
      if (i < 0) continue;
      const empty = !hasContent(cur);
      col.widgets.splice(empty ? i : i + 1, empty ? 1 : 0, ...ws);
      dirty = true;
      render();
      ws.forEach((w) => { if (w.type === 'image' && w.src && !w.src.startsWith('/uploads/')) copyImage(w); });
      return;
    }
  };
  const pastedImages = (cd) => [...(cd.files || [])].filter((f) => f.type.startsWith('image/'));
  const imageWidgetsFromFiles = (files) => files.map((f) => {
    const w = { type: 'image', src: '', alt: '', caption: '', href: '', _status: 'Uploading…' };
    upload({ image: f }).then((src) => { w.src = src; w._status = ''; }, (err) => { w._status = err.message; }).finally(() => { dirty = true; render(); });
    return w;
  });

  // ---------- rich text ----------
  document.execCommand('defaultParagraphSeparator', false, 'p');
  let savedRange = null;
  document.addEventListener('selectionchange', () => {
    const sel = getSelection();
    const node = sel.anchorNode;
    const el = node && (node.nodeType === 1 ? node : node.parentElement);
    if (sel.rangeCount && el?.closest('.pb-rich, .pb-cell')) savedRange = sel.getRangeAt(0).cloneRange();
  });
  // Toolbar buttons keep focus in the editor; the colour picker and prompts don't, so put the selection back
  const restore = (ed) => {
    const sel = getSelection();
    if (sel.rangeCount && ed.contains(sel.getRangeAt(0).commonAncestorContainer)) return;
    ed.focus();
    if (savedRange && ed.contains(savedRange.commonAncestorContainer)) {
      sel.removeAllRanges();
      sel.addRange(savedRange);
    }
  };
  const exec = (ed, name, value = null) => {
    restore(ed);
    document.execCommand(name, false, value);
    ed.dispatchEvent(new Event('input', { bubbles: true }));
  };
  const link = (ed) => {
    restore(ed);
    const sel = getSelection();
    const a = (sel.anchorNode?.nodeType === 1 ? sel.anchorNode : sel.anchorNode?.parentElement)?.closest('a');
    if (a && ed.contains(a)) {
      if (confirm(`Remove the link to ${a.getAttribute('href')}?`)) exec(ed, 'unlink');
      return;
    }
    if (sel.isCollapsed) return alert('Select the words you want to turn into a link first.');
    const url = prompt('Link address: https://…, a page such as /contact, or mailto:', 'https://');
    if (url && url.trim() !== 'https://') exec(ed, 'createLink', url.trim());
  };
  const richTools = (ed) => [
    btn(h('b', {}, 'B'), 'Bold (Ctrl+B)', () => exec(ed, 'bold')),
    btn(h('i', {}, 'I'), 'Italic (Ctrl+I)', () => exec(ed, 'italic')),
    btn(h('u', {}, 'U'), 'Underline (Ctrl+U)', () => exec(ed, 'underline')),
    h('input', { type: 'color', class: 'pb-color', title: 'Text colour (select text first)', 'aria-label': 'Text colour', value: '#062a52', oninput: (e) => exec(ed, 'foreColor', e.target.value) }),
    btn('🔗', 'Add or remove a link', () => link(ed)),
    btn('⌫', 'Clear formatting', () => { exec(ed, 'removeFormat'); exec(ed, 'unlink'); }),
  ];
  // contenteditable area bound to w[key]; `multi` allows several paragraphs
  const rich = (w, { key = 'html', multi = true, placeholder = '', cls = '' } = {}) => {
    const ed = h('div', { class: `pb-rich ${cls}`, contenteditable: 'true', 'data-ph': placeholder, html: w[key] || '' });
    ed.addEventListener('input', () => { w[key] = ed.textContent.trim() || ed.querySelector('img') ? ed.innerHTML : ''; });
    ed.addEventListener('keydown', (e) => { if (!multi && e.key === 'Enter') e.preventDefault(); });
    ed.addEventListener('paste', (e) => {
      e.preventDefault();
      const cd = e.clipboardData;
      const html = cd.getData('text/html');
      const files = pastedImages(cd);
      if (!html && files.length) return insertWidgets(w, imageWidgetsFromFiles(files)); // screenshot or copied image
      const ws = html ? htmlToWidgets(html) : [];
      // a table that only came through as tab-separated text
      const tt = !ws.some((x) => x.type !== 'paragraph') && textTable(cd.getData('text/plain'));
      if (tt) return insertWidgets(w, [tt]);
      if (html) {
        const one = ws.length === 1 ? ws[0] : null;
        // headings, tables, lists, images or several blocks: each becomes its own widget, formatted as copied
        if (ws.length && !(one && (one.type === 'paragraph' || (one.type === 'heading' && w.type === 'heading')))) return insertWidgets(w, ws);
        if (one?.type === 'paragraph' && multi) {
          const inner = one.html.match(/^<p>([\s\S]*)<\/p>$/);
          document.execCommand('insertHTML', false, inner && !inner[1].includes('<p>') ? inner[1] : one.html); // keeps bold, italic and links
          return;
        }
      }
      const text = cd.getData('text/plain');
      const paras = text.split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean);
      if (multi && paras.length > 1) document.execCommand('insertHTML', false, paras.map((p) => `<p>${escapeHtml(p).replace(/\n/g, '<br>')}</p>`).join(''));
      else document.execCommand('insertText', false, multi ? text : text.replace(/\s*\n\s*/g, ' '));
    });
    return ed;
  };

  // ---------- widgets ----------
  const field = (label, input) => h('label', { class: 'pb-field' }, label, input);
  const text = (w, key, props = {}) => h('input', { type: 'text', value: w[key] ?? '', oninput: (e) => (w[key] = e.target.value), ...props });

  function widgetBody(w) {
    switch (w.type) {
      case 'heading': {
        const ed = rich(w, { multi: false, placeholder: 'Heading text', cls: `pb-h pb-h${w.level}` });
        const level = h('select', { class: 'pb-level', 'aria-label': 'Heading level', onchange: (e) => { w.level = +e.target.value; render(); } },
          [2, 3, 4].map((n) => h('option', { value: n, selected: w.level === n }, `H${n}`)));
        return { ed, tools: [level, ...richTools(ed)] };
      }
      case 'paragraph':
      case 'quote': {
        const ed = rich(w, { placeholder: w.type === 'quote' ? 'Quote text' : 'Write a paragraph… press Enter for a new paragraph', cls: w.type === 'quote' ? 'pb-quote' : '' });
        // bullet / numbered list inside the text: click on a line (or select several) — click again to turn it back into text
        const lists = [
          btn('• List', 'Bulleted list (ul)', () => exec(ed, 'insertUnorderedList'), 'pb-ib pb-wide'),
          btn('1. List', 'Numbered list (ol)', () => exec(ed, 'insertOrderedList'), 'pb-ib pb-wide'),
        ];
        return { ed, tools: [...richTools(ed), ...lists] };
      }
      case 'list': {
        const ed = h(w.ordered ? 'ol' : 'ul', { class: 'pb-rich pb-list', contenteditable: 'true', html: (w.items.length ? w.items : ['']).map((i) => `<li>${i || '<br>'}</li>`).join('') });
        const sync = () => {
          const lis = [...ed.querySelectorAll('li')];
          w.items = lis.length ? lis.map((li) => (li.textContent.trim() ? li.innerHTML : '')) : [ed.innerHTML];
        };
        ed.addEventListener('input', sync);
        ed.addEventListener('paste', (e) => {
          e.preventDefault();
          const lines = e.clipboardData.getData('text/plain').split(/\n+/).map((l) => l.replace(/^\s*([-*•]|\d+[.)])\s*/, '').trim()).filter(Boolean);
          document.execCommand('insertHTML', false, lines.map(escapeHtml).join('</li><li>'));
        });
        const kind = h('select', { class: 'pb-level', 'aria-label': 'List style', onchange: (e) => { sync(); w.ordered = e.target.value === 'ol'; render(); } },
          h('option', { value: 'ul', selected: !w.ordered }, '• Bullets'), h('option', { value: 'ol', selected: w.ordered }, '1. Numbers'));
        return { ed, tools: [kind, ...richTools(ed)] };
      }
      case 'image': {
        const status = h('span', { class: 'pb-status' }, w._status || '');
        const file = h('input', {
          type: 'file', accept: 'image/jpeg,image/png,image/webp',
          onchange: async (e) => {
            const f = e.target.files[0];
            if (!f) return;
            status.textContent = 'Uploading…';
            try {
              w.src = await upload({ image: f });
              w._status = '';
              if (!w.alt) w.alt = f.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
              dirty = true;
              render();
            } catch (err) {
              status.textContent = err.message;
            }
          },
        });
        const body = h('div', { class: 'pb-image' },
          w.src ? h('img', { src: preview(w.src), alt: '', class: 'pb-image-prev' }) : h('div', { class: 'pb-image-empty' }, 'No image yet'),
          h('div', { class: 'pb-fields' },
            field(w.src ? 'Replace image (JPG, PNG or WebP, up to 4 MB)' : 'Upload image (JPG, PNG or WebP, up to 4 MB)', file),
            status,
            field('Alt text (describes the image for Google and screen readers)', text(w, 'alt', { maxlength: 300 })),
            field('Caption (optional)', text(w, 'caption', { maxlength: 300 })),
            field('Link the image to (optional)', text(w, 'href', { placeholder: 'https://… or /page' }))));
        return { ed: body, tools: [] };
      }
      case 'table': {
        let cur = { r: 0, c: 0 }; // cell the row/column buttons act on
        const ncols = () => w.rows[0]?.length || 1;
        const changed = () => { dirty = true; render(); };
        const cells = [];
        const table = h('table', { class: 'pb-table' }, w.rows.map((row, r) => h('tr', {}, row.map((v, c) => {
          const td = h(w.header && r === 0 ? 'th' : 'td', { class: 'pb-cell', contenteditable: 'true', html: v || '' });
          td.addEventListener('focus', () => (cur = { r, c }));
          td.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); document.execCommand('insertLineBreak'); } });
          td.addEventListener('paste', (e) => {
            e.preventDefault();
            const html = e.clipboardData.getData('text/html');
            const t = (html && htmlToWidgets(html).find((x) => x.type === 'table')) || textTable(e.clipboardData.getData('text/plain'));
            if (t) { // cells from a spreadsheet or another table: fill from this cell, growing the table as needed
              t.rows.forEach((tr, i) => tr.forEach((val, j) => {
                const R = r + i, C = c + j;
                if (C >= TABLE_MAX_COLS) return;
                while (w.rows.length <= R) w.rows.push(Array(ncols()).fill(''));
                if (C >= ncols()) w.rows.forEach((x) => { while (x.length <= C) x.push(''); });
                w.rows[R][C] = val;
              }));
              return changed();
            }
            document.execCommand('insertText', false, e.clipboardData.getData('text/plain').replace(/\s*\n\s*/g, ' '));
          });
          cells.push([td, r, c]);
          return td;
        }))));
        // one listener for typing and for the formatting buttons (they fire input on the table)
        table.addEventListener('input', () => cells.forEach(([td, r, c]) => (w.rows[r][c] = td.textContent.trim() ? td.innerHTML : '')));
        const header = h('select', { class: 'pb-level', 'aria-label': 'Header row', onchange: (e) => { w.header = e.target.value === '1'; changed(); } },
          h('option', { value: '1', selected: !!w.header }, 'Header row'), h('option', { value: '0', selected: !w.header }, 'No header'));
        const tools = [
          header,
          btn('+ Row', 'Add a row below the selected cell', () => { w.rows.splice(cur.r + 1, 0, Array(ncols()).fill('')); changed(); }, 'pb-ib pb-wide'),
          btn('+ Col', 'Add a column right of the selected cell', () => {
            if (ncols() >= TABLE_MAX_COLS) return alert(`A table can have up to ${TABLE_MAX_COLS} columns.`);
            w.rows.forEach((x) => x.splice(cur.c + 1, 0, ''));
            changed();
          }, 'pb-ib pb-wide'),
          btn('− Row', 'Delete the selected row', () => { if (w.rows.length > 1) { w.rows.splice(cur.r, 1); changed(); } }, 'pb-ib pb-wide'),
          btn('− Col', 'Delete the selected column', () => { if (ncols() > 1) { w.rows.forEach((x) => x.splice(cur.c, 1)); changed(); } }, 'pb-ib pb-wide'),
          ...richTools(table),
        ];
        return { ed: h('div', { class: 'pb-table-wrap' }, table), tools };
      }
      case 'button': {
        const body = h('div', { class: 'pb-fields pb-grid2' },
          field('Button text', text(w, 'text', { maxlength: 120, placeholder: 'e.g. Request a quote' })),
          field('Link address', text(w, 'href', { placeholder: 'https://… , /contact or mailto:' })),
          field('Style', h('select', { onchange: (e) => (w.style = e.target.value) },
            h('option', { value: 'solid', selected: w.style !== 'outline' }, 'Solid (navy)'),
            h('option', { value: 'outline', selected: w.style === 'outline' }, 'Outline'))),
          h('label', { class: 'check pb-check' }, h('input', { type: 'checkbox', checked: !!w.newTab, onchange: (e) => (w.newTab = e.target.checked) }), 'Open in a new tab'));
        return { ed: body, tools: [] };
      }
    }
    return { ed: h('p', { class: 'muted' }, 'Unknown widget'), tools: [] };
  }

  function widgetEl(w, i, col) {
    const { ed, tools } = widgetBody(w);
    if (w === focusTarget) setTimeout(() => (ed.matches('[contenteditable]') ? ed : ed.querySelector('input,[contenteditable]'))?.focus());
    return h('div', { class: 'pb-w' },
      h('div', { class: 'pb-w-head' },
        h('span', { class: 'pb-tag' }, TYPES[w.type]?.label ?? w.type),
        h('span', { class: 'pb-tools' }, tools,
          h('span', { class: 'pb-sep' }),
          btn('↑', 'Move up', () => move(col.widgets, i, -1)),
          btn('↓', 'Move down', () => move(col.widgets, i, 1)),
          btn('✕', 'Delete widget', () => {
            if (hasContent(w) && !confirm('Delete this widget?')) return;
            col.widgets.splice(i, 1);
            dirty = true;
            render();
          }, 'pb-ib pb-del'))),
      ed);
  }

  function addMenu(col) {
    const menu = h('details', { class: 'pb-add' },
      h('summary', { class: 'pb-soft' }, '+ Add widget'),
      h('div', { class: 'pb-menu', role: 'menu' }, Object.entries(TYPES).map(([type, t]) =>
        h('button', { type: 'button', role: 'menuitem', onclick: () => { const w = t.make(); col.widgets.push(w); focusTarget = w; dirty = true; render(); } }, t.label))));
    return menu;
  }
  document.addEventListener('click', (e) => document.querySelectorAll('.pb-add[open]').forEach((d) => { if (!d.contains(e.target)) d.open = false; }));

  // ---------- rows & columns ----------
  function render() {
    const y = window.scrollY;
    root.replaceChildren(
      ...state.rows.map((row, ri) => h('section', { class: 'pb-row' },
        h('div', { class: 'pb-row-head' },
          h('span', { class: 'pb-label' }, `Row ${ri + 1}`, row.cols.length > 1 ? h('span', { class: 'muted' }, ` · ${row.cols.length} columns`) : null),
          h('span', { class: 'pb-tools' },
            btn('↑', 'Move row up', () => move(state.rows, ri, -1)),
            btn('↓', 'Move row down', () => move(state.rows, ri, 1)),
            btn('✕ Row', 'Delete row', () => {
              if (row.cols.some((c) => c.widgets.length) && !confirm(`Delete row ${ri + 1} and everything in it?`)) return;
              state.rows.splice(ri, 1);
              if (!state.rows.length) state.rows.push({ cols: [{ widgets: [] }] });
              dirty = true;
              render();
            }, 'pb-ib pb-del pb-wide'))),
        h('div', { class: 'pb-cols', style: `--cols:${row.cols.length}` },
          row.cols.map((col, ci) => h('div', { class: 'pb-col' },
            h('div', { class: 'pb-col-head' },
              h('span', { class: 'pb-label' }, `Column ${ci + 1}`),
              row.cols.length > 1 ? h('span', { class: 'pb-tools' },
                btn('←', 'Move column left', () => move(row.cols, ci, -1)),
                btn('→', 'Move column right', () => move(row.cols, ci, 1)),
                btn('✕', 'Delete column', () => {
                  if (col.widgets.length && !confirm('Delete this column and its widgets?')) return;
                  row.cols.splice(ci, 1);
                  dirty = true;
                  render();
                }, 'pb-ib pb-del')) : null),
            col.widgets.length ? col.widgets.map((w, wi) => widgetEl(w, wi, col)) : h('p', { class: 'muted small pb-empty' }, 'No widgets yet.'),
            addMenu(col)))),
        row.cols.length < MAX_COLS
          ? h('button', { type: 'button', class: 'pb-soft pb-addcol', onclick: () => { row.cols.push({ widgets: [] }); dirty = true; render(); } }, '+ Add column')
          : null)),
      h('button', { type: 'button', class: 'btn pb-addrow', onclick: () => { state.rows.push({ cols: [{ widgets: [] }] }); dirty = true; render(); } }, '+ Add row'),
    );
    window.scrollTo(0, y);
    focusTarget = null;
  }
  render();
})();
