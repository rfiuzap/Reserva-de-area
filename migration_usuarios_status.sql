USE reserva_area;

ALTER TABLE usuarios
    ADD COLUMN status ENUM('ativo','oculto') NOT NULL DEFAULT 'ativo' AFTER tipo;
