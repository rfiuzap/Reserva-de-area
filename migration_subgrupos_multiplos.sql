USE reserva_area;

ALTER TABLE reservas
    ADD COLUMN subgrupos_ids VARCHAR(255) DEFAULT NULL AFTER subgrupo_id;

UPDATE reservas SET subgrupos_ids = subgrupo_id WHERE subgrupos_ids IS NULL;
