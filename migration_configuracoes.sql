USE reserva_area;

ALTER TABLE configuracoes
    ADD COLUMN titulo_site VARCHAR(120) NOT NULL DEFAULT 'Reserva de Áreas' AFTER hora_fim,
    ADD COLUMN logo VARCHAR(255) DEFAULT NULL AFTER titulo_site;
