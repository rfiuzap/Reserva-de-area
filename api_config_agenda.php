<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$config = $pdo->query('SELECT hora_inicio, hora_fim FROM configuracoes WHERE id = 1')->fetch();
echo json_encode([
    'hora_inicio' => substr($config['hora_inicio'] ?? '08:00:00', 0, 5),
    'hora_fim' => substr($config['hora_fim'] ?? '20:00:00', 0, 5),
], JSON_UNESCAPED_UNICODE);
