<?php
declare(strict_types=1);

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Copy this file to config.php and fill in your real database credentials.
const DB_HOST = 'localhost';
const DB_NAME = 'nome_do_banco';
const DB_USER = 'usuario_do_banco';
const DB_PASS = 'senha_do_banco';
// Valores aceitos: local, producao ou demo.
define('APP_ENV', getenv('RESERVAS_ENV') ?: 'local');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    exit('Não foi possível conectar ao banco de dados: ' . $exception->getMessage());
}

require_once __DIR__ . '/demo_environment.php';
demo_environment_cleanup($pdo);
