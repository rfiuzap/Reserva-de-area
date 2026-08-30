<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$groupId = (int) ($_GET['grupo_id'] ?? 0);
$areaId = (int) ($_GET['area_id'] ?? 0);

$group = null;
$area = null;

if ($groupId) {
    $statement = $pdo->prepare("SELECT tempo_padrao FROM grupos WHERE id = ? AND status = 'ativo'");
    $statement->execute([$groupId]);
    $group = $statement->fetch();
}

if ($areaId) {
    $statement = $pdo->prepare("SELECT nome, imagem FROM areas WHERE id = ? AND status = 'ativo'");
    $statement->execute([$areaId]);
    $area = $statement->fetch();
}

echo json_encode(['grupo' => $group, 'area' => $area], JSON_UNESCAPED_UNICODE);
