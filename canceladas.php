<?php
declare(strict_types=1);

$pageTitle = 'Reservas canceladas';
require_once __DIR__ . '/includes/header.php';

$groupId = (int) ($_GET['grupo_id'] ?? 0);
$areaId = (int) ($_GET['area_id'] ?? 0);
$reservationDate = $_GET['data_reserva'] ?? '';
$cancellationDate = $_GET['data_cancelamento'] ?? '';
$conditions = ["r.status = 'cancelado'"];
$parameters = [];

if ($groupId) { $conditions[] = 'r.grupo_id = ?'; $parameters[] = $groupId; }
if ($areaId) { $conditions[] = 'r.area_id = ?'; $parameters[] = $areaId; }
if ($reservationDate) { $conditions[] = 'DATE(r.data_inicio) = ?'; $parameters[] = $reservationDate; }
if ($cancellationDate) { $conditions[] = 'DATE(r.atualizado_em) = ?'; $parameters[] = $cancellationDate; }

$sql = "SELECT r.*, g.nome AS grupo, COALESCE((SELECT GROUP_CONCAT(sg.nome ORDER BY sg.nome SEPARATOR ', ') FROM subgrupos sg WHERE FIND_IN_SET(sg.id, r.subgrupos_ids)), s.nome) AS subgrupo, COALESCE(a.nome, 'Todas as áreas') AS area FROM reservas r JOIN grupos g ON g.id = r.grupo_id JOIN subgrupos s ON s.id = r.subgrupo_id LEFT JOIN areas a ON a.id = r.area_id WHERE " . implode(' AND ', $conditions) . ' ORDER BY r.atualizado_em DESC';
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$reservations = $statement->fetchAll();
$groups = $pdo->query('SELECT id, nome FROM grupos ORDER BY nome')->fetchAll();
$areas = $pdo->query('SELECT id, nome FROM areas ORDER BY nome')->fetchAll();
?><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h3 mb-1">Reservas canceladas</h1><p class="text-muted mb-0">Histórico para auditoria</p></div><a class="btn btn-outline-secondary" href="index.php">Voltar para agenda</a></div><form class="app-card mb-4" method="get"><div class="row align-items-end g-3"><div class="col-md-3"><label class="form-label">Grupo</label><select class="form-select" name="grupo_id"><option value="">Todos os grupos</option><?php foreach ($groups as $group): ?><option value="<?= $group['id'] ?>" <?= $groupId === (int) $group['id'] ? 'selected' : '' ?>><?= e($group['nome']) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label">Área</label><select class="form-select" name="area_id"><option value="">Todas as áreas</option><?php foreach ($areas as $area): ?><option value="<?= $area['id'] ?>" <?= $areaId === (int) $area['id'] ? 'selected' : '' ?>><?= e($area['nome']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><label class="form-label">Data da reserva</label><input class="form-control" type="date" name="data_reserva" value="<?= e($reservationDate) ?>"></div><div class="col-md-2"><label class="form-label">Data do cancelamento</label><input class="form-control" type="date" name="data_cancelamento" value="<?= e($cancellationDate) ?>"></div><div class="col-md-2 d-flex gap-2"><button class="btn btn-gold" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="canceladas.php">Limpar</a></div></div></form><section class="app-card p-0 overflow-hidden"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Reserva</th><th>Área</th><th>Data e horário</th><th>Criado por</th><th>Cancelado por</th><th>Data do cancelamento</th><th>Motivo</th></tr></thead><tbody><?php if (!$reservations): ?><tr><td class="text-center text-muted py-4" colspan="7">Não há reservas canceladas para os filtros informados.</td></tr><?php endif; ?><?php foreach ($reservations as $reservation): ?><tr><td><strong><?= e($reservation['grupo']) ?></strong><br><span class="text-muted small"><?= e($reservation['subgrupo']) ?></span></td><td><?= e($reservation['area']) ?></td><td><?= date('d/m/Y H:i', strtotime($reservation['data_inicio'])) ?></td><td><?= e($reservation['criado_por'] ?: 'Não informado') ?></td><td><?= e($reservation['cancelado_por'] ?: 'Não informado') ?></td><td><?= date('d/m/Y H:i', strtotime($reservation['atualizado_em'])) ?></td><td><?= e($reservation['motivo_cancelamento'] ?: 'Não informado') ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php require __DIR__ . '/includes/footer.php';
