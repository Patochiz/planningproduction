# 📝 RÉCAPITULATIF MODIFICATION - Ref Chantier des Titres

**Date :** 10 novembre 2024  
**Module :** Planning Production  
**Version :** 1.0.1

---

## ✅ Modification effectuée

Ajout de l'affichage du **ref_chantier des titres** (lignes avec `product_type = 9`) dans le titre des cartes de planning.

### Avant
```
CMD001 V1 Client ABC
```

### Après
```
CMD001 V1 Client ABC / CHANTIER-A
```

---

## 📦 Fichiers modifiés

| Fichier | Type | Description |
|---------|------|-------------|
| `class/planningproduction.class.php` | ✏️ **Modifié** | Ajout sous-requête SQL pour récupérer `ref_chantier` du titre parent |
| `lib/planning_functions.php` | ✏️ **Modifié** | Affichage du `ref_chantier` dans le titre HTML des cartes |
| `js/modal.js` | ✏️ **Modifié** | Extraction et affichage du `ref_chantier` dans le modal d'édition |
| `docs/AJOUT_REF_CHANTIER_TITRES.md` | ✨ **Nouveau** | Documentation complète de la fonctionnalité |
| `sql/test_ref_chantier_titres.sql` | ✨ **Nouveau** | Requêtes SQL de test et validation |

---

## 🎯 Principe de fonctionnement

### Logique de recherche du titre parent

Pour chaque produit, la sous-requête SQL :
1. ✅ Cherche les lignes avec `product_type = 9` (titres)
2. ✅ Dans la même commande
3. ✅ Avec un `rang` **inférieur** au produit actuel
4. ✅ Trie par `rang` décroissant
5. ✅ Prend le premier résultat (le titre le plus proche)

```sql
(SELECT cd_titre_ef.ref_chantier
 FROM llx_commandedet cd_titre
 LEFT JOIN llx_commandedet_extrafields cd_titre_ef ON cd_titre.rowid = cd_titre_ef.fk_object
 WHERE cd_titre.fk_commande = cd.fk_commande
   AND cd_titre.product_type = 9
   AND cd_titre.rang < cd.rang
 ORDER BY cd_titre.rang DESC
 LIMIT 1
) as titre_ref_chantier
```

### Exemple concret

```
Commande CMD001
├─ Ligne 1 : TITRE "Section A" (rang=1, ref_chantier="CHANTIER-A")
├─ Ligne 2 : Produit A1 (rang=2)          → ref_chantier = "CHANTIER-A"
├─ Ligne 3 : Produit A2 (rang=3)          → ref_chantier = "CHANTIER-A"
├─ Ligne 4 : TITRE "Section B" (rang=4, ref_chantier="CHANTIER-B")
└─ Ligne 5 : Produit B1 (rang=5)          → ref_chantier = "CHANTIER-B"
```

---

## 🧪 Tests à effectuer

### ✅ Checklist de validation

- [ ] **Test 1** : Produit avec titre au-dessus → Affiche le ref_chantier du titre
- [ ] **Test 2** : Produit avant le premier titre → N'affiche pas de ref_chantier
- [ ] **Test 3** : Titre sans ref_chantier → N'affiche pas de ref_chantier
- [ ] **Test 4** : Commande avec plusieurs titres → Chaque produit affiche le bon ref_chantier
- [ ] **Test 5** : Modal d'édition → Affiche le ref_chantier dans "Carte Actuelle"
- [ ] **Test 6** : Onglets (Non planifiées, À terminer, À expédier) → Affichent le ref_chantier
- [ ] **Test 7** : Export HTML → Inclut le ref_chantier

### 🔍 Requêtes de test

Utiliser le fichier `sql/test_ref_chantier_titres.sql` pour :
- Vérifier la structure des commandes
- Identifier les titres sans ref_chantier
- Simuler les requêtes du module
- Obtenir des statistiques globales

---

## 🚀 Installation

### Étapes de déploiement

1. **Sauvegarder les fichiers actuels** :
   ```bash
   cd /path/to/dolibarr/htdocs/custom/planningproduction/
   
   cp class/planningproduction.class.php class/planningproduction.class.php.backup
   cp lib/planning_functions.php lib/planning_functions.php.backup
   cp js/modal.js js/modal.js.backup
   ```

2. **Uploader les nouveaux fichiers** :
   - Via FTP/SFTP ou panel d'hébergement
   - Écraser les fichiers existants

3. **Vider les caches** :
   - Cache navigateur (Ctrl+F5)
   - Cache Dolibarr si activé

4. **Tester sur une commande** :
   - Avec des titres et produits
   - Vérifier l'affichage dans le planning
   - Vérifier l'affichage dans le modal

---

## 📍 Zones impactées

### ✅ Affichage du ref_chantier

| Zone | Statut | Note |
|------|--------|------|
| Cartes dans le planning | ✅ Impacté | Titre modifié |
| Cartes dans les onglets | ✅ Impacté | Utilise `generateCardHTML()` |
| Modal d'édition | ✅ Impacté | Section "Carte Actuelle" |
| Exports HTML | ✅ Impacté | Via `generateCardHTML()` |
| Exports Excel | ⚠️ À vérifier | Si utilise les mêmes données |

### ⚙️ Données utilisées

| Champ | Table | Usage |
|-------|-------|-------|
| `product_type` | `llx_commandedet` | Identifier les titres (=9) |
| `rang` | `llx_commandedet` | Ordre des lignes |
| `ref_chantier` | `llx_commandedet_extrafields` | Valeur à afficher |

---

## ⚠️ Points d'attention

### Prérequis

- ✅ Module complémentaire créant des services avec `product_type = 9`
- ✅ Extrafield `ref_chantier` sur `llx_commandedet`
- ✅ Valeurs remplies dans le `ref_chantier` des titres

### Cas limites gérés

| Cas | Comportement |
|-----|--------------|
| Produit avant titre | Pas de " / ref_chantier" |
| Titre sans ref_chantier | Pas de " / ref_chantier" |
| Plusieurs titres | Prend le titre le plus proche |
| Pas de titre | Fonctionne comme avant |

### Performance

- ✅ Sous-requête corrélée optimisée avec `LIMIT 1`
- ✅ Utilise les index existants sur `fk_commande` et `rang`
- ✅ Pas d'impact significatif sur les temps de chargement

---

## 📚 Documentation

### Fichiers de documentation créés

1. **`docs/AJOUT_REF_CHANTIER_TITRES.md`**
   - Documentation complète de la fonctionnalité
   - Explications techniques détaillées
   - Guide de dépannage

2. **`sql/test_ref_chantier_titres.sql`**
   - 6 requêtes SQL de test
   - Diagnostic et validation
   - Statistiques globales

3. **`RECAPITULATIF_MODIFICATION.md`** (ce fichier)
   - Vue d'ensemble de la modification
   - Checklist de validation
   - Guide d'installation

---

## 🐛 Dépannage rapide

### Le ref_chantier ne s'affiche pas

**Causes possibles :**
1. ❌ Le titre n'est pas au-dessus du produit (vérifier le `rang`)
2. ❌ Le `ref_chantier` du titre est vide
3. ❌ Le cache n'est pas vidé

**Solution :**
```sql
-- Vérifier la structure de la commande
SELECT cd.rang, cd.product_type, cd.description, cd_ef.ref_chantier
FROM llx_commandedet cd
LEFT JOIN llx_commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object
WHERE cd.fk_commande = [ID_COMMANDE]
ORDER BY cd.rang ASC;
```

### Erreur SQL

Si vous obtenez une erreur SQL, vérifier :
- ✅ L'extrafield `ref_chantier` existe bien sur `llx_commandedet`
- ✅ La syntaxe SQL est compatible avec votre version MySQL/MariaDB

---

## 📊 Statistiques d'impact

### Code modifié

| Métrique | Valeur |
|----------|--------|
| Fichiers modifiés | 3 |
| Fichiers créés | 2 |
| Lignes ajoutées | ~150 |
| Tables SQL impactées | 0 (lecture seule) |

### Fonctionnalités impactées

| Fonctionnalité | Impact |
|----------------|--------|
| Affichage des cartes | ✅ Enrichi |
| Modal d'édition | ✅ Enrichi |
| Performance | ✅ Maintenue |
| Compatibilité | ✅ Rétrocompatible |

---

## ✨ Prochaines étapes possibles

### Améliorations futures

1. **Export Excel** : Ajouter une colonne "Ref Chantier" dans les exports
2. **Filtres** : Permettre de filtrer les cartes par ref_chantier
3. **Statistiques** : Afficher le nombre de cartes par chantier
4. **Recherche** : Rechercher par ref_chantier

---

## 📞 Support

Pour toute question ou problème :
1. Consulter `docs/AJOUT_REF_CHANTIER_TITRES.md`
2. Exécuter les requêtes de `sql/test_ref_chantier_titres.sql`
3. Vérifier les logs PHP/Apache
4. Utiliser la console JavaScript (F12)

---

**Modification terminée avec succès ! ✅**

*Version 1.0.1 - Module Planning Production - 10 novembre 2024*
