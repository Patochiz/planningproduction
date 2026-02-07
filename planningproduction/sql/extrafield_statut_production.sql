-- Script SQL pour ajouter l'extrafield statut_production aux lignes de commande
-- À exécuter après l'installation du module ou via l'interface Dolibarr

-- 1. Ajouter la colonne statut_production si elle n'existe pas déjà
ALTER TABLE llx_commandedet_extrafields 
ADD COLUMN IF NOT EXISTS statut_production varchar(50) DEFAULT 'À PRODUIRE' 
COMMENT 'Statut de production de la ligne de commande';

-- 2. Mettre à jour les valeurs par défaut pour les lignes existantes sans statut
UPDATE llx_commandedet_extrafields 
SET statut_production = 'À PRODUIRE' 
WHERE statut_production IS NULL OR statut_production = '';

-- 3. Note : Pour créer l'extrafield via l'interface Dolibarr (recommandé) :
-- Administration > Configuration > Extrafields > Lignes de commande client
-- - Nom technique : statut_production
-- - Libellé : Statut de production  
-- - Type : Liste de sélection (select)
-- - Valeurs possibles : À PRODUIRE,EN COURS,À TERMINER,BON POUR EXPÉDITION
-- - Valeur par défaut : À PRODUIRE
-- - Visible sur liste : Oui
-- - Éditable : Oui
