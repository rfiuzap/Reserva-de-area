USE reserva_area;

ALTER TABLE usuarios
    ADD COLUMN tipo ENUM('admin','normal') NOT NULL DEFAULT 'normal' AFTER nome;
UPDATE usuarios SET tipo = 'admin' WHERE usuario = 'admin';

ALTER TABLE reservas
    ADD COLUMN criado_por VARCHAR(120) DEFAULT NULL AFTER dia_inteiro;
