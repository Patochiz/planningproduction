# Guide Utilisateur - Module Planning Production

**Module Dolibarr** | Version 1.0.0 | Auteur : Patrick Delcroix

---

## Table des matières

1. [Introduction](#1-introduction)
2. [Prérequis et installation](#2-prérequis-et-installation)
3. [Interface principale](#3-interface-principale)
4. [Les cartes Kanban](#4-les-cartes-kanban)
5. [Glisser-déposer (Drag & Drop)](#5-glisser-déposer-drag--drop)
6. [Groupes de production](#6-groupes-de-production)
7. [Gestion des matières premières](#7-gestion-des-matières-premières)
8. [Exports et impressions](#8-exports-et-impressions)
9. [Configuration](#9-configuration)
10. [Permissions](#10-permissions)
11. [FAQ et résolution de problèmes](#11-faq-et-résolution-de-problèmes)

---

## 1. Introduction

Le module **Planning Production** est un outil de planification de production intégré à Dolibarr. Il offre une interface hybride de type Kanban avec une timeline par semaines, permettant de :

- **Planifier** les lignes de commandes clients sur des semaines de production
- **Regrouper** les tâches de production en groupes logiques
- **Suivre** l'avancement de la production à travers plusieurs statuts
- **Gérer** les stocks de matières premières et leur disponibilité
- **Exporter** les plannings pour impression ou archivage

Le module s'appuie sur les commandes clients existantes dans Dolibarr et exploite les champs supplémentaires (extrafields) des lignes de commande pour stocker les informations de matière, statut MP, statut production et postlaquage.

---

## 2. Prérequis et installation

### Prérequis

| Élément | Version minimale |
|---------|-----------------|
| Dolibarr | 11.0 |
| PHP | 5.6 |
| Module Commandes | Activé (obligatoire) |

### Installation

1. Copier le dossier `planningproduction` dans le répertoire `htdocs/custom/` de votre installation Dolibarr.
2. Se connecter à Dolibarr en tant qu'administrateur.
3. Aller dans **Accueil > Configuration > Modules/Applications**.
4. Rechercher **PlanningProduction** et cliquer sur le bouton d'activation.
5. Le module crée automatiquement les tables nécessaires en base de données.

### Extrafields requis

Le module utilise les champs supplémentaires suivants sur les lignes de commande (`commandedet`) :

| Champ | Description |
|-------|-------------|
| `matiere` | Code de la matière première |
| `statut_mp` | Statut de la matière première |
| `statut_prod` | Statut de la production |
| `statut_ar` | Statut de l'accusé de réception |
| `postlaquage` | Nécessité d'un postlaquage (oui/non) |

Et sur les commandes (`commande`) :

| Champ | Description |
|-------|-------------|
| `version` | Version du produit |
| `delai_liv` | Délai de livraison |

---

## 3. Interface principale

L'interface principale est accessible via le menu **Planning Production** dans Dolibarr. Elle se compose de trois zones :

### 3.1. Barre latérale gauche

La barre latérale contient les contrôles globaux du planning :

#### Actions globales

| Bouton | Action |
|--------|--------|
| Configuration | Ouvre la page de configuration dans un nouvel onglet |
| Matières Premières | Ouvre la modale de gestion des matières premières |
| Export Global | Génère un export complet de toutes les catégories |
| Synchronisation | Rafraîchit les données depuis la base |

#### Sélection de la période

- **Année** : Sélectionner l'année courante, précédente ou suivante
- **Nombre de semaines** : Afficher 3, 5 ou 8 semaines simultanément
- **Semaine de début** : Naviguer entre les semaines (1 à 52) avec les boutons de navigation gauche/droite

#### Filtres

- **Client** : Filtrer par client (tiers)
- **Recherche** : Rechercher par numéro de commande ou référence

### 3.2. Timeline centrale (zone principale)

La zone principale affiche les semaines de production sous forme de colonnes verticales. Chaque semaine comprend :

- **En-tête** : Numéro de semaine, dates (format JJ/MM - JJ/MM), nombre d'éléments et de groupes
- **Boutons d'action** : Valider la semaine et Exporter la semaine
- **Groupes de production** : Conteneurs regroupant les cartes
- **Zone "Nouveau groupe"** : Zone de dépôt pour créer un nouveau groupe en y glissant une carte

Quand une semaine est vide, un message invite à y glisser des cartes.

### 3.3. Panneau latéral droit (onglets)

Le panneau droit est rétractable (bouton `◀`/`▶`) et contient trois onglets avec compteurs :

| Onglet | Contenu | Description |
|--------|---------|-------------|
| **Non planifiées** | Cartes sans semaine assignée | Lignes de commande à planifier (statut prod. = vide, "À PRODUIRE" ou "EN COURS") |
| **À terminer** | Cartes en finition | Lignes avec statut "À TERMINER" |
| **À expédier** | Cartes prêtes | Lignes avec statut "BON POUR EXPÉDITION" |

Chaque onglet affiche un badge avec le nombre d'éléments. Les cartes de ces onglets peuvent être glissées vers la timeline.

---

## 4. Les cartes Kanban

### 4.1. Anatomie d'une carte

Chaque carte représente une ligne de commande et affiche :

**En-tête :**
- Numéro de commande (lien cliquable vers la commande Dolibarr)
- Version du produit (ex. : V1, V2)
- Nom du client (lien cliquable vers la fiche tiers)
- Référence chantier (si renseignée)
- Badges de statut (MP, AR, Production)
- Boutons d'action (Editer, Déplanifier)

**Corps :**

| Colonne A | Colonne B | Colonne C |
|-----------|-----------|-----------|
| Délai | Produit (lien cliquable) | Quantité |
| Livraison (code postal + ville) | Matière | Unité |

Le badge **+VN** apparaît à côté du produit si la ligne suivante est un vernis (produit 299 ou 480).

### 4.2. Codes couleurs des cartes

| Indicateur visuel | Signification |
|-------------------|---------------|
| **Bordure verte** | Matière OK (`MP Ok`) ET Accusé de réception validé (`AR VALIDÉ`) |
| **Bordure rouge** | Matière non OK ou AR non validé |
| **Fond jaune fluo** | Postlaquage requis (`oui`) |

### 4.3. Badges de statut

**Statut Matière Première (MP) :**

| Valeur | Affichage |
|--------|-----------|
| `MP Ok` | Badge vert "MP OK" |
| `MP en attente` | Badge orange |
| `MP Manquante` | Badge orange |
| `BL A FAIRE` | Badge orange |
| `PROFORMA A VALIDER` | Badge orange |
| `MàJ AIRTABLE à Faire` | Badge orange |

**Statut Accusé de Réception (AR) :**

| Valeur | Affichage |
|--------|-----------|
| `AR VALIDÉ` | Badge vert |
| Autre | Badge orange |

**Statut Production :**

| Valeur | Description |
|--------|-------------|
| `À PRODUIRE` | En attente de production |
| `EN COURS` | Production en cours |
| `À PEINDRE` | En attente de peinture |
| `À TERMINER` | En finition |
| `BON POUR EXPÉDITION` | Prêt à expédier |

### 4.4. Modifier une carte

1. Cliquer sur le bouton **Editer** (icone crayon) de la carte.
2. Une modale s'ouvre affichant les valeurs actuelles et un formulaire de modification.
3. Champs modifiables :
   - **Matière** : Saisie libre avec autocomplétion
   - **Statut MP** : Liste déroulante
   - **Statut Production** : Liste déroulante
   - **Postlaquage** : Oui / Non
4. Cliquer sur **Enregistrer** pour valider ou **Annuler** pour fermer.

> **Raccourci** : Appuyer sur `Échap` ou cliquer en dehors de la modale pour la fermer.

### 4.5. Déplanifier une carte

Cliquer sur le bouton **Déplanifier** (icone corbeille) sur une carte planifiée. La carte retourne dans l'onglet "Non planifiées".

---

## 5. Glisser-déposer (Drag & Drop)

Le glisser-déposer est le moyen principal d'organiser le planning.

### Actions possibles

| Depuis | Vers | Résultat |
|--------|------|----------|
| Onglet "Non planifiées" | Groupe d'une semaine | Planifie la carte dans ce groupe |
| Onglet "Non planifiées" | Zone "Nouveau groupe" | Crée un nouveau groupe avec cette carte |
| Groupe d'une semaine | Groupe d'une autre semaine | Déplace la carte vers l'autre semaine/groupe |
| Groupe d'une semaine | Zone "Nouveau groupe" d'une semaine | Crée un nouveau groupe dans la semaine cible |
| Carte planifiée | Au sein du même groupe | Réordonne les cartes dans le groupe |

### Indicateurs visuels

- Les zones de dépôt sont mises en surbrillance lors du survol avec une carte
- Un indicateur de position apparaît dans les groupes pour montrer où la carte sera déposée

---

## 6. Groupes de production

Les groupes permettent d'organiser les cartes au sein d'une semaine par lot de production, matière, ou tout autre critère.

### Créer un groupe

Glisser une carte vers la zone **"Nouveau groupe"** d'une semaine. Un groupe est automatiquement créé avec un nom par défaut.

### Renommer un groupe

Cliquer directement sur le nom du groupe dans l'en-tête. Le texte devient éditable. Saisir le nouveau nom et valider (Entrée ou clic en dehors).

> Le nom du groupe ne peut pas être vide.

### Replier/Déplier un groupe

Cliquer sur l'en-tête du groupe pour replier ou déplier son contenu.

### Informations affichées par groupe

- Nom du groupe (éditable)
- Nombre d'éléments dans le groupe
- Quantité totale des éléments

---

## 7. Gestion des matières premières

### 7.1. Accéder à la modale Matières Premières

Cliquer sur le bouton **Matières Premières** dans la barre latérale gauche.

### 7.2. Tableau des matières premières

Le tableau affiche pour chaque matière :

| Colonne | Description |
|---------|-------------|
| **CODE MP** | Code unique de la matière (lien cliquable si URL configurée) |
| **STOCK** | Stock physique actuel (modifiable) |
| **CDE EN COURS** | Quantité calculée automatiquement depuis les commandes en cours |
| **CDE EN COURS à date** | Valeur de snapshot (modifiable manuellement) |
| **RESTE** | Disponible = Stock - CDE EN COURS à date |
| **DATE DE MàJ** | Date de dernière modification |

### 7.3. Alertes couleur

| Couleur | Signification |
|---------|---------------|
| **Rouge** | Alerte stock : le reste est inférieur ou égal à 0 (stock insuffisant) |
| **Orange** | Désynchronisation : la valeur "CDE EN COURS" calculée diffère de "CDE EN COURS à date" |

### 7.4. Mettre à jour le stock

1. Cliquer sur le champ **Stock** de la matière concernée.
2. Saisir la nouvelle valeur.
3. Cliquer sur le bouton de validation.

### 7.5. Synchroniser les commandes en cours

Cliquer sur le bouton **Rafraîchir** en bas de la modale. Le système recalcule automatiquement les quantités de commandes en cours pour chaque matière en analysant :
- Les commandes validées (statut = 1)
- Non facturées
- Avec un statut production "À PRODUIRE" ou "EN COURS"

La valeur calculée est comparée à la valeur "à date" pour détecter les écarts (affichés en orange).

---

## 8. Exports et impressions

### 8.1. Types d'export

Le module propose plusieurs vues d'export, accessibles depuis le bouton **Export Global** ou via les actions par semaine :

| Type | Description | Contenu |
|------|-------------|---------|
| **Non planifiées** | Cartes non assignées à une semaine | Toutes les lignes en attente de planification |
| **Planifiées** | Cartes planifiées par semaine | Groupées par semaine et par groupe de production |
| **À terminer** | Cartes en finition | Statut "À TERMINER" |
| **À peindre** | Cartes nécessitant peinture | Postlaquage = Oui |
| **À expédier** | Cartes prêtes à l'envoi | Statut "BON POUR EXPÉDITION" |
| **Global** | Export complet | Toutes les catégories ci-dessus combinées |

### 8.2. Contenu de l'export

Chaque export génère un tableau HTML avec les colonnes suivantes :

| Colonne | Description |
|---------|-------------|
| COMMANDE | Numéro de commande |
| REF | Référence produit |
| DÉLAI | Délai de livraison |
| PRODUIT | Désignation du produit |
| MATIÈRE | Code matière première |
| QTÉ | Quantité commandée |
| LIVRAISON | Adresse de livraison |
| STATUTS | Badges MP, AR et Production |

### 8.3. Codes couleurs dans les exports

- **Fond jaune** : Postlaquage requis
- **Bordure gauche verte** : MP Ok ET AR Validé
- **Bordure gauche rouge** : Conditions non remplies

### 8.4. Imprimer

Les exports sont optimisés pour l'impression. Utiliser la fonction d'impression du navigateur (`Ctrl+P` / `Cmd+P`) depuis la page d'export. Les éléments d'interface (boutons, menus) sont automatiquement masqués à l'impression.

---

## 9. Configuration

La page de configuration est accessible via **Configuration** dans la barre latérale ou via le menu Dolibarr **Accueil > Configuration > Modules > PlanningProduction**.

### 9.1. Paramètres généraux

| Paramètre | Description | Valeur par défaut | Min | Max |
|-----------|-------------|-------------------|-----|-----|
| Largeur des cartes Kanban | Largeur en pixels des cartes dans le planning | 260 px | 200 px | 1000 px |

### 9.2. Gestion des matières premières (admin)

Depuis la page de configuration, les administrateurs peuvent :

#### Ajouter une matière première

1. Remplir le formulaire en haut de la section "Matières Premières" :
   - **Code MP** : Code unique (ex. : "400 BLANC")
   - **Stock initial** : Quantité en stock (décimal, ex. : 150.50)
   - **Lien** : URL optionnelle vers la fiche fournisseur
2. Cliquer sur **Ajouter**

#### Modifier une matière première

Cliquer sur le bouton **Modifier** de la ligne concernée. Les champs deviennent éditables. Valider avec **Enregistrer**.

#### Supprimer une matière première

Cliquer sur le bouton **Supprimer** de la ligne concernée. Une confirmation est demandée.

#### Réordonner les matières premières

Glisser les matières à l'aide de l'icone de poignée pour modifier l'ordre d'affichage. L'ordre est sauvegardé automatiquement.

---

## 10. Permissions

Le module utilise le système de permissions Dolibarr avec deux niveaux :

| Permission | Description | Accès |
|------------|-------------|-------|
| `planningproduction > planning > read` | Lecture | Consulter le planning, voir les matières premières, accéder aux exports |
| `planningproduction > planning > write` | Écriture | Modifier les cartes, déplacer les cartes, gérer les matières premières, modifier les groupes |

La page de configuration (`admin/setup.php`) est réservée aux **administrateurs Dolibarr**.

---

## 11. FAQ et résolution de problèmes

### Le planning est vide, aucune carte n'apparaît

- Vérifiez que des **commandes validées** (statut = 1) existent dans Dolibarr.
- Vérifiez que les commandes contiennent des **produits manufacturés** (produit.finished = 1).
- Vérifiez que l'année et la semaine sélectionnées correspondent à la période souhaitée.

### Les extrafields ne s'affichent pas sur les cartes

- Vérifiez que les champs supplémentaires `matiere`, `statut_mp`, `statut_prod`, `statut_ar` et `postlaquage` sont bien configurés sur les lignes de commande dans Dolibarr.

### La table des matières premières n'existe pas

Ce message apparaît si les tables n'ont pas été créées lors de l'activation du module. **Désactivez puis réactivez le module** depuis la page des modules Dolibarr pour recréer les tables.

### Le drag & drop ne fonctionne pas

- Vérifiez que vous disposez des droits en **écriture** sur le module.
- Vérifiez que JavaScript est activé dans votre navigateur.
- Essayez de rafraîchir la page (`F5`).

### Les valeurs "CDE EN COURS" et "CDE EN COURS à date" sont différentes

C'est normal. "CDE EN COURS" est calculé en temps réel depuis les commandes, tandis que "CDE EN COURS à date" est une valeur de snapshot que vous pouvez synchroniser manuellement. Utilisez le bouton **Rafraîchir** dans la modale des matières premières pour synchroniser.

### Les bordures des cartes sont toutes rouges

Les cartes ont une bordure verte uniquement lorsque **les deux conditions** sont remplies :
1. Le statut MP contient "MP Ok"
2. Le statut AR est "AR VALIDÉ"

Si l'une des deux conditions manque, la bordure est rouge.

### Comment exporter vers Excel/PDF ?

Le module génère des exports au format HTML optimisé pour l'impression. Pour obtenir un PDF, utilisez la fonction d'impression du navigateur et sélectionnez "Enregistrer en PDF". Pour Excel, copiez-collez le tableau depuis la page d'export.

---

*Module PlanningProduction v1.0.0 - Licence GPLv3+*
