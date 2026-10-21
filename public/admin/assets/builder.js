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

  const TYPES = {
    heading: { label: 'Heading', make: () => ({ type: 'heading', level: 2, html: '' }) },
    paragraph: { label: 'Paragraph', make: () => ({ type: 'paragraph', html: '' }) },
    list: { label: 'List', make: () => ({ type: 'list', ordered: false, items: [''] }) },
    quote: { label: 'Quote', make: () => ({ type: 'quote', html: '' }) },
    image: { label: 'Image', make: () => ({ type: 'image', src: '', alt: '', caption: '', href: '' }) },
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
  const escapeHtml = (s) => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

  let dirty = false;
  let focusTarget = null; // widget to focus after the next render
  root.addEventListener('input', () => (dirty = true));
  window.addEventListener('beforeunload', (e) => { if (dirty) e.preventDefault(); });
  form.addEventListener('submit', () => { hidden.value = JSON.stringify(state); dirty = false; });

  // ---------- rich text ----------
  document.execCommand('defaultParagraphSeparator', false, 'p');
  let savedRange = null;
  document.addEventListener('selectionchange', () => {
    const sel = getSelection();
    const node = sel.anchorNode;
    const el = node && (node.nodeType === 1 ? node : node.parentElement);
    if (sel.rangeCount && el?.closest('.pb-rich')) savedRange = sel.getRangeAt(0).cloneRange();
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
      const text = e.clipboardData.getData('text/plain');
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
        return { ed, tools: richTools(ed) };
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
        const status = h('span', { class: 'pb-status' });
        const file = h('input', {
          type: 'file', accept: 'image/jpeg,image/png,image/webp',
          onchange: async (e) => {
            const f = e.target.files[0];
            if (!f) return;
            status.textContent = 'Uploading…';
            const fd = new FormData();
            fd.append('csrf', csrf);
            fd.append('image', f);
            try {
              const r = await fetch('upload-image.php', { method: 'POST', body: fd, credentials: 'same-origin' });
              const d = await r.json();
              if (!r.ok || d.error) throw new Error(d.error || 'Upload failed.');
              w.src = d.src;
              if (!w.alt) w.alt = f.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
              dirty = true;
              render();
            } catch (err) {
              status.textContent = err.message;
            }
          },
        });
        const body = h('div', { class: 'pb-image' },
          w.src ? h('img', { src: w.src, alt: '', class: 'pb-image-prev' }) : h('div', { class: 'pb-image-empty' }, 'No image yet'),
          h('div', { class: 'pb-fields' },
            field(w.src ? 'Replace image (JPG, PNG or WebP, up to 4 MB)' : 'Upload image (JPG, PNG or WebP, up to 4 MB)', file),
            status,
            field('Alt text (describes the image for Google and screen readers)', text(w, 'alt', { maxlength: 300 })),
            field('Caption (optional)', text(w, 'caption', { maxlength: 300 })),
            field('Link the image to (optional)', text(w, 'href', { placeholder: 'https://… or /page' }))));
        return { ed: body, tools: [] };
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
