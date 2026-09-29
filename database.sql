CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(80) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(120) NOT NULL,
    tipo ENUM('admin','normal') NOT NULL DEFAULT 'normal',
    status ENUM('ativo','oculto') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE areas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    lugares VARCHAR(30) NOT NULL,
    imagem VARCHAR(255) DEFAULT NULL,
    status ENUM('ativo','oculto') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE grupos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    tempo_padrao INT UNSIGNED NOT NULL,
    cor CHAR(7) NOT NULL DEFAULT '#223764',
    especial TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('ativo','oculto') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE subgrupos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT UNSIGNED NOT NULL,
    nome VARCHAR(120) NOT NULL,
    status ENUM('ativo','oculto') NOT NULL DEFAULT 'ativo',
    CONSTRAINT fk_subgrupo_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id),
    UNIQUE KEY unq_subgrupo_grupo_nome (grupo_id, nome)
) ENGINE=InnoDB;

CREATE TABLE configuracoes (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    dias_permitidos VARCHAR(30) NOT NULL DEFAULT '1,2,3,4,5',
    hora_inicio TIME NOT NULL DEFAULT '08:00:00',
    hora_fim TIME NOT NULL DEFAULT '20:00:00',
    titulo_site VARCHAR(120) NOT NULL DEFAULT 'Reserva de Áreas',
    logo VARCHAR(255) DEFAULT NULL,
    CONSTRAINT configuracao_unica CHECK (id = 1)
) ENGINE=InnoDB;

CREATE TABLE reservas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT UNSIGNED NOT NULL,
    subgrupo_id INT UNSIGNED NOT NULL,
    subgrupos_ids VARCHAR(255) DEFAULT NULL,
    area_id INT UNSIGNED DEFAULT NULL,
    areas_ids VARCHAR(255) DEFAULT NULL,
    todas_areas TINYINT(1) NOT NULL DEFAULT 0,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    dia_inteiro TINYINT(1) NOT NULL DEFAULT 0,
    criado_por VARCHAR(120) DEFAULT NULL,
    recorrencia VARCHAR(20) NOT NULL DEFAULT 'nenhuma',
    serie_id CHAR(36) DEFAULT NULL,
    status ENUM('ativa','cancelado') NOT NULL DEFAULT 'ativa',
    cancelado_por VARCHAR(120) DEFAULT NULL,
    motivo_cancelamento TEXT DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id),
    CONSTRAINT fk_reserva_subgrupo FOREIGN KEY (subgrupo_id) REFERENCES subgrupos(id),
    CONSTRAINT fk_reserva_area FOREIGN KEY (area_id) REFERENCES areas(id),
    KEY idx_reserva_area_horario (area_id, data_inicio, data_fim),
    KEY idx_reserva_status (status)
) ENGINE=InnoDB;

CREATE TABLE demo_runtime (
    id TINYINT UNSIGNED PRIMARY KEY,
    iniciado_em DATETIME NOT NULL,
    reservas_snapshot LONGTEXT NOT NULL,
    areas_snapshot LONGTEXT NOT NULL,
    grupos_snapshot LONGTEXT NOT NULL,
    subgrupos_snapshot LONGTEXT NOT NULL,
    configuracoes_snapshot LONGTEXT NOT NULL
) ENGINE=InnoDB;

INSERT INTO usuarios (usuario, senha_hash, nome, tipo) VALUES
('admin', '$2y$12$zf4VzxJTAHgD0moVL9IaguykvAukKW/qN7q0O/2h0i0P5JZdytpzy', 'Administrador', 'admin');

INSERT INTO areas (nome, lugares) VALUES
('Fonte', '32'), ('Teatro', '50'), ('Jogos', '70'), ('Pista/Parque', '30'), ('Quadras', '+ de 50'), ('Descanso', '20');

INSERT INTO grupos (id, nome, tempo_padrao, cor, especial) VALUES
(1, 'Educação Infantil', 45, '#e76f51', 0),
(2, 'Fund 1', 45, '#457b9d', 0),
(3, 'Fund 2', 45, '#2a9d8f', 0),
(4, 'Ensino Médio', 50, '#9b5de5', 0),
(5, 'Extras', 60, '#f4a261', 0),
(6, 'MIL', 45, '#06a6d4', 0),
(7, 'Reservado Institucional', 60, '#223764', 1);

INSERT INTO subgrupos (grupo_id, nome) VALUES
(1,'G1'),(1,'G2'),(1,'G3'),(1,'G4'),(1,'G5'),
(2,'F1'),(2,'F2'),(2,'F3'),(2,'F4'),(2,'F5'),
(3,'F6'),(3,'F7'),(3,'F8'),(3,'F9'),
(4,'1 - EM'),(4,'2 - EM'),(4,'3 - EM'),
(5,'Futebol'),(5,'Judô'),(5,'Balé'),(5,'Natação'),(5,'Integral'),(5,'Teatro'),(5,'Coral'),(5,'Violão'),
(6,'Re-C 1'),(6,'Re-C 2'),(6,'Re-C 3'),(6,'Elementary 2'),(6,'Elementary 3'),(6,'Elementary 4'),(6,'Elementary 5'),(6,'Middle 1'),(6,'Middle 2'),(6,'Middle 3'),(6,'High School 9'),(6,'High School 1'),(6,'High School 2'),(6,'High School 3'),
(7,'Reserva Especial');

INSERT INTO configuracoes (id, dias_permitidos, hora_inicio, hora_fim, titulo_site) VALUES (1, '1,2,3,4,5', '08:00:00', '20:00:00', 'Reserva de Áreas');
