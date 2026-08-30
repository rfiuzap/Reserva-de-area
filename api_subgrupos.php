<?php
require_once __DIR__ . '/includes/config.php'; require_once __DIR__ . '/includes/functions.php'; require_login();
header('Content-Type: application/json; charset=utf-8');
$groupId=(int)($_GET['grupo_id']??0); $s=$pdo->prepare("SELECT id,nome FROM subgrupos WHERE grupo_id=? AND status='ativo' ORDER BY nome"); $s->execute([$groupId]); echo json_encode($s->fetchAll());
