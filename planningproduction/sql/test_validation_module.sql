-- ============================================================================
-- Script de test et validation - Module Planning Production
-- À exécuter APRÈS la migration pour vérifier que tout fonctionne
-- Date: 2025-01-02
-- ============================================================================

-- 1. VÉRIFIER LES CONTRAINTES FK
SELECT 
    '=== VERIFICATION DES CONTRAINTES FK ===' as test_section;

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
    AND CONSTRAINT_SCHEMA = DATABASE()
ORDER BY CONSTRAINT_NAME;

-- Résultat attendu:
-- fk_planningproduction_planning_fk_commande      | CASCADE  | RESTRICT
-- fk_planningproduction_planning_fk_commandedet   | CASCADE  | RESTRICT  
-- fk_planningproduction_planning_fk_user_creat    | SET NULL | RESTRICT

-- ============================================================================

-- 2. VÉRIFIER LA STRUCTURE DE LA TABLE PLANNING
SELECT 
    '=== STRUCTURE TABLE PLANNING ===' as test_section;

SHOW CREATE TABLE llx_planningproduction_planning;

-- ============================================================================

-- 3. VÉRIFIER LA STRUCTURE DE LA TABLE MATIÈRES
SELECT 
    '=== STRUCTURE TABLE MATIERES ===' as test_section;

SHOW CREATE TABLE llx_planningproduction_matieres;

-- ============================================================================

-- 4. COMPTER LES DONNÉES EXISTANTES
SELECT 
    '=== STATISTIQUES DES DONNEES ===' as test_section;

SELECT 
    'Plannings enregistrés' as type_donnee,
    COUNT(*) as nombre
FROM llx_planningproduction_planning
WHERE entity IN (SELECT entity FROM llx_const WHERE name = 'MAIN_INFO_SOCIETE_NOM' LIMIT 1)

UNION ALL

SELECT 
    'Matières premières configurées' as type_donnee,
    COUNT(*) as nombre
FROM llx_planningproduction_matieres
WHERE entity IN (SELECT entity FROM llx_const WHERE name = 'MAIN_INFO_SOCIETE_NOM' LIMIT 1)

UNION ALL

SELECT 
    'Commandes validées total' as type_donnee,
    COUNT(*) as nombre
FROM llx_commande
WHERE fk_statut = 1 AND facture = 0;

-- ============================================================================

-- 5. VÉRIFIER L'INDEX UNIQUE SUR CODE MP
SELECT 
    '=== INDEX SUR TABLE MATIERES ===' as test_section;

SHOW INDEX FROM llx_planningproduction_matieres
WHERE Key_name LIKE '%code_mp%';

-- Résultat attendu: Index uk_planningproduction_matieres_code_mp (UNIQUE)

-- ============================================================================

-- 6. TEST D'INTÉGRITÉ: Vérifier qu'il n'y a pas de doublons de code MP
SELECT 
    '=== TEST DOUBLONS CODE MP ===' as test_section;

SELECT 
    code_mp,
    entity,
    COUNT(*) as nb_occurrences
FROM llx_planningproduction_matieres
GROUP BY code_mp, entity
HAVING COUNT(*) > 1;

-- Résultat attendu: Aucune ligne (pas de doublon)

-- ============================================================================

-- 7. TEST D'INTÉGRITÉ: Vérifier les FK orphelines dans planning
SELECT 
    '=== TEST FK ORPHELINES PLANNING ===' as test_section;

-- Planning sans commande
SELECT 
    'Planning sans commande' as probleme,
    COUNT(*) as nombre_problemes
FROM llx_planningproduction_planning p
LEFT JOIN llx_commande c ON p.fk_commande = c.rowid
WHERE c.rowid IS NULL

UNION ALL

-- Planning sans ligne commande
SELECT 
    'Planning sans ligne commande' as probleme,
    COUNT(*) as nombre_problemes
FROM llx_planningproduction_planning p
LEFT JOIN llx_commandedet cd ON p.fk_commandedet = cd.rowid
WHERE cd.rowid IS NULL;

-- Résultat attendu: 0 problèmes pour chaque type

-- ============================================================================

-- 8. VÉRIFIER LES VALEURS ABERRANTES
SELECT 
    '=== TEST VALEURS ABERRANTES ===' as test_section;

-- Stocks négatifs (ne devrait pas exister)
SELECT 
    'Stocks négatifs' as probleme,
    COUNT(*) as nombre
FROM llx_planningproduction_matieres
WHERE stock < 0

UNION ALL

-- Semaines invalides
SELECT 
    'Semaines invalides (< 1 ou > 53)' as probleme,
    COUNT(*) as nombre
FROM llx_planningproduction_planning
WHERE semaine < 1 OR semaine > 53

UNION ALL

-- Années aberrantes
SELECT 
    'Années aberrantes (< 2020 ou > 2050)' as probleme,
    COUNT(*) as nombre
FROM llx_planningproduction_planning
WHERE annee < 2020 OR annee > 2050;

-- Résultat attendu: 0 pour chaque type de problème

-- ============================================================================

-- 9. VÉRIFIER LES PERMISSIONS MODULE
SELECT 
    '=== PERMISSIONS MODULE ===' as test_section;

SELECT 
    r.module,
    r.perms,
    r.subperms,
    r.libelle
FROM llx_rights_def r
WHERE r.module = 'planningproduction'
ORDER BY r.id;

-- Résultat attendu:
-- planningproduction | planning | read  | Lire les plannings
-- planningproduction | planning | write | Créer/modifier les plannings

-- ============================================================================

-- 10. RÉSUMÉ FINAL DE VALIDATION
SELECT 
    '=== RESUME FINAL ===' as test_section;

SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS 
              WHERE TABLE_NAME = 'llx_planningproduction_planning' 
              AND DELETE_RULE = 'CASCADE') >= 2 
        THEN '✅ OK'
        ELSE '❌ ERREUR'
    END as contraintes_fk,
    
    CASE 
        WHEN (SELECT COUNT(*) FROM llx_planningproduction_matieres 
              GROUP BY code_mp, entity 
              HAVING COUNT(*) > 1) IS NULL
        THEN '✅ OK'
        ELSE '❌ ERREUR - Doublons détectés'
    END as unicite_code_mp,
    
    CASE 
        WHEN (SELECT COUNT(*) FROM llx_planningproduction_planning p
              LEFT JOIN llx_commande c ON p.fk_commande = c.rowid
              WHERE c.rowid IS NULL) = 0
        THEN '✅ OK'
        ELSE '❌ ERREUR - FK orphelines'
    END as integrite_fk,
    
    CASE 
        WHEN (SELECT COUNT(*) FROM llx_planningproduction_matieres WHERE stock < 0) = 0
        THEN '✅ OK'
        ELSE '❌ ERREUR - Stocks négatifs'
    END as validation_stocks,
    
    CASE 
        WHEN (SELECT COUNT(*) FROM llx_rights_def WHERE module = 'planningproduction') >= 2
        THEN '✅ OK'
        ELSE '❌ ERREUR - Permissions manquantes'
    END as permissions_module;

-- ============================================================================

-- MESSAGE FINAL
SELECT 
    CONCAT(
        'Tests terminés. ',
        'Vérifiez que toutes les lignes ci-dessus affichent ✅ OK. ',
        'Si vous voyez des ❌ ERREUR, corrigez les problèmes avant de passer en production.'
    ) as message_final;

-- ============================================================================
-- FIN DU SCRIPT DE TEST
-- Si tous les tests sont OK, le module est prêt pour la production!
-- ============================================================================
