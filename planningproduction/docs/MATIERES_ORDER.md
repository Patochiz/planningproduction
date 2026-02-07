# Gestion de l'ordre des Matières Premières

## Fonctionnalité

Ce module permet de réorganiser l'ordre d'affichage des matières premières dans la configuration du module Planning Production par simple glisser-déposer (drag & drop).

## Comment utiliser

### 1. Accès à la fonctionnalité

- Aller dans le menu **Configuration > Modules/Applications**
- Rechercher et cliquer sur **Planning Production**
- Aller dans l'onglet **Configuration**
- Faire défiler jusqu'à la section **Gestion des Matières Premières**

### 2. Réorganiser les matières premières

1. **Condition préalable** : Vous devez avoir au moins 2 matières premières configurées
2. **Permissions** : Vous devez avoir les droits d'écriture sur le module
3. **Interface** : Une poignée ≡ apparaît à côté de chaque code MP

### 3. Procédure de réorganisation

1. **Cliquer et maintenir** sur la poignée ≡ de la matière première à déplacer
2. **Glisser** la ligne vers sa nouvelle position
3. **Relâcher** la ligne à l'endroit souhaité
4. **Confirmation automatique** : L'ordre est sauvegardé automatiquement

### 4. Indicateurs visuels

- **Ligne en cours de déplacement** : Devient semi-transparente et légèrement inclinée
- **Zone de dépôt** : Une ligne bleue indique où la matière première sera insérée
- **Survol** : Les lignes changent de couleur au survol
- **Messages de confirmation** : Apparaissent en haut à droite de l'écran

## Fonctionnalités techniques

### Sauvegarde automatique
- L'ordre est automatiquement sauvegardé en base de données
- Aucun bouton "Sauvegarder" à cliquer
- En cas d'erreur, l'ordre original est restauré automatiquement

### Gestion des erreurs
- Messages d'erreur clairs en cas de problème
- Restauration automatique en cas d'échec
- Logs détaillés pour le débogage

### Compatibilité
- Fonctionne sur desktop et mobile
- Compatible avec tous les navigateurs modernes
- Interface responsive

## Structure technique

### Fichiers ajoutés/modifiés

1. **SQL** : `sql/llx_planningproduction_matieres_ordre.sql`
   - Ajoute la colonne `ordre` à la table des matières premières

2. **PHP** : 
   - `class/planningproduction.class.php` - Méthodes pour gérer l'ordre
   - `ajax_matieres_order.php` - Traitement AJAX des changements d'ordre
   - `admin/setup.php` - Interface utilisateur mise à jour

3. **JavaScript** : `js/matieres_order.js`
   - Gestion du drag & drop
   - Communication AJAX avec le serveur

4. **CSS** : `css/matieres_order.css`
   - Styles pour le drag & drop
   - Indicateurs visuels

### Base de données

La colonne `ordre` est ajoutée à la table `llx_planningproduction_matieres` :
- Type : `integer`
- Valeur par défaut : `0`
- Index ajouté pour les performances
- Les enregistrements existants sont mis à jour automatiquement

## Installation

### Mise à jour de la base de données

Exécuter le script SQL pour ajouter la colonne `ordre` :

```sql
-- Depuis phpMyAdmin ou ligne de commande MySQL
source sql/llx_planningproduction_matieres_ordre.sql;
```

### Permissions requises

Les utilisateurs doivent avoir :
- Le module **Planning Production** activé
- Les droits de **lecture/écriture** sur le module

## Dépannage

### Problèmes courants

1. **La poignée n'apparaît pas**
   - Vérifier que vous avez les droits d'écriture
   - Vérifier qu'il y a au moins 2 matières premières

2. **Le glisser-déposer ne fonctionne pas**
   - Vérifier que JavaScript est activé
   - Vider le cache du navigateur
   - Vérifier la console JavaScript pour les erreurs

3. **L'ordre n'est pas sauvegardé**
   - Vérifier les permissions sur le module
   - Vérifier les logs Dolibarr
   - Vérifier que la colonne `ordre` existe en base

### Debug

Les logs sont disponibles dans :
- **Dolibarr** : Menu Outils > Logs Dolibarr
- **Navigateur** : Console de développement (F12)

### Support

En cas de problème :
1. Vérifier les logs d'erreur
2. Tester avec un autre navigateur
3. Vérifier les permissions utilisateur
4. Contacter l'administrateur système

## Évolutions futures

- Tri par glisser-déposer dans d'autres sections
- Sauvegarde de plusieurs ordres de tri
- Export/Import de la configuration d'ordre
