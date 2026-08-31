<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

try {
    $config = $pdo->query('SELECT * FROM configuracoes WHERE id = 1')->fetch();
    $allowedDays = explode(',', $config['dias_permitidos']);
    $groupId = (int) ($_POST['grupo_id'] ?? 0);
    $subgroupIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['subgrupo_id'] ?? [])))));
    $areaId = (int) ($_POST['area_id'] ?? 0);
    $allAreas = isset($_POST['todas_areas']);
    $date = $_POST['data'] ?? '';
    $time = $_POST['hora_inicio'] ?? '';
    $endTime = $_POST['hora_fim'] ?? '';
    $allDay = isset($_POST['dia_inteiro']);
    $recurrence = $_POST['recorrencia'] ?? 'nenhuma';
    $weekdays = $_POST['dias_semana'] ?? [];
    $until = $_POST['data_limite'] ?: null;
    $reservationId = (int) ($_POST['id'] ?? 0);

    if (!$subgroupIds) throw new RuntimeException('Selecione ao menos um subgrupo.');

    $groupStatement = $pdo->prepare("SELECT especial FROM grupos WHERE id = ? AND status = 'ativo'");
    $groupStatement->execute([$groupId]);
    $group = $groupStatement->fetch();
    if (!$group) throw new RuntimeException('Grupo ou subgrupo inválido.');

    $subgroupCheck = $pdo->prepare("SELECT id FROM subgrupos WHERE id = ? AND grupo_id = ? AND status = 'ativo'");
    foreach ($subgroupIds as $subgroupId) {
        $subgroupCheck->execute([$subgroupId, $groupId]);
        if (!$subgroupCheck->fetch()) throw new RuntimeException('Grupo ou subgrupo inválido.');
    }

    if ($allAreas && !(int) $group['especial']) throw new RuntimeException('Somente grupos especiais podem marcar todas as áreas.');
    if ($allAreas && $reservationId) throw new RuntimeException('Para marcar todas as áreas, crie uma nova reserva especial.');
    if (!$allAreas && !$areaId) throw new RuntimeException('Selecione uma área.');
    if ($allDay && !(int) $group['especial']) throw new RuntimeException('Somente grupos especiais podem fechar a agenda durante todo o dia.');
    if (!valid_time($time) || (!$allDay && !valid_time($endTime)) || !strtotime($date)) throw new RuntimeException('Data ou horário inválido.');
    if (!$allDay && $endTime <= $time) throw new RuntimeException('O horário de término deve ser depois do início.');
    if (!in_array(date('N', strtotime($date)), $allowedDays, true)) throw new RuntimeException('Este dia não está permitido para reserva.');
    if ($recurrence !== 'nenhuma' && !$until) throw new RuntimeException('Informe a data limite para reservas recorrentes.');
    if ($recurrence === 'semanal' && !$weekdays) throw new RuntimeException('Selecione ao menos um dia da semana.');

    $start = new DateTimeImmutable("$date $time");
    $end = $allDay ? new DateTimeImmutable("$date {$config['hora_fim']}") : new DateTimeImmutable("$date $endTime");
    if ($start->format('H:i:s') < $config['hora_inicio'] || $end->format('H:i:s') > $config['hora_fim']) throw new RuntimeException('O horário deve estar dentro do período configurado.');

    $dates = dates_for_recurrence($date, $recurrence, $weekdays, $until);
    if (count($dates) > 1 && $reservationId) throw new RuntimeException('Edite uma reserva recorrente individualmente.');

    foreach ($dates as $reservationDate) {
        $itemStart = "$reservationDate $time";
        $itemEnd = $allDay ? "$reservationDate {$config['hora_fim']}" : "$reservationDate $endTime";
        if (reservation_conflict($pdo, $allAreas ? null : $areaId, $itemStart, $itemEnd, $allAreas, $reservationId)) throw new RuntimeException("Há conflito de horário em $reservationDate. A reserva não foi criada.");
    }

    $pdo->beginTransaction();
    $subgroupsCsv = implode(',', $subgroupIds);
    if ($reservationId) {
        $update = $pdo->prepare('UPDATE reservas SET grupo_id=?, subgrupo_id=?, subgrupos_ids=?, area_id=?, todas_areas=?, data_inicio=?, data_fim=?, dia_inteiro=?, recorrencia=? WHERE id=?');
        $update->execute([$groupId, $subgroupIds[0], $subgroupsCsv, $allAreas ? null : $areaId, $allAreas ? 1 : 0, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $allDay ? 1 : 0, 'nenhuma', $reservationId]);
    } else {
        $seriesId = count($dates) > 1 ? bin2hex(random_bytes(16)) : null;
        $insert = $pdo->prepare('INSERT INTO reservas (grupo_id,subgrupo_id,subgrupos_ids,area_id,todas_areas,data_inicio,data_fim,dia_inteiro,criado_por,recorrencia,serie_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($dates as $reservationDate) {
            $itemStart = "$reservationDate $time";
            $itemEnd = $allDay ? "$reservationDate {$config['hora_fim']}" : "$reservationDate $endTime";
            $insert->execute([$groupId, $subgroupIds[0], $subgroupsCsv, $allAreas ? null : $areaId, $allAreas ? 1 : 0, $itemStart, $itemEnd, $allDay ? 1 : 0, $_SESSION['usuario_nome'] ?? null, $recurrence, $seriesId]);
        }
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $reservationId ? 'Reserva atualizada com sucesso.' : 'Reserva criada com sucesso.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
}
