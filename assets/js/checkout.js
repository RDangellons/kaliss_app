(async () => {
  const form = document.getElementById('checkoutForm');
  const msg = document.getElementById('msg');
  const btn = document.getElementById('btnConfirm');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    msg.textContent = "Procesando pedido...";
    if (btn) {
      btn.disabled = true;
      btn.style.opacity = "0.75";
      btn.textContent = "Creando pedido...";
    }

    const formData = new FormData(form);

    try {
      const res = await fetch('api/checkout_create.php', { method:'POST', body: formData });
      const r = await res.json();

      if (!r.ok) {
        msg.textContent = r.error || "Error al crear el pedido.";
        if (btn) {
          btn.disabled = false;
          btn.style.opacity = "1";
          btn.textContent = "Confirmar pedido";
        }
        return;
      }

      window.location.href = "gracias.php?order=" + encodeURIComponent(r.order_number);
    } catch (err) {
      msg.textContent = "Error de red. Intenta de nuevo.";
      if (btn) {
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.textContent = "Confirmar pedido";
      }
    }
  });
})();
