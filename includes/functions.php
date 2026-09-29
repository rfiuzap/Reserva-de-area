<?php
declare(strict_types=1);

require_once __DIR__ . '/history.php';

function require_login(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (($_SESSION['usuario_tipo'] ?? '') !== 'admin') {
        flash('error', 'Acesso restrito ao administrador.');
        redirect('index.php');
    }
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) { $_SESSION['flash'][$key] = $message; return null; }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function e(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

function valid_time(string $time): bool { return (bool) preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', $time); }

function upload_image(array $file, ?string $oldFile = null): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $oldFile;
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) throw new RuntimeException('Envie uma imagem de até 5 MB.');
    $type = mime_content_type($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$type])) throw new RuntimeException('Formato inválido. Use JPG, PNG ou WEBP.');
    $filename = bin2hex(random_bytes(12)) . '.' . $extensions[$type];
    $directory = __DIR__ . '/../uploads';
    if (!is_dir($directory)) mkdir($directory, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('Não foi possível salvar a imagem.');
    if ($oldFile && is_file($directory . '/' . $oldFile)) unlink($directory . '/' . $oldFile);
    return $filename;
}

function normalize_area_ids(array $areaIds): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $areaIds))));
    sort($ids, SORT_NUMERIC);
    return $ids;
}

function serialize_area_ids(array $areaIds): ?string {
    $ids = normalize_area_ids($areaIds);
    return $ids ? implode(',', $ids) : null;
}

/** SQL expression with the reservation's area names ("A; B; C"). Expects reservas r and areas a (LEFT JOIN on r.area_id). */
function area_names_sql(): string {
    return "CASE WHEN r.todas_areas = 1 THEN 'Todas as áreas' ELSE COALESCE((SELECT GROUP_CONCAT(ar.nome ORDER BY ar.nome SEPARATOR '; ') FROM areas ar WHERE FIND_IN_SET(ar.id, r.areas_ids)), a.nome, 'Todas as áreas') END";
}

function area_label_from_ids(?string $areasCsv, ?int $mainAreaId): string {
    $ids = $areasCsv ? array_filter(array_map('intval', explode(',', $areasCsv))) : [];
    if ($ids) {
        $names = [];
        foreach ($ids as $id) {
            $names[] = (string) $id;
        }
        return implode(', ', $names);
    }

    return $mainAreaId ? (string) $mainAreaId : 'Todas as áreas';
}

function reservation_conflict(PDO $pdo, ?int $areaId, string $start, string $end, bool $allAreas = false, int $ignoreId = 0): bool {
    $statement = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE status = 'ativa' AND data_inicio < ? AND data_fim > ? AND id <> ? AND (todas_areas = 1 OR ? = 1 OR area_id = ? OR FIND_IN_SET(?, areas_ids))");
    $statement->execute([$end, $start, $ignoreId, $allAreas ? 1 : 0, $areaId, $areaId]);
    return (int) $statement->fetchColumn() > 0;
}

function dates_for_recurrence(string $startDate, string $recurrence, array $weekdays, ?string $until): array {
    if ($recurrence === 'nenhuma') return [$startDate];
    $last = $until ?: $startDate;
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($last);
    $dates = [];
    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        if (($recurrence === 'semanal' && in_array((string) $date->format('N'), $weekdays, true)) || ($recurrence === 'mensal' && $date->format('d') === $start->format('d'))) $dates[] = $date->format('Y-m-d');
    }
    return $dates ?: [$startDate];
}
