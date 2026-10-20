// Row checkboxes + "select all" + selection bar for bulk delete.
(() => {
  const all = document.querySelector('[data-all]');
  const boxes = [...document.querySelectorAll('input[name="ids[]"][form="bulk"]')];
  const bar = document.querySelector('.bulkbar');
  if (!all || !bar) return;
  const sync = () => {
    const n = boxes.filter((b) => b.checked).length;
    bar.hidden = n === 0;
    bar.querySelector('[data-count]').textContent = n;
    all.checked = n === boxes.length && n > 0;
    all.indeterminate = n > 0 && n < boxes.length;
    boxes.forEach((b) => b.closest('tr').classList.toggle('sel', b.checked));
  };
  all.addEventListener('change', () => { boxes.forEach((b) => (b.checked = all.checked)); sync(); });
  boxes.forEach((b) => b.addEventListener('change', sync));
})();
