<?php
declare(strict_types=1);
$order = trim((string)($_GET['order'] ?? ''));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pedido confirmado | KALISS</title>
  <link rel="stylesheet" href="assets/css/index.css" />
  <link rel="stylesheet" href="assets/css/perfil.css" />
</head>
<body>

<?php include __DIR__ . '/partials/header.php'; ?>

<main class="section">
  <div class="container" style="text-align:center;">
    <h1 class="page__title">¡Pedido confirmado! ✅</h1>
    <p class="lead">Gracias por comprar en KALISS.</p>

    <?php if ($order !== ''): ?>
      <p style="font-size:18px;">
        Tu número de pedido es: <strong><?= htmlspecialchars($order) ?></strong>
      </p>
    <?php endif; ?>

    <div style="margin-top:14px;">
      <a class="btn btn--primary" href="index.php#negocios">Seguir comprando</a>
      <a class="btn btn--ghost" href="carrito.php">Ver carrito</a>
    </div>
  </div>
</main>

<footer class="footer">
  <div class="container footer__inner">
    <p>© <?= date("Y") ?> KALISS</p>
  </div>
</footer>

</body>
</html>
