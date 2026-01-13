(() => {
  const form = document.getElementById('perfilForm');
  const msg = document.getElementById('perfilMsg');
  const btn = document.getElementById('btnSave');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (msg) msg.textContent = 'Guardando...';
    if (btn) {
      btn.disabled = true;
      btn.style.opacity = '0.75';
      btn.textContent = 'Guardando...';
    }

    const data = new FormData(form);

    try {
      const res = await fetch('api/profile_save.php', {
        method: 'POST',
        body: data
      });
      const r = await res.json();

      if (!r.ok) {
        if (msg) msg.textContent = r.error || 'No se pudo guardar.';
      } else {
        if (msg) msg.textContent = 'Listo ✅ Datos guardados.';
        // recargar para que el header tome el nuevo nombre
        setTimeout(() => window.location.reload(), 600);
      }
    } catch (err) {
      if (msg) msg.textContent = 'Error de red. Intenta de nuevo.';
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.textContent = 'Guardar';
      }
    }
  });
})();
