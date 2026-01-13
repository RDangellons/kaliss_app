<?php
declare(strict_types=1);

// api/mp/webhook.php
// Endpoint para recibir notificaciones de Mercado Pago (Checkout Pro)
// IMPORTANT: este endpoint NO lleva CSRF porque Mercado Pago lo llama desde fuera.

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/mercadopago.php';

header('Content-Type: application/json');

// 1) Obtener payment id desde distintas formas
$raw = file_get_contents('php://input');
$body = json_decode($raw ?: '', true);

$paymentId = '';

// webhooks nuevos suelen mandar {"type":"payment","data":{"id":"123"}}
if (is_array($body)) {
  $paymentId = (string)($body['data']['id'] ?? $body['id'] ?? '');
}

// IPN o algunos webhooks pueden traer query params
if ($paymentId === '') {
  $paymentId = (string)($_GET['data.id'] ?? $_GET['data_id'] ?? $_GET['id'] ?? '');
}

// A veces viene ?type=payment&id=123
if ($paymentId === '' && isset($_GET['type']) && $_GET['type'] === 'payment') {
  $paymentId = (string)($_GET['id'] ?? '');
}

if ($paymentId === '') {
  // Respondemos 200 para que MP no reintente sin parar.
  echo json_encode(["ok"=>true, "ignored"=>true]);
  exit;
}

// 2) Consultar el pago REAL a Mercado Pago (esto es lo que vuelve seguro el webhook)
$mp = mp_get_payment($paymentId);
if (!$mp['ok']) {
  // Respondemos 200 para evitar spam de reintentos.
  echo json_encode(["ok"=>true, "fetched"=>false]);
  exit;
}

$p = $mp['data'];
$ext = (string)($p['external_reference'] ?? '');
$status = (string)($p['status'] ?? '');
$amount = (float)($p['transaction_amount'] ?? 0);
$prefId = (string)($p['order']['id'] ?? $p['preference_id'] ?? '');

if ($ext === '') {
  echo json_encode(["ok"=>true, "no_external_reference"=>true]);
  exit;
}

$pdo = db();

try {
  // 3) Encontrar la orden por order_number (external_reference)
  $st = $pdo->prepare("SELECT id, total FROM orders WHERE order_number=? LIMIT 1");
  $st->execute([$ext]);
  $order = $st->fetch();

  if (!$order) {
    echo json_encode(["ok"=>true, "order_not_found"=>true]);
    exit;
  }

  $orderId = (int)$order['id'];
  $orderTotal = (float)$order['total'];

  // 4) Validaciones mínimas
  // Si el monto no coincide, no marcamos como pagado (pero sí registramos).
  $amountOk = (abs($orderTotal - $amount) < 0.01) || ($amount === 0.0);

  // 5) Mapear status de MP -> status interno
  $newOrderStatus = 'pending';
  if ($status === 'approved') $newOrderStatus = 'paid';
  elseif (in_array($status, ['rejected','cancelled','charged_back','refunded'], true)) $newOrderStatus = 'failed';
  elseif (in_array($status, ['in_process','pending'], true)) $newOrderStatus = 'pending';

  // Si el pago dice approved pero el monto no cuadra, lo dejamos pending para revisión
  if ($status === 'approved' && !$amountOk) {
    $newOrderStatus = 'pending';
  }

  $pdo->beginTransaction();

  // 6) Actualizar orden
  $paidAt = null;
  if ($newOrderStatus === 'paid') {
    $paidAt = date('Y-m-d H:i:s');
  }

  $pdo->prepare("UPDATE orders SET status=?, mp_payment_id=?, mp_status=?, paid_at=COALESCE(paid_at, ?) WHERE id=? LIMIT 1")
      ->execute([$newOrderStatus, (string)$paymentId, $status, $paidAt, $orderId]);

  // 7) Registrar en payments (guardamos respuesta cruda)
  $pdo->prepare("INSERT INTO payments (order_id, provider, mp_payment_id, mp_preference_id, status, amount, raw_json)
                 VALUES (?, 'mercadopago', ?, ?, ?, ?, ?)")
      ->execute([$orderId, (string)$paymentId, $prefId ?: null, $status, $amount, json_encode($p)]);

  $pdo->commit();

  echo json_encode(["ok"=>true, "order"=>$ext, "status"=>$status]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(["ok"=>true, "error"=>"db"]); // 200 para evitar reintentos agresivos
}
