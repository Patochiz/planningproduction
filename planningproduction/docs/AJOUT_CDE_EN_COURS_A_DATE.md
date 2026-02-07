# Ajout de la colonne "CDE EN COURS à date" - Documentation

## 📋 Vue d'ensemble

Cette modification ajoute une colonne **"CDE EN COURS à date"** dans le tableau des matières premières pour gérer la désynchronisation entre deux personnes qui mettent à jour des informations différentes :

- **Personne A** : Met à jour les statuts de commandes (affecte "CDE EN COURS" calculé automatiquement)
- **Personne B** : Met à jour le stock de matières premières et les "CDE EN COURS à date"

## 🎯 Fonctionnement

### Colonnes du tableau

| Colonne | Type | Description |
|---------|------|-------------|
| **CODE MP** | Texte | Code de la matière première |
| **STOCK** | Input éditable | Quantité disponible en stock (modifiable manuellement) |
| **CDE EN COURS** | Calculé | Somme automatique des quantités des cartes ayant ce code MP (hors À TERMINER/BON POUR EXPÉDITION) |
| **CDE EN COURS à date** ⭐ | Input éditable | Valeur figée des commandes en cours (modifiable manuellement, fond orange) |
| **RESTE** | Calculé | `Stock - CDE EN COURS à date` (utilise la valeur figée, pas la calculée) |
| **DATE DE MàJ** | Date | Dernière modification |
| **ACTIONS** | Bouton MàJ | Synchronise "CDE EN COURS à date" avec "CDE EN COURS" |

### Alertes visuelles

#### 🔴 Ligne rouge complète (désynchronisation)
Quand `CDE EN COURS ≠ CDE EN COURS à date` (différence > 0.01)

**Signification** : Les deux colonnes ne sont plus synchronisées
- Soit une commande a changé de statut (A TERMINER/BON POUR EXPÉDITION)
- Soit "CDE EN COURS à date" n'a pas été mis à jour après un changement

**Action** : Cliquer sur le bouton **MàJ** pour copier la valeur calculée vers la valeur figée

#### 🔴 Cellule RESTE rouge (stock insuffisant)
Quand `RESTE ≤ 0`

**Signification** : Stock insuffisant pour les commandes en cours à date

**Action** : Commander de la matière première ou ajuster le stock

## ⚙️ Modifications techniques

### 1. Base de données

#### Script SQL : `migration_add_cde_en_cours_date.sql`
```sql
ALTER TABLE llx_planningproduction_matieres 
ADD COLUMN cde_en_cours_date DOUBLE(24,8) DEFAULT 0 
COMMENT 'Commandes en cours à une date fixe (éditable manuellement)';
```

**À exécuter** :
```bash
mysql -u root -p dolibarr_database < sql/migration_add_cde_en_cours_date.sql
```

### 2. Classe PHP

#### Fichier : `class/planningproduction.class.php`

**Méthode modifiée** : `getAllMatieres()`
- Récupère maintenant le champ `cde_en_cours_date`
- Calcule le RESTE avec cette valeur : `reste = stock - cde_en_cours_date`

**Nouvelle méthode** : `updateMatiereCdeEnCoursDate($rowid, $cde_en_cours_date)`
- Met à jour la valeur de `cde_en_cours_date` dans la base de données
- Enregistre l'utilisateur qui a effectué la modification

### 3. Endpoint AJAX

#### Fichier : `ajax_matieres.php`

**Action existante modifiée** : `get_matieres`
- Ajoute un flag `is_desync` pour chaque matière
- `is_desync = true` si `|cde_en_cours - cde_en_cours_date| > 0.01`

**Nouvelle action** : `update_cde_en_cours_date`
- Met à jour la valeur de CDE EN COURS à date
- Validation CSRF, droits d'écriture requis
- Retourne la nouvelle valeur

**Nouvelle action** : `sync_cde_en_cours`
- Calcule CDE EN COURS automatiquement
- Copie cette valeur dans CDE EN COURS à date
- Utilisé par le bouton "MàJ"

### 4. Interface JavaScript

#### Fichier : `js/matieres.js`

**Fonctions ajoutées** :
- `updateCdeEnCoursDate(rowid, newCdeEnCoursDate)` : Met à jour manuellement
- `syncCdeEnCours(codeMP, rowid)` : Synchronise via bouton MàJ
- `updateRowDesyncStatus(rowid)` : Applique le style rouge si désynchronisé

**Rendu du tableau** :
- Colonne "CDE EN COURS à date" avec input éditable (fond orange)
- Détection automatique de la désynchronisation
- Application du style `row-desync` si nécessaire

### 5. Styles CSS

#### Dans : `planning.php` (section `<style>`)

```css
/* Ligne rouge pour désynchronisation */
.row-desync {
    background-color: #ffe5e5 !important;
}

/* Input CDE EN COURS à date avec fond orange */
.cde-editable {
    background: #fff8e1;
    border: 1px solid #f39c12;
    /* ... */
}
```

## 📝 Workflow utilisateur

### Scénario 1 : Mise à jour normale

1. **Personne B** ouvre le tableau des matières premières
2. Modifie le **STOCK** (input éditable)
3. Si besoin, modifie **CDE EN COURS à date** (input éditable, fond orange)
4. Le **RESTE** se met à jour automatiquement : `Stock - CDE EN COURS à date`

### Scénario 2 : Synchronisation après changement de statut

1. **Personne A** met à jour une commande : "À PRODUIRE" → "BON POUR EXPÉDITION"
2. Cette commande n'est plus comptée dans "CDE EN COURS" (calculé)
3. La ligne devient **rouge** (désynchronisation car CDE EN COURS ≠ CDE EN COURS à date)
4. **Personne B** clique sur le bouton **MàJ**
5. "CDE EN COURS à date" prend la nouvelle valeur calculée
6. La ligne redevient normale (blanche ou rouge seulement si RESTE ≤ 0)

### Scénario 3 : Stock insuffisant

1. Le calcul `Stock - CDE EN COURS à date` donne un résultat ≤ 0
2. La cellule **RESTE** devient rouge
3. **Action** : Commander de la MP ou ajuster le stock

## 🔧 Installation

### Étape 1 : Exécuter le script SQL

```bash
cd /home/diamanti/www/doli/custom/planningproduction
mysql -u USERNAME -p DATABASE_NAME < sql/migration_add_cde_en_cours_date.sql
```

### Étape 2 : Uploader les fichiers modifiés

Uploader ces fichiers sur le serveur OVH :
- `class/planningproduction.class.php`
- `ajax_matieres.php`
- `js/matieres.js`
- `planning.php`
- `sql/migration_add_cde_en_cours_date.sql`

### Étape 3 : Tester

1. Ouvrir le planning de production
2. Cliquer sur le bouton "🧱 Matières"
3. Vérifier que la colonne "CDE EN COURS à date" est présente
4. Tester la modification du stock et de CDE EN COURS à date
5. Tester le bouton "MàJ"

## ✅ Avantages de cette approche

1. **Pas de fausses alertes** : Le RESTE utilise une valeur figée, pas la valeur calculée en temps réel
2. **Visibilité de la désynchronisation** : Ligne rouge quand les valeurs diffèrent
3. **Synchronisation facile** : Un clic sur "MàJ" pour mettre à jour
4. **Traçabilité** : Date de dernière modification visible
5. **Workflow séparé** : Deux personnes peuvent travailler indépendamment

## 🎨 Légende visuelle

| Couleur | Signification | Action |
|---------|---------------|--------|
| ⬜ Blanc | Tout est normal | Rien à faire |
| 🟥 Rouge (ligne complète) | Désynchronisation | Cliquer sur "MàJ" |
| 🟥 Rouge (cellule RESTE) | Stock insuffisant | Commander de la MP |
| 🟧 Orange (input) | Champ éditable "CDE EN COURS à date" | Peut être modifié manuellement |

## 🐛 Dépannage

### La colonne "CDE EN COURS à date" ne s'affiche pas
- Vérifier que le script SQL a été exécuté
- Vérifier la console JavaScript pour des erreurs

### Les lignes ne deviennent pas rouges
- Vérifier que le fichier `planning.php` contient le nouveau CSS
- Vider le cache du navigateur

### Le bouton "MàJ" ne fonctionne pas
- Vérifier les droits d'écriture de l'utilisateur
- Vérifier la console JavaScript pour des erreurs AJAX
- Vérifier que `ajax_matieres.php` a été mis à jour

## 📅 Date de mise en œuvre

**Date** : 10 novembre 2025
**Version** : Module planningproduction v1.0.2

---

**Note** : Cette fonctionnalité améliore grandement la gestion collaborative des stocks de matières premières en évitant les conflits entre les mises à jour des différents intervenants.
