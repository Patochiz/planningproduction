-- Copyright (C) 2024 Patrick Delcroix
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- ============================================================================
-- Indexes et clés étrangères pour llx_planningproduction_planning
-- VERSION AVEC AUTO-CORRECTION (supprime et recrée les contraintes)
-- ============================================================================

-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_planningproduction_planning ADD INDEX idx_planningproduction_planning_rowid (rowid);
ALTER TABLE llx_planningproduction_planning ADD INDEX idx_planningproduction_planning_fk_commande (fk_commande);
ALTER TABLE llx_planningproduction_planning ADD INDEX idx_planningproduction_planning_fk_commandedet (fk_commandedet);
ALTER TABLE llx_planningproduction_planning ADD INDEX idx_planningproduction_planning_semaine_annee (semaine, annee);
ALTER TABLE llx_planningproduction_planning ADD INDEX idx_planningproduction_planning_status (status);
-- END MODULEBUILDER INDEXES

-- BEGIN MODULEBUILDER FOREIGN KEYS

-- Supprimer les anciennes contraintes si elles existent (pour permettre la mise à jour)
ALTER TABLE llx_planningproduction_planning DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_user_creat;
ALTER TABLE llx_planningproduction_planning DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_commande;
ALTER TABLE llx_planningproduction_planning DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_commandedet;

-- Recréer les contraintes avec les bons paramètres

-- ON DELETE SET NULL pour fk_user_creat : on garde les plannings même si l'utilisateur est supprimé
ALTER TABLE llx_planningproduction_planning 
ADD CONSTRAINT fk_planningproduction_planning_fk_user_creat 
FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid) 
ON DELETE SET NULL;

-- ON DELETE CASCADE pour fk_commande : si la commande est supprimée, on supprime aussi la planification
ALTER TABLE llx_planningproduction_planning 
ADD CONSTRAINT fk_planningproduction_planning_fk_commande 
FOREIGN KEY (fk_commande) REFERENCES llx_commande(rowid) 
ON DELETE CASCADE;

-- ON DELETE CASCADE pour fk_commandedet : si la ligne de commande est supprimée, on supprime aussi la planification
-- CRITIQUE : Sans cette cascade, impossible de supprimer les lignes de commandes planifiées !
ALTER TABLE llx_planningproduction_planning 
ADD CONSTRAINT fk_planningproduction_planning_fk_commandedet 
FOREIGN KEY (fk_commandedet) REFERENCES llx_commandedet(rowid) 
ON DELETE CASCADE;

-- END MODULEBUILDER FOREIGN KEYS
