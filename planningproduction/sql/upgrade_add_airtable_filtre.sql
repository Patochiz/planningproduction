-- Migration : Ajout du champ airtable_filtre dans llx_planningproduction_matieres
-- Filtre Airtable de la matière (JSON : IDs de champ => IDs de choix), lecture seule côté Airtable
-- Exemple : {"fldIAkoFSsNkz2rwU":["selEJle2CFa6R3Sr6"],"flduA9iBzQnYvSq8H":["selOb8VIgmc46XTdp"]}
-- À appliquer une seule fois en base de données (phpMyAdmin), ou désactiver puis réactiver le module
-- Note : "IF NOT EXISTS" est supporté par MariaDB ; sous MySQL, retirer "IF NOT EXISTS"

ALTER TABLE llx_planningproduction_matieres
ADD COLUMN IF NOT EXISTS airtable_filtre text DEFAULT NULL;
