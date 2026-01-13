document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkoutForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    try {
      const fd = new FormData(form);

      // ✅ Ruta al endpoint (ajústala si tu archivo se llama diferente)
      const endpoint = 'api/mp_create_preference.php';

      const res = await fetch(endpoint, { method: 'POST', body: fd });

      // 👇 NO uses res.json() directo. Primero lee texto para debug.
      const text = await res.text();
      console.log('Respuesta cruda:', text);

      let r;
      try {
        r = JSON.parse(text);
      } catch (err) {
        throw new Error(
          'El servidor NO regresó JSON. Código HTTP: ' + res.status +
          '\nInicio respuesta: ' + text.slice(0, 150)
        );
      }

      if (r.ok && r.pay_url) {
        window.location.href = r.pay_url;
        return;
      }

      alert(r.error || 'No se pudo iniciar el pago.');

    } catch (err) {
      console.error(err);
      alert(err.message || 'Error');
    } finally {
      if (btn) btn.disabled = false;
    }
  });
});
