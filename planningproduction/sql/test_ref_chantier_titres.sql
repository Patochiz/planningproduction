-- ============================================================================
-- TEST SQL - Validation du ref_chantier des titres
-- Module Planning Production
-- ============================================================================

-- ============================================================================
-- 1. VÉRIFIER LA STRUCTURE D'UNE COMMANDE AVEC TITRES
-- ============================================================================
-- Remplacer [ID_COMMANDE] par l'ID d'une vraie commande
-- Cette requête affiche toutes les lignes avec leur rang et ref_chantier

SELECT 
    cd.rowid as ligne_id,
    cd.rang,
    cd.product_type,
    CASE 
        WHEN cd.product_type = 9 THEN '📁 TITRE'
        WHEN cd.product_type = 0 THEN '📦 PRODUIT'
        ELSE '❓ AUTRE'
    END as type_ligne,
    cd.description,
    cd_ef.ref_chantier as ref_chantier_ligne,
    -- Sous-requête pour trouver le titre parent
    (SELECT cd_titre_ef.ref_chantier
     FROM llx_commandedet cd_titre
     LEFT JOIN llx_commandedet_extrafields cd_titre_ef ON cd_titre.rowid = cd_titre_ef.fk_object
     WHERE cd_titre.fk_commande = cd.fk_commande
       AND cd_titre.product_type = 9
       AND cd_titre.rang < cd.rang
     ORDER BY cd_titre.rang DESC
     LIMIT 1
    ) as titre_parent_ref_chantier
FROM llx_commandedet cd
LEFT JOIN llx_commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object
WHERE cd.fk_commande = [ID_COMMANDE]
ORDER BY cd.rang ASC;

-- ============================================================================
-- 2. TROUVER DES COMMANDES AVEC TITRES
-- ============================================================================
-- Cette requête trouve les commandes qui ont au moins un titre (product_type=9)

SELECT DISTINCT
    c.rowid as commande_id,
    c.ref as commande_ref,
    s.nom as client,
    COUNT(DISTINCT cd.rowid) as nb_lignes,
    COUNT(DISTINCT CASE WHEN cd.product_type = 9 THEN cd.rowid END) as nb_titres,
    COUNT(DISTINCT CASE WHEN cd.product_type = 0 THEN cd.rowid END) as nb_produits
FROM llx_commande c
INNER JOIN llx_commandedet cd ON c.rowid = cd.fk_commande
LEFT JOIN llx_societe s ON c.fk_soc = s.rowid
WHERE c.fk_statut = 1
  AND c.facture = 0
  AND cd.product_type IN (0, 9)
GROUP BY c.rowid
HAVING nb_titres > 0
ORDER BY c.date_creation DESC
LIMIT 10;

-- ============================================================================
-- 3. VÉRIFIER QUE LES TITRES ONT BIEN ref_chantier REMPLI
-- ============================================================================
-- Trouve les titres qui n'ont pas de ref_chantier (à corriger)

SELECT 
    c.ref as commande_ref,
    cd.rowid as ligne_id,
    cd.rang,
    cd.description as titre_description,
    CASE 
        WHEN cd_ef.ref_chantier IS NULL THEN '❌ NULL'
        WHEN cd_ef.ref_chantier = '' THEN '❌ VIDE'
        ELSE '✅ OK'
    END as statut_ref_chantier,
    cd_ef.ref_chantier
FROM llx_commande c
INNER JOIN llx_commandedet cd ON c.rowid = cd.fk_commande
LEFT JOIN llx_commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object
WHERE cd.product_type = 9
  AND (cd_ef.ref_chantier IS NULL OR cd_ef.ref_chantier = '')
ORDER BY c.date_creation DESC
LIMIT 20;

-- ============================================================================
-- 4. TEST COMPLET : SIMULATION DE LA REQUÊTE DU MODULE
-- ============================================================================
-- Cette requête simule exactement ce que fait getCardsByStatus()
-- Remplacer [ID_COMMANDE] par l'ID d'une commande de test

SELECT 
    cd.rowid as commandedet_id,
    c.rowid as commande_id,
    c.ref as commande_ref,
    c.fk_soc,
    s.nom as societe_nom,
    cd.description as produit_description,
    cd.qty,
    cd.product_type,
    p.ref as produit_ref,
    p.label as produit_label,
    -- Extrafields de commande
    c_ef.version,
    c_ef.ref_chantierfp,
    c_ef.delai_liv,
    c_ef.statut_ar,
    -- Extrafields de ligne
    cd_ef.matiere,
    cd_ef.statut_mp,
    cd_ef.statut_prod,
    cd_ef.postlaquage,
    -- NOUVEAU : ref_chantier du titre parent
    (SELECT cd_titre_ef.ref_chantier
     FROM llx_commandedet cd_titre
     LEFT JOIN llx_commandedet_extrafields cd_titre_ef ON cd_titre.rowid = cd_titre_ef.fk_object
     WHERE cd_titre.fk_commande = cd.fk_commande
       AND cd_titre.product_type = 9
       AND cd_titre.rang < cd.rang
     ORDER BY cd_titre.rang DESC
     LIMIT 1
    ) as titre_ref_chantier
FROM llx_commande c
INNER JOIN llx_commandedet cd ON c.rowid = cd.fk_commande
LEFT JOIN llx_societe s ON c.fk_soc = s.rowid
LEFT JOIN llx_product p ON cd.fk_product = p.rowid
LEFT JOIN llx_commande_extrafields c_ef ON c.rowid = c_ef.fk_object
LEFT JOIN llx_commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object
WHERE c.fk_statut = 1
  AND c.facture = 0
  AND p.finished = 1
  AND c.rowid = [ID_COMMANDE]
ORDER BY cd.rang ASC;

-- ============================================================================
-- 5. ANALYSE DÉTAILLÉE D'UNE COMMANDE COMPLEXE
-- ============================================================================
-- Pour comprendre comment les titres structurent une commande
-- Remplacer [ID_COMMANDE] par l'ID d'une commande

SELECT 
    cd.rang,
    cd.product_type,
    CASE 
        WHEN cd.product_type = 9 THEN CONCAT('🏷️  TITRE: ', cd.description)
        WHEN cd.product_type = 0 THEN CONCAT('   📦 Produit: ', COALESCE(p.label, cd.description))
        ELSE CONCAT('   ❓ Autre: ', cd.description)
    END as ligne,
    cd_ef.ref_chantier as ref_chantier_propre,
    (SELECT CONCAT('➡️ ', cd_titre_ef.ref_chantier)
     FROM llx_commandedet cd_titre
     LEFT JOIN llx_commandedet_extrafields cd_titre_ef ON cd_titre.rowid = cd_titre_ef.fk_object
     WHERE cd_titre.fk_commande = cd.fk_commande
       AND cd_titre.product_type = 9
       AND cd_titre.rang < cd.rang
     ORDER BY cd_titre.rang DESC
     LIMIT 1
    ) as ref_chantier_herite
FROM llx_commandedet cd
LEFT JOIN llx_commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object
LEFT JOIN llx_product p ON cd.fk_product = p.rowid
WHERE cd.fk_commande = [ID_COMMANDE]
ORDER BY cd.rang ASC;

-- ============================================================================
-- 6. STATISTIQUES GLOBALES SUR LES TITRES
-- ============================================================================
-- Donne une vue d'ensemble de l'utilisation des titres

SELECT 
    'Total commandes validées' as metrique,
    COUNT(DISTINCT c.rowid) as valeur
FROM llx_commande c
WHERE c.fk_statut = 1 AND c.facture = 0

UNION ALL

SELECT 
    'Commandes avec titres',
    COUNT(DISTINCT c.rowid)
FROM llx_commande c
INNER JOIN llx_commandedet cd ON c.rowid = cd.fk_commande
WHERE c.fk_statut = 1 
  AND c.facture = 0
  AND cd.product_type = 9

UNION ALL

SELECT 
    'Total titres (product_type=9)',
    COUNT(cd.rowid)
FROM llx_commande c
INNER JOIN llx_commandedet cd ON c.rowid = cd.fk_commande
WHERE c.fk_statut = 1 
  AND c.facture = 0
  AND cd.product_type = 9

UNION ALL

SELECT 
    'Titres avec ref_chantier rempli',
    COUNT(cd.rowid)
FROM llx_commande c
INNER JOIN llx_commandedet cd ON c.rowid = cd.fk_commande
LEFT JOIN llx_commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object
WHERE c.fk_statut = 1 
  AND c.facture = 0
  AND cd.product_type = 9
  AND cd_ef.ref_chantier IS NOT NULL
  AND cd_ef.ref_chantier != ''

UNION ALL

SELECT 
    'Produits manufacturés sous titres',
    COUNT(cd.rowid)
FROM llx_commande c
INNER JOIN llx_commandedet cd ON c.rowid = cd.fk_commande
LEFT JOIN llx_product p ON cd.fk_product = p.rowid
WHERE c.fk_statut = 1 
  AND c.facture = 0
  AND p.finished = 1
  AND EXISTS (
      SELECT 1 
      FROM llx_commandedet cd_titre
      WHERE cd_titre.fk_commande = cd.fk_commande
        AND cd_titre.product_type = 9
        AND cd_titre.rang < cd.rang
  );

-- ============================================================================
-- NOTES D'UTILISATION
-- ============================================================================
-- 
-- 1. Remplacer [ID_COMMANDE] par un vrai ID de commande
-- 2. Exécuter les requêtes dans phpMyAdmin ou un autre outil SQL
-- 3. Vérifier que :
--    - Les titres (product_type=9) sont bien identifiés
--    - Leur ref_chantier est rempli
--    - Les produits récupèrent bien le ref_chantier du titre au-dessus
-- 4. Si un produit n'a pas de titre parent, titre_parent_ref_chantier sera NULL
-- 
-- ============================================================================
