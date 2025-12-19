<?php
// config/db.php
// Conexión PDO (MySQL) compatible con Hostinger y XAMPP

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    // ====== CONFIG ======
    // En XAMPP normalmente:
    //   host=localhost, db=kaliss, user=root, pass=""
    //
    // En Hostinger te dan estos datos en:
    //   Panel -> Bases de datos MySQL -> Gestión -> detalles
  /*
    $DB_HOST = 'localhost';
    $DB_NAME = 'kaliss';
    $DB_USER = 'root';
    $DB_PASS = '';

    */

//Conexion de base de datos del hostinguer
    
    $DB_HOST = 'localhost';
    $DB_NAME = 'u653652676_kaliss';
    $DB_USER = 'u653652676_ARD2010207user';
    $DB_PASS = 'Angelalonso21';

    // Si estás en Hostinger, cambia DB_USER y DB_PASS por los reales.

    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // No mostrar credenciales ni detalles sensibles al público
        http_response_code(500);
        echo "Error de conexión a la base de datos.";
        exit;
    }
}
