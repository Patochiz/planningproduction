/* 
 * Planning de Production - Gestion des onglets
 * Copyright (C) 2024 Patrick Delcroix
 */

// === GESTION PANNEAU ONGLETS ===
function toggleTabsPanel() {
    const column = document.getElementById('tabsColumn');
    const toggle = document.getElementById('tabsToggle');
    
    if (!column || !toggle) {
        console.error('Éléments non trouvés:', { column, toggle });
        return;
    }
    
    if (column.classList.contains('collapsed')) {
        column.classList.remove('collapsed');
        toggle.textContent = '◀';
        showToast('Panneau étendu', 'info');
    } else {
        column.classList.add('collapsed');
        toggle.textContent = '▶';
        showToast('Panneau réduit', 'info');
    }
}

// === GESTION DES ONGLETS ===
function switchTab(tabName) {
    // Initialiser activeTab si elle n'existe pas
    if (typeof window.activeTab === 'undefined') {
        window.activeTab = 'unplanned';
    }
    // Mettre à jour les boutons d'onglets
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    const tabButton = document.querySelector(`[data-tab="${tabName}"]`);
    if (tabButton) {
        tabButton.classList.add('active');
    }
    
    // Mettre à jour le contenu des onglets
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    const tabContent = document.querySelector(`.tab-content[data-tab="${tabName}"]`);
    if (tabContent) {
        tabContent.classList.add('active');
    }
    
    // Mettre à jour l'attribut data sur la colonne pour changer la couleur de bordure
    const column = document.getElementById('tabsColumn');
    if (column) {
        column.setAttribute('data-active-tab', tabName);
    }
    
    // Mettre à jour la variable globale
    window.activeTab = tabName;
    
    console.log('Onglet actif:', tabName);
    
    // Réattacher les événements aux nouvelles cartes si nécessaire
    if (typeof initializeAllEvents === 'function') {
        setTimeout(() => {
            const visibleCards = document.querySelectorAll(`.tab-content[data-tab="${tabName}"] .kanban-card`);
            visibleCards.forEach(card => {
                if (typeof attachCardEvents === 'function') {
                    attachCardEvents(card);
                }
            });
        }, 100);
    }
}

// === INITIALISATION DES ONGLETS ===
function initializeTabs() {
    console.log('Initialisation des onglets...');
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });
    
    // Activer le premier onglet par défaut
    const activeButton = document.querySelector('.tab-button.active');
    if (activeButton) {
        const tabName = activeButton.getAttribute('data-tab');
        switchTab(tabName);
    } else {
        // Fallback : activer le premier onglet
        const firstButton = document.querySelector('.tab-button');
        if (firstButton) {
            const tabName = firstButton.getAttribute('data-tab');
            switchTab(tabName);
        }
    }
    
    console.log('Onglets initialisés avec', document.querySelectorAll('.tab-button').length, 'boutons');
}
