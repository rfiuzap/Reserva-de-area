<?php
declare(strict_types=1);

const DEMO_RUNTIME_TABLE = 'demo_runtime';
const DEMO_RESERVAS_COLUMNS = 'id,grupo_id,subgrupo_id,subgrupos_ids,area_id,areas_ids,todas_areas,data_inicio,data_fim,dia_inteiro,criado_por,recorrencia,serie_id,status,cancelado_por,motivo_cancelamento,criado_em,atualizado_em';

function demo_environment_cleanup(PDO $pdo): void
{
    if (APP_ENV !== 'demo') {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS demo_runtime (
        id TINYINT UNSIGNED PRIMARY KEY,
        iniciado_em DATETIME NOT NULL,
        reservas_snapshot LONGTEXT NOT NULL,
        areas_snapshot LONGTEXT NOT NULL,
        grupos_snapshot LONGTEXT NOT NULL,
        subgrupos_snapshot LONGTEXT NOT NULL,
        configuracoes_snapshot LONGTEXT NOT NULL
    ) ENGINE=InnoDB");

    $runtime = $pdo->query('SELECT * FROM demo_runtime WHERE id = 1')->fetch();
    if (!$runtime) {
        $insert = $pdo->prepare('INSERT INTO demo_runtime (id, iniciado_em, reservas_snapshot, areas_snapshot, grupos_snapshot, subgrupos_snapshot, configuracoes_snapshot) VALUES (1, NOW(), ?, ?, ?, ?, ?)');
        $insert->execute([
            json_encode($pdo->query('SELECT ' . DEMO_RESERVAS_COLUMNS . ' FROM reservas ORDER BY id')->fetchAll(), JSON_THROW_ON_ERROR),
            json_encode($pdo->query('SELECT * FROM areas ORDER BY id')->fetchAll(), JSON_THROW_ON_ERROR),
            json_encode($pdo->query('SELECT * FROM grupos ORDER BY id')->fetchAll(), JSON_THROW_ON_ERROR),
            json_encode($pdo->query('SELECT * FROM subgrupos ORDER BY id')->fetchAll(), JSON_THROW_ON_ERROR),
            json_encode($pdo->query('SELECT * FROM configuracoes WHERE id = 1')->fetch(), JSON_THROW_ON_ERROR),
        ]);
        return;
    }

    // Compared with the database clock: iniciado_em is written with NOW(), and PHP's timezone may differ.
    $expired = (bool) $pdo->query('SELECT iniciado_em <= NOW() - INTERVAL 2 HOUR FROM demo_runtime WHERE id = 1')->fetchColumn();
    if (!$expired) {
        return;
    }

    $baselineAreas = json_decode($runtime['areas_snapshot'], true, 512, JSON_THROW_ON_ERROR);
    $baselineConfig = json_decode($runtime['configuracoes_snapshot'], true, 512, JSON_THROW_ON_ERROR);
    $baselineFiles = array_filter(array_merge(
        array_column($baselineAreas, 'imagem'),
        [$baselineConfig['logo'], 'if1im5if1im5if1.jpeg']
    ));
    $pdo->beginTransaction();
    try {
        foreach (['reservas', 'subgrupos', 'grupos', 'areas'] as $table) {
            $pdo->exec('DELETE FROM ' . $table);
        }
        demo_restore_rows($pdo, 'areas', 'id,nome,lugares,imagem,status,criado_em', json_decode($runtime['areas_snapshot'], true, 512, JSON_THROW_ON_ERROR));
        demo_restore_rows($pdo, 'grupos', 'id,nome,tempo_padrao,cor,especial,status,criado_em', json_decode($runtime['grupos_snapshot'], true, 512, JSON_THROW_ON_ERROR));
        demo_restore_rows($pdo, 'subgrupos', 'id,grupo_id,nome,status', json_decode($runtime['subgrupos_snapshot'], true, 512, JSON_THROW_ON_ERROR));
        demo_restore_rows($pdo, 'reservas', DEMO_RESERVAS_COLUMNS, json_decode($runtime['reservas_snapshot'], true, 512, JSON_THROW_ON_ERROR));

        $pdo->prepare('UPDATE configuracoes SET dias_permitidos=?, hora_inicio=?, hora_fim=?, titulo_site=?, logo=? WHERE id=1')->execute([
            $baselineConfig['dias_permitidos'], $baselineConfig['hora_inicio'], $baselineConfig['hora_fim'], $baselineConfig['titulo_site'], $baselineConfig['logo'],
        ]);
        $pdo->exec('UPDATE demo_runtime SET iniciado_em = NOW() WHERE id = 1');
        $pdo->commit();
        try {
            $pdo->exec('DELETE FROM historico_alteracoes'); // demo changes are discarded, so is their history
        } catch (PDOException) {
        }
        demo_remove_non_baseline_uploads($baselineFiles);
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function demo_restore_rows(PDO $pdo, string $table, string $columns, array $rows): void
{
    if (!$rows) {
        return;
    }
    $columnList = array_map('trim', explode(',', $columns));
    $placeholders = implode(',', array_fill(0, count($columnList), '?'));
    $insert = $pdo->prepare('INSERT INTO ' . $table . ' (' . $columns . ') VALUES (' . $placeholders . ')');
    foreach ($rows as $row) {
        $insert->execute(array_map(static fn (string $column): mixed => $row[$column] ?? null, $columnList));
    }
}

function demo_remove_non_baseline_uploads(array $baselineFiles): void
{
    $directory = __DIR__ . '/../uploads';
    if (!is_dir($directory)) {
        return;
    }
    $allowed = array_flip($baselineFiles);
    foreach (glob($directory . '/*') ?: [] as $path) {
        if (is_file($path) && !isset($allowed[basename($path)])) {
            unlink($path);
        }
    }
}