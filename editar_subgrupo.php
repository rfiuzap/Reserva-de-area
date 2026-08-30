<?php
declare(strict_types=1);

$pageTitle = 'Editar subgrupo';
require_once __DIR__ . '/includes/header.php';

$subgroupId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$statement = $pdo->prepare('SELECT s.*, g.nome AS grupo_nome FROM subgrupos s JOIN grupos g ON g.id = s.grupo_id WHERE s.id = ?');
$statement->execute([$subgroupId]);
$subgroup = $statement->fetch();

if (!$subgroup) {
    flash('error', 'Subgrupo não encontrado.');
    redirect('grupos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['nome'] ?? '');
    $status = ($_POST['status'] ?? '') === 'oculto' ? 'oculto' : 'ativo';

    if ($name === '') {
        flash('error', 'Informe o nome do subgrupo.');
    } else {
        $update = $pdo->prepare('UPDATE subgrupos SET nome = ?, status = ? WHERE id = ?');
        $update->execute([$name, $status, $subgroupId]);
        flash('success', 'Subgrupo atualizado.');
        redirect('grupos.php?editar_grupo=' . $subgroup['grupo_id']);
    }
}
?><div class="row justify-content-center"><div class="col-md-7"><section class="app-card"><h1 class="h3 mb-1">Editar subgrupo</h1><p class="text-muted mb-4">Grupo: <?= e($subgroup['grupo_nome']) ?></p><form method="post"><input type="hidden" name="id" value="<?= $subgroupId ?>"><div class="mb-3"><label class="form-label">Nome do subgrupo</label><input class="form-control" name="nome" required autofocus value="<?= e($subgroup['nome']) ?>"></div><div class="mb-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="ativo" <?= $subgroup['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option><option value="oculto" <?= $subgroup['status'] === 'oculto' ? 'selected' : '' ?>>Oculto</option></select></div><button class="btn btn-gold">Salvar Alterações</button><a class="btn btn-outline-secondary" href="grupos.php?editar_grupo=<?= $subgroup['grupo_id'] ?>">Cancelar</a></form></section></div></div><?php require __DIR__ . '/includes/footer.php';
