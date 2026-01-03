<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_post_csrf();

cart_set([]);
echo json_encode(["ok"=>true, "count"=>0]);
