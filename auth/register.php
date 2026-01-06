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
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_post_csrf();

  $name = trim((string)($_POST['name'] ?? ''));
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $pass1 = (string)($_POST['password'] ?? '');
  $pass2 = (string)($_POST['password2'] ?? '');

  if ($name === '' || $email === '' || $pass1 === '') {
    $error = "Completa los campos obligatorios.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email inválido.";
  } elseif (strlen($pass1) < 6) {
    $error = "La contraseña debe tener al menos 6 caracteres.";
  } elseif ($pass1 !== $pass2) {
    $error = "Las contraseñas no coinciden.";
  } else {
    $hash = password_hash($pass1, PASSWORD_DEFAULT);

    try {
      $st = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, status) VALUES (?,?,?,?, 'active')");
      $st->execute([$name, $email, $phone !== '' ? $phone : null, $hash]);

      $id = (int)$pdo->lastInsertId();
      login_user(["id"=>$id, "name"=>$name, "email"=>$email]);

      header("Location: " . $next);
      exit;
    } catch (PDOException $e) {
      // 23000 = duplicate key
      if ((string)$e->getCode() === '23000') {
        $error = "Ese email ya está registrado. Inicia sesión.";
      } else {
        $error = "No se pudo crear la cuenta.";
      }
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crear cuenta | KALISS</title>
  <link rel="stylesheet" href="../assets/css/index.css" />
  <link rel="stylesheet" href="../assets/css/checkout.css" />
</head>
<body>

<header class="topbar">
  <div class="container topbar__inner">
    <a class="brand" href="../index.php">
      <img src="../assets/img/logo.jpeg" alt="Logo" height="50">
    </a>
    <button class="hamburger" id="btnMenu" aria-label="Abrir menú">
      <span></span><span></span><span></span>
    </button>
    <nav class="nav" id="nav">
      <a href="../index.php" class="nav__link">Inicio</a>
      <a href="../index.php#negocios" class="nav__link">Descubrir Marcas</a>
      <a href="../carrito.php" class="nav__link">Carrito</a>
      <a href="../auth/register.php" class="nav__link is-active">Cuenta</a>
      <a href="/index.php#unete" class="nav__cta">ÚNETE A KALISS</a>
    </nav>
  </div>
</header>

<main class="section checkout">
  <div class="container">

    <div class="checkout__top">
      <div>
        <h1 class="checkout__title">Crear cuenta</h1>
        <p class="checkout__sub">Te toma menos de un minuto.</p>
      </div>
    </div>

    <div class="checkout__grid" style="grid-template-columns: 1fr .8fr;">
      <section class="panel">
        <div class="panel__head">
          <h2 class="panel__title">Registro</h2>
          <p class="panel__hint">Al crear cuenta, podrás continuar al pago.</p>
        </div>

        <form method="POST" class="form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

          <div class="form__group">
            <label class="form__label">Nombre *</label>
            <input class="form__input" name="name" required value="<?= htmlspecialchars($name) ?>" placeholder="Maria juanita">
          </div>

          <div class="form__group">
            <label class="form__label">Email *</label>
            <input class="form__input" type="email" name="email" required value="<?= htmlspecialchars($email) ?>" placeholder="correo@ejemplo.com">
          </div>

          <div class="form__group">
            <label class="form__label">Teléfono</label>
            <input class="form__input" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="771 000 0000">
          </div>

          <div class="form__row">
            <div class="form__group">
              <label class="form__label">Contraseña *</label>
              <input class="form__input" type="password" name="password" required placeholder="Mínimo 6 caracteres">
            </div>
            <div class="form__group">
              <label class="form__label">Confirmar *</label>
              <input class="form__input" type="password" name="password2" required placeholder="Repite la contraseña">
            </div>
          </div>

          <?php if ($error): ?>
            <p class="form__msg" style="color:#7b1d1d; opacity:1;"><?= htmlspecialchars($error) ?></p>
          <?php endif; ?>

          <div class="form__actions">
            <a class="btn btn--ghost" href="/auth/login.php?next=<?= urlencode($next) ?>">Ya tengo cuenta</a>
            <button class="btn btn--primary" type="submit">Crear cuenta</button>
          </div>
        </form>
      </section>

      <aside class="panel panel--sticky">
        <div class="panel__head">
          <h2 class="panel__title">¿Ya tienes cuenta?</h2>
          <p class="panel__hint">Inicia sesión y continúa.</p>
        </div>
        <div style="padding:16px;">
          <a class="btn btn--primary" style="width:100%;text-align:center;" href="/auth/login.php?next=<?= urlencode($next) ?>">
            Iniciar sesión
          </a>
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
