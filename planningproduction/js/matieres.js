/**
 * Gestion du modal des matières premières - VERSION avec CDE EN COURS à date
 * 
 * Fonctionnalités:
 * - Affichage du modal avec tableau des matières premières
 * - Édition du stock en temps réel
 * - Édition des commandes en cours à date
 * - Calcul des commandes en cours (temps réel)
 * - Calcul automatique du reste (stock - commandes en cours à date)
 * - Mise en évidence des stocks insuffisants
 * - Alerte visuelle quand CDE EN COURS ≠ CDE EN COURS à date (désynchronisation)
 * - Bouton MàJ pour synchroniser cde_en_cours_date avec cde_en_cours calculé
 */

// Variables globales
let matieresData = [];
let isMatieresModalOpen = false;

/**
 * Ouvrir le modal des matières premières
 */
function openMatieresModal() {
    console.log('Ouverture du modal matières premières');
    
    const modal = document.getElementById('matieresModal');
    if (!modal) {
        console.error('Modal matières premières non trouvé');
        return;
    }
    
    modal.style.display = 'block';
    isMatieresModalOpen = true;
    
    // Charger les données
    loadMatieresData();
    
    // Gérer l'escape pour fermer
    document.addEventListener('keydown', handleMatieresModalEscape);
}

/**
 * Fermer le modal des matières premières
 */
function closeMatieresModal() {
    console.log('Fermeture du modal matières premières');
    
    const modal = document.getElementById('matieresModal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    isMatieresModalOpen = false;
    
    // Retirer le listener d'escape
    document.removeEventListener('keydown', handleMatieresModalEscape);
}

/**
 * Gérer la touche Escape pour fermer le modal
 */
function handleMatieresModalEscape(e) {
    if (e.key === 'Escape' && isMatieresModalOpen) {
        closeMatieresModal();
    }
}

/**
 * Charger les données des matières premières
 */
function loadMatieresData() {
    console.log('Chargement des données matières premières...');
    
    const container = document.getElementById('matieresTableContainer');
    if (!container) return;
    
    // Afficher le spinner de chargement
    container.innerHTML = `
        <div class="loading-spinner" style="text-align: center; padding: 50px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p>Chargement des données...</p>
        </div>
    `;
    
    // Requête AJAX pour récupérer les matières
    fetch('ajax_matieres.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_matieres&token=${window.DOLIBARR_PLANNING_CONFIG.current_token}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Données reçues:', data);
        
        if (data.success) {
            matieresData = data.data || [];
            renderMatieresTable();
        } else {
            showMatieresError(data.message || 'Erreur lors du chargement des données');
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        showMatieresError('Erreur de communication avec le serveur');
    });
}

/**
 * Afficher le tableau des matières premières
 */
function renderMatieresTable() {
    console.log('Rendu du tableau avec', matieresData.length, 'matières');
    
    const container = document.getElementById('matieresTableContainer');
    if (!container) return;
    
    if (matieresData.length === 0) {
        container.innerHTML = `
            <div class="matiere-message info">
                <strong>Aucune matière première configurée</strong><br>
                <small>Configurez vos matières premières dans les paramètres du module pour voir les stocks et quantités en cours.</small>
            </div>
        `;
        return;
    }
    
    let html = `
        <table class="matieres-table">
            <thead>
                <tr>
                    <th>CODE MP</th>
                    <th>STOCK</th>
                    <th>CDE EN COURS</th>
                    <th style="background: #f39c12; color: white;">CDE EN COURS à date</th>
                    <th>RESTE</th>
                    <th>DATE DE MàJ</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    matieresData.forEach(matiere => {
        const reste = parseFloat(matiere.stock) - parseFloat(matiere.cde_en_cours_date);
        const isStockAlert = reste <= 0;
        const isDesync = matiere.is_desync || false;

        // Classes cumulables sur la ligne
        let rowClasses = [];
        if (isDesync) {
            rowClasses.push('row-desync');
        }
        if (isStockAlert) {
            rowClasses.push('row-stock-alert');
        }

        html += `
            <tr class="${rowClasses.join(' ')}" data-rowid="${matiere.rowid}">
                <td><strong>${escapeHtml(matiere.code_mp)}</strong></td>
                <td class="numeric-cell">
                    <input type="number" 
                           class="stock-editable" 
                           value="${matiere.stock}" 
                           step="0.01"
                           data-rowid="${matiere.rowid}"
                           onchange="updateStock(${matiere.rowid}, this.value)"
                           onblur="updateStock(${matiere.rowid}, this.value)">
                </td>
                <td class="numeric-cell" data-field="cde_en_cours">${formatNumber(matiere.cde_en_cours)}</td>
                <td class="numeric-cell">
                    <input type="number" 
                           class="cde-editable" 
                           value="${matiere.cde_en_cours_date}" 
                           step="0.01"
                           data-rowid="${matiere.rowid}"
                           onchange="updateCdeEnCoursDate(${matiere.rowid}, this.value)"
                           onblur="updateCdeEnCoursDate(${matiere.rowid}, this.value)">
                </td>
                <td class="numeric-cell ${isStockAlert ? 'reste-alert' : ''}" data-field="reste">${formatNumber(reste)}</td>
                <td>${formatDate(matiere.date_maj)}</td>
                <td>
                    <button type="button" 
                            class="btn-update-cde"
                            onclick="syncCdeEnCours('${matiere.code_mp}', ${matiere.rowid})"
                            title="Synchroniser CDE EN COURS à date avec la valeur calculée">
                        MàJ
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
        
        <div style="margin-top: 15px; font-size: 13px; color: #666;">
            <p><strong>Légende :</strong></p>
            <ul style="margin: 5px 0; padding-left: 20px;">
                <li><strong>STOCK</strong> : Quantité disponible en stock (éditable)</li>
                <li><strong>CDE EN COURS</strong> : Somme des quantités des cartes ayant ce code MP (calculé en temps réel, hors À TERMINER/BON POUR EXPÉDITION)</li>
                <li style="color: #f39c12;"><strong>CDE EN COURS à date</strong> : Valeur figée des commandes en cours (éditable manuellement)</li>
                <li><strong>RESTE</strong> : Stock - CDE EN COURS à date</li>
                <li style="color: #e74c3c;"><strong>Ligne rouge clair</strong> : Stock insuffisant (reste ≤ 0)</li>
                <li style="color: #e67e22;"><strong>Ligne orange</strong> : Désynchronisation (CDE EN COURS ≠ CDE EN COURS à date) - Cliquez sur "MàJ" pour synchroniser</li>
            </ul>
            <p style="margin-top: 10px;"><em><strong>Note importante :</strong> Le bouton "MàJ" copie la valeur de "CDE EN COURS" (calculée) vers "CDE EN COURS à date" pour les synchroniser.</em></p>
        </div>
    `;
    
    container.innerHTML = html;
}

/**
 * Mettre à jour le stock d'une matière première
 */
function updateStock(rowid, newStock) {
    const stock = parseFloat(newStock);
    if (isNaN(stock) || stock < 0) {
        showMatieresError('Stock invalide');
        // Restaurer l'ancienne valeur
        const input = document.querySelector(`input.stock-editable[data-rowid="${rowid}"]`);
        if (input) {
            const matiere = matieresData.find(m => m.rowid == rowid);
            if (matiere) {
                input.value = matiere.stock;
            }
        }
        return;
    }
    
    console.log(`Mise à jour du stock pour rowid ${rowid}: ${stock}`);
    
    // Mise à jour optimiste de l'interface
    const matiere = matieresData.find(m => m.rowid == rowid);
    if (matiere) {
        matiere.stock = stock;
        updateRowReste(rowid);
    }
    
    // Envoi de la mise à jour au serveur
    fetch('ajax_matieres.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_stock&rowid=${rowid}&stock=${stock}&token=${window.DOLIBARR_PLANNING_CONFIG.current_token}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Stock mis à jour avec succès');
            showMatieresSuccess('Stock mis à jour');
        } else {
            console.error('Erreur mise à jour stock:', data.message);
            showMatieresError(data.message || 'Erreur lors de la mise à jour du stock');
            // Recharger les données pour avoir les valeurs correctes
            loadMatieresData();
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        showMatieresError('Erreur de communication');
        // Recharger les données pour avoir les valeurs correctes
        loadMatieresData();
    });
}

/**
 * Mettre à jour les commandes en cours à date d'une matière première
 */
function updateCdeEnCoursDate(rowid, newCdeEnCoursDate) {
    const cdeEnCoursDate = parseFloat(newCdeEnCoursDate);
    if (isNaN(cdeEnCoursDate) || cdeEnCoursDate < 0) {
        showMatieresError('Valeur de commandes en cours à date invalide');
        // Restaurer l'ancienne valeur
        const input = document.querySelector(`input.cde-editable[data-rowid="${rowid}"]`);
        if (input) {
            const matiere = matieresData.find(m => m.rowid == rowid);
            if (matiere) {
                input.value = matiere.cde_en_cours_date;
            }
        }
        return;
    }
    
    console.log(`Mise à jour des CDE EN COURS à date pour rowid ${rowid}: ${cdeEnCoursDate}`);
    
    // Mise à jour optimiste de l'interface
    const matiere = matieresData.find(m => m.rowid == rowid);
    if (matiere) {
        matiere.cde_en_cours_date = cdeEnCoursDate;
        matiere.is_desync = Math.abs(matiere.cde_en_cours - cdeEnCoursDate) > 0.01;
        updateRowReste(rowid);
        updateRowDesyncStatus(rowid);
    }
    
    // Envoi de la mise à jour au serveur
    fetch('ajax_matieres.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_cde_en_cours_date&rowid=${rowid}&cde_en_cours_date=${cdeEnCoursDate}&token=${window.DOLIBARR_PLANNING_CONFIG.current_token}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('CDE EN COURS à date mis à jour avec succès');
            showMatieresSuccess('Commandes en cours à date mises à jour');
        } else {
            console.error('Erreur mise à jour CDE EN COURS à date:', data.message);
            showMatieresError(data.message || 'Erreur lors de la mise à jour');
            // Recharger les données pour avoir les valeurs correctes
            loadMatieresData();
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        showMatieresError('Erreur de communication');
        // Recharger les données pour avoir les valeurs correctes
        loadMatieresData();
    });
}

/**
 * Synchroniser CDE EN COURS à date avec CDE EN COURS calculé
 * Cette fonction est appelée par le bouton "MàJ"
 */
function syncCdeEnCours(codeMP, rowid) {
    console.log(`Synchronisation CDE EN COURS pour code MP: ${codeMP}`);
    
    // Désactiver le bouton temporairement
    const button = document.querySelector(`button[onclick="syncCdeEnCours('${codeMP}', ${rowid})"]`);
    if (button) {
        button.disabled = true;
        button.innerHTML = '...';
    }
    
    fetch('ajax_matieres.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=sync_cde_en_cours&code_mp=${encodeURIComponent(codeMP)}&rowid=${rowid}&token=${window.DOLIBARR_PLANNING_CONFIG.current_token}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour les données locales
            const matiere = matieresData.find(m => m.rowid == rowid);
            if (matiere) {
                matiere.cde_en_cours = data.data.cde_en_cours;
                matiere.cde_en_cours_date = data.data.cde_en_cours_date;
                matiere.is_desync = false;
                
                // Mettre à jour l'affichage
                updateRowCdeEnCours(rowid, data.data.cde_en_cours);
                updateRowCdeEnCoursDate(rowid, data.data.cde_en_cours_date);
                updateRowReste(rowid);
                updateRowDesyncStatus(rowid);
            }
            
            showMatieresSuccess(`Synchronisation effectuée: ${formatNumber(data.data.cde_en_cours)}`);
        } else {
            showMatieresError(data.message || 'Erreur lors de la synchronisation');
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        showMatieresError('Erreur de communication');
    })
    .finally(() => {
        // Réactiver le bouton
        if (button) {
            button.disabled = false;
            button.innerHTML = 'MàJ';
        }
    });
}

/**
 * Actualiser toutes les données des matières
 */
function refreshMatieresData() {
    console.log('Actualisation complète des données matières');
    loadMatieresData();
}

/**
 * Mettre à jour une cellule CDE EN COURS
 */
function updateRowCdeEnCours(rowid, cdeEnCours) {
    const row = document.querySelector(`tr[data-rowid="${rowid}"]`);
    if (row) {
        const cell = row.querySelector('td[data-field="cde_en_cours"]');
        if (cell) {
            cell.textContent = formatNumber(cdeEnCours);
        }
    }
}

/**
 * Mettre à jour l'input CDE EN COURS à date
 */
function updateRowCdeEnCoursDate(rowid, cdeEnCoursDate) {
    const input = document.querySelector(`input.cde-editable[data-rowid="${rowid}"]`);
    if (input) {
        input.value = cdeEnCoursDate;
    }
}

/**
 * Mettre à jour une cellule RESTE et appliquer le style d'alerte si nécessaire
 */
function updateRowReste(rowid) {
    const matiere = matieresData.find(m => m.rowid == rowid);
    if (!matiere) return;
    
    const reste = parseFloat(matiere.stock) - parseFloat(matiere.cde_en_cours_date);
    const isStockAlert = reste <= 0;
    
    const row = document.querySelector(`tr[data-rowid="${rowid}"]`);
    if (row) {
        const cell = row.querySelector('td[data-field="reste"]');
        if (cell) {
            cell.textContent = formatNumber(reste);
            
            // Appliquer ou retirer le style d'alerte
            if (isStockAlert) {
                cell.classList.add('stock-alert');
            } else {
                cell.classList.remove('stock-alert');
            }
        }
    }
}

/**
 * Mettre à jour le statut de désynchronisation d'une ligne
 */
function updateRowDesyncStatus(rowid) {
    const matiere = matieresData.find(m => m.rowid == rowid);
    if (!matiere) return;
    
    const row = document.querySelector(`tr[data-rowid="${rowid}"]`);
    if (row) {
        const isDesync = Math.abs(matiere.cde_en_cours - matiere.cde_en_cours_date) > 0.01;
        const reste = parseFloat(matiere.stock) - parseFloat(matiere.cde_en_cours_date);
        const isStockAlert = reste <= 0;
        
        // Priorité: désynchronisation > stock alert
        row.classList.remove('row-desync', 'stock-alert');
        if (isDesync) {
            row.classList.add('row-desync');
        } else if (isStockAlert) {
            row.classList.add('stock-alert');
        }
    }
}

/**
 * Afficher un message de succès
 */
function showMatieresSuccess(message) {
    showMatieresMessage(message, 'success');
}

/**
 * Afficher un message d'erreur
 */
function showMatieresError(message) {
    showMatieresMessage(message, 'error');
}

/**
 * Afficher un message dans le modal
 */
function showMatieresMessage(message, type = 'info') {
    const container = document.getElementById('matieresTableContainer');
    if (!container) return;
    
    // Retirer les anciens messages
    const oldMessages = container.querySelectorAll('.matiere-message');
    oldMessages.forEach(msg => msg.remove());
    
    // Créer le nouveau message
    const messageEl = document.createElement('div');
    messageEl.className = `matiere-message ${type}`;
    messageEl.textContent = message;
    
    // Insérer au début du container
    container.insertBefore(messageEl, container.firstChild);
    
    // Faire disparaître après 5 secondes
    setTimeout(() => {
        if (messageEl.parentNode) {
            messageEl.parentNode.removeChild(messageEl);
        }
    }, 5000);
}

/**
 * Utilitaires
 */

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function formatNumber(value) {
    const num = parseFloat(value);
    if (isNaN(num)) return '0,00';
    
    return num.toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (isMatieresModalOpen && e.target.id === 'matieresModal') {
        closeMatieresModal();
    }
});

console.log('Module matières premières chargé (version avec CDE EN COURS à date)');
