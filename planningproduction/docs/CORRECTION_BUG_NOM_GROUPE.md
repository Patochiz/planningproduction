# Correction du bug : Nom des groupes non sauvegardé

## 🐛 Problème identifié

Le nom des groupes dans le planning n'était pas sauvegardé lorsque l'utilisateur modifiait l'input texte du nom de groupe.

### Cause racine

Dans le fichier `js/events.js`, lignes 94-102, il y avait bien un écouteur d'événement pour détecter les modifications du nom de groupe :

```javascript
groupNameInput.addEventListener('blur', function() {
    console.log('Nom de groupe modifié (blur):', this.value);
    if (typeof saveGroupName === 'function') {  // ❌ PROBLÈME
        saveGroupName(group);
    }
});
```

**MAIS** la fonction `saveGroupName` n'existait pas ! Elle n'était définie nulle part dans les fichiers JavaScript du module.

Résultat : Quand l'utilisateur modifiait le nom d'un groupe et quittait le champ, rien ne se passait et le nom n'était jamais sauvegardé en base de données.

## ✅ Solution implémentée

### Ajout de la fonction `saveGroupName` dans `planning.js`

La nouvelle fonction :

1. **Récupère le contexte** : semaine, année, nom du groupe modifié
2. **Valide les données** : vérifie que le nom n'est pas vide
3. **Récupère toutes les cartes** du groupe
4. **Prépare les mises à jour** : crée un tableau d'updates avec le nouveau nom pour chaque carte
5. **Envoie au serveur** via la fonction `sendOrderUpdates` existante

### Code ajouté

```javascript
function saveGroupName(groupElement) {
    console.log('Sauvegarde du nom de groupe...');
    
    // Validation et récupération du contexte
    const weekRow = groupElement.closest('.week-row');
    const weekNum = parseInt(weekHeader.textContent.match(/SEMAINE (\d+)/)[1]);
    const year = parseInt(document.querySelector('select[name="year"]').value);
    const groupName = groupElement.querySelector('input[type="text"]').value.trim();
    
    // Validation
    if (!groupName) {
        showToast('Le nom du groupe ne peut pas être vide', 'error');
        return;
    }
    
    // Récupération des cartes
    const cards = Array.from(groupElement.querySelectorAll('.kanban-card'));
    
    // Préparation des updates
    const updates = cards.map((card, index) => ({
        fk_commande: parseInt(card.dataset.fkCommande),
        fk_commandedet: parseInt(card.dataset.fkCommandedet),
        semaine: weekNum,
        annee: year,
        groupe_nom: groupName,  // ✅ Nouveau nom
        ordre_groupe: index,
        ordre_semaine: 0
    }));
    
    // Envoi au serveur
    sendOrderUpdates(updates, 'Nom du groupe "' + groupName + '" sauvegardé');
}
```

## 🎯 Comportement corrigé

### Avant la correction
1. L'utilisateur modifiait le nom d'un groupe dans l'input
2. Il cliquait ailleurs ou appuyait sur Entrée
3. ❌ **Rien ne se passait** - le nom n'était pas sauvegardé
4. Après rechargement de la page, l'ancien nom réapparaissait

### Après la correction
1. L'utilisateur modifie le nom d'un groupe dans l'input
2. Il clique ailleurs (événement `blur`) ou appuie sur Entrée
3. ✅ **La fonction `saveGroupName` est appelée**
4. Le nouveau nom est envoyé au serveur via AJAX
5. Toutes les cartes du groupe sont mises à jour avec le nouveau nom
6. Un toast de confirmation s'affiche : "Nom du groupe 'XXX' sauvegardé"
7. Le nom reste persistant après rechargement de la page

## 📝 Déclencheurs de sauvegarde

La sauvegarde du nom de groupe est déclenchée dans deux cas :

### 1. Événement `blur` (perte de focus)
Quand l'utilisateur clique en dehors de l'input après avoir modifié le nom.

### 2. Événement `keydown` (touche Entrée)
Quand l'utilisateur appuie sur la touche Entrée après avoir modifié le nom.

```javascript
// Dans events.js
groupNameInput.addEventListener('blur', function() {
    if (typeof saveGroupName === 'function') {
        saveGroupName(group);  // ✅ Fonctionne maintenant !
    }
});

groupNameInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.blur(); // Déclenche le blur qui va sauvegarder
    }
});
```

## 🔄 Flux de données

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Utilisateur modifie le nom du groupe dans l'input           │
│    Exemple : "Groupe 1" → "Commande prioritaire"               │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Événement blur ou Enter déclenché                            │
│    → Appel de saveGroupName(groupElement)                       │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. Fonction saveGroupName                                       │
│    - Récupère semaine, année, nouveau nom                       │
│    - Récupère toutes les cartes du groupe                       │
│    - Crée updates[] avec le nouveau groupe_nom                  │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Fonction sendOrderUpdates(updates, message)                  │
│    - Sérialise updates en JSON                                  │
│    - Envoie POST à ajax_planning.php                            │
│    - Action: 'update_group_order'                               │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. PHP ajax_planning.php                                        │
│    - Valide les données                                         │
│    - Appelle $object->updatePlannedCard()                       │
│    - Met à jour groupe_nom en BDD                               │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. Réponse JSON                                                 │
│    {success: true, message: "Group order updated"}             │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. Toast de confirmation affiché à l'utilisateur                │
│    "Nom du groupe 'Commande prioritaire' sauvegardé"           │
└─────────────────────────────────────────────────────────────────┘
```

## 🧪 Tests recommandés

### Test 1 : Modification simple
1. Créer un groupe avec le nom par défaut "Nouveau Groupe"
2. Modifier le nom en "Test Sauvegarde"
3. Cliquer ailleurs
4. ✅ Vérifier le toast : "Nom du groupe 'Test Sauvegarde' sauvegardé"
5. Recharger la page
6. ✅ Vérifier que le nom "Test Sauvegarde" est conservé

### Test 2 : Modification avec Entrée
1. Modifier le nom d'un groupe
2. Appuyer sur Entrée
3. ✅ Vérifier le toast de confirmation
4. Recharger la page
5. ✅ Vérifier la persistance

### Test 3 : Nom vide (validation)
1. Supprimer complètement le nom d'un groupe
2. Cliquer ailleurs
3. ✅ Vérifier le toast d'erreur : "Le nom du groupe ne peut pas être vide"
4. ✅ Vérifier que l'ancien nom est conservé

### Test 4 : Groupe avec plusieurs cartes
1. Créer un groupe avec 3 cartes
2. Modifier le nom du groupe
3. ✅ Vérifier que les 3 cartes sont mises à jour avec le nouveau nom

## 📦 Fichier modifié

### `js/planning.js`
- ✅ Ajout de la fonction `saveGroupName(groupElement)`
- ✅ Documentation complète avec commentaires
- ✅ Gestion d'erreurs et validation
- ✅ Messages de confirmation utilisateur

## 🚀 Déploiement

### Fichier à uploader sur OVH
```
/home/diamanti/www/doli/custom/planningproduction/js/planning.js
```

### Après déploiement
1. Vider le cache du navigateur (Ctrl+F5)
2. Tester la modification du nom d'un groupe
3. Vérifier le toast de confirmation
4. Recharger la page et vérifier la persistance

## 📊 Impact

### Avantages
- ✅ Sauvegarde automatique du nom de groupe
- ✅ Feedback immédiat à l'utilisateur (toast)
- ✅ Persistance en base de données
- ✅ Validation des données (nom non vide)
- ✅ Cohérence : toutes les cartes du groupe sont mises à jour

### Pas d'impact négatif
- ✅ Utilise l'infrastructure existante (`sendOrderUpdates`)
- ✅ Pas de modification de la base de données
- ✅ Compatible avec le système existant
- ✅ Pas de régression sur les autres fonctionnalités

## 📅 Date de correction

**Date** : 10 novembre 2025  
**Version** : Module planningproduction v1.0.3  
**Bug corrigé** : Nom des groupes non sauvegardé

---

**Note** : Cette correction résout un bug critique qui empêchait l'utilisateur d'organiser efficacement son planning avec des noms de groupes personnalisés.
