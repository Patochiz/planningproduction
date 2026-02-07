# ⚡ GUIDE RAPIDE - Ref Chantier des Titres

## 🎯 En bref

**Modification** : Affichage du `ref_chantier` des titres dans les cartes  
**Résultat** : `N° Commande V1 Client` → `N° Commande V1 Client / Ref chantier`

---

## 📦 Fichiers à uploader

```
✅ class/planningproduction.class.php  (modifié)
✅ lib/planning_functions.php          (modifié)
✅ js/modal.js                          (modifié)
```

---

## 🚀 Installation en 3 étapes

1. **Sauvegarder** les 3 fichiers actuels
2. **Uploader** les 3 nouveaux fichiers
3. **Vider** le cache navigateur (Ctrl+F5)

---

## 🧪 Test rapide

1. Ouvrir une commande avec des titres (product_type=9)
2. Planifier un produit qui est sous un titre
3. Vérifier que la carte affiche : `CMD001 V1 Client / CHANTIER-A`

---

## 📝 Validation SQL

Remplacer `[ID_COMMANDE]` par un vrai ID :

```sql
SELECT 
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

## 🐛 Dépannage

**Problème** : Le ref_chantier ne s'affiche pas  
**Solution** : 
- Vérifier que le titre (product_type=9) est au-dessus du produit (rang inférieur)
- Vérifier que le `ref_chantier` du titre est rempli
- Vider le cache (Ctrl+F5)

---

## 📚 Documentation complète

- **Technique** : `docs/AJOUT_REF_CHANTIER_TITRES.md`
- **SQL Tests** : `sql/test_ref_chantier_titres.sql`
- **Récapitulatif** : `RECAPITULATIF_MODIFICATION.md`

---

## ✅ Checklist

- [ ] Fichiers uploadés
- [ ] Cache vidé
- [ ] Test sur une commande avec titres
- [ ] Affichage OK dans le planning
- [ ] Affichage OK dans les onglets
- [ ] Affichage OK dans le modal

---

**Modification simple, impact important ! 🎉**
