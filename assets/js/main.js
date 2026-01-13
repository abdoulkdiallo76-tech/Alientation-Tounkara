// Scripts principaux pour Alimentation Tounkara

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialiser les popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide des alertes
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Fonctions utilitaires
function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showLoadingSpinner(element) {
    if (element) {
        element.innerHTML = '<div class="spinner-wrapper"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
    }
}

function hideLoadingSpinner(element, content) {
    if (element && content) {
        element.innerHTML = content;
    }
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-hide après 5 secondes
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
    }
}

// Fonctions pour la caisse (POS)
class POS {
    constructor() {
        this.cart = [];
        this.total = 0;
        this.discount = 0;
        this.customer = '';
        this.paymentMethod = 'cash';
    }

    addToCart(product) {
        const existingItem = this.cart.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += 1;
            existingItem.total = existingItem.quantity * existingItem.price;
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: product.selling_price,
                quantity: 1,
                total: product.selling_price
            });
        }
        
        this.updateCart();
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.updateCart();
    }

    updateQuantity(productId, quantity) {
        const item = this.cart.find(item => item.id === productId);
        if (item) {
            item.quantity = parseInt(quantity);
            item.total = item.quantity * item.price;
            this.updateCart();
        }
    }

    updateCart() {
        this.total = this.cart.reduce((sum, item) => sum + item.total, 0);
        this.renderCart();
    }

    renderCart() {
        const cartContainer = document.getElementById('cart-items');
        const totalElement = document.getElementById('cart-total');
        const discountElement = document.getElementById('cart-discount');
        const finalTotalElement = document.getElementById('final-total');
        
        if (cartContainer) {
            cartContainer.innerHTML = '';
            
            this.cart.forEach(item => {
                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';
                cartItem.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">${item.name}</h6>
                            <small class="text-muted">${formatMoney(item.price)} × ${item.quantity}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <input type="number" class="form-control form-control-sm me-2" value="${item.quantity}" min="1" style="width: 60px;" onchange="pos.updateQuantity(${item.id}, this.value)">
                            <span class="fw-bold me-2">${formatMoney(item.total)}</span>
                            <button class="btn btn-sm btn-outline-danger" onclick="pos.removeFromCart(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                cartContainer.appendChild(cartItem);
            });
        }
        
        if (totalElement) {
            totalElement.textContent = formatMoney(this.total);
        }
        
        if (discountElement) {
            discountElement.textContent = formatMoney(this.discount);
        }
        
        if (finalTotalElement) {
            finalTotalElement.textContent = formatMoney(this.total - this.discount);
        }
    }

    calculateTotal() {
        return this.total - this.discount;
    }

    clearCart() {
        this.cart = [];
        this.total = 0;
        this.discount = 0;
        this.updateCart();
    }
}

// Recherche de produits
function searchProducts(query) {
    if (query.length < 2) return [];
    
    // Simulation de recherche - à remplacer par appel API
    return fetch(`api/search_products.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => data.products || []);
}

// Gestion du stock
function updateStock(productId, quantity, type = 'in') {
    return fetch('api/update_stock.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity,
            type: type
        })
    })
    .then(response => response.json());
}

// Impression de ticket
function printReceipt(saleId) {
    const printWindow = window.open(`print_receipt.php?id=${saleId}`, '_blank');
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}

// Exportation de données
function exportData(type, format = 'csv') {
    window.open(`api/export.php?type=${type}&format=${format}`, '_blank');
}

// Validation de formulaires
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Confirmation avant suppression
function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    return confirm(message);
}

// Gestion des notifications
function showNotification(title, message, type = 'info') {
    if ('Notification' in window) {
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/assets/images/logo.png'
            });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification(title, {
                        body: message,
                        icon: '/assets/images/logo.png'
                    });
                }
            });
        }
    }
}

// Auto-refresh des données
function startAutoRefresh(interval = 30000) {
    setInterval(() => {
        // Rafraîchir les statistiques du tableau de bord
        if (window.location.pathname.endsWith('index.php')) {
            location.reload();
        }
    }, interval);
}

// Gestion du mode hors ligne
function checkOnlineStatus() {
    const statusIndicator = document.getElementById('online-status');
    
    if (statusIndicator) {
        if (navigator.onLine) {
            statusIndicator.className = 'badge bg-success';
            statusIndicator.textContent = 'En ligne';
        } else {
            statusIndicator.className = 'badge bg-danger';
            statusIndicator.textContent = 'Hors ligne';
        }
    }
}

window.addEventListener('online', checkOnlineStatus);
window.addEventListener('offline', checkOnlineStatus);

// Initialisation du mode hors ligne
checkOnlineStatus();

// Variables globales
let pos = new POS();

// Démarrer l'auto-refresh si nécessaire
if (window.location.pathname.includes('index.php')) {
    startAutoRefresh();
}
