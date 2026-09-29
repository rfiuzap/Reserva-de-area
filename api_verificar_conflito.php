<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$areaId = (int) ($_POST['area_id'] ?? 0);
$areaIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['area_ids'] ?? [])))));
if (!$areaIds && $areaId > 0) {
    $areaIds = [$areaId];
}
$allAreas = isset($_POST['todas_areas']);
$groupId = (int) ($_POST['grupo_id'] ?? 0);
$date = $_POST['data'] ?? '';
$time = $_POST['hora_inicio'] ?? '';
$endTime = $_POST['hora_fim'] ?? '';
$reservationId = (int) ($_POST['id'] ?? 0);
$recurrence = $_POST['recorrencia'] ?? 'nenhuma';
$weekdays = $_POST['dias_semana'] ?? [];
$until = $_POST['data_limite'] ?: null;
$allDay = isset($_POST['dia_inteiro']);

if ((!$areaIds && !$allAreas && !$allDay) || !$groupId || !strtotime($date) || !valid_time($time) || (!$allDay && !valid_time($endTime))) {
    http_response_code(422);
    echo json_encode(['error' => 'Preencha grupo, área, data e horário antes de confirmar.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$statement = $pdo->prepare('SELECT id FROM grupos WHERE id = ? AND status = \'ativo\'');
$statement->execute([$groupId]);
$group = $statement->fetch();
$config = $pdo->query('SELECT hora_fim FROM configuracoes WHERE id = 1')->fetch();

if (!$group || !$config) {
    http_response_code(422);
    echo json_encode(['error' => 'Não foi possível validar os dados da reserva.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$conflictingDates = [];
$globalConflict = $allAreas || $allDay;
foreach (dates_for_recurrence($date, $recurrence, $weekdays, $until) as $reservationDate) {
    $start = $reservationDate . ' ' . $time;
    $end = $allDay
        ? $reservationDate . ' ' . $config['hora_fim']
        : $reservationDate . ' ' . $endTime;

    $areasToCheck = $globalConflict ? [null] : $areaIds;
    foreach ($areasToCheck as $selectedAreaId) {
        if (reservation_conflict($pdo, $selectedAreaId, $start, $end, $globalConflict, $reservationId)) {
            $conflictingDates[] = $reservationDate;
            break;
        }
    }
}

echo json_encode(['hasConflict' => count($conflictingDates) > 0, 'dates' => $conflictingDates], JSON_UNESCAPED_UNICODE);
