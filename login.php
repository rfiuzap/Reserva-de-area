<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
if (!empty($_SESSION['usuario_id'])) redirect('index.php');
$siteConfig = $pdo->query('SELECT titulo_site, logo FROM configuracoes WHERE id = 1')->fetch();
$siteName = $siteConfig['titulo_site'] ?? 'Reserva de Áreas';
$favicon = !empty($siteConfig['logo']) ? 'uploads/' . rawurlencode($siteConfig['logo']) : 'assets/logo.svg';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (APP_ENV !== 'demo' && trim($_POST['usuario'] ?? '') === 'demo') {
        $error = 'O usuário de demonstração só está disponível no ambiente demo.';
    }
    $statement = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? LIMIT 1');
    $statement->execute([trim($_POST['usuario'] ?? '')]);
    $user = $statement->fetch();
    if (!$error && $user && $user['status'] === 'oculto') { $error = 'Este usuário está desativado.'; }
    elseif (!$error && $user && password_verify($_POST['senha'] ?? '', $user['senha_hash'])) { session_regenerate_id(true); $_SESSION['usuario_id'] = $user['id']; $_SESSION['usuario_nome'] = $user['nome']; $_SESSION['usuario_login'] = $user['usuario']; $_SESSION['usuario_tipo'] = $user['tipo'] ?? 'normal'; redirect('index.php'); }
    elseif (!$error) { $error = 'Usuário ou senha inválidos.'; }
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#223764"><link rel="icon" href="<?= e($favicon) ?>"><link rel="apple-touch-icon" href="<?= e($favicon) ?>"><link rel="manifest" href="manifest.php"><title>Acessar | <?= e($siteName) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head><body class="d-flex align-items-center min-vh-100"><div class="container"><div class="row justify-content-center"><div class="col-md-5 col-lg-4"><div class="app-card"><h1 class="h3 text-center mb-1">Reserva de Áreas</h1><p class="text-muted text-center mb-4">Acesse a agenda institucional</p><?php if (APP_ENV === 'demo'): ?><div class="alert alert-warning"><strong>Acesso de demonstração:</strong> demo / demo123</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><div class="mb-3"><label class="form-label">Usuário</label><input class="form-control" name="usuario" required autofocus></div><div class="mb-4"><label class="form-label">Senha</label><input class="form-control" type="password" name="senha" required></div><button class="btn btn-gold w-100" type="submit">Entrar</button></form></div></div></div></div><script>if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js');</script></body></html>
