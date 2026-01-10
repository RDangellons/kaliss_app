<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';

$token = mp_access_token();
$pdo = db();

// MP puede mandar parámetros por query: ?type=payment&data.id=123
$type = $_GET['type'] ?? '';
$paymentId = $_GET['data_id'] ?? ($_GET['data']['id'] ?? ($_GET['data.id'] ?? ''));

// Algunos envían JSON en body (por si acaso)
$raw = file_get_contents('php://input');
if (!$paymentId && $raw) {
  $j = json_decode($raw, true);
  $paymentId = $j['data']['id'] ?? $j['data_id'] ?? '';
  $type = $j['type'] ?? $type;
}

if (!$paymentId) {
  http_response_code(200);
  echo "ok";
  exit;
}

// Consultar pago a MP
$ch = curl_init("https://api.mercadopago.com/v1/payments/" . urlencode((string)$paymentId));
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $token],
]);
$resp = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false || $http < 200 || $http >= 300) {
  http_response_code(200);
  echo "ok";
  exit;
}

$p = json_decode($resp, true);
$status = $p['status'] ?? 'unknown';
$extRef = $p['external_reference'] ?? ''; // order_number

if ($extRef !== '') {
  // Actualiza orden por order_number
  if ($status === 'approved') {
    $pdo->prepare("UPDATE orders SET status='paid' WHERE order_number=?")->execute([$extRef]);
  } elseif (in_array($status, ['rejected','cancelled'], true)) {
    $pdo->prepare("UPDATE orders SET status='cancelled' WHERE order_number=?")->execute([$extRef]);
  }

  // Guarda payment status
  $pdo->prepare("
    UPDATE payments p
    INNER JOIN orders o ON o.id = p.order_id
    SET p.payment_id=?, p.status=?, p.raw_json=?
    WHERE o.order_number=? AND p.provider='mercadopago'
    ORDER BY p.id DESC
    LIMIT 1
  ")->execute([(string)$paymentId, (string)$status, $resp, (string)$extRef]);
}

http_response_code(200);
echo "ok";
