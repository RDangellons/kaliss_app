<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

require_post_csrf();

$productId = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 1);
if ($productId <= 0) { echo json_encode(["ok"=>false,"error"=>"Producto inválido"]); exit; }
if ($qty < 0) $qty = 0;

$pdo = db();

// validar producto activo + stock
$sql = "
  SELECT p.id, p.stock
  FROM products p
  INNER JOIN shops s ON s.id = p.shop_id
  WHERE p.id=? AND p.status='active' AND s.status='active'
  LIMIT 1
";
$st = $pdo->prepare($sql);
$st->execute([$productId]);
$row = $st->fetch();
if (!$row) { echo json_encode(["ok"=>false,"error"=>"Producto no disponible"]); exit; }

$stock = (int)$row['stock'];
if ($stock > 0 && $qty > $stock) $qty = $stock;

$cart = cart_get();
if ($qty === 0) unset($cart[$productId]);
else $cart[$productId] = $qty;

cart_set($cart);

echo json_encode(["ok"=>true, "count"=>cart_count(), "product_id"=>$productId, "qty"=>$qty]);
