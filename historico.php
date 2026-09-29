<?php
declare(strict_types=1);

$pageTitle = 'Histórico de alterações';
require_once __DIR__ . '/includes/header.php';

if (!can_view_history()) {
    flash('error', 'Acesso restrito ao administrador.');
    redirect('index.php');
}

$perPage = 30;
$page = max(1, (int) ($_GET['pagina'] ?? 1));
$entity = array_key_exists($_GET['tipo'] ?? '', HISTORY_ENTITIES) ? $_GET['tipo'] : '';
$author = trim((string) ($_GET['autor'] ?? ''));

$conditions = ['1 = 1'];
$params = [];
if ($entity !== '') { $conditions[] = 'entidade = ?'; $params[] = $entity; }
if ($author !== '') { $conditions[] = 'usuario_nome = ?'; $params[] = $author; }
$where = implode(' AND ', $conditions);

$entries = [];
$total = 0;
$authors = [];
try {
    $count = $pdo->prepare("SELECT COUNT(*) FROM historico_alteracoes WHERE $where");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $list = $pdo->prepare("SELECT * FROM historico_alteracoes WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
    $list->execute($params);
    $entries = $list->fetchAll();

    $authors = $pdo->query('SELECT DISTINCT usuario_nome FROM historico_alteracoes ORDER BY usuario_nome')->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    if ($e->getCode() !== '42S02') throw $e; // table is created on the first recorded change
}

$pages = max(1, (int) ceil($total / $perPage));
$link = static fn (array $changes): string => 'historico.php?' . http_build_query(array_filter(array_merge(['tipo' => $entity, 'autor' => $author], $changes), static fn ($v) => $v !== '' && $v !== null && $v !== 1));
$badge = ['cadastro' => 'text-bg-success', 'alteracao' => 'text-bg-primary', 'cancelamento' => 'text-bg-danger'];
?><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><div><h1 class="h3 mb-1">Histórico de alterações</h1><p class="text-muted mb-0">O que foi alterado no sistema, por quem e quando. Mais recentes primeiro.</p></div><a class="btn btn-outline-secondary" href="index.php?visao=mes">Voltar para agenda</a></div>

<div class="d-flex flex-wrap align-items-end gap-3 mb-4">
    <nav class="d-flex flex-wrap gap-2" aria-label="Filtrar por tipo">
        <a class="btn btn-sm <?= $entity === '' ? 'btn-navy' : 'btn-outline-secondary' ?>" href="<?= e($link(['tipo' => ''])) ?>">Tudo</a>
        <?php foreach (HISTORY_ENTITIES as $key => $label): ?>
            <a class="btn btn-sm <?= $entity === $key ? 'btn-navy' : 'btn-outline-secondary' ?>" href="<?= e($link(['tipo' => $key])) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="ms-lg-auto" style="min-width:220px">
        <?php if ($entity !== ''): ?><input type="hidden" name="tipo" value="<?= e($entity) ?>"><?php endif; ?>
        <label class="form-label small text-muted mb-1" for="autor">Feito por</label>
        <select class="form-select form-select-sm" id="autor" name="autor" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($authors as $name): ?>
                <option value="<?= e($name) ?>" <?= $author === $name ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (!$entries): ?>
    <div class="app-card text-center text-muted py-5">Nenhuma alteração registrada<?= $entity || $author ? ' com esses filtros' : '' ?>.</div>
<?php else: ?>
    <ol class="history-list">
        <?php foreach ($entries as $entry): $changes = json_decode((string) $entry['alteracoes'], true) ?: []; ?>
            <li class="history-item">
                <div class="history-head">
                    <span class="badge <?= $badge[$entry['acao']] ?? 'text-bg-secondary' ?>"><?= e((HISTORY_ACTIONS[$entry['acao']] ?? $entry['acao']) . ' · ' . (HISTORY_ENTITIES[$entry['entidade']] ?? $entry['entidade'])) ?></span>
                    <strong><?= e($entry['titulo']) ?></strong>
                </div>
                <div class="text-muted small"><?= e(date('d/m/Y \à\s H:i', strtotime($entry['criado_em']))) ?> · por <?= e($entry['usuario_nome']) ?></div>
                <?php if ($changes): ?>
                    <ul class="history-changes">
                        <?php foreach ($changes as $c): ?>
                            <li>
                                <span class="history-field"><?= e($c['campo']) ?>:</span>
                                <?php if ($c['antes'] !== ''): ?><del><?= e($c['antes']) ?></del> <span aria-hidden="true">→</span><?php endif; ?>
                                <span><?= $c['depois'] !== '' ? e($c['depois']) : '<em class="text-muted">(vazio)</em>' ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>

    <?php if ($pages > 1): ?>
        <nav class="d-flex justify-content-center gap-2 mt-4" aria-label="Paginação">
            <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($link(['pagina' => $page - 1])) ?>">‹ Anterior</a><?php endif; ?>
            <span class="btn btn-sm disabled">Página <?= $page ?> de <?= $pages ?></span>
            <?php if ($page < $pages): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($link(['pagina' => $page + 1])) ?>">Próxima ›</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php';
