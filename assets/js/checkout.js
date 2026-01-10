(async () => {
  const form = document.getElementById('checkoutForm');
  const msg = document.getElementById('msg');
  const btn = document.getElementById('btnConfirm');
  const mpMount = document.getElementById('mp_button');

  if (!form) return;

  // ✅ Instancia MercadoPago (Public Key TEST)
  const publicKey = window.MP_PUBLIC_KEY;
  if (!publicKey || !publicKey.startsWith("TEST-")) {
    console.error("Falta MP_PUBLIC_KEY TEST o no es de prueba.");
  }

  const mp = publicKey ? new MercadoPago(publicKey, { locale: 'es-MX' }) : null;

  let walletBrick = null; // para no duplicar

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    msg.textContent = "Creando preferencia de pago...";
    if (btn) {
      btn.disabled = true;
      btn.style.opacity = "0.75";
      btn.textContent = "Preparando pago...";
    }

    // Limpia brick anterior si existe
    if (walletBrick && walletBrick.unmount) {
      try { walletBrick.unmount(); } catch (_) {}
      walletBrick = null;
    }
    if (mpMount) mpMount.innerHTML = "";

    const formData = new FormData(form);

    try {
      const res = await fetch('api/mp_create_preference.php', { method: 'POST', body: formData });
      const r = await res.json();

      if (!r.ok) {
        msg.textContent = r.error || "Error al iniciar el pago.";
        if (btn) {
          btn.disabled = false;
          btn.style.opacity = "1";
          btn.textContent = "Confirmar pedido";
        }
        return;
      }

      if (!mp || !mpMount) {
        msg.textContent = "No se pudo cargar Mercado Pago (SDK).";
        if (btn) {
          btn.disabled = false;
          btn.style.opacity = "1";
          btn.textContent = "Confirmar pedido";
        }
        return;
      }

      // ✅ NECESITAS preference_id para Bricks
      const preferenceId = r.preference_id;
      if (!preferenceId) {
        msg.textContent = "El servidor no devolvió preference_id.";
        if (btn) {
          btn.disabled = false;
          btn.style.opacity = "1";
          btn.textContent = "Confirmar pedido";
        }
        return;
      }

      msg.textContent = "Listo. Elige tu método en Mercado Pago:";
      if (btn) {
        btn.textContent = "Actualizar datos";
        btn.disabled = false;
        btn.style.opacity = "1";
      }

      const bricksBuilder = mp.bricks();

      // ✅ Render del Wallet Brick
      walletBrick = await bricksBuilder.create("wallet", "mp_button", {
        initialization: { preferenceId },
      });

    } catch (err) {
      console.error(err);
      msg.textContent = "Error de red. Intenta de nuevo.";
      if (btn) {
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.textContent = "Confirmar pedido";
      }
    }
  });
})();
