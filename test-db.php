<?php
require_once __DIR__ . '/config/db.php';

$pdo = db();
$stmt = $pdo->query("SELECT NOW() AS server_time");
$row = $stmt->fetch();

echo "Conexión OK ✅ - Hora del servidor: " . $row['server_time'];
