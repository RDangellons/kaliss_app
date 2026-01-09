<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_login_json();

require_post_csrf();

$pdo = db();
$cart = cart_get();
if (empty($cart)) {
  echo json_encode(["ok"=>false,"error"=>"El carrito está vacío."]);
  exit;
}

// Datos del cliente
$name  = trim((string)($_POST['customer_name'] ?? ''));
$phone = trim((string)($_POST['customer_phone'] ?? ''));
$email = trim((string)($_POST['customer_email'] ?? ''));
$a1    = trim((string)($_POST['address_line1'] ?? ''));
$a2    = trim((string)($_POST['address_line2'] ?? ''));
$city  = trim((string)($_POST['city'] ?? ''));
$state = trim((string)($_POST['state'] ?? ''));
$cp    = trim((string)($_POST['postal_code'] ?? ''));

if ($name === '' || $a1 === '') {
  echo json_encode(["ok"=>false,"error"=>"Nombre y Dirección son obligatorios."]);
  exit;
}

$ids = array_keys($cart);
$in = implode(',', array_fill(0, count($ids), '?'));

// Traer productos actuales (precio/stock) para calcular total seguro
$sql = "
  SELECT p.id, p.name, p.price, p.stock, p.shop_id
  FROM products p
  INNER JOIN shops s ON s.id = p.shop_id
  WHERE p.id IN ($in)
    AND p.status='active'
    AND s.status='active'
";
$st = $pdo->prepare($sql);
$st->execute($ids);
$rows = $st->fetchAll();

if (empty($rows)) {
  echo json_encode(["ok"=>false,"error"=>"No hay productos válidos en el carrito."]);
  exit;
}

$items = [];
$subtotal = 0.0;

foreach ($rows as $r) {
  $pid = (int)$r['id'];
  $qty = (int)($cart[$pid] ?? 0);
  if ($qty <= 0) continue;

  $stock = (int)$r['stock'];
  if ($stock > 0 && $qty > $stock) $qty = $stock;
  if ($qty <= 0) continue;

  $price = (float)$r['price'];
  $line = $price * $qty;
  $subtotal += $line;

  $items[] = [
    "product_id"=>$pid,
    "shop_id"=>(int)$r['shop_id'],
    "name"=>(string)$r['name'],
    "price"=>$price,
    "qty"=>$qty,
    "line"=>$line
  ];
}

if (empty($items)) {
  echo json_encode(["ok"=>false,"error"=>"Carrito inválido o sin stock."]);
  exit;
}

$shipping = 0.00;
$total = $subtotal + $shipping;

// Número de orden simple y único
$orderNumber = "KLS-" . date("Ymd") . "-" . strtoupper(substr(bin2hex(random_bytes(4)),0,8));

try {
  $pdo->beginTransaction();

  // Crear orden
  $sqlO = "
    INSERT INTO orders
    (order_number, user_id, customer_name, customer_phone, customer_email,
     address_line1, address_line2, city, state, postal_code,
     subtotal, shipping, total, status)
    VALUES
    (?, ?,  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
  ";

  $userId = (int)($_SESSION['user_id'] ?? 0);
  $stO = $pdo->prepare($sqlO);
  $stO->execute([
    $orderNumber, $userId, $name, $phone, $email,
    $a1, $a2, $city, $state, $cp,
    $subtotal, $shipping, $total
  ]);

  $orderId = (int)$pdo->lastInsertId();

  // Insertar items
  $sqlI = "
    INSERT INTO order_items
    (order_id, product_id, shop_id, product_name, unit_price, qty, line_total)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ";
  $stI = $pdo->prepare($sqlI);

  foreach ($items as $it) {
    $stI->execute([
      $orderId,
      $it['product_id'],
      $it['shop_id'],
      $it['name'],
      $it['price'],
      $it['qty'],
      $it['line']
    ]);
  }

  // Vaciar carrito
  cart_set([]);

  $pdo->commit();

  echo json_encode(["ok"=>true, "order_number"=>$orderNumber]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(["ok"=>false, "error"=>"No se pudo crear el pedido."]);
}
