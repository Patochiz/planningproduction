# Installation et Mise à Jour - Module Planning Production v1.0.0

## 🚀 Nouvelle Fonctionnalité : Gestion des Matières Premières

Cette version ajoute une fonctionnalité complète de gestion des matières premières avec :
- Tableau récapitulatif des stocks
- Calcul automatique des commandes en cours  
- Édition en temps réel des stocks
- Alertes visuelles pour les stocks insuffisants

---

## 📋 Checklist d'Installation/Mise à Jour

### ✅ Étape 1 : Sauvegarde
- [ ] Sauvegarder la base de données Dolibarr
- [ ] Sauvegarder le répertoire `/custom/planningproduction/` (si existant)

### ✅ Étape 2 : Déploiement des Fichiers
- [ ] Copier tous les fichiers du module dans `/htdocs/custom/planningproduction/`
- [ ] Vérifier que le fichier `ajax_matieres.php` est présent à la racine du module
- [ ] Vérifier que le fichier `js/matieres.js` est présent

### ✅ Étape 3 : Mise à Jour de la Base de Données

#### Option A : Via l'Interface Dolibarr (Recommandé)
1. [ ] Aller dans **Configuration** → **Modules**
2. [ ] Désactiver le module "Planning Production" 
3. [ ] Réactiver le module "Planning Production"
4. [ ] ✨ Les nouvelles tables seront automatiquement créées

#### Option B : Exécution Manuelle des SQL
Si l'option A ne fonctionne pas, exécuter manuellement :

```sql
-- 1. Créer la table des matières premières
source /path/to/dolibarr/htdocs/custom/planningproduction/sql/llx_planningproduction_matieres.sql

-- 2. Créer les index
source /path/to/dolibarr/htdocs/custom/planningproduction/sql/llx_planningproduction_matieres.key.sql

-- 3. (Optionnel) Importer les données d'exemple
source /path/to/dolibarr/htdocs/custom/planningproduction/sql/data_example_matieres.sql
```

### ✅ Étape 4 : Vérification de l'Installation

1. [ ] Accéder au planning de production
2. [ ] Vérifier que le bouton "🧱 Matières Premières" est visible
3. [ ] Cliquer sur le bouton et vérifier que le modal s'ouvre
4. [ ] Si données d'exemple importées, vérifier que le tableau contient les matières

### ✅ Étape 5 : Configuration des Matières Premières

#### Première Configuration
1. [ ] Aller dans **Configuration** → **Modules** → **Planning Production** → **Paramètres**
2. [ ] Descendre à la section "Gestion des Matières Premières"  
3. [ ] Ajouter vos codes MP avec leurs stocks initiaux

#### Configuration basée sur votre fichier Excel
Ajouter les codes MP suivants (adaptez les stocks selon votre situation) :

| Code MP | Stock Suggéré | Description |
|---------|---------------|-------------|
| `400 BLANC` | 815.00 | Matière première 400 blanc |
| `400 RAL 9003` | 379.00 | Matière première 400 RAL 9003 |  
| `400 RAL 9006` | 1187.00 | Matière première 400 RAL 9006 |
| `400 RAL 9005` | 525.00 | Matière première 400 RAL 9005 |
| `400 RAL 7016` | 774.00 | Matière première 400 RAL 7016 |
| `300 RAL 9010` | 204.00 | Matière première 300 RAL 9010 |
| `300 RAL 7035` | 771.00 | Matière première 300 RAL 7035 |
| `300 RAL 9006` | 92.00 | Matière première 300 RAL 9006 |
| `300 RAL 9016` | 30.00 | Matière première 300 RAL 9016 |
| `11%1,5mm` | 224.00 | Perforation 11% épaisseur 1,5mm |
| `23%1,5mm` | 409.00 | Perforation 23% épaisseur 1,5mm |
| `16%2,5mm` | 368.00 | Perforation 16% épaisseur 2,5mm |
| `23%2,5mm` | 422.00 | Perforation 23% épaisseur 2,5mm |
| `22%2mm RAL 9006` | 264.00 | Perforation 22% épaisseur 2mm RAL 9006 |

### ✅ Étape 6 : Test des Fonctionnalités

#### Test du Modal Matières Premières
1. [ ] Ouvrir le planning de production
2. [ ] Cliquer sur "🧱 Matières Premières"
3. [ ] Vérifier que le tableau s'affiche correctement
4. [ ] Tester la modification d'un stock (changer une valeur et perdre le focus)
5. [ ] Tester le bouton "MàJ" d'une ligne

#### Test du Calcul des Commandes en Cours
1. [ ] S'assurer d'avoir des cartes avec des matières contenant vos codes MP
2. [ ] Dans le modal matières, cliquer sur "MàJ" d'une ligne
3. [ ] Vérifier que la colonne "CDE EN COURS" se met à jour
4. [ ] Vérifier que la colonne "RESTE" se recalcule

#### Test des Alertes Stocks Insuffisants
1. [ ] Mettre un stock très faible sur une matière (ex: 0.1)
2. [ ] S'assurer que cette matière a des commandes en cours
3. [ ] Vérifier que la ligne devient rouge si le reste ≤ 0

### ✅ Étape 7 : Configuration des Permissions (Si nécessaire)

Si de nouveaux utilisateurs doivent accéder aux matières premières :
1. [ ] Aller dans **Configuration** → **Utilisateurs & Groupes** → **Groupes**
2. [ ] Pour chaque groupe concerné, vérifier que les permissions Planning Production sont accordées :
   - [ ] "Lire les plannings de production" (lecture du tableau)
   - [ ] "Créer/modifier les plannings de production" (modification des stocks)

---

## 🔧 Résolution des Problèmes Courants

### ❌ Le bouton "Matières Premières" n'apparaît pas
**Cause possible :** Cache navigateur ou fichiers non mis à jour
**Solution :**
1. Vider le cache du navigateur (Ctrl+F5)
2. Vérifier que `planning.php` contient bien le nouveau bouton
3. Vérifier que `js/matieres.js` est présent

### ❌ "Erreur 404" sur ajax_matieres.php
**Cause possible :** Fichier manquant ou permissions incorrectes
**Solution :**
1. Vérifier que `ajax_matieres.php` est à la racine du module
2. Vérifier les permissions du fichier (644 recommandé)
3. Tester l'accès direct : `https://votre-dolibarr.com/custom/planningproduction/ajax_matieres.php`

### ❌ Le modal se charge mais affiche "Erreur lors du chargement"
**Cause possible :** Table manquante ou permissions base de données
**Solution :**
1. Vérifier que la table `llx_planningproduction_matieres` existe
2. Dans phpMyAdmin : `DESCRIBE llx_planningproduction_matieres;`
3. Si la table n'existe pas, réexécuter les scripts SQL

### ❌ Les calculs "CDE EN COURS" sont à 0
**Cause possible :** Aucune correspondance trouvée entre les codes MP et les matières des cartes
**Solution :**
1. Vérifier que vos cartes ont bien un champ "matière" rempli
2. Vérifier que ce champ contient les codes MP (ex: "NP TATA 400 BLANC" doit contenir "400 BLANC")
3. Tester avec une carte simple contenant exactement le code MP

### ❌ Erreur JavaScript dans la console
**Cause possible :** Conflit avec d'autres scripts ou chargement incomplet
**Solution :**
1. Ouvrir la console JavaScript (F12)
2. Recharger la page et noter les erreurs
3. Vérifier que tous les fichiers JS sont chargés
4. Vérifier l'ordre de chargement dans `planning.php`

---

## 📞 Support Post-Installation

### 🔍 Vérification de l'Installation

Pour vérifier que tout fonctionne, exécutez cette checklist rapide :

```bash
# Vérifier la présence des fichiers critiques
ls -la /path/to/dolibarr/htdocs/custom/planningproduction/ajax_matieres.php
ls -la /path/to/dolibarr/htdocs/custom/planningproduction/js/matieres.js

# Vérifier la table en base
mysql -u user -p dolibarr_db -e "SHOW TABLES LIKE '%matieres%';"
mysql -u user -p dolibarr_db -e "SELECT COUNT(*) FROM llx_planningproduction_matieres;"
```

### 📋 Logs à Consulter en Cas de Problème

1. **Logs Dolibarr :** `/documents/dolibarr.log`
2. **Logs Apache/Nginx :** `/var/log/apache2/error.log` ou `/var/log/nginx/error.log`  
3. **Console JavaScript :** F12 → Console dans le navigateur
4. **Requêtes AJAX :** F12 → Réseau → Filtrer sur XHR

### 🎯 Points de Contrôle Essentiels

- [ ] Module activé et tables créées
- [ ] Bouton matières premières visible
- [ ] Modal s'ouvre correctement  
- [ ] Données se chargent dans le tableau
- [ ] Modification des stocks fonctionne
- [ ] Boutons "MàJ" calculent correctement
- [ ] Alertes visuelles fonctionnent (stocks négatifs)

---

## ✨ Profitez de la Nouvelle Fonctionnalité !

Une fois l'installation terminée, vous pourrez :

- 📊 Avoir une vue d'ensemble de vos stocks de matières premières
- 🔄 Calculer automatiquement les besoins selon votre planning
- ⚠️ Être alerté des risques de rupture de stock
- ✏️ Gérer vos stocks directement depuis l'interface de planning
- 📈 Optimiser votre gestion des approvisionnements

**Bonne utilisation ! 🚀**
