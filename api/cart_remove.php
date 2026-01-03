<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_post_csrf();

$productId = (int)($_POST['product_id'] ?? 0);

$cart = cart_get();
unset($cart[$productId]);
cart_set($cart);

echo json_encode(["ok"=>true, "count"=>cart_count()]);
