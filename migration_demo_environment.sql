CREATE TABLE IF NOT EXISTS demo_runtime (
    id TINYINT UNSIGNED PRIMARY KEY,
    iniciado_em DATETIME NOT NULL,
    reservas_snapshot LONGTEXT NOT NULL,
    areas_snapshot LONGTEXT NOT NULL,
    grupos_snapshot LONGTEXT NOT NULL,
    subgrupos_snapshot LONGTEXT NOT NULL,
    configuracoes_snapshot LONGTEXT NOT NULL
) ENGINE=InnoDB;