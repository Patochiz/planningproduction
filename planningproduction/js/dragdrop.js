/* 
 * Planning de Production - Gestion Drag & Drop
 * Copyright (C) 2024 Patrick Delcroix
 */

// === GESTIONNAIRES DRAG & DROP CARTES ===
function handleCardDragStart(e) {
    draggedCard = this;
    draggedFromContainer = this.closest('.group-cards, .tab-body');
    dragType = 'card';
    this.classList.add('dragging');
    
    // Définir les données de transfert
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.outerHTML);
    
    console.log('Début drag carte:', {
        fkCommande: this.dataset.fkCommande,
        fkCommandedet: this.dataset.fkCommandedet
    });
}

function handleCardDragEnd(e) {
    this.classList.remove('dragging');
    
    // Nettoyer les indicateurs
    document.querySelectorAll('.drop-target').forEach(el => {
        el.classList.remove('drop-target');
    });
    document.querySelectorAll('.card-drop-indicator').forEach(el => {
        el.remove();
    });
    
    // Réinitialiser les variables
    draggedCard = null;
    draggedFromContainer = null;
    dragType = null;
}

// === GESTIONNAIRES DRAG & DROP GROUPES ===
function handleGroupDragStart(e) {
    draggedGroup = this.closest('.production-group');
    draggedFromContainer = draggedGroup.closest('.week-groups');
    dragType = 'group';
    draggedGroup.classList.add('group-dragging');
    
    // Définir les données de transfert
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', draggedGroup.outerHTML);
    
    const groupName = draggedGroup.querySelector('input[type="text"]')?.value || 'Sans nom';
    console.log('Début drag groupe:', groupName);
}

function handleGroupDragEnd(e) {
    if (draggedGroup) {
        draggedGroup.classList.remove('group-dragging');
    }
    
    // Nettoyer les indicateurs
    document.querySelectorAll('.group-drag-over').forEach(el => {
        el.classList.remove('group-drag-over');
    });
    document.querySelectorAll('.group-drop-indicator').forEach(el => {
        el.remove();
    });
    
    // Réinitialiser les variables
    draggedGroup = null;
    draggedFromContainer = null;
    dragType = null;
}

// === GESTIONNAIRES DRAG OVER / DROP ===
function handleDragOver(e) {
    if (e.preventDefault) e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    if (dragType === 'card') {
        if (this.classList.contains('production-group')) {
            showCardDropIndicator(e, this);
        } else if (this.classList.contains('new-group-zone') || 
                  this.classList.contains('empty-week') ||
                  this.classList.contains('tab-body')) {
            // Pas d'indicateur spécial pour ces zones
        }
    } else if (dragType === 'group') {
        if (this.classList.contains('week-groups')) {
            showGroupDropIndicator(e, this);
        }
    }
    
    return false;
}

function handleDragEnter(e) {
    if (dragType === 'card') {
        if (this.classList.contains('new-group-zone') || 
            this.classList.contains('empty-week') ||
            this.classList.contains('production-group') ||
            this.classList.contains('tab-body')) {
            this.classList.add('drop-target');
        }
    } else if (dragType === 'group') {
        if (this.classList.contains('week-groups')) {
            this.classList.add('group-drag-over');
        }
    }
}

function handleDragLeave(e) {
    if (!this.contains(e.relatedTarget)) {
        this.classList.remove('drop-target');
        this.classList.remove('group-drag-over');
        this.querySelectorAll('.card-drop-indicator').forEach(indicator => {
            indicator.remove();
        });
        this.querySelectorAll('.group-drop-indicator').forEach(indicator => {
            indicator.remove();
        });
    }
}

function handleDrop(e) {
    if (e.stopPropagation) e.stopPropagation();
    
    if (dragType === 'card') {
        return handleCardDrop(e, this);
    } else if (dragType === 'group') {
        return handleGroupDrop(e, this);
    }
    
    return false;
}

// === FONCTIONS DE DROP ===
function handleCardDrop(e, dropZone) {
    if (!draggedCard) return false;
    
    const fkCommande = draggedCard.dataset.fkCommande;
    const fkCommandedet = draggedCard.dataset.fkCommandedet;
    
    if (!fkCommande || !fkCommandedet) {
        showToast('Erreur : données de la carte manquantes', 'error');
        return false;
    }
    
    let actionPromise = null;
    let actionText = '';
    
    if (dropZone.classList.contains('tab-body')) {
        // Déplanifier la carte
        actionPromise = moveCardToUnplanned(fkCommande, fkCommandedet);
        actionText = 'Carte déplanifiée';
        
    } else if (dropZone.classList.contains('production-group')) {
        // Vérifier si c'est le même groupe (réorganisation)
        const sourceGroup = draggedCard.closest('.production-group');
        if (sourceGroup === dropZone) {
            return handleCardReorderInGroup(e, dropZone);
        } else {
            // Déplacer vers un groupe différent
            const weekRow = dropZone.closest('.week-row');
            const weekHeader = weekRow.querySelector('.week-header span').textContent;
            const weekNum = parseInt(weekHeader.match(/SEMAINE (\d+)/)?.[1] || '0');
            const groupName = dropZone.querySelector('input[type="text"]')?.value || 'Groupe par défaut';
            
            actionPromise = moveCardToGroup(fkCommande, fkCommandedet, weekNum, getCurrentYear(), groupName);
            actionText = 'Ajouté au groupe "' + groupName + '"';
        }
        
    } else if (dropZone.classList.contains('new-group-zone')) {
        // Créer un nouveau groupe
        const week = parseInt(dropZone.dataset.week);
        const weekElement = dropZone.closest('.week-row');
        const groupName = generateUniqueGroupName(weekElement);
        
        actionPromise = moveCardToGroup(fkCommande, fkCommandedet, week, getCurrentYear(), groupName);
        actionText = 'Groupe créé "' + groupName + '" en semaine ' + week;
        
    } else if (dropZone.classList.contains('empty-week')) {
        // Planifier dans une semaine vide
        const week = parseInt(dropZone.dataset.week);
        const weekElement = dropZone.closest('.week-row');
        const groupName = generateUniqueGroupName(weekElement);
        
        actionPromise = moveCardToGroup(fkCommande, fkCommandedet, week, getCurrentYear(), groupName);
        actionText = 'Planifié en semaine ' + week + ' avec groupe "' + groupName + '"';
    }
    
    if (actionPromise) {
        actionPromise.then(success => {
            if (success) {
                showToast(actionText, 'success');
                setTimeout(() => window.location.reload(), 1000);
            }
        }).catch(error => {
            console.error('Drop error:', error);
            showToast('Erreur de sauvegarde', 'error');
        });
    }
    
    // Nettoyer les indicateurs
    dropZone.classList.remove('drop-target');
    document.querySelectorAll('.card-drop-indicator').forEach(ind => ind.remove());
    
    return false;
}

function handleGroupDrop(e, dropZone) {
    console.log('handleGroupDrop appelé avec:', {
        draggedGroup: !!draggedGroup,
        draggedFromContainer: !!draggedFromContainer,
        dropZoneClasses: dropZone.className,
        isWeekGroups: dropZone.classList.contains('week-groups')
    });
    
    if (!draggedGroup) {
        console.error('Pas de groupe en cours de déplacement');
        return false;
    }
    
    if (dropZone.classList.contains('week-groups')) {
        const sourceWeekGroups = draggedFromContainer;
        
        console.log('Drop sur week-groups:', {
            memeSource: sourceWeekGroups === dropZone,
            sourceElement: sourceWeekGroups?.closest('.week-row')?.querySelector('.week-header span')?.textContent,
            targetElement: dropZone.closest('.week-row')?.querySelector('.week-header span')?.textContent
        });
        
        if (sourceWeekGroups === dropZone) {
            // Réorganisation dans la même semaine
            console.log('Réorganisation dans la même semaine');
            return handleGroupReorderInWeek(e, dropZone);
        } else {
            // Déplacer vers une semaine différente
            console.log('Déplacement vers une semaine différente');
            return moveGroupToWeek(dropZone);
        }
    } else {
        console.warn('Drop zone n\'est pas week-groups:', dropZone.className);
    }
    
    return false;
}

// === INDICATEURS DE DROP ===
function showCardDropIndicator(e, group) {
    // Supprimer les anciens indicateurs
    group.querySelectorAll('.card-drop-indicator').forEach(indicator => {
        indicator.remove();
    });
    
    const groupCards = group.querySelector('.group-cards');
    if (!groupCards) return;
    
    const cards = Array.from(groupCards.querySelectorAll('.kanban-card:not(.dragging)'));
    const mouseX = e.clientX;
    
    let insertIndex = cards.length;
    
    // Déterminer où insérer l'indicateur
    for (let i = 0; i < cards.length; i++) {
        const cardRect = cards[i].getBoundingClientRect();
        const cardMiddle = cardRect.left + cardRect.width / 2;
        
        if (mouseX < cardMiddle) {
            insertIndex = i;
            break;
        }
    }
    
    // Créer l'indicateur
    const indicator = document.createElement('div');
    indicator.className = 'card-drop-indicator';
    indicator.style.cssText = `
        width: 3px;
        height: 100px;
        background: #27ae60;
        border-radius: 2px;
        margin: 0 3px;
        opacity: 0.8;
        flex-shrink: 0;
    `;
    
    // Insérer l'indicateur
    if (insertIndex === 0 && cards.length > 0) {
        groupCards.insertBefore(indicator, cards[0]);
    } else if (insertIndex >= cards.length) {
        groupCards.appendChild(indicator);
    } else {
        groupCards.insertBefore(indicator, cards[insertIndex]);
    }
}

function showGroupDropIndicator(e, weekGroups) {
    // Supprimer les anciens indicateurs
    weekGroups.querySelectorAll('.group-drop-indicator').forEach(indicator => {
        indicator.remove();
    });
    
    const groups = Array.from(weekGroups.querySelectorAll('.production-group:not(.group-dragging)'));
    const mouseY = e.clientY;
    
    let insertIndex = groups.length;
    
    // Déterminer où insérer l'indicateur
    for (let i = 0; i < groups.length; i++) {
        const groupRect = groups[i].getBoundingClientRect();
        const groupMiddle = groupRect.top + groupRect.height / 2;
        
        if (mouseY < groupMiddle) {
            insertIndex = i;
            break;
        }
    }
    
    // Créer l'indicateur
    const indicator = document.createElement('div');
    indicator.className = 'group-drop-indicator';
    indicator.style.cssText = `
        height: 4px;
        background: #27ae60;
        border-radius: 2px;
        margin: 5px 0;
        opacity: 0.8;
        width: 100%;
    `;
    
    // Insérer l'indicateur
    if (insertIndex === 0 && groups.length > 0) {
        weekGroups.insertBefore(indicator, groups[0]);
    } else if (insertIndex >= groups.length) {
        const newGroupZone = weekGroups.querySelector('.new-group-zone');
        if (newGroupZone) {
            weekGroups.insertBefore(indicator, newGroupZone);
        } else {
            weekGroups.appendChild(indicator);
        }
    } else {
        weekGroups.insertBefore(indicator, groups[insertIndex]);
    }
}

// === RÉORGANISATION ===
function handleCardReorderInGroup(e, group) {
    const groupCards = group.querySelector('.group-cards');
    const indicator = groupCards.querySelector('.card-drop-indicator');
    
    if (!indicator) {
        return false;
    }
    
    const cards = Array.from(groupCards.querySelectorAll('.kanban-card:not(.dragging)'));
    let newIndex = cards.length;
    
    // Trouver la position de l'indicateur
    for (let i = 0; i < groupCards.children.length; i++) {
        if (groupCards.children[i] === indicator) {
            newIndex = i;
            break;
        }
    }
    
    // Déplacer la carte
    if (newIndex < cards.length) {
        groupCards.insertBefore(draggedCard, cards[newIndex]);
    } else {
        groupCards.appendChild(draggedCard);
    }
    
    // Nettoyer
    indicator.remove();
    draggedCard.classList.remove('dragging');
    group.classList.remove('drop-target');
    
    // Sauvegarder l'ordre (si la fonction existe)
    if (typeof saveGroupCardOrder === 'function') {
        saveGroupCardOrder(group);
    }
    
    showToast('Ordre des cartes mis à jour', 'success');
    
    return false;
}

function handleGroupReorderInWeek(e, weekGroups) {
    const indicator = weekGroups.querySelector('.group-drop-indicator');
    
    if (!indicator) {
        return false;
    }
    
    const groups = Array.from(weekGroups.querySelectorAll('.production-group:not(.group-dragging)'));
    let newIndex = groups.length;
    
    // Trouver la position de l'indicateur
    for (let i = 0; i < weekGroups.children.length; i++) {
        if (weekGroups.children[i] === indicator) {
            newIndex = i;
            break;
        }
    }
    
    // Déplacer le groupe
    if (newIndex === 0) {
        weekGroups.insertBefore(draggedGroup, weekGroups.firstChild);
    } else if (newIndex >= weekGroups.children.length - 1) {
        weekGroups.appendChild(draggedGroup);
    } else {
        weekGroups.insertBefore(draggedGroup, weekGroups.children[newIndex]);
    }
    
    // Nettoyer
    indicator.remove();
    draggedGroup.classList.remove('group-dragging');
    weekGroups.classList.remove('group-drag-over');
    
    // Sauvegarder l'ordre (si la fonction existe)
    if (typeof saveWeekGroupOrder === 'function') {
        saveWeekGroupOrder(weekGroups);
    }
    
    showToast('Ordre des groupes mis à jour', 'success');
    
    return false;
}

// === FONCTION D'ACTIVATION ===
function enableDragAndDrop() {
    // Cartes draggables
    document.querySelectorAll('.kanban-card').forEach(card => {
        card.draggable = true;
        card.addEventListener('dragstart', handleCardDragStart);
        card.addEventListener('dragend', handleCardDragEnd);
    });

    // Headers de groupes draggables
    document.querySelectorAll('.group-header').forEach(header => {
        header.draggable = true;
        header.addEventListener('dragstart', handleGroupDragStart);
        header.addEventListener('dragend', handleGroupDragEnd);
    });

    // Zones de drop
    document.querySelectorAll('.tab-body, .production-group, .new-group-zone, .empty-week, .week-groups').forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('drop', handleDrop);
        zone.addEventListener('dragenter', handleDragEnter);
        zone.addEventListener('dragleave', handleDragLeave);
    });
    
    // Debug détaillé
    const weekGroupsZones = document.querySelectorAll('.week-groups');
    console.log('Drag & Drop activé pour', {
        cartes: document.querySelectorAll('.kanban-card').length,
        groupes: document.querySelectorAll('.group-header').length,
        zones: document.querySelectorAll('.tab-body, .production-group, .new-group-zone, .empty-week, .week-groups').length,
        weekGroupsZones: weekGroupsZones.length,
        weekGroupsDetails: Array.from(weekGroupsZones).map(zone => {
            const weekRow = zone.closest('.week-row');
            const weekHeader = weekRow?.querySelector('.week-header span')?.textContent;
            return {
                weekText: weekHeader,
                hasClass: zone.classList.contains('week-groups'),
                element: zone
            };
        })
    });
    
    // Vérifier que moveGroupToWeek existe
    console.log('moveGroupToWeek function exists:', typeof moveGroupToWeek === 'function');
}

// === FONCTIONS UTILITAIRES ===
function getCurrentYear() {
    return window.DOLIBARR_PLANNING_CONFIG ? window.DOLIBARR_PLANNING_CONFIG.year : new Date().getFullYear();
}
