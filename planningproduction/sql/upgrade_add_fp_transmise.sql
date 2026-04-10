-- Migration : Ajout du champ fp_transmise dans commande_extrafields
-- Ce champ indique si la Fiche de Production a été transmise à l'atelier
-- Valeurs : 'oui' / 'non' (défaut 'non')
-- À appliquer une seule fois en base de données

ALTER TABLE llx_commande_extrafields
ADD COLUMN IF NOT EXISTS fp_transmise varchar(3) DEFAULT 'non';
