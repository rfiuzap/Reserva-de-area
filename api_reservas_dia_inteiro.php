<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
if (!$ids) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$statement = $pdo->prepare("SELECT id, DATE(data_inicio) AS data FROM reservas WHERE status = 'ativa' AND dia_inteiro = 1 AND id IN ($placeholders)");
$statement->execute($ids);
echo json_encode($statement->fetchAll(), JSON_UNESCAPED_UNICODE);
