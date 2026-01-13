<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_login_page('perfil.php');

$pdo = db();
$userId = (int)($_SESSION['user_id'] ?? 0);

// Cargar usuario y dirección (si existe)
$st = $pdo->prepare("SELECT name, email, phone FROM users WHERE id=? LIMIT 1");
$st->execute([$userId]);
$u = $st->fetch() ?: ["name"=>($_SESSION['user_name'] ?? ''), "email"=>($_SESSION['user_email'] ?? ''), "phone"=>''];

$st2 = $pdo->prepare("SELECT phone, address_line1, address_line2, city, state, postal_code FROM user_addresses WHERE user_id=? LIMIT 1");
$st2->execute([$userId]);
$a = $st2->fetch() ?: [];

$phone = (string)($a['phone'] ?? $u['phone'] ?? '');

$profile = [
  'name' => (string)($u['name'] ?? ''),
  'email' => (string)($u['email'] ?? ''),
  'phone' => $phone,
  'address_line1' => (string)($a['address_line1'] ?? ''),
  'address_line2' => (string)($a['address_line2'] ?? ''),
  'city' => (string)($a['city'] ?? ''),
  'state' => (string)($a['state'] ?? ''),
  'postal_code' => (string)($a['postal_code'] ?? ''),
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mi perfil | KALISS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/index.css" />
  <link rel="stylesheet" href="assets/css/checkout.css" />
  <link rel="stylesheet" href="assets/css/perfil.css" />
  <style>
    .profile-grid{ display:grid; grid-template-columns: 1.1fr .9fr; gap:18px; align-items:start; }
    @media (max-width: 900px){ .profile-grid{ grid-template-columns:1fr; } }
    .kpi{ display:flex; gap:10px; flex-wrap:wrap; padding:16px; }
    .badge{ padding:10px 12px; border:1px solid var(--border); border-radius:999px; background: rgba(255,255,255,.12); font-size:12px; }
    .muted{ opacity:.85; font-size:12px; }
  </style>
</head>
<body>

<?php include __DIR__ . '/partials/header.php'; ?>

<main class="section checkout">
  <div class="container">

    <div class="checkout__top">
      <div>
        <h1 class="checkout__title">Mi perfil</h1>
        <p class="checkout__sub">Guarda tus datos para que el checkout se llene automático.</p>
      </div>

      <div class="checkout__steps">
        <div class="step active"><span>✓</span><small>Cuenta</small></div>
        <div class="step"><span>✓</span><small>Dirección</small></div>
        <div class="step"><span>✓</span><small>Checkout rápido</small></div>
      </div>
    </div>

    <div class="profile-grid">

      <section class="panel">
        <div class="panel__head">
          <h2 class="panel__title">Datos</h2>
          <p class="panel__hint">Puedes cambiar tu nombre y guardar tu dirección.</p>
        </div>

        <form id="perfilForm" class="form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <div class="form__row">
            <div class="form__group">
              <label class="form__label">Nombre *</label>
              <input class="form__input" name="name" required value="<?= htmlspecialchars($profile['name']) ?>">
            </div>
            <div class="form__group">
              <label class="form__label">Teléfono</label>
              <input class="form__input" name="phone" value="<?= htmlspecialchars($profile['phone']) ?>" placeholder="771 000 0000">
            </div>
          </div>

          <div class="form__group">
            <label class="form__label">Email</label>
            <input class="form__input" value="<?= htmlspecialchars($profile['email']) ?>" disabled>
            <p class="muted" style="margin:6px 0 0;">(Por ahora el email no se edita desde aquí.)</p>
          </div>

          <hr style="border:none;border-top:1px solid var(--border); opacity:.7; margin:14px 0;">

          <h3 style="margin:0 0 8px; font-size:16px;">Dirección de envío</h3>

          <div class="form__group">
            <label class="form__label">Dirección</label>
            <input class="form__input" name="address_line1" value="<?= htmlspecialchars($profile['address_line1']) ?>" placeholder="Calle, número, colonia">
          </div>

          <div class="form__group">
            <label class="form__label">Referencias</label>
            <input class="form__input" name="address_line2" value="<?= htmlspecialchars($profile['address_line2']) ?>" placeholder="Entre calles, color de casa, etc.">
          </div>

          <div class="form__row">
            <div class="form__group">
              <label class="form__label">Ciudad</label>
              <input class="form__input" name="city" value="<?= htmlspecialchars($profile['city']) ?>" placeholder="Tulancingo">
            </div>
            <div class="form__group">
              <label class="form__label">Estado</label>
              <input class="form__input" name="state" value="<?= htmlspecialchars($profile['state']) ?>" placeholder="Hidalgo">
            </div>
          </div>

          <div class="form__group">
            <label class="form__label">C.P.</label>
            <input class="form__input" name="postal_code" value="<?= htmlspecialchars($profile['postal_code']) ?>" placeholder="43600">
          </div>

          <div class="form__actions">
            <a class="btn btn--ghost" href="auth/logout.php">Cerrar sesión</a>
            <button class="btn btn--primary" type="submit" id="btnSave">Guardar</button>
          </div>

          <p id="perfilMsg" class="form__msg"></p>
        </form>
      </section>

      <aside class="panel panel--sticky">
        <div class="panel__head">
          <h2 class="panel__title">Tip rápido</h2>
          <p class="panel__hint">Lo que guardes aquí se precarga al pagar.</p>
        </div>

        <div class="kpi">
          <div class="badge">✅ Nombre en navbar</div>
          <div class="badge">✅ Dirección guardada</div>
          <div class="badge">✅ Checkout más rápido</div>
        </div>

        <div style="padding:16px;">
          <p style="margin:0 0 10px;">¿Quieres probarlo?</p>
          <a class="btn btn--primary" style="width:100%;text-align:center;" href="checkout.php">Ir al checkout</a>
        </div>
      </aside>

    </div>

  </div>
</main>

<footer class="footer">
  <div class="container footer__inner">
    <p>© <?= date("Y") ?> KALISS</p>
  </div>
</footer>

<script src="assets/js/menu.js"></script>
<script src="assets/js/perfil.js"></script>
</body>
</html>
