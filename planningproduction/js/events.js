/* 
 * Planning de Production - Gestion des événements
 * Copyright (C) 2024 Patrick Delcroix
 */

// === GESTION DES ÉVÉNEMENTS DES CARTES ===
function attachCardEvents(card) {
    if (!card) return;
    
    // Mettre à jour la bordure selon les statuts MP/AR
    updateCardBorder(card);
    
    // Bouton éditer
    const editBtn = card.querySelector('.card-btn-edit');
    if (editBtn) {
        // Nettoyer l'ancien événement en clonant
        const newEditBtn = editBtn.cloneNode(true);
        editBtn.parentNode.replaceChild(newEditBtn, editBtn);
        
        newEditBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            openEditModal(card);
        });
    }
    
    // Bouton supprimer (seulement dans le planning, pas dans les onglets)
    const deleteBtn = card.querySelector('.card-btn-delete');
    if (deleteBtn) {
        const isInTabZone = card.closest('.tab-body');
        
        if (isInTabZone) {
            // Masquer le bouton dans les onglets
            deleteBtn.style.display = 'none';
        } else {
            // Afficher et gérer le bouton dans le planning
            deleteBtn.style.display = 'inline-block';
            
            // Nettoyer l'ancien événement
            const newDeleteBtn = deleteBtn.cloneNode(true);
            deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
            
            newDeleteBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                
                const tiersElement = card.querySelector('.card-tiers');
                const title = tiersElement ? tiersElement.textContent : 'Carte sans titre';
                
                if (confirm('Voulez-vous vraiment déplanifier "' + title + '" et la remettre dans "Non planifiées" ?')) {
                    const fkCommande = card.dataset.fkCommande;
                    const fkCommandedet = card.dataset.fkCommandedet;
                    
                    if (!fkCommande || !fkCommandedet) {
                        showToast('Erreur : données de la carte manquantes', 'error');
                        return;
                    }
                    
                    moveCardToUnplanned(fkCommande, fkCommandedet)
                        .then(success => {
                            if (success) {
                                showToast('Carte "' + title + '" déplanifiée avec succès', 'success');
                                
                                // Animation de disparition
                                card.style.transition = 'all 0.5s ease';
                                card.style.opacity = '0';
                                card.style.transform = 'translateX(-20px) scale(0.8)';
                                
                                setTimeout(() => {
                                    if (card && card.parentNode) {
                                        card.parentNode.removeChild(card);
                                    }
                                    setTimeout(() => window.location.reload(), 500);
                                }, 500);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la déplanification:', error);
                            showToast('Erreur lors de la déplanification : ' + error.message, 'error');
                        });
                }
            });
        }
    }
}

// === GESTION DES ÉVÉNEMENTS DES GROUPES ===
function attachGroupEvents(group) {
    if (!group) return;
    
    const groupHeader = group.querySelector('.group-header');
    if (groupHeader) {
        // Rendre le header draggable
        groupHeader.draggable = true;
        
        console.log('Groupe rendu draggable:', group.querySelector('input[type="text"]')?.value || 'Sans nom');
    }
    
    // Gestion de l'input du nom de groupe
    const groupNameInput = group.querySelector('input[type="text"]');
    if (groupNameInput) {
        // Sauvegarder quand l'utilisateur quitte le champ
        groupNameInput.addEventListener('blur', function() {
            console.log('Nom de groupe modifié (blur):', this.value);
            if (typeof saveGroupName === 'function') {
                saveGroupName(group);
            }
        });
        
        // Sauvegarder quand l'utilisateur appuie sur Entrée
        groupNameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur(); // Déclencher le blur qui va sauvegarder
                console.log('Nom de groupe modifié (Enter):', this.value);
            }
        });
        
        // Empêcher la propagation du drag sur l'input
        groupNameInput.addEventListener('mousedown', function(e) {
            e.stopPropagation();
        });
        
        groupNameInput.addEventListener('dragstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
        
        console.log('Événements attachés au nom de groupe:', groupNameInput.value || 'Vide');
    }
    
    // Bouton toggle du groupe
    const toggleButton = group.querySelector('.group-toggle');
    if (toggleButton) {
        toggleButton.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleGroup(this);
        });
    }
}

// === FONCTIONS UTILITAIRES ===
function updateCardBorder(card) {
    if (!card) return;
    
    const statusBadges = card.querySelectorAll('.status-badge');
    let hasMpOk = false;
    let hasArValide = false;
    
    statusBadges.forEach(badge => {
        if (badge.classList.contains('badge-mp-ok')) {
            hasMpOk = true;
        }
        if (badge.classList.contains('badge-ar-ok')) {
            hasArValide = true;
        }
    });
    
    // Supprimer les anciennes classes
    card.classList.remove('border-green', 'border-red');
    
    // Appliquer la nouvelle couleur
    if (hasMpOk && hasArValide) {
        card.classList.add('border-green');
        console.log('Bordure verte appliquée - MP OK et AR VALIDÉ');
    } else {
        card.classList.add('border-red');
        console.log('Bordure rouge appliquée - Conditions non remplies:', { hasMpOk, hasArValide });
    }
}

// === FONCTIONS DE GROUPES ===
function toggleGroup(button) {
    const group = button.closest('.production-group');
    if (!group) return;
    
    const groupCards = group.querySelector('.group-cards');
    if (!groupCards) return;
    
    if (groupCards.classList.contains('collapsed')) {
        groupCards.classList.remove('collapsed');
        button.textContent = '🔽';
        console.log('Groupe étendu');
    } else {
        groupCards.classList.add('collapsed');
        button.textContent = '▶️';
        console.log('Groupe réduit');
    }
}

// === FONCTIONS AJAX ===
function moveCardToUnplanned(fkCommande, fkCommandedet) {
    const formData = new FormData();
    formData.append('action', 'move_card');
    formData.append('fk_commande', fkCommande);
    formData.append('fk_commandedet', fkCommandedet);
    
    // Ajouter le token CSRF si disponible
    if (window.DOLIBARR_PLANNING_CONFIG && window.DOLIBARR_PLANNING_CONFIG.current_token) {
        formData.append('token', window.DOLIBARR_PLANNING_CONFIG.current_token);
    }
    
    return fetch('ajax_planning.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Erreur de sauvegarde');
        }
        return true;
    });
}

function moveCardToGroup(fkCommande, fkCommandedet, semaine, annee, groupeNom) {
    const formData = new FormData();
    formData.append('action', 'move_card');
    formData.append('fk_commande', fkCommande);
    formData.append('fk_commandedet', fkCommandedet);
    formData.append('semaine', semaine);
    formData.append('annee', annee);
    formData.append('groupe_nom', groupeNom);
    formData.append('ordre_groupe', 0);
    formData.append('ordre_semaine', 0);
    
    // Ajouter le token CSRF si disponible
    if (window.DOLIBARR_PLANNING_CONFIG && window.DOLIBARR_PLANNING_CONFIG.current_token) {
        formData.append('token', window.DOLIBARR_PLANNING_CONFIG.current_token);
    }
    
    return fetch('ajax_planning.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Erreur de sauvegarde');
        }
        return true;
    });
}

// === GÉNÉRATION DE NOMS UNIQUES ===
function generateUniqueGroupName(weekElement) {
    if (!weekElement) return 'Nouveau Groupe';
    
    // Trouver tous les groupes existants dans cette semaine
    const existingGroups = weekElement.querySelectorAll('.production-group input[type="text"]');
    const existingNames = Array.from(existingGroups).map(input => input.value.toLowerCase());
    
    // Générer un nom unique
    let baseName = 'Nouveau Groupe';
    let counter = 1;
    let uniqueName = baseName;
    
    while (existingNames.includes(uniqueName.toLowerCase())) {
        counter++;
        uniqueName = baseName + ' ' + counter;
    }
    
    console.log('Nom de groupe généré:', uniqueName, 'dans une semaine contenant:', existingNames);
    return uniqueName;
}

// === INITIALISATION GLOBALE ===
function initializeAllEvents() {
    console.log('Initialisation des événements...');
    
    // Attacher les événements aux cartes existantes
    document.querySelectorAll('.kanban-card').forEach(card => {
        attachCardEvents(card);
    });
    
    // Attacher les événements aux groupes existants
    document.querySelectorAll('.production-group').forEach(group => {
        attachGroupEvents(group);
    });
    
    console.log('Événements attachés:', {
        cartes: document.querySelectorAll('.kanban-card').length,
        groupes: document.querySelectorAll('.production-group').length
    });
}

// === OBSERVATEUR DE MUTATIONS (pour les nouveaux éléments) ===
function initializeMutationObserver() {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    // Nouvelles cartes
                    if (node.classList && node.classList.contains('kanban-card')) {
                        attachCardEvents(node);
                        console.log('Nouveaux événements attachés à une carte');
                    }
                    
                    // Nouveaux groupes
                    if (node.classList && node.classList.contains('production-group')) {
                        attachGroupEvents(node);
                        console.log('Nouveaux événements attachés à un groupe');
                    }
                    
                    // Chercher des cartes/groupes dans les nœuds ajoutés
                    const newCards = node.querySelectorAll ? node.querySelectorAll('.kanban-card') : [];
                    const newGroups = node.querySelectorAll ? node.querySelectorAll('.production-group') : [];
                    
                    newCards.forEach(attachCardEvents);
                    newGroups.forEach(attachGroupEvents);
                    
                    if (newCards.length > 0 || newGroups.length > 0) {
                        console.log('Événements attachés aux nouveaux éléments:', {
                            cartes: newCards.length,
                            groupes: newGroups.length
                        });
                    }
                }
            });
        });
    });
    
    // Observer les changements dans le conteneur principal
    const planningContainer = document.querySelector('.planning-container');
    if (planningContainer) {
        observer.observe(planningContainer, {
            childList: true,
            subtree: true
        });
        console.log('Observateur de mutations initialisé');
    }
}
