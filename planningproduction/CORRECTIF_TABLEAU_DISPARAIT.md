# 🔧 CORRECTIF - Tableau qui disparaît après sauvegarde

## 🎯 Problème identifié et corrigé

**Problème** : Après avoir sauvegardé une ligne dans la configuration du module, le tableau des matières premières disparaissait.

**Cause** : Le JavaScript du drag & drop ne se réinitialisait pas correctement après le rechargement de la page.

## ✅ Corrections apportées

### 1. **Fichier setup.php modifié**
- ✅ Correction de `getAllMatieres()` → `getAllMatieres(true)` pour récupérer l'ordre
- ✅ Ajout de vérification `$matiere['ordre'] ?? 0` pour éviter les erreurs si ordre manquant
- ✅ Déplacement du CSS/JS avant le tableau pour assurer le chargement
- ✅ Ajout d'un script de réinitialisation après chaque rechargement

### 2. **Fichier matieres_order.js amélioré**
- ✅ Fonction `initializeMatieresOrder()` globale pour éviter les doublons
- ✅ Méthode `cleanup()` pour nettoyer les anciens event listeners
- ✅ Vérifications robustes (tableau existe, assez de lignes, etc.)
- ✅ Logs détaillés pour faciliter le debug

## 🚀 Test de la correction

### Vérification rapide
```bash
# 1. Ouvrir la console JavaScript (F12)
# 2. Aller à Configuration > Modules > Planning Production > Configuration
# 3. Ajouter ou modifier une matière première
# 4. Vérifier dans la console :
#    - "Réinitialisation du drag & drop..."
#    - "Drag & drop des matières premières initialisé avec succès ! X matières"
```

### Test complet
1. **Ajouter une matière** : Le tableau reste visible avec poignées ≡
2. **Modifier une matière** : Le drag & drop continue de fonctionner
3. **Supprimer une matière** : L'interface se met à jour correctement
4. **Glisser-déposer** : Fonctionne immédiatement après sauvegarde

## 🐛 Debug si problème persiste

### Vérifier dans la console (F12)
```javascript
// Vérifier que la classe est chargée
typeof MatieresOrderManager

// Vérifier l'instance
window.matieresOrderManager

// Réinitialiser manuellement si besoin
initializeMatieresOrder()
```

### Vérifications techniques
```bash
# 1. Fichiers présents
ls js/matieres_order.js
ls css/matieres_order.css

# 2. Base de données
SELECT COUNT(*), MIN(ordre), MAX(ordre) FROM llx_planningproduction_matieres;

# 3. Permissions
# Vérifier que l'utilisateur a les droits d'écriture sur le module
```

## 📋 Points de contrôle

- [ ] **JavaScript chargé** : Voir `matieres_order.js` dans les sources
- [ ] **CSS chargé** : Voir `matieres_order.css` dans les sources
- [ ] **Au moins 2 matières** : Nécessaire pour voir les poignées ≡
- [ ] **Droits d'écriture** : Requis pour le drag & drop
- [ ] **Colonne ordre** : Doit exister en base de données

## 🎉 Résultat

Le tableau des matières premières reste maintenant **parfaitement visible et fonctionnel** après toute sauvegarde, avec :
- ✅ **Drag & drop opérationnel** immédiatement après modifications
- ✅ **Interface stable** : Plus de disparition de tableau
- ✅ **Réinitialisation automatique** après chaque rechargement
- ✅ **Messages de debug** clairs dans la console
- ✅ **Gestion des erreurs** robuste

**Le problème est maintenant résolu !** 🚀

---

*Correction appliquée le 31/08/2024*  
*Fichiers modifiés : admin/setup.php + js/matieres_order.js*
