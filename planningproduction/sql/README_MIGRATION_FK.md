# 🔧 CORRECTIF : Contraintes de clés étrangères

## Problème rencontré

**Erreur lors de la suppression d'une commande planifiée :**
```
Cannot delete or update a parent row: a foreign key constraint fails 
(`diamantidoli`.`llx_planningproduction_planning`, 
CONSTRAINT `fk_planningproduction_planning_fk_commandedet` 
FOREIGN KEY (`fk_commandedet`) REFERENCES `llx_commandedet` (`rowid`))
```

## Cause

Les contraintes de clés étrangères ont été créées **SANS** l'option `ON DELETE CASCADE`, ce qui empêche la suppression des lignes de commandes qui sont planifiées dans le module.

## Solution appliquée

Ajout de `ON DELETE CASCADE` sur les contraintes :
- `fk_commande` → Si commande supprimée, planning supprimé automatiquement
- `fk_commandedet` → Si ligne commande supprimée, planning supprimé automatiquement

## 📝 Procédure d'application

### Option 1 : Via phpMyAdmin (RECOMMANDÉ)

1. **Connectez-vous à phpMyAdmin**
2. **Sélectionnez votre base de données** : `diamantidoli`
3. **Cliquez sur l'onglet "SQL"**
4. **Copiez-collez le contenu du fichier** `migration_fix_fk_cascade.sql`
5. **Cliquez sur "Exécuter"**
6. **Vérifiez le résultat** : Un tableau doit s'afficher avec les contraintes et leur `DELETE_RULE` = `CASCADE`

### Option 2 : En ligne de commande MySQL

```bash
# Depuis le serveur
mysql -u [votre_user] -p diamantidoli < /home/diamanti/www/doli/custom/planningproduction/sql/migration_fix_fk_cascade.sql
```

### Option 3 : Via l'interface Dolibarr (avancé)

1. Allez dans **Outils** → **Base de données**
2. Onglet **SQL**
3. Copiez le contenu du fichier `migration_fix_fk_cascade.sql`
4. Exécutez

## ✅ Vérification

Après l'application du script, vérifiez que les contraintes sont bien configurées :

```sql
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
```

**Résultat attendu :**
```
fk_planningproduction_planning_fk_commande      | CASCADE  | RESTRICT
fk_planningproduction_planning_fk_commandedet   | CASCADE  | RESTRICT
fk_planningproduction_planning_fk_user_creat    | SET NULL | RESTRICT
```

## 🧪 Test

Après l'application :

1. **Créez une commande de test**
2. **Planifiez-la** dans le module Planning Production
3. **Essayez de supprimer la commande** → Devrait fonctionner maintenant
4. **Vérifiez** que l'entrée dans `llx_planningproduction_planning` a été supprimée automatiquement

## ⚠️ Important

- **Faites une sauvegarde** de votre base avant d'appliquer la migration
- Cette migration est **idempotente** : vous pouvez l'exécuter plusieurs fois sans risque
- Les données existantes ne sont **pas affectées**, seules les contraintes sont modifiées

## 📁 Fichiers concernés

- `sql/migration_fix_fk_cascade.sql` : Script de migration à appliquer **UNE FOIS**
- `sql/llx_planningproduction_planning.key.sql` : Fichier mis à jour pour les futures installations

## 🚀 Prochaines installations

Pour les nouvelles installations du module, les contraintes seront automatiquement créées avec `ON DELETE CASCADE`. Ce correctif n'est nécessaire que pour les bases de données existantes.

---

**Date du correctif** : 2025-01-02  
**Version module** : 1.0.0+  
**Testeur** : À exécuter sur environnement de production
