-- Permite que uma reserva ocupe várias áreas (lista separada por vírgula).
-- Rodar no banco já selecionado: mysql -u USUARIO -p BANCO < migration_areas_ids.sql
ALTER TABLE reservas
    ADD COLUMN areas_ids VARCHAR(255) DEFAULT NULL AFTER area_id;
