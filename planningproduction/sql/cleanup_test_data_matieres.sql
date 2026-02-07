-- Copyright (C) 2024 Patrick Delcroix
--
-- Script de nettoyage des données de test pour les matières premières
-- Ce script supprime les données de test ajoutées par test_data_matieres_order.sql

-- ATTENTION: Vérifiez bien que vous voulez supprimer ces données
-- Ce script supprime uniquement les données de test, pas les vraies données

-- Supprimer les matières premières de test (basé sur les codes spécifiques)
DELETE FROM llx_planningproduction_matieres WHERE code_mp IN (
    'ACIER INOX 304',
    'ALUMINIUM 6061', 
    'CUIVRE C101',
    'BRONZE PHOSPHOREUX',
    'LAITON CZ121',
    'ACIER CARBONE S235',
    'TITANE GRADE 2',
    'INCONEL 625',
    'HASTELLOY C276',
    'DUPLEX 2205',
    '400 BLANC',
    '400 NOIR',
    '400 ROUGE', 
    '400 BLEU',
    '500 STANDARD',
    '500 PREMIUM',
    '600 ÉCONOM.',
    'TUBE Ø20x2',
    'TUBE Ø25x2',
    'TUBE Ø30x3',
    'STOCK CRITIQUE 1',
    'STOCK CRITIQUE 2',
    'STOCK ZERO',
    'MATIÈRE SPÉCIALE É',
    'MAT-AVEC-TIRETS',
    'MAT_AVEC_UNDERSCORES',
    'MAT (PARENTHÈSES)',
    'MAT&SYMBOLES#123'
);

-- Réorganiser les ordres après suppression (optionnel)
-- Cette partie remet l'ordre séquentiel pour les matières restantes
SET @order_counter = 0;
UPDATE llx_planningproduction_matieres 
SET ordre = (@order_counter := @order_counter + 1)
ORDER BY ordre ASC, code_mp ASC;

-- Afficher un résumé
SELECT 
    COUNT(*) as 'Matières restantes',
    MIN(ordre) as 'Ordre min',
    MAX(ordre) as 'Ordre max'
FROM llx_planningproduction_matieres;
