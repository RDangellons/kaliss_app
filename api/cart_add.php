<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

require_post_csrf();

$productId = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 1);
if ($productId <= 0) { echo json_encode(["ok"=>false,"error"=>"Producto inválido"]); exit; }
if ($qty < 1) $qty = 1;

$pdo = db();

// Validar producto activo + tienda activa
$sql = "
  SELECT p.id, p.stock
  FROM products p
  INNER JOIN shops s ON s.id = p.shop_id
  WHERE p.id = ? AND p.status='active' AND s.status='active'
  LIMIT 1
";
$st = $pdo->prepare($sql);
$st->execute([$productId]);
$row = $st->fetch();

if (!$row) { echo json_encode(["ok"=>false,"error"=>"Producto no disponible"]); exit; }

$stock = (int)$row['stock'];
$cart = cart_get();
$current = (int)($cart[$productId] ?? 0);
$newQty = $current + $qty;

// limitar a stock (si stock=0, lo dejamos en 0 y avisamos)
if ($stock > 0 && $newQty > $stock) $newQty = $stock;

$cart[$productId] = $newQty;
cart_set($cart);

echo json_encode(["ok"=>true, "count"=>cart_count(), "product_id"=>$productId, "qty"=>$newQty]);
