/* 
 * Planning de Production - Variables et fonctions principales
 * Copyright (C) 2024 Patrick Delcroix
 */

// === EXTENSION DATE POUR NUMÉRO DE SEMAINE ===
// Extension de Date pour obtenir le numéro de semaine
Date.prototype.getWeek = function() {
    const date = new Date(this.getTime());
    date.setHours(0, 0, 0, 0);
    // Thursday in current week decides the year.
    date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
    // January 4 is always in week 1.
    const week1 = new Date(date.getFullYear(), 0, 4);
    // Adjust to Thursday in week 1 and count weeks from there.
    return 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
};

// === VARIABLES GLOBALES ===
let draggedCard = null;
let draggedGroup = null;
let draggedFromContainer = null;
let dragType = null;
let currentEditCard = null;
let currentWeek = parseInt(document.querySelector('input[name="start_week"]')?.value || new Date().getWeek());
// Variables globales attachées à window pour être partagées entre les fichiers
window.activeTab = 'unplanned';

// === FONCTIONS DE NOTIFICATION ===
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// === FONCTIONS DE SAUVEGARDE D'ORDRE ===

/**
 * Sauvegarder l'ordre des cartes dans un groupe
 */
function saveGroupCardOrder(groupElement) {
    console.log('Sauvegarde ordre cartes dans groupe...');
    
    const weekRow = groupElement.closest('.week-row');
    if (!weekRow) {
        console.error('Week row non trouvée');
        return;
    }
    
    const weekHeader = weekRow.querySelector('.week-header span');
    if (!weekHeader) {
        console.error('Week header non trouvé');
        return;
    }
    
    const weekMatch = weekHeader.textContent.match(/SEMAINE (\d+)/);
    const weekNum = weekMatch ? parseInt(weekMatch[1]) : 0;
    const year = parseInt(document.querySelector('select[name="year"]')?.value || new Date().getFullYear());
    const groupNameInput = groupElement.querySelector('input[type="text"]');
    const groupName = groupNameInput ? groupNameInput.value : 'Groupe par défaut';
    
    if (!weekNum) {
        console.error('Numéro de semaine non trouvé');
        return;
    }
    
    // Récupérer toutes les cartes du groupe dans l'ordre
    const cards = Array.from(groupElement.querySelectorAll('.kanban-card'));
    const updates = [];
    
    cards.forEach((card, index) => {
        const fkCommande = card.dataset.fkCommande;
        const fkCommandedet = card.dataset.fkCommandedet;
        
        if (fkCommande && fkCommandedet) {
            updates.push({
                fk_commande: parseInt(fkCommande),
                fk_commandedet: parseInt(fkCommandedet),
                semaine: weekNum,
                annee: year,
                groupe_nom: groupName,
                ordre_groupe: index,
                ordre_semaine: 0 // Sera mis à jour par saveWeekGroupOrder si nécessaire
            });
        }
    });
    
    if (updates.length === 0) {
        console.log('Aucune carte à sauvegarder');
        return;
    }
    
    console.log('Envoi mise à jour ordre cartes:', updates);
    
    // Envoyer au serveur
    sendOrderUpdates(updates, 'Ordre des cartes sauvegardé');
}

/**
 * Sauvegarder le nom d'un groupe (et mettre à jour toutes ses cartes)
 * NOUVELLE FONCTION pour corriger le bug du nom de groupe non sauvegardé
 */
function saveGroupName(groupElement) {
    console.log('Sauvegarde du nom de groupe...');
    
    if (!groupElement) {
        console.error('Élément groupe non fourni');
        return;
    }
    
    const weekRow = groupElement.closest('.week-row');
    if (!weekRow) {
        console.error('Week row non trouvée');
        return;
    }
    
    const weekHeader = weekRow.querySelector('.week-header span');
    if (!weekHeader) {
        console.error('Week header non trouvé');
        return;
    }
    
    const weekMatch = weekHeader.textContent.match(/SEMAINE (\d+)/);
    const weekNum = weekMatch ? parseInt(weekMatch[1]) : 0;
    const year = parseInt(document.querySelector('select[name="year"]')?.value || new Date().getFullYear());
    const groupNameInput = groupElement.querySelector('input[type="text"]');
    const groupName = groupNameInput ? groupNameInput.value.trim() : '';
    
    if (!weekNum) {
        console.error('Numéro de semaine non trouvé');
        return;
    }
    
    if (!groupName) {
        console.error('Nom de groupe vide');
        showToast('Le nom du groupe ne peut pas être vide', 'error');
        return;
    }
    
    console.log('Sauvegarde du nom de groupe:', groupName, 'pour semaine', weekNum);
    
    // Récupérer toutes les cartes du groupe
    const cards = Array.from(groupElement.querySelectorAll('.kanban-card'));
    
    if (cards.length === 0) {
        console.log('Aucune carte dans le groupe, sauvegarde annulée');
        return;
    }
    
    // Préparer les mises à jour pour toutes les cartes
    const updates = [];
    
    cards.forEach((card, index) => {
        const fkCommande = card.dataset.fkCommande;
        const fkCommandedet = card.dataset.fkCommandedet;
        
        if (fkCommande && fkCommandedet) {
            updates.push({
                fk_commande: parseInt(fkCommande),
                fk_commandedet: parseInt(fkCommandedet),
                semaine: weekNum,
                annee: year,
                groupe_nom: groupName,  // ✅ Nouveau nom du groupe
                ordre_groupe: index,
                ordre_semaine: 0 // Sera calculé si nécessaire
            });
        }
    });
    
    if (updates.length === 0) {
        console.log('Aucune carte à mettre à jour');
        return;
    }
    
    console.log('Mise à jour du nom de groupe pour', updates.length, 'cartes:', updates);
    
    // Envoyer au serveur
    sendOrderUpdates(updates, 'Nom du groupe "' + groupName + '" sauvegardé');
}

/**
 * Sauvegarder l'ordre des groupes dans une semaine
 */
function saveWeekGroupOrder(weekGroupsElement) {
    console.log('Sauvegarde ordre groupes dans semaine...');
    
    const weekRow = weekGroupsElement.closest('.week-row');
    if (!weekRow) {
        console.error('Week row non trouvée');
        return;
    }
    
    const weekHeader = weekRow.querySelector('.week-header span');
    if (!weekHeader) {
        console.error('Week header non trouvé');
        return;
    }
    
    const weekMatch = weekHeader.textContent.match(/SEMAINE (\d+)/);
    const weekNum = weekMatch ? parseInt(weekMatch[1]) : 0;
    const year = parseInt(document.querySelector('select[name="year"]')?.value || new Date().getFullYear());
    
    if (!weekNum) {
        console.error('Numéro de semaine non trouvé');
        return;
    }
    
    // Récupérer tous les groupes dans l'ordre
    const groups = Array.from(weekGroupsElement.querySelectorAll('.production-group'));
    const updates = [];
    
    groups.forEach((group, groupIndex) => {
        const groupNameInput = group.querySelector('input[type="text"]');
        const groupName = groupNameInput ? groupNameInput.value : 'Groupe par défaut';
        
        // Récupérer toutes les cartes de ce groupe
        const cards = Array.from(group.querySelectorAll('.kanban-card'));
        
        cards.forEach((card, cardIndex) => {
            const fkCommande = card.dataset.fkCommande;
            const fkCommandedet = card.dataset.fkCommandedet;
            
            if (fkCommande && fkCommandedet) {
                updates.push({
                    fk_commande: parseInt(fkCommande),
                    fk_commandedet: parseInt(fkCommandedet),
                    semaine: weekNum,
                    annee: year,
                    groupe_nom: groupName,
                    ordre_groupe: cardIndex,
                    ordre_semaine: groupIndex
                });
            }
        });
    });
    
    if (updates.length === 0) {
        console.log('Aucune carte à sauvegarder');
        return;
    }
    
    console.log('Envoi mise à jour ordre groupes:', updates);
    
    // Envoyer au serveur
    sendOrderUpdates(updates, 'Ordre des groupes sauvegardé');
}

/**
 * Envoyer les mises à jour d'ordre au serveur
 */
function sendOrderUpdates(updates, successMessage) {
    const formData = new FormData();
    formData.append('action', 'update_group_order');
    formData.append('updates', JSON.stringify(updates));
    formData.append('token', document.querySelector('input[name="token"]')?.value || '');
    
    fetch('ajax_planning.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Ordre sauvegardé avec succès');
            showToast(successMessage, 'success');
        } else {
            console.error('Erreur sauvegarde ordre:', data.error);
            showToast('Erreur de sauvegarde: ' + (data.error || 'Erreur inconnue'), 'error');
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        showToast('Erreur de communication avec le serveur', 'error');
    });
}

// === FONCTIONS D'EXPORT ===

/**
 * Export global de tous les éléments
 */
function exportGlobal() {
    showToast('Génération de l\'export global...', 'info');
    
    // Récupérer les paramètres actuels
    const year = document.querySelector('select[name="year"]')?.value || new Date().getFullYear();
    const startWeek = document.querySelector('select[name="start_week"]')?.value || new Date().getWeek();
    const weekCount = document.querySelector('select[name="week_count"]')?.value || 5;
    
    // Construire l'URL
    const params = new URLSearchParams({
        type: 'global',
        year: year,
        start_week: startWeek,
        week_count: weekCount
    });
    
    // Ouvrir dans un nouvel onglet
    const url = 'export_planning.php?' + params.toString();
    window.open(url, '_blank');
}

/**
 * Export des éléments non planifiés
 */
function exportUnplanned() {
    showToast('Export des éléments non planifiés...', 'info');
    
    const url = 'export_planning.php?type=unplanned';
    window.open(url, '_blank');
}

/**
 * Export des éléments à terminer
 */
function exportToFinish() {
    showToast('Export des éléments à terminer...', 'info');
    
    const url = 'export_planning.php?type=to_finish';
    window.open(url, '_blank');
}

/**
 * Export des éléments à expédier
 */
function exportToShip() {
    showToast('Export des éléments à expédier...', 'info');
    
    const url = 'export_planning.php?type=to_ship';
    window.open(url, '_blank');
}

/**
 * Export des éléments planifiés
 */
function exportPlanned() {
    showToast('Export des éléments planifiés...', 'info');
    
    // Récupérer les paramètres actuels
    const year = document.querySelector('select[name="year"]')?.value || new Date().getFullYear();
    const startWeek = document.querySelector('select[name="start_week"]')?.value || new Date().getWeek();
    const weekCount = document.querySelector('select[name="week_count"]')?.value || 5;
    
    // Construire l'URL
    const params = new URLSearchParams({
        type: 'planned',
        year: year,
        start_week: startWeek,
        week_count: weekCount
    });
    
    const url = 'export_planning.php?' + params.toString();
    window.open(url, '_blank');
}

/**
 * Export d'une semaine spécifique
 */
function exportSemaine(week) {
    showToast('Export de la semaine ' + week + '...', 'info');
    
    const year = document.querySelector('select[name="year"]')?.value || new Date().getFullYear();
    
    // Pour une semaine spécifique, on utilise l'export planifié avec une seule semaine
    const params = new URLSearchParams({
        type: 'planned',
        year: year,
        start_week: week,
        week_count: 1
    });
    
    const url = 'export_planning.php?' + params.toString();
    window.open(url, '_blank');
}

// === FONCTIONS PRINCIPALES ===

function synchroniser() {
    showToast('Synchronisation en cours...', 'info');
    setTimeout(() => window.location.reload(), 1000);
}

function validerSemaine(week) {
    const formData = new FormData();
    formData.append('action', 'validate_week');
    formData.append('semaine', week);
    formData.append('annee', parseInt(document.querySelector('select[name="year"]')?.value || new Date().getFullYear()));
    formData.append('token', document.querySelector('input[name="token"]')?.value || '');
    
    fetch('ajax_planning.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Semaine validée ' + week, 'success');
        } else {
            showToast(data.error || 'Erreur de sauvegarde', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Erreur de sauvegarde', 'error');
    });
}

function navigatePrevWeek() {
    const select = document.getElementById('startWeekSelect');
    let newWeek = parseInt(select.value) - 1;
    if (newWeek < 1) newWeek = 1;
    select.value = newWeek;
    select.form.submit();
}

function navigateNextWeek() {
    const select = document.getElementById('startWeekSelect');
    let newWeek = parseInt(select.value) + 1;
    if (newWeek > 52) newWeek = 52;
    select.value = newWeek;
    select.form.submit();
}

// === FONCTIONS UTILITAIRES ===
function updateCardBorder(card) {
    const statusBadges = card.querySelectorAll('.status-badge');
    let hasMpOk = false;
    let hasArValide = false;
    
    statusBadges.forEach(badge => {
        // Vérifier les classes CSS plutôt que le texte pour plus de fiabilité
        if (badge.classList.contains('badge-mp-ok')) {
            hasMpOk = true;
        }
        if (badge.classList.contains('badge-ar-ok')) {
            hasArValide = true;
        }
    });
    
    // Supprimer les anciennes classes de bordure
    card.classList.remove('border-green', 'border-red');
    
    // Bordure verte si MP OK ET AR VALIDÉ, rouge sinon
    if (hasMpOk && hasArValide) {
        card.classList.add('border-green');
        console.log('Bordure verte appliquée - MP OK et AR VALIDÉ');
    } else {
        card.classList.add('border-red');
        console.log('Bordure rouge appliquée - Conditions non remplies:', { hasMpOk, hasArValide });
    }
}

function generateUniqueGroupName(weekElement) {
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

// === FONCTIONS AJAX ===

/**
 * Déplacer un groupe complet vers une autre semaine
 */
function moveGroupToWeek(targetWeekGroups) {
    console.log('Déplacement groupe vers nouvelle semaine...');
    
    if (!draggedGroup) {
        console.error('Aucun groupe en cours de déplacement');
        return false;
    }
    
    // Récupérer les informations de la semaine de destination
    const targetWeekRow = targetWeekGroups.closest('.week-row');
    if (!targetWeekRow) {
        console.error('Semaine de destination non trouvée');
        return false;
    }
    
    const targetWeekHeader = targetWeekRow.querySelector('.week-header span');
    if (!targetWeekHeader) {
        console.error('Header de la semaine de destination non trouvé');
        return false;
    }
    
    const targetWeekMatch = targetWeekHeader.textContent.match(/SEMAINE (\d+)/);
    const targetWeekNum = targetWeekMatch ? parseInt(targetWeekMatch[1]) : 0;
    
    if (!targetWeekNum) {
        console.error('Numéro de semaine de destination non trouvé');
        return false;
    }
    
    // Récupérer les informations du groupe source
    const groupNameInput = draggedGroup.querySelector('input[type="text"]');
    const groupName = groupNameInput ? groupNameInput.value : 'Groupe déplacé';
    const year = parseInt(document.querySelector('select[name="year"]')?.value || new Date().getFullYear());
    
    // Récupérer toutes les cartes du groupe
    const cards = Array.from(draggedGroup.querySelectorAll('.kanban-card'));
    
    if (cards.length === 0) {
        console.warn('Aucune carte dans le groupe à déplacer');
        showToast('Le groupe est vide', 'warning');
        return false;
    }
    
    // Préparer les promesses de déplacement pour toutes les cartes
    const movePromises = cards.map((card, index) => {
        const fkCommande = card.dataset.fkCommande;
        const fkCommandedet = card.dataset.fkCommandedet;
        
        if (!fkCommande || !fkCommandedet) {
            console.warn('Données de carte manquantes');
            return Promise.resolve(false);
        }
        
        return moveCardToGroup(
            fkCommande, 
            fkCommandedet, 
            targetWeekNum, 
            year, 
            groupName
        );
    });
    
    // Exécuter tous les déplacements
    Promise.all(movePromises)
        .then(results => {
            const successCount = results.filter(result => result === true).length;
            const totalCount = cards.length;
            
            if (successCount === totalCount) {
                showToast(`Groupe "${groupName}" déplacé vers semaine ${targetWeekNum} (${successCount} cartes)`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(`Déplacement partiel: ${successCount}/${totalCount} cartes déplacées`, 'warning');
                setTimeout(() => window.location.reload(), 1500);
            }
        })
        .catch(error => {
            console.error('Erreur lors du déplacement du groupe:', error);
            showToast('Erreur lors du déplacement du groupe', 'error');
        });
    
    // Nettoyer les indicateurs
    targetWeekGroups.classList.remove('group-drag-over');
    document.querySelectorAll('.group-drop-indicator').forEach(indicator => {
        indicator.remove();
    });
    
    return false;
}

function moveCardToUnplanned(fkCommande, fkCommandedet) {
    const formData = new FormData();
    formData.append('action', 'move_card');
    formData.append('fk_commande', fkCommande);
    formData.append('fk_commandedet', fkCommandedet);
    formData.append('token', document.querySelector('input[name="token"]')?.value || '');
    
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
    formData.append('token', document.querySelector('input[name="token"]')?.value || '');
    
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

// === INITIALISATION PRINCIPALE ===
function initializePlanning() {
    console.log('Planning: Initialisation...');
    
    // Initialiser les onglets
    if (typeof initializeTabs === 'function') {
        initializeTabs();
    }
    
    // Initialiser le drag & drop
    if (typeof enableDragAndDrop === 'function') {
        enableDragAndDrop();
    }
    
    // Attacher les événements aux éléments existants
    if (typeof initializeAllEvents === 'function') {
        initializeAllEvents();
    }
    
    // Initialiser l'observateur de mutations pour les nouveaux éléments
    if (typeof initializeMutationObserver === 'function') {
        initializeMutationObserver();
    }
    
    console.log('Planning: Initialisation terminée avec succès!');
}