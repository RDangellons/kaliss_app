<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';

$order = trim((string)($_GET['order'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$pdo = db();

if ($order === '') {
  header("Location: index.php");
  exit;
}

$st = $pdo->prepare("SELECT status FROM orders WHERE order_number=? LIMIT 1");
$st->execute([$order]);
$row = $st->fetch();

$current = $row['status'] ?? 'pending';

// Si el webhook ya lo marcó pagado → manda a gracias
if ($current === 'paid') {
  header("Location: gracias.php?order=" . urlencode($order));
  exit;
}

// Si no está pagado:
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Estado del pago | KALISS</title>
  <link rel="stylesheet" href="assets/css/index.css" />
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="section">
  <div class="container" style="text-align:center;">
    <h1 class="page__title">Estado del pago</h1>
    <p>Pedido: <strong><?= htmlspecialchars($order) ?></strong></p>
    <p>Estado actual: <strong><?= htmlspecialchars($current) ?></strong></p>

    <p style="margin-top:12px;color:#666;">
      Si acabas de pagar, espera unos segundos y recarga esta página.
    </p>

    <div style="margin-top:14px;">
      <a class="btn btn--primary" href="mp_return.php?status=<?= urlencode($status) ?>&order=<?= urlencode($order) ?>">Actualizar</a>
      <a class="btn btn--ghost" href="index.php#negocios">Volver</a>
    </div>
  </div>
</main>
</body>
</html>
