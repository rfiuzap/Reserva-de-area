<?php
require_once __DIR__ . '/includes/config.php';

$config = $pdo->query('SELECT titulo_site, logo FROM configuracoes WHERE id = 1')->fetch();
$siteName = $config['titulo_site'] ?? 'Reserva de Áreas';
$logo = !empty($config['logo']) ? 'uploads/' . rawurlencode($config['logo']) : 'assets/logo.svg';
$icons = [];

if (!empty($config['logo'])) {
    $extension = strtolower(pathinfo($config['logo'], PATHINFO_EXTENSION));
    $mimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $mimeType = $mimeTypes[$extension] ?? 'image/png';
    $icons = [
        ['src' => $logo, 'sizes' => '192x192', 'type' => $mimeType, 'purpose' => 'any maskable'],
        ['src' => $logo, 'sizes' => '512x512', 'type' => $mimeType, 'purpose' => 'any maskable'],
    ];
} else {
    $icons = [
        ['src' => $logo, 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any maskable'],
    ];
}

header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
    'name' => $siteName,
    'short_name' => $siteName,
    'start_url' => 'index.php?visao=mes',
    'scope' => './',
    'display' => 'standalone',
    'background_color' => '#f4f6f9',
    'theme_color' => '#223764',
    'icons' => $icons,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
