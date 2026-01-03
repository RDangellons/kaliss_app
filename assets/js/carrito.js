(async () => {
  const csrf = document.getElementById('csrf')?.value || '';

  const post = async (url, data) => {
    const form = new FormData();
    Object.entries(data).forEach(([k,v]) => form.append(k, v));
    form.append('csrf', csrf);

    const res = await fetch(url, { method:'POST', body: form });
    return res.json();
  };

  document.querySelectorAll('.qty').forEach(inp => {
    inp.addEventListener('change', async () => {
      const productId = inp.dataset.productId;
      const qty = parseInt(inp.value || '1', 10);

      const r = await post('api/cart_update.php', { product_id: productId, qty: qty });
      if (!r.ok) alert(r.error || 'Error');
      location.reload();
    });
  });

  document.querySelectorAll('.btn-remove').forEach(btn => {
    btn.addEventListener('click', async () => {
      const productId = btn.dataset.productId;
      const r = await post('api/cart_remove.php', { product_id: productId });
      if (!r.ok) alert(r.error || 'Error');
      location.reload();
    });
  });

  const btnClear = document.getElementById('btnClear');
  if (btnClear) {
    btnClear.addEventListener('click', async () => {
      const r = await post('api/cart_clear.php', {});
      if (!r.ok) alert(r.error || 'Error');
      location.reload();
    });
  }
})();
