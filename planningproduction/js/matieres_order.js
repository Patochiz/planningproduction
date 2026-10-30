/**
 * Planification Production - Gestion de l'ordre des matières premières
 * Copyright (C) 2024 Patrick Delcroix
 */

class MatieresOrderManager {
    constructor(tableSelector = '#matieres-sortable', options = {}) {
        this.tableSelector = tableSelector;
        // URL par défaut calculée à partir de la balise <script> (respecte DOL_URL_ROOT et le dossier custom réel)
        const tag = document.querySelector('script[src*="matieres_order.js"]');
        const tokenInput = document.querySelector('input[name="token"]');
        this.options = {
            handleSelector: '.drag-handle',
            rowSelector: 'tr[data-matiere-id]',
            saveUrl: tag ? tag.src.replace(/js\/matieres_order\.js.*$/, 'ajax_matieres_order.php') : 'ajax_matieres_order.php',
            token: tokenInput ? tokenInput.value : '',
            ...options
        };
        
        this.isDragging = false;
        this.draggedElement = null;
        this.placeholder = null;
        this.originalOrder = [];
        
        this.init();
    }
    
    init() {
        const table = document.querySelector(this.tableSelector);
        if (!table) {
            console.warn('Table des matières premières non trouvée:', this.tableSelector);
            return;
        }
        
        // Nettoyer les événements précédents (important après rechargement)
        this.cleanup();
        
        this.setupDragAndDrop(table);
        this.saveOriginalOrder();
        
        // Vérifier qu'on a assez de lignes
        const rows = table.querySelectorAll(this.options.rowSelector);
        console.log('MatieresOrderManager initialisé pour', rows.length, 'matières sur', this.tableSelector);
        
        if (rows.length < 2) {
            console.log('Pas assez de matières pour le drag & drop, fonctionnalité désactivée');
            return;
        }
    }
    
    cleanup() {
        // Nettoyer les anciennes instances et événements pour éviter les doublons
        const table = document.querySelector(this.tableSelector);
        if (table) {
            const rows = table.querySelectorAll(this.options.rowSelector);
            rows.forEach(row => {
                // Retirer les attributs de draggable
                row.draggable = false;
                row.style.cursor = '';
                
                // Retirer toutes les classes liées au drag & drop
                row.classList.remove('dragging', 'drag-over');
            });
        }
        
        // Nettoyer les variables d'instance
        this.isDragging = false;
        this.draggedElement = null;
        if (this.placeholder && this.placeholder.parentNode) {
            this.placeholder.parentNode.removeChild(this.placeholder);
        }
        this.placeholder = null;
        this.originalOrder = [];
    }
    
    setupDragAndDrop(table) {
        const rows = table.querySelectorAll(this.options.rowSelector);
        
        rows.forEach(row => {
            // Rendre la ligne draggable
            row.draggable = true;
            row.style.cursor = 'move';
            
            // Ajouter les gestionnaires d'événements
            row.addEventListener('dragstart', (e) => this.handleDragStart(e));
            row.addEventListener('dragend', (e) => this.handleDragEnd(e));
            row.addEventListener('dragover', (e) => this.handleDragOver(e));
            row.addEventListener('drop', (e) => this.handleDrop(e));
            row.addEventListener('dragenter', (e) => this.handleDragEnter(e));
            row.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        });
        
        // Style CSS pour le drag & drop
        this.addDragStyles();
    }
    
    addDragStyles() {
        if (document.getElementById('matieres-drag-styles')) {
            return; // Déjà ajouté
        }
        
        const style = document.createElement('style');
        style.id = 'matieres-drag-styles';
        style.textContent = `
            .matieres-table tr[draggable="true"] {
                transition: background-color 0.2s ease;
            }
            .matieres-table tr[draggable="true"]:hover {
                background-color: #f0f8ff !important;
            }
            .matieres-table .dragging {
                opacity: 0.5;
                background-color: #e3f2fd !important;
            }
            .matieres-table .drag-over {
                border-top: 2px solid #2196f3;
            }
            .matieres-table .drag-placeholder {
                height: 2px;
                background-color: #2196f3;
                margin: 0;
                padding: 0;
            }
            .drag-handle {
                cursor: grab;
                font-size: 16px;
                color: #666;
                margin-right: 5px;
            }
            .drag-handle:hover {
                color: #2196f3;
            }
        `;
        document.head.appendChild(style);
    }
    
    saveOriginalOrder() {
        const rows = document.querySelectorAll(`${this.tableSelector} ${this.options.rowSelector}`);
        this.originalOrder = Array.from(rows).map(row => 
            parseInt(row.getAttribute('data-matiere-id'))
        );
    }
    
    handleDragStart(e) {
        this.isDragging = true;
        this.draggedElement = e.target;
        
        // Ajouter la classe CSS
        this.draggedElement.classList.add('dragging');
        
        // Définir les données de transfert
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.draggedElement.outerHTML);
        
        console.log('Drag started:', this.draggedElement.getAttribute('data-matiere-id'));
    }
    
    handleDragEnd(e) {
        this.isDragging = false;
        
        // Nettoyer les classes CSS
        if (this.draggedElement) {
            this.draggedElement.classList.remove('dragging');
        }
        
        // Nettoyer les indicateurs visuels
        document.querySelectorAll('.drag-over').forEach(el => {
            el.classList.remove('drag-over');
        });
        
        if (this.placeholder && this.placeholder.parentNode) {
            this.placeholder.parentNode.removeChild(this.placeholder);
        }
        
        this.draggedElement = null;
        this.placeholder = null;
        
        console.log('Drag ended');
    }
    
    handleDragOver(e) {
        if (!this.isDragging) return;
        
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        
        const targetRow = e.target.closest(this.options.rowSelector);
        if (!targetRow || targetRow === this.draggedElement) return;
        
        // Déterminer si on doit insérer avant ou après
        const rect = targetRow.getBoundingClientRect();
        const midpoint = rect.top + rect.height / 2;
        const insertBefore = e.clientY < midpoint;
        
        // Créer ou déplacer le placeholder
        if (!this.placeholder) {
            this.placeholder = document.createElement('tr');
            this.placeholder.className = 'drag-placeholder';
            this.placeholder.innerHTML = '<td colspan="100%" style="height: 2px; padding: 0; background-color: #2196f3;"></td>';
        }
        
        if (insertBefore) {
            targetRow.parentNode.insertBefore(this.placeholder, targetRow);
        } else {
            targetRow.parentNode.insertBefore(this.placeholder, targetRow.nextSibling);
        }
    }
    
    handleDrop(e) {
        if (!this.isDragging || !this.draggedElement) return;
        
        e.preventDefault();
        
        const targetRow = e.target.closest(this.options.rowSelector);
        if (!targetRow || targetRow === this.draggedElement) return;
        
        // Déterminer la position d'insertion
        const rect = targetRow.getBoundingClientRect();
        const midpoint = rect.top + rect.height / 2;
        const insertBefore = e.clientY < midpoint;
        
        // Déplacer l'élément
        if (insertBefore) {
            targetRow.parentNode.insertBefore(this.draggedElement, targetRow);
        } else {
            targetRow.parentNode.insertBefore(this.draggedElement, targetRow.nextSibling);
        }
        
        // Sauvegarder le nouvel ordre
        this.saveNewOrder();
        
        console.log('Drop completed');
    }
    
    handleDragEnter(e) {
        if (!this.isDragging) return;
        
        const targetRow = e.target.closest(this.options.rowSelector);
        if (targetRow && targetRow !== this.draggedElement) {
            targetRow.classList.add('drag-over');
        }
    }
    
    handleDragLeave(e) {
        if (!this.isDragging) return;
        
        const targetRow = e.target.closest(this.options.rowSelector);
        if (targetRow) {
            targetRow.classList.remove('drag-over');
        }
    }
    
    getCurrentOrder() {
        const rows = document.querySelectorAll(`${this.tableSelector} ${this.options.rowSelector}`);
        return Array.from(rows).map(row => 
            parseInt(row.getAttribute('data-matiere-id'))
        );
    }
    
    hasOrderChanged() {
        const currentOrder = this.getCurrentOrder();
        return JSON.stringify(currentOrder) !== JSON.stringify(this.originalOrder);
    }
    
    async saveNewOrder() {
        if (!this.hasOrderChanged()) {
            console.log('Ordre inchangé, pas de sauvegarde nécessaire');
            return;
        }
        
        const newOrder = this.getCurrentOrder();
        console.log('Sauvegarde du nouvel ordre:', newOrder);
        
        try {
            // Afficher un indicateur de chargement
            this.showLoadingIndicator(true);
            
            const formData = new FormData();
            formData.append('action', 'reorder_matieres');
            formData.append('token', this.options.token);
            formData.append('order', JSON.stringify(newOrder));
            
            const response = await fetch(this.options.saveUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            // Lire la réponse en texte pour pouvoir afficher le message du serveur même en cas d'erreur HTTP
            const text = await response.text();
            let result = null;
            try {
                result = JSON.parse(text);
            } catch (e) {
                result = null;
            }

            if (!response.ok) {
                let detail = (result && result.error) ? result.error : (response.statusText || 'Erreur serveur');
                if (response.status === 404) {
                    detail = 'Script introuvable (' + this.options.saveUrl + ')';
                }
                throw new Error(`HTTP ${response.status} : ${detail}`);
            }

            if (!result) {
                throw new Error('Réponse invalide du serveur');
            }

            if (result.error) {
                throw new Error(result.error);
            }
            
            if (result.success) {
                // Mettre à jour l'ordre de référence
                this.originalOrder = [...newOrder];
                
                // Afficher un message de succès
                this.showMessage('Ordre mis à jour avec succès', 'success');
                
                console.log('Ordre sauvegardé avec succès');
            } else {
                throw new Error('Réponse inattendue du serveur');
            }
            
        } catch (error) {
            console.error('Erreur lors de la sauvegarde de l\'ordre:', error);
            this.showMessage('Erreur lors de la sauvegarde: ' + error.message, 'error');
            
            // Remettre les lignes dans leur ordre initial
            this.restoreOriginalOrder();
        } finally {
            this.showLoadingIndicator(false);
        }
    }
    
    restoreOriginalOrder() {
        const table = document.querySelector(this.tableSelector);
        if (!table) return;
        
        // Récupérer toutes les lignes et les trier selon l'ordre original
        const rows = Array.from(table.querySelectorAll(this.options.rowSelector));
        if (rows.length === 0) return;
        const tbody = rows[0].parentNode;
        
        // Trier selon l'ordre original
        rows.sort((a, b) => {
            const idA = parseInt(a.getAttribute('data-matiere-id'));
            const idB = parseInt(b.getAttribute('data-matiere-id'));
            return this.originalOrder.indexOf(idA) - this.originalOrder.indexOf(idB);
        });
        
        // Réinsérer les lignes dans le bon ordre
        rows.forEach(row => tbody.appendChild(row));
        
        console.log('Ordre original restauré');
    }
    
    showLoadingIndicator(show) {
        let indicator = document.getElementById('matieres-loading');
        
        if (show && !indicator) {
            indicator = document.createElement('div');
            indicator.id = 'matieres-loading';
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #2196f3;
                color: white;
                padding: 10px 15px;
                border-radius: 4px;
                z-index: 9999;
                font-size: 14px;
            `;
            indicator.textContent = 'Sauvegarde en cours...';
            document.body.appendChild(indicator);
        } else if (!show && indicator) {
            indicator.remove();
        }
    }
    
    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
            color: white;
            padding: 15px;
            border-radius: 4px;
            z-index: 9999;
            font-size: 14px;
            max-width: 300px;
        `;
        messageDiv.textContent = message;
        
        document.body.appendChild(messageDiv);
        
        // Les erreurs restent affichées plus longtemps pour pouvoir être lues
        setTimeout(() => {
            messageDiv.remove();
        }, type === 'error' ? 8000 : 4000);
    }
}

// Auto-initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    // Attendre un peu pour que le tableau soit complètement rendu
    setTimeout(() => {
        initializeMatieresOrder();
    }, 100);
});

// Fonction d'initialisation globale pour éviter les doublons
function initializeMatieresOrder(force = false) {
    // Une instance existe déjà (créée par la page avec ses options) : ne pas en créer une seconde,
    // sinon chaque glisser-déposer serait enregistré deux fois
    let previousOptions = {};
    if (window.matieresOrderManager) {
        if (!force) {
            return;
        }
        console.log('Nettoyage de l\'instance existante');
        previousOptions = { saveUrl: window.matieresOrderManager.options.saveUrl, token: window.matieresOrderManager.options.token };
        window.matieresOrderManager = null;
    }
    
    // Vérifier que le tableau existe
    const table = document.querySelector('#matieres-sortable');
    if (!table) {
        console.log('Tableau #matieres-sortable non trouvé, drag & drop non initialisé');
        return;
    }
    
    // Pas de poignée affichée (droits insuffisants ou matière en cours d'édition) : pas de drag & drop
    if (!table.querySelector('.drag-handle')) {
        console.log('Aucune poignée de déplacement, drag & drop non initialisé');
        return;
    }

    // Vérifier qu'il y a des lignes draggables
    const draggableRows = table.querySelectorAll('tr[data-matiere-id]');
    if (draggableRows.length < 2) {
        console.log('Pas assez de matières (', draggableRows.length, ') pour le drag & drop');
        return;
    }
    
    try {
        window.matieresOrderManager = new MatieresOrderManager('#matieres-sortable', previousOptions);
        console.log('Drag & drop des matières premières initialisé avec succès !', draggableRows.length, 'matières');
    } catch (error) {
        console.error('Erreur lors de l\'initialisation du drag & drop:', error);
    }
}

// Fonction globale pour réinitialiser après des modifications AJAX
window.reinitializeMatieresOrder = function() {
    console.log('Réinitialisation demandée...');
    setTimeout(() => {
        initializeMatieresOrder(true);
    }, 50);
};
