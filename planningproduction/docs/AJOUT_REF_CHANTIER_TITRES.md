# Ajout du Ref Chantier des Titres dans les Cartes - Documentation

**Date :** 10 novembre 2024  
**Module :** Planning Production  
**Fonctionnalité :** Affichage du ref_chantier des titres dans les cartes

---

## 📋 Vue d'ensemble

Cette modification enrichit l'affichage des cartes en ajoutant la **référence de chantier** qui provient des lignes de titre (services avec `product_type = 9`) dans les commandes.

### Avant
```
N° Commande V{version} Client
```

### Après
```
N° Commande V{version} Client / Ref chantier
```

---

## 🎯 Principe de fonctionnement

### 1. Identification du titre parent

Le titre parent d'un produit est déterminé par :
- C'est une ligne avec `product_type = 9` (service/titre)
- Elle est **au-dessus** du produit (basé sur le champ `rang`)
- On prend le titre avec le `rang` le plus proche inférieur au produit

**Exemple de structure de commande :**

```
Ligne 1 : TITRE 1 (product_type=9, rang=1, ref_chantier="CHANTIER-A")
Ligne 2 : Produit A (product_type=0, rang=2) → ref_chantier = "CHANTIER-A"
Ligne 3 : Produit B (product_type=0, rang=3) → ref_chantier = "CHANTIER-A"
Ligne 4 : TITRE 2 (product_type=9, rang=4, ref_chantier="CHANTIER-B")
Ligne 5 : Produit C (product_type=0, rang=5) → ref_chantier = "CHANTIER-B"
```

### 2. Cas particuliers gérés

| Cas | Comportement |
|-----|--------------|
| **Produit avant le premier titre** | Pas de ref_chantier affiché : `N° Commande V{version} Client` |
| **Titre sans ref_chantier** | Affichage : `N° Commande V{version} Client / -` |
| **Pas de titre dans la commande** | Pas de ref_chantier affiché |

---

## 🔧 Modifications techniques

### Fichiers modifiés

#### 1. **class/planningproduction.class.php**

**Méthodes modifiées :**
- `getCardsByStatus()` 
- `getPlannedCards()`

**Modification SQL :**  
Ajout d'une sous-requête corrélée pour récupérer le `ref_chantier` du titre parent :

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

**Logique :**
1. Cherche les lignes avec `product_type = 9` (titres)
2. Qui appartiennent à la même commande (`cd_titre.fk_commande = cd.fk_commande`)
3. Qui sont avant le produit actuel (`cd_titre.rang < cd.rang`)
4. Trie par rang décroissant et prend le premier (le plus proche)

**Ajout au tableau `$card` :**
```php
'titre_ref_chantier' => $obj->titre_ref_chantier ?: null
```

---

#### 2. **lib/planning_functions.php**

**Fonction modifiée :** `generateCardHTML()`

**Modification de l'affichage du titre :**

```php
// Avant
$html .= '<a href="' . $client_link . '" class="card-tiers" target="_blank">' . $card['client'] . '</a>';

// Après
$html .= '<a href="' . $client_link . '" class="card-tiers" target="_blank">' . $card['client'] . '</a>';

// NOUVEAU : Ajouter le ref_chantier du titre si présent
if (!empty($card['titre_ref_chantier'])) {
    $html .= ' / ' . htmlspecialchars($card['titre_ref_chantier']);
}
```

---

#### 3. **js/modal.js**

**Fonction modifiée :** `openEditModal()`

**Extraction du ref_chantier depuis le titre de la carte :**

```javascript
// Extraire le titre complet
const cardTitleElement = card.querySelector('.card-title');
let fullTitle = cardTitleElement ? cardTitleElement.textContent.trim() : '';

// Extraire le ref_chantier depuis le titre
let refChantierValue = '-';
if (fullTitle.includes(' / ')) {
    const parts = fullTitle.split(' / ');
    refChantierValue = parts.length > 1 ? parts[1].trim() : '-';
}
```

**Ajout de l'affichage dans le modal :**
```javascript
const elements = {
    'editCurrentTitle': clientValue,
    'editCurrentClient': refValue,
    'editCurrentRefChantier': refChantierValue,  // NOUVEAU
    'editCurrentOrder': productValue,
    // ...
};
```

---

## 📍 Où s'affiche le ref_chantier

### ✅ Zones impactées

1. **Cartes dans le planning** (timeline des semaines)
2. **Cartes dans les onglets** :
   - Non planifiées
   - À terminer
   - À expédier
3. **Modal d'édition de carte** (section "Carte Actuelle")
4. **Exports HTML** (via `generateCardHTML()`)

### 📝 Note importante

Le ref_chantier affiché provient toujours de l'**extrafield `ref_chantier` des lignes de titre** (product_type=9), **PAS** de l'extrafield `ref_chantierfp` de la commande.

---

## 🧪 Tests recommandés

### Test 1 : Produit avec titre au-dessus
```
Commande CMD001
├─ Ligne 1 : TITRE (rang=1, ref_chantier="CHANTIER-A")
└─ Ligne 2 : PRODUIT (rang=2)

Résultat attendu : CMD001 V1 Client / CHANTIER-A
```

### Test 2 : Produit avant le premier titre
```
Commande CMD002
├─ Ligne 1 : PRODUIT (rang=1)
└─ Ligne 2 : TITRE (rang=2, ref_chantier="CHANTIER-B")

Résultat attendu : CMD002 V1 Client
```

### Test 3 : Titre sans ref_chantier
```
Commande CMD003
├─ Ligne 1 : TITRE (rang=1, ref_chantier="")
└─ Ligne 2 : PRODUIT (rang=2)

Résultat attendu : CMD003 V1 Client (pas de " / " affiché)
```

### Test 4 : Plusieurs titres dans une commande
```
Commande CMD004
├─ Ligne 1 : TITRE (rang=1, ref_chantier="CHANTIER-A")
├─ Ligne 2 : PRODUIT (rang=2)
├─ Ligne 3 : TITRE (rang=3, ref_chantier="CHANTIER-B")
└─ Ligne 4 : PRODUIT (rang=4)

Résultats attendus :
- Ligne 2 : CMD004 V1 Client / CHANTIER-A
- Ligne 4 : CMD004 V1 Client / CHANTIER-B
```

---

## 🔍 Validation SQL

Pour vérifier que la logique fonctionne correctement, vous pouvez exécuter cette requête de test :

```sql
SELECT 
    cd.rowid,
    cd.rang,
    cd.product_type,
    cd.description,
    cd_ef.ref_chantier as ligne_ref_chantier,
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
```

---

## 📦 Déploiement

### Étapes d'installation

1. **Sauvegarder les fichiers existants** :
   ```bash
   cp class/planningproduction.class.php class/planningproduction.class.php.backup
   cp lib/planning_functions.php lib/planning_functions.php.backup
   cp js/modal.js js/modal.js.backup
   ```

2. **Copier les nouveaux fichiers** :
   - `class/planningproduction.class.php`
   - `lib/planning_functions.php`
   - `js/modal.js`

3. **Vider le cache du navigateur** (Ctrl+F5)

4. **Tester sur une commande** avec des titres et des produits

---

## ⚠️ Points d'attention

### Données requises

Cette fonctionnalité nécessite :
- ✅ Module complémentaire qui crée des services/titres avec `product_type = 9`
- ✅ Extrafield `ref_chantier` sur `llx_commandedet`
- ✅ Valeurs remplies dans `ref_chantier` des lignes de titre

### Performance

La sous-requête SQL est optimisée car :
- ✅ Elle est corrélée (une seule ligne par produit)
- ✅ Elle utilise `LIMIT 1` pour s'arrêter dès le premier résultat
- ✅ Elle exploite l'index sur `fk_commande` et `rang`

### Compatibilité

- ✅ Compatible avec toutes les cartes (planifiées ou non)
- ✅ Pas de modification des tables SQL requise
- ✅ Rétrocompatible : si pas de titre, fonctionne comme avant

---

## 🐛 Dépannage

### Le ref_chantier ne s'affiche pas

**Vérifier :**
1. Le titre (product_type=9) est-il bien au-dessus du produit (rang inférieur) ?
2. Le champ `ref_chantier` du titre est-il rempli ?
3. Les caches navigateur sont-ils vidés ?

**Requête de diagnostic :**
```sql
-- Afficher la structure d'une commande
SELECT 
    cd.rang,
    cd.product_type,
    cd.description,
    cd_ef.ref_chantier
FROM llx_commandedet cd
LEFT JOIN llx_commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object
WHERE cd.fk_commande = [ID_COMMANDE]
ORDER BY cd.rang ASC;
```

### La carte affiche " / -" au lieu de rien

C'est normal si le titre existe mais que son `ref_chantier` est vide. Pour éviter cela, toujours remplir le `ref_chantier` des titres.

---

## 📝 Notes de développement

### Choix techniques

**Pourquoi une sous-requête plutôt qu'une jointure ?**
- ✅ Évite les doublons en cas de plusieurs titres
- ✅ Plus simple à comprendre
- ✅ Meilleure performance avec `LIMIT 1`

**Pourquoi `rang` plutôt qu'un lien direct ?**
- ✅ C'est la logique métier : les titres structurent les sections
- ✅ Pas besoin de modifier la structure de la base
- ✅ Fonctionne automatiquement pour toutes les commandes

---

## ✅ Checklist de validation

- [ ] Les cartes dans le planning affichent le ref_chantier
- [ ] Les cartes dans les onglets affichent le ref_chantier
- [ ] Le modal d'édition affiche le ref_chantier
- [ ] Les exports HTML incluent le ref_chantier
- [ ] Pas de régression sur les cartes sans titre
- [ ] Pas de régression sur les titres sans ref_chantier
- [ ] Performance acceptable (pas de ralentissement visible)

---

## 📚 Ressources

- [Module Planning Production - README](README.md)
- [Documentation extrafields Dolibarr](https://wiki.dolibarr.org/index.php/Extrafields)
- [Sous-requêtes SQL](https://dev.mysql.com/doc/refman/8.0/en/subqueries.html)

---

**Fin de la documentation**
