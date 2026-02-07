-- Migration pour corriger les contraintes de clés étrangères
-- Ajoute ON DELETE CASCADE pour permettre la suppression des commandes planifiées
-- Date: 2025-10-02

-- Supprimer les anciennes contraintes
ALTER TABLE llx_planningproduction_planning 
DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_commande;

ALTER TABLE llx_planningproduction_planning 
DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_commandedet;

ALTER TABLE llx_planningproduction_planning 
DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_user_creat;

-- Recréer les contraintes avec ON DELETE CASCADE pour fk_commande et fk_commandedet
-- Pour fk_user_creat, on garde SET NULL car on ne veut pas perdre les plannings si un utilisateur est supprimé

ALTER TABLE llx_planningproduction_planning 
ADD CONSTRAINT fk_planningproduction_planning_fk_commande 
FOREIGN KEY (fk_commande) REFERENCES llx_commande(rowid) 
ON DELETE CASCADE;

ALTER TABLE llx_planningproduction_planning 
ADD CONSTRAINT fk_planningproduction_planning_fk_commandedet 
FOREIGN KEY (fk_commandedet) REFERENCES llx_commandedet(rowid) 
ON DELETE CASCADE;

ALTER TABLE llx_planningproduction_planning 
ADD CONSTRAINT fk_planningproduction_planning_fk_user_creat 
FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid) 
ON DELETE SET NULL;
