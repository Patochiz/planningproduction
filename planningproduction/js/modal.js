/* 
 * Planning de Production - Gestion des modales
 * Copyright (C) 2024 Patrick Delcroix
 */

// === MODAL D'ÉDITION ===
function openEditModal(card) {
    currentEditCard = card;
    const modal = document.getElementById('editModal');
    
    if (!modal) {
        console.error('Modal d\'édition non trouvée');
        return;
    }
    
    // Extraire le titre complet de la carte (N° Commande V{version} Client / Ref chantier)
    const cardTitleElement = card.querySelector('.card-title');
    let fullTitle = cardTitleElement ? cardTitleElement.textContent.trim() : '';
    
    // Extraire le nom du client depuis le lien dans le titre
    const tiersElement = card.querySelector('.card-tiers');
    const clientValue = tiersElement ? tiersElement.textContent.trim() : '-';
    
    // NOUVEAU : Extraire le ref_chantier depuis le titre
    // Le titre est au format : "N° Commande V{version} Client / Ref chantier" ou "N° Commande V{version} Client"
    let refChantierValue = '-';
    if (fullTitle.includes(' / ')) {
        const parts = fullTitle.split(' / ');
        refChantierValue = parts.length > 1 ? parts[1].trim() : '-';
    }
    
    // MODIFICATION : Rechercher dans différentes structures de carte
    let refValue = '-', productValue = '-', matiereValue = '-';
    
    // Rechercher dans les lignes simples (.card-row-single)
    const singleRows = card.querySelectorAll('.card-row-single');
    singleRows.forEach(row => {
        const label = row.querySelector('.card-label');
        const value = row.querySelector('.card-value');
        if (label && value) {
            const labelText = label.textContent.trim();
            if (labelText.includes('Ref')) {
                refValue = value.textContent.trim();
            }
        }
    });
    
    // Rechercher dans les lignes duales (.card-row-dual > .card-col)
    const dualRows = card.querySelectorAll('.card-row-dual');
    dualRows.forEach(row => {
        const cols = row.querySelectorAll('.card-col');
        cols.forEach(col => {
            const label = col.querySelector('.card-label');
            const value = col.querySelector('.card-value');
            if (label && value) {
                const labelText = label.textContent.trim();
                if (labelText.includes('Matière') || labelText.includes('Matiere')) {
                    matiereValue = value.textContent.trim();
                }
            }
        });
    });
    
    // Rechercher dans les anciennes structures (.card-row) pour compatibilité
    const cardRows = card.querySelectorAll('.card-row');
    cardRows.forEach(row => {
        const label = row.querySelector('.card-label');
        const value = row.querySelector('.card-value, .card-client');
        if (label && value) {
            const labelText = label.textContent.trim();
            if (labelText.includes('Ref')) {
                refValue = value.textContent.trim();
            } else if (labelText.includes('Produit')) {
                productValue = value.textContent.trim();
            } else if (labelText.includes('Matière') || labelText.includes('Matiere')) {
                matiereValue = value.textContent.trim();
            }
        }
    });
    
    // Rechercher dans la grille (.card-grid)
    const gridCells = card.querySelectorAll('.card-grid-cell');
    gridCells.forEach(cell => {
        const label = cell.querySelector('.card-label');
        const value = cell.querySelector('.card-value');
        if (label && value) {
            const labelText = label.textContent.trim();
            if (labelText.includes('Matière') || labelText.includes('Matiere')) {
                matiereValue = value.textContent.trim();
            }
        }
    });
    
    // Si le produit n'est pas dans les lignes, le récupérer depuis les cellules de la grille
    if (productValue === '-') {
        const productCell = card.querySelector('.card-grid-product');
        if (productCell) {
            // Récupérer le texte sans les enfants (liens)
            const productLink = productCell.querySelector('a');
            productValue = productLink ? productLink.textContent.trim() : productCell.textContent.trim();
        }
    }
    
    const productionStatusElement = card.querySelector('.badge-production');
    let currentProductionStatus = productionStatusElement ? productionStatusElement.textContent.trim() : 'À PRODUIRE';
    
    // Debug pour vérifier les valeurs extraites
    console.log('Valeurs extraites de la carte:', {
        client: clientValue,
        ref: refValue,
        refChantier: refChantierValue,
        produit: productValue,
        matiere: matiereValue,
        statut_prod: currentProductionStatus
    });
    
    // Remplir les informations actuelles
    const elements = {
        'editCurrentTitle': clientValue,
        'editCurrentClient': refValue,
        'editCurrentRefChantier': refChantierValue,  // NOUVEAU
        'editCurrentOrder': productValue,
        'editMatiere': matiereValue === '-' ? '' : matiereValue,
        'editProductionStatus': currentProductionStatus
    };
    
    for (const [id, value] of Object.entries(elements)) {
        const element = document.getElementById(id);
        if (element) {
            if (element.tagName.toLowerCase() === 'input' || element.tagName.toLowerCase() === 'select') {
                element.value = value;
            } else {
                element.textContent = value;
            }
        }
    }
    
    // Détecter les statuts actuels depuis les badges
    const statusBadges = card.querySelectorAll('.status-badge');
    statusBadges.forEach(badge => {
        const badgeText = badge.textContent.trim().toUpperCase();
        
        if (badgeText.includes('MP OK')) {
            document.getElementById('editMpStatus').value = 'MP Ok,MP Ok';
        } else if (badgeText.includes('MP EN ATTENTE') || badgeText.includes('MP ATTENTE')) {
            document.getElementById('editMpStatus').value = 'MP en attente,MP en attente';
        } else if (badgeText.includes('MP MANQUANTE')) {
            document.getElementById('editMpStatus').value = 'MP Manquante,MP Manquante';
        } else if (badgeText.includes('BL A FAIRE')) {
            document.getElementById('editMpStatus').value = 'BL A FAIRE,BL A FAIRE';
        } else if (badgeText.includes('PROFORMA')) {
            document.getElementById('editMpStatus').value = 'PROFORMA A VALIDER,PROFORMA A VALIDER';
        } else if (badgeText.includes('AIRTABLE')) {
            document.getElementById('editMpStatus').value = 'MàJ AIRTABLE à Faire,MàJ AIRTABLE à Faire';
        }
    });
    
    // Détecter si la carte doit être peinte
    const hasPaintIcon = card.querySelector('.badge-peindre');
    const hasYellowBg = card.classList.contains('paint-required');
    document.getElementById('editPeindre').value = (hasPaintIcon || hasYellowBg) ? 'oui' : 'non';
    
    updateBadgePreview('mp', document.getElementById('editMpStatus').value);
    modal.classList.add('show');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (!modal) return;
    
    modal.classList.remove('show');
    currentEditCard = null;
    
    // Reset du formulaire
    const form = document.getElementById('editForm');
    if (form) {
        form.reset();
    }
    
    // Nettoyer les badges de prévisualisation
    document.querySelectorAll('.edit-badge-preview').forEach(badge => {
        badge.textContent = '';
        badge.className = 'edit-badge-preview';
    });
}

function updateBadgePreview(type, value) {
    const previewElement = document.getElementById(type + 'StatusPreview');
    if (!previewElement) return;
    
    previewElement.className = 'edit-badge-preview';
    
    if (type === 'mp') {
        switch(value) {
            case 'MP Ok,MP Ok':
                previewElement.textContent = 'MP OK';
                previewElement.classList.add('green');
                break;
            case 'MP en attente,MP en attente':
                previewElement.textContent = 'MP EN ATTENTE';
                previewElement.classList.add('red');
                break;
            case 'MP Manquante,MP Manquante':
                previewElement.textContent = 'MP MANQUANTE';
                previewElement.classList.add('red');
                break;
            case 'BL A FAIRE,BL A FAIRE':
                previewElement.textContent = 'BL A FAIRE';
                previewElement.classList.add('red');
                break;
            case 'PROFORMA A VALIDER,PROFORMA A VALIDER':
                previewElement.textContent = 'PROFORMA A VALIDER';
                previewElement.classList.add('red');
                break;
            case 'MàJ AIRTABLE à Faire,MàJ AIRTABLE à Faire':
                previewElement.textContent = 'MAJ AIRTABLE A FAIRE';
                previewElement.classList.add('red');
                break;
            default:
                previewElement.textContent = '';
        }
    }
}

function saveCardEdit() {
    if (!currentEditCard) return;
    
    const fkCommandedet = currentEditCard.dataset.fkCommandedet;
    const matiereValue = document.getElementById('editMatiere').value.trim();
    const mpStatus = document.getElementById('editMpStatus').value;
    const prodStatus = document.getElementById('editProductionStatus').value;
    const peindreStatus = document.getElementById('editPeindre').value;
    
    if (!fkCommandedet) {
        showToast('Erreur : données de la carte manquantes', 'error');
        return;
    }
    
    // Déterminer si la carte va changer d'emplacement (planning vers onglets)
    let willMoveToTabs = false;
    let newTabLocation = '';
    
    if (prodStatus === 'À TERMINER') {
        willMoveToTabs = true;
        newTabLocation = 'onglet "À terminer"';
    } else if (prodStatus === 'BON POUR EXPÉDITION') {
        willMoveToTabs = true;
        newTabLocation = 'onglet "À expédier"';
    }
    
    const formData = new FormData();
    formData.append('action', 'update_card');
    formData.append('fk_commandedet', fkCommandedet);
    formData.append('matiere', matiereValue);
    formData.append('statut_mp', mpStatus);
    formData.append('statut_prod', prodStatus);
    formData.append('postlaquage', peindreStatus);
    
    // Ajouter le token CSRF si disponible
    if (window.DOLIBARR_PLANNING_CONFIG && window.DOLIBARR_PLANNING_CONFIG.current_token) {
        formData.append('token', window.DOLIBARR_PLANNING_CONFIG.current_token);
    }
    
    fetch('ajax_planning.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = 'Carte mise à jour';
            
            if (willMoveToTabs) {
                message += ' et déplacée vers ' + newTabLocation;
            }
            
            showToast(message, 'success');
            
            // Mettre à jour la bordure de la carte immédiatement
            if (currentEditCard && typeof updateCardBorder === 'function') {
                updateCardBorder(currentEditCard);
            }
            
            closeEditModal();
            
            // Si la carte va changer d'emplacement, recharger la page
            if (willMoveToTabs) {
                setTimeout(() => window.location.reload(), 1500);
            } else {
                setTimeout(() => window.location.reload(), 1000);
            }
        } else {
            showToast(data.error || 'Erreur de sauvegarde', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Erreur de sauvegarde', 'error');
    });
}

// Fermer la modal en cliquant en dehors
document.addEventListener('click', function(e) {
    const modal = document.getElementById('editModal');
    if (modal && e.target === modal) {
        closeEditModal();
    }
});

// Fermer la modal avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('editModal');
        if (modal && modal.classList.contains('show')) {
            closeEditModal();
        }
    }
});
