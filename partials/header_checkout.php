<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

$userName = $_SESSION['user_name'] ?? '';
$next = $_SERVER['REQUEST_URI'] ?? '/checkout.php';
?>

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

     <?php if (is_logged_in()): ?>
  <a class="nav__userChip" href="/perfil.php" title="Mi perfil">
    <span class="nav__userIcon" aria-hidden="true">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
        <path d="M20 21a8 8 0 0 0-16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M12 13a5 5 0 1 0-5-5 5 5 0 0 0 12 0Z" stroke="currentColor" stroke-width="2"/>
      </svg>
    </span>
    <span class="nav__userText">Hola, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></span>
  </a>
<?php else: ?>
  <a class="nav__cta" href="/auth/login.php?next=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>">
    ÚNETE A KALISS
  </a>
<?php endif; ?>

    </nav>
  </div>
</header>
