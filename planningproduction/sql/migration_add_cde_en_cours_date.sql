-- Migration: Ajout du champ cde_en_cours_date pour gestion des stocks à date fixe
-- Date: 2025-11-10
-- Description: Permet de figer les commandes en cours à une date donnée pour calcul du reste

ALTER TABLE llx_planningproduction_matieres 
ADD COLUMN cde_en_cours_date DOUBLE(24,8) DEFAULT 0 COMMENT 'Commandes en cours à une date fixe (éditable manuellement)';

-- Initialiser la valeur avec la valeur actuelle (optionnel, peut être fait via l'interface)
-- UPDATE llx_planningproduction_matieres SET cde_en_cours_date = 0 WHERE cde_en_cours_date IS NULL;

-- Note: Après cette migration, l'utilisateur devra cliquer sur "MàJ" pour chaque ligne
-- afin de synchroniser cde_en_cours_date avec la valeur calculée de cde_en_cours
