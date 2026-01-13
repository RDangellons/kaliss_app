<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/mercadopago.php';

header('Content-Type: application/json; charset=utf-8');

require_login_json();
require_post_csrf();

/**
 * DEBUG: si algo truena, que lo veas en JSON y no en HTML
 */
set_error_handler(function($severity, $message, $file, $line) {
  echo json_encode(["ok"=>false, "error"=>"PHP: $message", "file"=>$file, "line"=>$line]);
  exit;
});

$pdo = db();
$userId = (int)$_SESSION['user_id'];

// ====== TU CARRITO (ajusta a tu estructura real) ======
$cartItems = $_SESSION['cart'] ?? [];
if (!$cartItems) {
  echo json_encode(["ok"=>false, "error"=>"Carrito vacío"]);
  exit;
}

$orderNumber = 'KLS-' . date('YmdHis') . '-' . $userId;

$total = 0.0;
foreach ($cartItems as $ci) {
  $qty = (int)($ci['quantity'] ?? 1);
  $price = (float)($ci['unit_price'] ?? 0);
  $total += $qty * $price;
}

// crea orden (ajusta columnas si difieren)
$pdo->prepare("INSERT INTO orders (user_id, order_number, total, status, created_at)
               VALUES (?, ?, ?, 'created', NOW())")
    ->execute([$userId, $orderNumber, $total]);
$orderId = (int)$pdo->lastInsertId();

// ====== Crear preferencia MP ======
$base = site_base_url();

// ====== CARRITO NORMALIZADO A product_id + quantity ======
$cartRaw = $_SESSION['cart'] ?? [];
$cart = [];

if (is_array($cartRaw)) {
  $isAssoc = array_keys($cartRaw) !== range(0, count($cartRaw) - 1);

  if ($isAssoc) {
    // formato: [id => qty]
    foreach ($cartRaw as $pid => $qty) {
      $pid = (int)$pid;
      $qty = (int)$qty;
      if ($pid > 0) $cart[] = ['product_id'=>$pid, 'quantity'=> max(1,$qty)];
    }
  } else {
    // formato: [id, id, id] o [ ['id'=>..,'quantity'=>..], ... ]
    foreach ($cartRaw as $ci) {
      if (is_array($ci)) {
        $pid = (int)($ci['product_id'] ?? $ci['id'] ?? 0);
        $qty = (int)($ci['quantity'] ?? $ci['qty'] ?? 1);
        if ($pid > 0) $cart[] = ['product_id'=>$pid, 'quantity'=> max(1,$qty)];
      } else {
        $pid = (int)$ci;
        if ($pid > 0) $cart[] = ['product_id'=>$pid, 'quantity'=>1];
      }
    }
  }
}

if (!$cart) {
  echo json_encode(["ok"=>false,"error"=>"Carrito vacío o formato no soportado","debug_cart"=>$cartRaw]);
  exit;
}

// ====== CONSULTAR PRODUCTOS EN BD (precio DECIMAL) ======
$ids = array_values(array_unique(array_map(fn($x)=> (int)$x['product_id'], $cart)));
$placeholders = implode(',', array_fill(0, count($ids), '?'));

/**
 * 🔥 AJUSTA ESTO A TU TABLA/COLUMNAS:
 * Si tu tabla es "productos" y columnas "nombre" y "precio", cámbialo:
 * SELECT id, nombre AS name, precio AS price FROM productos ...
 */
$stP = $pdo->prepare("
  SELECT id, name, price
  FROM products
  WHERE id IN ($placeholders)
");
$stP->execute($ids);

$rows = $stP->fetchAll(PDO::FETCH_ASSOC);
$byId = [];
foreach ($rows as $r) $byId[(int)$r['id']] = $r;

// ====== ARMAR ITEMS PARA MERCADO PAGO ======
$mpItems = [];

foreach ($cart as $ci) {
  $pid = (int)$ci['product_id'];
  $qty = (int)$ci['quantity'];

  if (!isset($byId[$pid])) {
    echo json_encode(["ok"=>false,"error"=>"Producto no encontrado en BD","product_id"=>$pid]);
    exit;
  }

  $title = trim((string)$byId[$pid]['name']);

  // DECIMAL viene como string tipo "120.00". Eso es correcto.
  $priceStr = (string)$byId[$pid]['price'];

  // Convertimos DECIMAL string a float seguro
  $price = (float) str_replace(',', '.', $priceStr); // por si acaso alguna config lo trae raro
  $price = round($price, 2);

  if ($price <= 0) {
    echo json_encode(["ok"=>false,"error"=>"Precio inválido en BD","product_id"=>$pid,"price"=>$priceStr]);
    exit;
  }

  if ($qty < 1) $qty = 1;

  $mpItems[] = [
    "title" => ($title !== '' ? $title : 'Producto'),
    "quantity" => $qty,
    "unit_price" => $price,
    "currency_id" => "MXN"
  ];
}


$payload = [
  "items" => $mpItems,
  "external_reference" => $orderNumber,
  "back_urls" => [
    "success" => $base . "/pago_success.php?order=" . urlencode($orderNumber),
    "pending" => $base . "/pago_pending.php?order=" . urlencode($orderNumber),
    "failure" => $base . "/pago_failure.php?order=" . urlencode($orderNumber),
  ],
  "auto_return" => "approved",
  "notification_url" => $base . "/api/mp/webhook.php"
];

$mp = mp_create_preference($payload);
if (!$mp['ok']) {
  echo json_encode(["ok"=>false, "error"=>"No se pudo crear preferencia", "mp"=>$mp]);
  exit;
}

$pref = $mp['data'];
$prefId = (string)($pref['id'] ?? '');

$payUrl = (string)($pref['init_point'] ?? '');


if ($payUrl === '') {
  echo json_encode(["ok"=>false, "error"=>"MP no devolvió pay_url", "pref"=>$pref]);
  exit;
}

$pdo->prepare("UPDATE orders SET mp_preference_id=?, mp_status=? WHERE id=? LIMIT 1")
    ->execute([$prefId, 'preference_created', $orderId]);

echo json_encode([
  "ok" => true,
  "order_number" => $orderNumber,
  "order_id" => $orderId,
  "pay_url" => $payUrl
]);
