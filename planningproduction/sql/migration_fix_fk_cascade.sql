-- ============================================================================
-- Migration pour corriger les contraintes de clés étrangères
-- Ajoute ON DELETE CASCADE pour permettre la suppression des commandes planifiées
-- 
-- À exécuter via phpMyAdmin ou en ligne de commande MySQL
-- Date: 2025-01-02
-- ============================================================================

-- 1. Supprimer les anciennes contraintes FK (si elles existent sans CASCADE)
ALTER TABLE llx_planningproduction_planning DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_commande;
ALTER TABLE llx_planningproduction_planning DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_commandedet;
ALTER TABLE llx_planningproduction_planning DROP FOREIGN KEY IF EXISTS fk_planningproduction_planning_fk_user_creat;

-- 2. Recréer les contraintes FK avec les bonnes options

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
ALTER TABLE llx_planningproduction_planning 
ADD CONSTRAINT fk_planningproduction_planning_fk_commandedet 
FOREIGN KEY (fk_commandedet) REFERENCES llx_commandedet(rowid) 
ON DELETE CASCADE;

-- Afficher les contraintes pour vérification
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME,
    DELETE_RULE,
    UPDATE_RULE
FROM 
    information_schema.REFERENTIAL_CONSTRAINTS
WHERE 
    TABLE_NAME = 'llx_planningproduction_planning'
    AND CONSTRAINT_SCHEMA = DATABASE();
