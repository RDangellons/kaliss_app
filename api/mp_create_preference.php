<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/payments.php';

require_login_json();
require_post_csrf();

$token = trim((string)mp_access_token());
if ($token === '') {
  echo json_encode(["ok"=>false,"error"=>"Falta configurar MP_ACCESS_TOKEN (o token TEST en config/payments.php)."]);
  exit;
}

$pdo = db();
$cart = cart_get(); // debe regresar array: [product_id => qty]
if (empty($cart)) {
  echo json_encode(["ok"=>false,"error"=>"El carrito está vacío."]);
  exit;
}

/* =========================
   Normaliza datos del cliente
   ========================= */
$name  = trim((string)($_POST['customer_name'] ?? $_POST['nombre'] ?? ''));
$phone = trim((string)($_POST['customer_phone'] ?? ''));
$email = trim((string)($_POST['customer_email'] ?? $_POST['email'] ?? ''));
$a1    = trim((string)($_POST['address_line1'] ?? ''));
$a2    = trim((string)($_POST['address_line2'] ?? ''));
$city  = trim((string)($_POST['city'] ?? ''));
$state = trim((string)($_POST['state'] ?? ''));
$cp    = trim((string)($_POST['postal_code'] ?? ''));

if ($name === '' || $a1 === '') {
  echo json_encode(["ok"=>false,"error"=>"Nombre y Dirección (línea 1) son obligatorios."]);
  exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

/* =========================
   Traer productos de BD
   ========================= */
$ids = array_keys($cart);
$in  = implode(',', array_fill(0, count($ids), '?'));

$sql = "
  SELECT p.id, p.name, p.price, p.stock, p.shop_id, p.status,
         s.status AS shop_status
  FROM products p
  INNER JOIN shops s ON s.id = p.shop_id
  WHERE p.id IN ($in)
";
$st = $pdo->prepare($sql);
$st->execute($ids);
$rows = $st->fetchAll();

if (!$rows) {
  echo json_encode(["ok"=>false,"error"=>"No se encontraron productos para pagar."]);
  exit;
}

$itemsDb  = [];
$subtotal = 0.0;

foreach ($rows as $r) {
  // validar producto/tienda activos
  if (($r['status'] ?? '') !== 'active') continue;
  if (($r['shop_status'] ?? '') !== 'active') continue;

  $pid = (int)$r['id'];
  $qty = (int)($cart[$pid] ?? 0);
  if ($qty <= 0) continue;

  $stock = (int)($r['stock'] ?? 0);
  if ($stock > 0 && $qty > $stock) $qty = $stock; // recorta por stock
  if ($qty <= 0) continue;

  $price = (float)$r['price'];
  $line  = $price * $qty;

  $subtotal += $line;

  $itemsDb[] = [
    "product_id" => $pid,
    "shop_id"    => (int)$r['shop_id'],
    "name"       => (string)$r['name'],
    "price"      => $price,
    "qty"        => $qty,
    "line"       => $line,
  ];
}

if (!$itemsDb) {
  echo json_encode(["ok"=>false,"error"=>"Carrito inválido o productos inactivos/sin stock."]);
  exit;
}

/* =========================
   Totales (ajusta si ya tienes envío)
   ========================= */
$shipping = 0.00;
$total    = $subtotal + $shipping;

$orderNumber = "KLS-" . date("Ymd") . "-" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

/* =========================
   Crear orden + preferencia MP
   ========================= */
try {
  $pdo->beginTransaction();

  // 1) Orders
  $sqlO = "
    INSERT INTO orders
    (order_number, user_id, customer_name, customer_phone, customer_email,
     address_line1, address_line2, city, state, postal_code,
     subtotal, shipping, total, status)
    VALUES
    (?, ?,  ?, ?, ?,  ?, ?, ?, ?, ?,  ?, ?, ?, 'pending')
  ";
  $stO = $pdo->prepare($sqlO);
  $stO->execute([
    $orderNumber, $userId,
    $name, $phone, $email,
    $a1, $a2, $city, $state, $cp,
    $subtotal, $shipping, $total
  ]);

  $orderId = (int)$pdo->lastInsertId();

  // 2) Order items
  $sqlI = "
    INSERT INTO order_items
    (order_id, product_id, shop_id, product_name, unit_price, qty, line_total)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ";
  $stI = $pdo->prepare($sqlI);

  foreach ($itemsDb as $it) {
    $stI->execute([
      $orderId,
      $it['product_id'],
      $it['shop_id'],
      $it['name'],
      $it['price'],
      $it['qty'],
      $it['line'],
    ]);
  }

  // 3) Crear preferencia MP
  $base = rtrim((string)base_url(), '/');

  // payer sin nulos
  $payer = ["name" => $name];
  if ($email !== '') $payer["email"] = $email;
  if ($phone !== '') $payer["phone"] = ["number" => $phone];

  $payload = [
    "external_reference" => (string)$orderNumber,
    "payer" => $payer,
    "items" => array_map(fn($it) => [
      "title"       => $it["name"],
      "quantity"    => (int)$it["qty"],
      "unit_price"  => (float)$it["price"],
      "currency_id" => "MXN",
    ], $itemsDb),
    "back_urls" => [
      "success" => $base . "/mp_return.php?order=" . urlencode($orderNumber),
      "failure" => $base . "/mp_return.php?order=" . urlencode($orderNumber),
      "pending" => $base . "/mp_return.php?order=" . urlencode($orderNumber),
    ],
    "auto_return" => "approved",
    "notification_url" => $base . "/api/mp_webhook.php",
  ];

  $ch = curl_init("https://api.mercadopago.com/checkout/preferences");
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
      "Authorization: Bearer " . $token,
      "Content-Type: application/json",
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
  ]);

  $resp = curl_exec($ch);
  $curlErr = curl_error($ch);
  $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($resp === false || $http < 200 || $http >= 300) {
    $pdo->rollBack();
    echo json_encode([
      "ok" => false,
      "error" => "MP_ERROR",
      "http" => $http,
      "curl_error" => $curlErr,
      "resp" => $resp,
    ]);
    exit;
  }

  $mp = json_decode($resp, true);

  $prefId = $mp["id"] ?? null;
  $initPoint = $mp["init_point"] ?? null;
  $sandboxInitPoint = $mp["sandbox_init_point"] ?? null;

  // ✅ TEST -> sandbox_init_point, PROD -> init_point
  $isTest = str_starts_with($token, "TEST-");
  $redirect = $isTest ? ($sandboxInitPoint ?: $initPoint) : ($initPoint ?: $sandboxInitPoint);

  if (!$prefId || !$redirect) {
    $pdo->rollBack();
    echo json_encode(["ok"=>false,"error"=>"MP no devolvió preference/redirect válidos.","mp"=>$mp]);
    exit;
  }

  // 4) Guardar payment
  $stP = $pdo->prepare("
    INSERT INTO payments (order_id, provider, preference_id, status, raw_json)
    VALUES (?, 'mercadopago', ?, 'created', ?)
  ");
  $stP->execute([$orderId, (string)$prefId, $resp]);

  // 5) NO vaciar carrito aquí. Mejor en webhook cuando approved.
  // cart_set([]);

  $pdo->commit();

echo json_encode([
  "ok" => true,
  "order_number" => $orderNumber,
  "preference_id" => (string)$prefId
]);



} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(["ok"=>false,"error"=>"No se pudo crear el pedido/pago."]);
  exit;
}
