<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$pdo = db();
$next = trim((string)($_GET['next'] ?? ($_POST['next'] ?? '../checkout.php')));

// evita redirects externos
if (str_contains($next, '://') || str_starts_with($next, '//')) {
  $next = '../index.php';
}

// si te mandan "checkout.php", lo convierto a "../checkout.php"
if ($next !== '' && !str_starts_with($next, '/') && !str_starts_with($next, '../')) {
  $next = '../' . $next;
}

// si viene vacío, default
if ($next === '') $next = '../checkout.php';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_post_csrf();

  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $password = (string)($_POST['password'] ?? '');

  if ($email === '' || $password === '') {
    $error = "Ingresa email y contraseña.";
  } else {
    $st = $pdo->prepare("SELECT id, name, email, password_hash, status FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $u = $st->fetch();

    if (!$u || !password_verify($password, (string)$u['password_hash'])) {
      $error = "Credenciales incorrectas.";
    } elseif ($u['status'] !== 'active') {
      $error = "Tu cuenta está bloqueada.";
    } else {
      login_user($u);
      header("Location: " . $next);
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Iniciar sesión | KALISS</title>
  <link rel="stylesheet" href="../assets/css/index.css" />
  <link rel="stylesheet" href="../assets/css/checkout.css" />
</head>
<body>

<header class="topbar">
  <div class="container topbar__inner">
    <a class="brand" href="/index.php">
      <img src="../assets/img/logo.jpeg" alt="Logo" height="50">
    </a>
    <button class="hamburger" id="btnMenu" aria-label="Abrir menú">
      <span></span><span></span><span></span>
    </button>
    <nav class="nav" id="nav">
      <a href="../index.php" class="nav__link">Inicio</a>
      <a href="/..index.php#negocios" class="nav__link">Descubrir Marcas</a>
      <a href="/carrito.php" class="nav__link">Carrito</a>
      <a href="/auth/login.php" class="nav__link is-active">Cuenta</a>
      <a href="/index.php#unete" class="nav__cta">ÚNETE A KALISS</a>
    </nav>
  </div>
</header>

<main class="section checkout">
  <div class="container">

    <div class="checkout__top">
      <div>
        <h1 class="checkout__title">Iniciar sesión</h1>
        <p class="checkout__sub">Para pagar, necesitas una cuenta.</p>
      </div>
    </div>

    <div class="checkout__grid" style="grid-template-columns: 1fr .8fr;">
      <section class="panel">
        <div class="panel__head">
          <h2 class="panel__title">Accede a tu cuenta</h2>
          <p class="panel__hint">Si no tienes cuenta, créala en 30 segundos.</p>
        </div>

        <form method="POST" class="form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

          <div class="form__group">
            <label class="form__label">Email</label>
            <input class="form__input" type="email" name="email" required value="<?= htmlspecialchars($email) ?>" placeholder="correo@ejemplo.com">
          </div>

          <div class="form__group">
            <label class="form__label">Contraseña</label>
            <input class="form__input" type="password" name="password" required placeholder="••••••••">
          </div>

          <?php if ($error): ?>
            <p class="form__msg" style="color:#7b1d1d; opacity:1;"><?= htmlspecialchars($error) ?></p>
          <?php endif; ?>

          <div class="form__actions">
            <a class="btn btn--ghost" href="/carrito.php">Volver</a>
            <button class="btn btn--primary" type="submit">Entrar</button>
          </div>
        </form>
      </section>

      <aside class="panel panel--sticky">
        <div class="panel__head">
          <h2 class="panel__title">¿No tienes cuenta?</h2>
          <p class="panel__hint">Regístrate y continúa al pago.</p>
        </div>
        <div style="padding:16px;">
          <a class="btn btn--primary" style="width:100%;text-align:center;" href="register.php?next=<?= urlencode($next) ?>">
            Crear cuenta
          </a>
          <p style="margin-top:10px;font-size:12px;opacity:.85;">
            Con tu cuenta podrás ver historial de pedidos y guardar tus datos.
          </p>
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

<script src="../assets/js/menu.js"></script>
</body>
</html>
