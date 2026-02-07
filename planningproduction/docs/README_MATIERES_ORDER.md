# Mise à jour : Gestion de l'ordre des Matières Premières

## 🎯 Objectif

Cette mise à jour ajoute la possibilité de réorganiser l'ordre d'affichage des matières premières dans la configuration du module Planning Production par simple glisser-déposer (drag & drop).

## 📋 Fonctionnalités ajoutées

- ✅ Réorganisation par drag & drop des matières premières
- ✅ Sauvegarde automatique de l'ordre en base de données
- ✅ Interface utilisateur intuitive avec indicateurs visuels
- ✅ Gestion des erreurs et restauration automatique
- ✅ Compatible desktop et mobile
- ✅ Messages de confirmation et feedback utilisateur

## 📁 Fichiers ajoutés/modifiés

### Nouveaux fichiers :
- `sql/llx_planningproduction_matieres_ordre.sql` - Script SQL pour ajouter la colonne ordre
- `ajax_matieres_order.php` - Traitement AJAX des changements d'ordre
- `js/matieres_order.js` - Gestion du drag & drop côté client
- `css/matieres_order.css` - Styles pour l'interface de drag & drop
- `docs/MATIERES_ORDER.md` - Documentation utilisateur
- `test_matieres_order.php` - Fichier de test des fonctionnalités

### Fichiers modifiés :
- `class/planningproduction.class.php` - Nouvelles méthodes pour gérer l'ordre
- `admin/setup.php` - Interface utilisateur mise à jour

## 🔧 Installation

### Étape 1 : Mise à jour de la base de données

**Obligatoire** : Exécuter le script SQL pour ajouter la colonne `ordre` :

#### Option A : Via phpMyAdmin
1. Se connecter à phpMyAdmin
2. Sélectionner la base de données Dolibarr
3. Aller dans l'onglet "SQL"
4. Copier/coller le contenu de `sql/llx_planningproduction_matieres_ordre.sql`
5. Exécuter le script

#### Option B : Ligne de commande
```bash
mysql -u username -p database_name < sql/llx_planningproduction_matieres_ordre.sql
```

#### Option C : Via Dolibarr (si disponible)
1. Menu **Outils > Base de données**
2. Onglet **SQL**
3. Coller le contenu du fichier SQL
4. Exécuter

### Étape 2 : Vérification

1. Aller sur `test_matieres_order.php` depuis votre navigateur
2. Cliquer sur **"Lancer les tests"**
3. Vérifier que tous les tests sont ✅ verts

### Étape 3 : Test utilisateur

1. Aller dans **Configuration > Modules > Planning Production > Configuration**
2. Section **Gestion des Matières Premières**
3. Vérifier la présence des poignées ≡ si vous avez au moins 2 matières premières
4. Tester le drag & drop

## 🛠 Structure technique

### Base de données

La colonne `ordre` est ajoutée à `llx_planningproduction_matieres` :
```sql
ALTER TABLE llx_planningproduction_matieres 
ADD COLUMN ordre integer DEFAULT 0 NOT NULL;

-- Index pour les performances
ALTER TABLE llx_planningproduction_matieres 
ADD INDEX idx_planningproduction_matieres_ordre (ordre);
```

### Nouvelles méthodes PHP

Dans la classe `PlanningProduction` :

- `getAllMatieres($order_by_position = true)` - Récupère les matières avec tri par ordre
- `getNextMatiereOrdre()` - Génère le prochain numéro d'ordre
- `updateMatiereOrdre($rowid, $ordre)` - Met à jour l'ordre d'une matière
- `reorderMatieres($ordered_ids)` - Réorganise toutes les matières selon un nouvel ordre

### API AJAX

Endpoint : `ajax_matieres_order.php`

Actions supportées :
- `reorder_matieres` - Réorganiser les matières premières
- `get_matieres_order` - Récupérer l'ordre actuel

### JavaScript

Classe `MatieresOrderManager` pour gérer :
- Drag & drop natif HTML5
- Communication AJAX
- Indicateurs visuels
- Gestion d'erreurs
- Feedback utilisateur

## 🔐 Sécurité

- ✅ Vérification du token CSRF
- ✅ Validation des permissions utilisateur  
- ✅ Validation des données côté serveur
- ✅ Protection contre les requêtes non-AJAX
- ✅ Logs de sécurité

## 📱 Compatibilité

- ✅ **Navigateurs** : Chrome, Firefox, Safari, Edge (dernières versions)
- ✅ **Mobile** : Interface responsive, drag & drop tactile
- ✅ **Dolibarr** : Version 13.0+ (testé sur 17.0+)

## 🚨 Prérequis

### Technique
- Module Planning Production activé
- JavaScript activé côté client
- Permissions d'écriture sur le module

### Utilisateur
- Droits administrateur (pour les tests)
- Droits d'écriture sur Planning Production (pour utiliser)
- Au moins 2 matières premières configurées (pour voir les poignées)

## 🔍 Dépannage

### La poignée ≡ n'apparaît pas

**Causes possibles :**
- Moins de 2 matières premières → Ajouter des matières premières
- Pas de droits d'écriture → Vérifier les permissions utilisateur
- JavaScript désactivé → Activer JavaScript

### Le drag & drop ne fonctionne pas

**Solutions :**
```javascript
// Vérifier la console JavaScript (F12)
console.log('Erreurs JavaScript ?');

// Vider le cache
Ctrl + F5

// Vérifier le chargement des fichiers
// Réseau > Vérifier matieres_order.js et matieres_order.css
```

### L'ordre n'est pas sauvegardé

**Vérifications :**
1. **Permissions** : L'utilisateur a-t-il les droits d'écriture ?
2. **Base** : La colonne `ordre` existe-t-elle ?
3. **AJAX** : Y a-t-il des erreurs dans la console réseau ?
4. **Logs** : Vérifier les logs Dolibarr

### Tests échouent

**Solutions :**
```bash
# Vérifier que la table existe
DESCRIBE llx_planningproduction_matieres;

# Vérifier la colonne ordre
SELECT ordre FROM llx_planningproduction_matieres LIMIT 1;

# Vérifier les données
SELECT rowid, code_mp, ordre FROM llx_planningproduction_matieres ORDER BY ordre;
```

## 📊 Logs et debug

### Logs Dolibarr
```
Menu Outils > Logs Dolibarr
Rechercher : "matieres" ou "reorder"
```

### Debug JavaScript
```javascript
// Console navigateur (F12)
window.matieresOrderManager.getCurrentOrder();
```

### Debug PHP
```php
// Dans ajax_matieres_order.php
dol_syslog("Debug: " . print_r($_POST, true), LOG_DEBUG);
```

## 🔄 Migration depuis version antérieure

Si vous avez déjà des matières premières configurées :

1. **Sauvegarde** : Faire une sauvegarde de la base avant la mise à jour
2. **Migration** : Le script SQL met automatiquement à jour les enregistrements existants
3. **Vérification** : Utiliser `test_matieres_order.php` pour valider la migration

## 🎨 Personnalisation

### Modifier les styles
Éditer `css/matieres_order.css` pour personnaliser :
- Couleurs des indicateurs
- Animation du drag & drop  
- Position des messages de feedback

### Ajouter des fonctionnalités
Étendre la classe `MatieresOrderManager` dans `js/matieres_order.js`

## 📞 Support

En cas de problème :

1. **Tests** : Lancer `test_matieres_order.php`
2. **Logs** : Consulter les logs Dolibarr et JavaScript
3. **Documentation** : Lire `docs/MATIERES_ORDER.md`
4. **Permissions** : Vérifier les droits utilisateur

## 🔮 Évolutions futures

- [ ] Drag & drop pour d'autres éléments du module
- [ ] Sauvegarde/restauration de configurations d'ordre
- [ ] Interface d'administration des ordres
- [ ] Export/import des configurations

---

**Version** : 1.0  
**Date** : Août 2025  
**Auteur** : Patrick Delcroix  
**Licence** : GPL v3+
