// Navigation fix for all pages
document.addEventListener('DOMContentLoaded', function() {
    console.log('Navigation script loaded');
    
    // Attendre que tout soit chargé
    setTimeout(function() {
        fixNavigation();
    }, 200);
});

function fixNavigation() {
    // Forcer tous les liens à être cliquables
    const allLinks = document.querySelectorAll('a');
    console.log('Found links:', allLinks.length);
    
    allLinks.forEach((link, index) => {
        // Forcer les styles
        link.style.pointerEvents = 'auto';
        link.style.cursor = 'pointer';
        link.style.display = 'block';
        
        // Ajouter un gestionnaire de clic direct
        link.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const href = this.getAttribute('href');
            console.log('Link clicked:', href);
            
            if (href && href !== '#' && href !== 'javascript:void(0)') {
                console.log('Navigating to:', href);
                window.location.href = href;
                return false;
            }
        };
        
        // Écouteur d'événements supplémentaire
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const href = this.getAttribute('href');
            console.log('Link event clicked:', href);
            
            if (href && href !== '#' && href !== 'javascript:void(0)') {
                console.log('Event navigating to:', href);
                window.location.href = href;
                return false;
            }
        });
    });
    
    // Forcer les dropdowns Bootstrap
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdownMenu = this.nextElementSibling;
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                dropdownMenu.classList.toggle('show');
            }
        });
    });
    
    // Fermer les dropdowns en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    console.log('Navigation fixed');
}

// Réparer la navigation si appelé directement
if (typeof window !== 'undefined') {
    window.fixNavigation = fixNavigation;
}
