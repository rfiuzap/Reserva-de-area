<?php
declare(strict_types=1);

/*
 * Histórico de alterações: guarda, para cada cadastro/alteração, os campos
 * que mudaram ("Campo: antes → depois"), quem fez e quando.
 * Uso: $antes = history_snapshot(...); (grava no banco); history_log(..., $antes, history_snapshot(...));
 */

const HISTORY_ENTITIES = [
    'reserva' => 'Reserva',
    'area' => 'Área',
    'grupo' => 'Grupo',
    'subgrupo' => 'Subgrupo',
    'usuario' => 'Usuário',
    'configuracoes' => 'Configurações',
];

const HISTORY_ACTIONS = [
    'cadastro' => 'Cadastro',
    'alteracao' => 'Alteração',
    'cancelamento' => 'Cancelamento',
];

/** Values of a record as shown in the history (label => text). Empty array when not found. */
function history_snapshot(PDO $pdo, string $entity, int $id): array
{
    $status = static fn (?string $s): string => $s === 'oculto' ? 'Oculto' : 'Ativo';
    $time = static fn (?string $t): string => $t ? substr($t, 0, 5) : '';

    switch ($entity) {
        case 'reserva':
            $s = $pdo->prepare("SELECT r.*, g.nome AS grupo, COALESCE((SELECT GROUP_CONCAT(sg.nome ORDER BY sg.nome SEPARATOR ', ') FROM subgrupos sg WHERE FIND_IN_SET(sg.id, r.subgrupos_ids)), s.nome) AS subgrupos, " . area_names_sql() . " AS areas FROM reservas r JOIN grupos g ON g.id = r.grupo_id JOIN subgrupos s ON s.id = r.subgrupo_id LEFT JOIN areas a ON a.id = r.area_id WHERE r.id = ?");
            $s->execute([$id]);
            if (!$r = $s->fetch()) return [];
            $start = new DateTimeImmutable($r['data_inicio']);
            $end = new DateTimeImmutable($r['data_fim']);
            return [
                'Grupo' => $r['grupo'],
                'Subgrupos' => $r['subgrupos'],
                'Áreas' => $r['areas'],
                'Data' => $start->format('d/m/Y'),
                'Horário' => $r['dia_inteiro'] ? 'Dia inteiro' : $start->format('H:i') . ' às ' . $end->format('H:i'),
                'Recorrência' => ['nenhuma' => 'Não recorrente', 'semanal' => 'Semanal', 'mensal' => 'Mensal'][$r['recorrencia']] ?? $r['recorrencia'],
                'Status' => $r['status'] === 'cancelado' ? 'Cancelada' : 'Ativa',
                'Motivo do cancelamento' => (string) $r['motivo_cancelamento'],
            ];

        case 'area':
            $s = $pdo->prepare('SELECT * FROM areas WHERE id = ?');
            $s->execute([$id]);
            if (!$r = $s->fetch()) return [];
            return ['Nome' => $r['nome'], 'Lugares' => $r['lugares'], 'Imagem' => $r['imagem'] ? 'imagem ' . substr($r['imagem'], 0, 6) : '', 'Status' => $status($r['status'])];

        case 'grupo':
            $s = $pdo->prepare('SELECT * FROM grupos WHERE id = ?');
            $s->execute([$id]);
            if (!$r = $s->fetch()) return [];
            return ['Nome' => $r['nome'], 'Cor' => $r['cor'], 'Grupo especial' => $r['especial'] ? 'Sim' : 'Não', 'Status' => $status($r['status'])];

        case 'subgrupo':
            $s = $pdo->prepare('SELECT s.*, g.nome AS grupo FROM subgrupos s JOIN grupos g ON g.id = s.grupo_id WHERE s.id = ?');
            $s->execute([$id]);
            if (!$r = $s->fetch()) return [];
            return ['Grupo' => $r['grupo'], 'Nome' => $r['nome'], 'Status' => $status($r['status'])];

        case 'usuario':
            $s = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
            $s->execute([$id]);
            if (!$r = $s->fetch()) return [];
            return ['Nome' => $r['nome'], 'Usuário' => $r['usuario'], 'Tipo' => $r['tipo'] === 'admin' ? 'Administrador' : 'Normal', 'Status' => $status($r['status'])];

        case 'configuracoes':
            $r = $pdo->query('SELECT * FROM configuracoes WHERE id = 1')->fetch();
            if (!$r) return [];
            $names = ['1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb', '7' => 'Dom'];
            $days = array_map(static fn ($d) => $names[$d] ?? $d, array_filter(explode(',', (string) $r['dias_permitidos'])));
            return ['Título da página' => $r['titulo_site'], 'Logo' => $r['logo'] ? 'logo ' . substr($r['logo'], 0, 6) : 'padrão', 'Dias permitidos' => implode(', ', $days), 'Horário de início' => $time($r['hora_inicio']), 'Horário de término' => $time($r['hora_fim'])];
    }

    return [];
}

function history_title(string $entity, array $snapshot): string
{
    return match ($entity) {
        'reserva' => trim(($snapshot['Grupo'] ?? '') . ' · ' . ($snapshot['Subgrupos'] ?? '') . ' · ' . ($snapshot['Data'] ?? '') . ' ' . ($snapshot['Horário'] ?? ''), ' ·'),
        'subgrupo' => ($snapshot['Grupo'] ?? '') . ' › ' . ($snapshot['Nome'] ?? ''),
        'configuracoes' => 'Configurações da agenda',
        default => (string) ($snapshot['Nome'] ?? ''),
    };
}

/**
 * Records the difference between two snapshots. Never interrupts the page:
 * failures only go to the PHP error log.
 *
 * @param list<array{campo: string, antes: string, depois: string}> $extra changes not visible in the snapshot (e.g. password)
 */
function history_log(PDO $pdo, string $entity, int $id, array $before, array $after, array $extra = [], ?string $action = null): void
{
    try {
        $action ??= $before ? 'alteracao' : 'cadastro';
        $changes = [];
        foreach (array_keys($after + $before) as $field) {
            $old = (string) ($before[$field] ?? '');
            $new = (string) ($after[$field] ?? '');
            if ($old !== $new && !($action === 'cadastro' && $new === '')) {
                $changes[] = ['campo' => $field, 'antes' => $action === 'cadastro' ? '' : $old, 'depois' => $new];
            }
        }
        $changes = array_merge($changes, $extra);
        if ($action !== 'cadastro' && !$changes) return;

        $row = [
            $_SESSION['usuario_id'] ?? null,
            $_SESSION['usuario_nome'] ?? 'Sistema',
            $action,
            $entity,
            $id ?: null,
            history_title($entity, $after ?: $before),
            json_encode($changes, JSON_UNESCAPED_UNICODE),
        ];
        $sql = 'INSERT INTO historico_alteracoes (usuario_id, usuario_nome, acao, entidade, entidade_id, titulo, alteracoes) VALUES (?, ?, ?, ?, ?, ?, ?)';
        try {
            $pdo->prepare($sql)->execute($row);
        } catch (PDOException $e) {
            if ($e->getCode() !== '42S02') throw $e; // only "table does not exist" is handled here
            history_create_table($pdo);
            $pdo->prepare($sql)->execute($row);
        }
    } catch (Throwable $e) {
        error_log('Histórico de alterações não registrado: ' . $e->getMessage());
    }
}

function history_create_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS historico_alteracoes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED DEFAULT NULL,
        usuario_nome VARCHAR(120) NOT NULL,
        acao VARCHAR(20) NOT NULL,
        entidade VARCHAR(20) NOT NULL,
        entidade_id INT UNSIGNED DEFAULT NULL,
        titulo VARCHAR(255) NOT NULL,
        alteracoes TEXT DEFAULT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_historico_entidade (entidade, criado_em),
        KEY idx_historico_usuario (usuario_nome)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** The history screen is for administrators (and for the demo user in the demo environment). */
function can_view_history(): bool
{
    return ($_SESSION['usuario_tipo'] ?? '') === 'admin'
        || (APP_ENV === 'demo' && ($_SESSION['usuario_login'] ?? '') === 'demo');
}
