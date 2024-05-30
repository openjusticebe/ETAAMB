-- 02/11/21 : add types order
ALTER TABLE types ADD `ord` tinyint(4) unsigned NULL;
CREATE INDEX types_ord ON types(ord);
UPDATE types SET `ord` = NULL;
UPDATE types SET `ord` = 200 WHERE type_nl = 'document' and type_fr = 'document'; 
UPDATE types SET `ord` = 150 WHERE type_nl LIKE 'benoeming%'; 
UPDATE types SET `ord` = 156 WHERE type_nl LIKE 'aanwerving%'; 
UPDATE types SET `ord` = 153 WHERE type_nl LIKE 'vacante%'; 
UPDATE types SET `ord` = 49 WHERE type_nl LIKE 'vergunning%'; 
UPDATE types SET `ord` = 45 WHERE type_nl LIKE 'gewestplan'; 
UPDATE types SET `ord` = 40 WHERE type_nl LIKE 'lijst'; 
UPDATE types SET `ord` = 38 WHERE type_nl LIKE 'bekendmaking%'; 
UPDATE types SET `ord` = 37 WHERE type_nl LIKE 'erkenning'; 
UPDATE types SET `ord` = 35 WHERE type_nl LIKE 'bericht'; 
UPDATE types SET `ord` = 30 WHERE type_nl LIKE 'omzendbrief'; 
UPDATE types SET `ord` = 26 WHERE type_nl LIKE 'overeenkomst%'; 
UPDATE types SET `ord` = 24 WHERE type_nl LIKE 'besluit%' or type_fr LIKE 'arrêté%'; 
UPDATE types SET `ord` = 21 WHERE type_nl LIKE 'beschikking%'; 
UPDATE types SET `ord` = 21 WHERE type_fr LIKE 'avis%'; 
UPDATE types SET `ord` = 18 WHERE type_nl LIKE 'arrest%' OR type_fr LIKE 'arrêt %'; 
UPDATE types SET `ord` = 20 WHERE type_nl = 'decreet' OR type_nl LIKE 'decreet%'; 
UPDATE types SET `ord` = 15 WHERE type_nl = 'ministerieel besluit' OR type_fr = 'arrêté ministeriel';
UPDATE types SET `ord` = 12 WHERE type_nl = 'koninklijk besluit'; 
UPDATE types SET `ord` = 6 WHERE type_nl LIKE 'burgerlijk%'; 
UPDATE types SET `ord` = 4 WHERE type_nl = 'programmawet'; 
UPDATE types SET `ord` = 3 WHERE type_nl = 'wet'; 
UPDATE types SET `ord` = 2 WHERE type_nl = 'wijziging aan de grondwet'; 
UPDATE types SET `ord` = 100 WHERE ord IS NULL; 

-- 30/05/24 : add value raw_source_version
ALTER TABLE raw_pages ADD `raw_source_version` varchar(255);
UPDATE raw_pages SET `raw_source_version` = 'original';
