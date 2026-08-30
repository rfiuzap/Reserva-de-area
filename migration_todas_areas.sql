USE reserva_area;

ALTER TABLE reservas
    MODIFY area_id INT UNSIGNED NULL,
    ADD COLUMN todas_areas TINYINT(1) NOT NULL DEFAULT 0 AFTER area_id;

CREATE TEMPORARY TABLE reservas_globais_legadas AS
SELECT MIN(id) AS id
FROM reservas
WHERE recorrencia = 'todas_areas'
GROUP BY grupo_id, subgrupo_id, data_inicio, data_fim, dia_inteiro, status;

UPDATE reservas
SET area_id = NULL, todas_areas = 1, recorrencia = 'nenhuma'
WHERE id IN (SELECT id FROM reservas_globais_legadas);

DELETE FROM reservas
WHERE recorrencia = 'todas_areas'
  AND id NOT IN (SELECT id FROM reservas_globais_legadas);

DROP TEMPORARY TABLE reservas_globais_legadas;
