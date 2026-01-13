<?php
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$orderNo = trim((string)($_GET['order'] ?? ''));
$pdo = db();
$order = null;
if ($orderNo !== '') {
  $st = $pdo->prepare("SELECT order_number, status, total, mp_status FROM orders WHERE order_number=? LIMIT 1");
  $st->execute([$orderNo]);
  $order = $st->fetch();
}

?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pago pendiente | KALISS</title>
  <link rel="stylesheet" href="assets/css/index.css" />
  <link rel="stylesheet" href="assets/css/checkout.css" />
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="section">
  <div class="container">
    <h1 class="checkout__title">Pago pendiente</h1>
    <p class="checkout__sub">Tu pago quedó en revisión o pendiente. En cuanto Mercado Pago lo confirme, se actualizará tu orden automáticamente.</p>

    <?php if ($order): ?>
      <div class="panel" style="margin-top:16px;">
        <div class="panel__head">
          <h2 class="panel__title">Orden <?= htmlspecialchars($order['order_number']) ?></h2>
          <p class="panel__hint">Total: $<?= number_format((float)$order['total'],2) ?></p>
        </div>
        <div style="padding:16px;">
          <p><strong>Estatus actual:</strong> <?= htmlspecialchars((string)$order['status']) ?></p>
          <p><strong>MP status:</strong> <?= htmlspecialchars((string)($order['mp_status'] ?? '')) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <div style="margin-top:16px;">
      <a class="btn btn--ghost" href="index.php">Seguir comprando</a>
    </div>
  </div>
</main>

<script src="assets/js/menu.js"></script>
</body>
</html>
