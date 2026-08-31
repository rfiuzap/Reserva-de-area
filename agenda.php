<?php
declare(strict_types=1);

$pageTitle = 'Agenda';
require_once __DIR__ . '/includes/header.php';

$reference = new DateTimeImmutable($_GET['data'] ?? date('Y-m-d'));
$start = $reference;
$end = $start->modify('+9 days');
$previous = $reference->modify('-10 days');
$next = $reference->modify('+10 days');
$filterMine = isset($_GET['meu']);
$filterGroupId = (int) ($_GET['grupo_filtro'] ?? 0);
$filterAreaId = (int) ($_GET['area_filtro'] ?? 0);
$conditions = ["r.status = 'ativa'", 'r.data_inicio >= ?', 'r.data_inicio < ?'];
$params = [$start->format('Y-m-d 00:00:00'), $end->modify('+1 day')->format('Y-m-d 00:00:00')];
if ($filterMine) { $conditions[] = 'r.criado_por = ?'; $params[] = $_SESSION['usuario_nome'] ?? ''; }
if ($filterGroupId) { $conditions[] = 'r.grupo_id = ?'; $params[] = $filterGroupId; }
if ($filterAreaId) { $conditions[] = 'r.area_id = ?'; $params[] = $filterAreaId; }
$statement = $pdo->prepare("SELECT r.*, g.nome AS grupo, g.cor, COALESCE((SELECT GROUP_CONCAT(sg.nome ORDER BY sg.nome SEPARATOR ', ') FROM subgrupos sg WHERE FIND_IN_SET(sg.id, r.subgrupos_ids)), s.nome) AS subgrupo, COALESCE(a.nome, 'Todas as áreas') AS area FROM reservas r JOIN grupos g ON g.id = r.grupo_id JOIN subgrupos s ON s.id = r.subgrupo_id LEFT JOIN areas a ON a.id = r.area_id WHERE " . implode(' AND ', $conditions) . ' ORDER BY r.data_inicio');
$statement->execute($params);
$byDay = [];
foreach ($statement->fetchAll() as $reservation) $byDay[date('Y-m-d', strtotime($reservation['data_inicio']))][] = $reservation;
$filterGroups = $pdo->query('SELECT id,nome FROM grupos ORDER BY nome')->fetchAll();
$filterAreas = $pdo->query('SELECT id,nome FROM areas ORDER BY nome')->fetchAll();
$filterActive = $filterMine || $filterGroupId || $filterAreaId;
?><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><div><h1 class="h3 mb-1">Agenda</h1><p class="agenda-period mb-0"><?= $start->format('d/m') ?> a <?= $end->format('d/m/Y') ?></p></div><div class="d-flex gap-2 no-print"><a class="btn btn-outline-secondary" href="agenda.php?data=<?= $previous->format('Y-m-d') ?>">Anterior</a><a class="btn btn-outline-secondary" href="agenda.php?data=<?= $next->format('Y-m-d') ?>">Próxima</a><button class="btn btn-print" type="button" onclick="window.print()">Imprimir</button></div></div><div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 no-print"><div class="btn-group"><a class="btn btn-sm btn-navy" href="agenda.php?data=<?= $reference->format('Y-m-d') ?>">Dia</a><a class="btn btn-sm btn-outline-secondary" href="index.php?visao=mes&data=<?= $reference->format('Y-m-d') ?>">Mês</a></div><div class="d-flex align-items-center gap-3"><a class="small" href="canceladas.php">Exibir reservas canceladas</a><button class="btn btn-sm <?= $filterActive?'btn-navy':'btn-outline-secondary' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">Filtro<?= $filterActive?' ●':'' ?></button></div></div><div class="collapse mb-3<?= $filterActive?' show':'' ?> no-print" id="filterPanel"><div class="app-card"><form method="get" class="row g-3 align-items-end"><input type="hidden" name="data" value="<?= $reference->format('Y-m-d') ?>"><div class="col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="meu" id="meuFiltro" <?= $filterMine?'checked':'' ?>><label class="form-check-label" for="meuFiltro">Somente minhas reservas</label></div></div><div class="col-md-3"><label class="form-label">Grupo</label><select class="form-select" name="grupo_filtro"><option value="">Todos</option><?php foreach($filterGroups as $g): ?><option value="<?= $g['id'] ?>" <?= $filterGroupId===(int)$g['id']?'selected':'' ?>><?= e($g['nome']) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label">Área</label><select class="form-select" name="area_filtro"><option value="">Todas</option><?php foreach($filterAreas as $a): ?><option value="<?= $a['id'] ?>" <?= $filterAreaId===(int)$a['id']?'selected':'' ?>><?= e($a['nome']) ?></option><?php endforeach; ?></select></div><div class="col-md-3 d-flex gap-2"><button class="btn btn-gold" type="submit">Aplicar</button><a class="btn btn-outline-secondary" href="agenda.php?data=<?= $reference->format('Y-m-d') ?>">Limpar</a></div></form></div></div><section class="agenda-list"><div class="agenda-day-list"><?php for ($day = $start; $day <= $end; $day = $day->modify('+1 day')): $items = $byDay[$day->format('Y-m-d')] ?? []; ?><div class="agenda-day-row"><div class="agenda-day-row-date"><span><?= ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'][(int)$day->format('N') - 1] ?></span><strong><?= $day->format('d/m') ?></strong></div><div class="agenda-day-row-events"><?php foreach ($items as $reservation): ?><a class="calendar-event" href="reserva_form.php?id=<?= $reservation['id'] ?>" style="background-color:<?= e($reservation['cor']) ?>55" title="<?= e($reservation['grupo'].' '.$reservation['subgrupo'].($reservation['criado_por']?' · Criado por '.$reservation['criado_por']:'')) ?>"><strong><?= e($reservation['grupo']) ?></strong><br><?= e($reservation['subgrupo']) ?> · <?= e($reservation['area']) ?></a><?php endforeach; ?><?php if (!$items): ?><span class="text-muted small">Sem reservas</span><?php endif; ?></div></div><?php endfor; ?></div></section><?php require __DIR__ . '/includes/footer.php';
