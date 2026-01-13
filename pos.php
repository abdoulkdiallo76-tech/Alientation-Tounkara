<?php
$page_title = 'Point de Vente';

// Vérifier si une session de caisse journalière est ouverte
require_once 'config/database.php';
requireLogin();

// Vérifier le statut de la session du jour
$day_status = checkDaySessionStatus();

if ($day_status['status'] !== 'open') {
    // Rediriger vers la gestion de caisse avec un message clair
    $_SESSION['error'] = 'Vous devez ouvrir la caisse journalière pour commencer les ventes.';
    header('Location: cash_management.php');
    exit();
}

// Traitement de la vente - AVANT l'inclusion du header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_sale'])) {
    
    $cart_items = json_decode($_POST['cart_items'], true);
    $total_amount = floatval($_POST['total_amount']);
    $discount_amount = floatval($_POST['discount_amount']);
    $final_amount = floatval($_POST['final_amount']);
    $payment_method = cleanInput($_POST['payment_method']);
    $customer_name = cleanInput($_POST['customer_name']);
    $customer_phone = cleanInput($_POST['customer_phone']);
    $notes = cleanInput($_POST['notes']);
    
    try {
        $pdo->beginTransaction();
        
        // Récupérer la session de caisse actuelle
        $current_session = getCurrentCashSession();
        if (!$current_session) {
            throw new Exception('Aucune session de caisse ouverte');
        }
        
        // Insérer la vente avec l'ID de session
        $stmt = $pdo->prepare("INSERT INTO sales (total_amount, discount_amount, final_amount, payment_method, customer_name, customer_phone, cashier_id, notes, cash_session_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$total_amount, $discount_amount, $final_amount, $payment_method, $customer_name, $customer_phone, $_SESSION['user_id'], $notes, $current_session['id']]);
        $sale_id = $pdo->lastInsertId();
        
        // Insérer les détails de vente et mettre à jour le stock
        foreach ($cart_items as $item) {
            // Détail de vente
            $stmt = $pdo->prepare("INSERT INTO sale_details (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $item['total']]);
            
            // Mettre à jour le stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
            $stmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
            
            // Ajouter mouvement de stock
            $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason, reference_id, reference_type, created_by) VALUES (?, 'out', ?, 'Vente', ?, 'sale', ?)");
            $stmt->execute([$item['id'], $item['quantity'], $sale_id, $_SESSION['user_id']]);
        }
        
        $pdo->commit();
        
        $_SESSION['sale_success'] = true;
        $_SESSION['sale_id'] = $sale_id;
        
        header('Location: sale_success.php');
        exit();
        
    } catch(PDOException $e) {
        $pdo->rollback();
        $_SESSION['error'] = 'Erreur lors du traitement de la vente: ' . $e->getMessage();
        header('Location: pos.php');
        exit();
    }
}

// Maintenant inclure le header après tous les traitements
require_once 'includes/header.php';

// Récupérer les produits pour la caisse
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND p.stock_quantity > 0 ORDER BY p.name ASC");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Récupérer les catégories
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

// Check for session errors from POST processing
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-cash-register me-2"></i>Caisse
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Colonne de gauche: Produits -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">Produits</h5>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="productSearch" placeholder="Rechercher un produit...">
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtres par catégorie -->
                <div class="mb-3">
                    <button class="btn btn-sm btn-outline-primary active" data-category="all">Tous</button>
                    <?php foreach ($categories as $category): ?>
                        <button class="btn btn-sm btn-outline-secondary" data-category="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Grille de produits -->
                <div class="product-grid" id="productGrid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-item" 
                             data-product-id="<?php echo $product['id']; ?>"
                             data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                             data-product-price="<?php echo $product['selling_price']; ?>"
                             data-product-stock="<?php echo $product['stock_quantity']; ?>"
                             data-category="<?php echo $product['category_id']; ?>">
                            <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="text-primary"><?php echo formatMoney($product['selling_price']); ?></div>
                            <small class="text-muted">Stock: <?php echo $product['stock_quantity']; ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Colonne de droite: Panier -->
    <div class="col-lg-4">
        <div class="card pos-container">
            <div class="card-header">
                <h5 class="mb-0">Panier</h5>
            </div>
            <div class="card-body">
                <div id="cart-items" class="cart-container">
                    <p class="text-muted text-center">Le panier est vide</p>
                </div>
                
                <div class="mt-3">
                    <div class="row mb-2">
                        <div class="col-6">Sous-total:</div>
                        <div class="col-6 text-end fw-bold" id="cart-total">0 FCFA</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">Remise:</div>
                        <div class="col-6 text-end">
                            <input type="number" class="form-control form-control-sm text-end" id="cart-discount" value="0" min="0">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">Total:</div>
                        <div class="col-6 text-end fw-bold text-primary" id="final-total">0 FCFA</div>
                    </div>
                </div>
                
                <!-- Informations client -->
                <div class="mb-3">
                    <input type="text" class="form-control form-control-sm mb-2" id="customer-name" placeholder="Nom du client">
                    <input type="text" class="form-control form-control-sm mb-2" id="customer-phone" placeholder="Téléphone du client">
                    <textarea class="form-control form-control-sm" id="sale-notes" placeholder="Notes (optionnel)" rows="2"></textarea>
                </div>
                
                <!-- Méthode de paiement -->
                <div class="mb-3">
                    <select class="form-select form-select-sm" id="payment-method">
                        <option value="cash">Espèces</option>
                        <option value="card">Carte bancaire</option>
                        <option value="mobile">Mobile Money</option>
                    </select>
                </div>
                
                <!-- Boutons d'action -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" id="process-sale" disabled>
                        <i class="fas fa-cash-register me-2"></i>Traiter la vente
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="clear-cart">
                        <i class="fas fa-trash me-2"></i>Vider le panier
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de vente caché -->
<form id="saleForm" method="POST" style="display: none;">
    <input type="hidden" name="process_sale">
    <input type="hidden" name="cart_items" id="cart-items-input">
    <input type="hidden" name="total_amount" id="total-amount-input">
    <input type="hidden" name="discount_amount" id="discount-amount-input">
    <input type="hidden" name="final_amount" id="final-amount-input">
    <input type="hidden" name="payment_method" id="payment-method-input">
    <input type="hidden" name="customer_name" id="customer-name-input">
    <input type="hidden" name="customer_phone" id="customer-phone-input">
    <input type="hidden" name="notes" id="notes-input">
</form>

<?php
$page_script = "
// Initialisation de la caisse
const cart = [];
let total = 0;
let discount = 0;

// Recherche de produits
document.getElementById('productSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const products = document.querySelectorAll('.product-item');
    
    products.forEach(product => {
        const name = product.dataset.productName.toLowerCase();
        if (name.includes(searchTerm)) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
});

// Filtrage par catégorie
document.querySelectorAll('[data-category]').forEach(button => {
    button.addEventListener('click', function() {
        // Mettre à jour le bouton actif
        document.querySelectorAll('[data-category]').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        // Filtrer les produits
        const category = this.dataset.category;
        const products = document.querySelectorAll('.product-item');
        
        products.forEach(product => {
            if (category === 'all' || product.dataset.category === category) {
                product.style.display = 'block';
            } else {
                product.style.display = 'none';
            }
        });
    });
});

// Ajout de produits au panier
document.querySelectorAll('.product-item').forEach(item => {
    item.addEventListener('click', function() {
        const productId = parseInt(this.dataset.productId);
        const productName = this.dataset.productName;
        const productPrice = parseFloat(this.dataset.productPrice);
        const productStock = parseInt(this.dataset.productStock);
        
        if (productStock <= 0) {
            alert('Ce produit est en rupture de stock');
            return;
        }
        
        addToCart(productId, productName, productPrice, productStock);
    });
});

function addToCart(id, name, price, stock) {
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        if (existingItem.quantity >= stock) {
            alert('Stock insuffisant');
            return;
        }
        existingItem.quantity += 1;
        existingItem.total = existingItem.quantity * existingItem.price;
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            quantity: 1,
            total: price
        });
    }
    
    updateCart();
}

function removeFromCart(productId) {
    const index = cart.findIndex(item => item.id === productId);
    if (index > -1) {
        cart.splice(index, 1);
        updateCart();
    }
}

function updateQuantity(productId, quantity) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        const qty = parseInt(quantity);
        if (qty > 0) {
            item.quantity = qty;
            item.total = item.quantity * item.price;
            updateCart();
        }
    }
}

function updateCart() {
    total = cart.reduce((sum, item) => sum + item.total, 0);
    discount = parseFloat(document.getElementById('cart-discount').value) || 0;
    const finalTotal = Math.max(0, total - discount);
    
    renderCart();
    
    // Mettre à jour les totaux
    document.getElementById('cart-total').textContent = formatMoney(total);
    document.getElementById('final-total').textContent = formatMoney(finalTotal);
    
    // Activer/désactiver le bouton de vente
    document.getElementById('process-sale').disabled = cart.length === 0;
}

function renderCart() {
    const cartContainer = document.getElementById('cart-items');
    
    if (cart.length === 0) {
        cartContainer.innerHTML = '<p class=\"text-muted text-center\">Le panier est vide</p>';
        return;
    }
    
    cartContainer.innerHTML = '';
    
    cart.forEach(item => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = '<div class=\"d-flex justify-content-between align-items-center\"><div><h6 class=\"mb-0\">' + item.name + '</h6><small class=\"text-muted\">' + formatMoney(item.price) + ' × ' + item.quantity + '</small></div><div class=\"d-flex align-items-center\"><input type=\"number\" class=\"form-control form-control-sm me-2\" value=\"' + item.quantity + '\" min=\"1\" style=\"width: 60px;\" onchange=\"updateQuantity(' + item.id + ', this.value)\"><span class=\"fw-bold me-2\">' + formatMoney(item.total) + '</span><button class=\"btn btn-sm btn-outline-danger\" onclick=\"removeFromCart(' + item.id + ')\"><i class=\"fas fa-trash\"></i></button></div></div>';
        cartContainer.appendChild(cartItem);
    });
}

// Remise
document.getElementById('cart-discount').addEventListener('input', updateCart);

// Vider le panier
document.getElementById('clear-cart').addEventListener('click', function() {
    if (confirm('Vider le panier ?')) {
        cart.length = 0;
        updateCart();
    }
});

// Traiter la vente
document.getElementById('process-sale').addEventListener('click', function() {
    if (cart.length === 0) {
        alert('Le panier est vide');
        return;
    }
    
    const finalTotal = Math.max(0, total - discount);
    
    // Remplir le formulaire
    document.getElementById('cart-items-input').value = JSON.stringify(cart);
    document.getElementById('total-amount-input').value = total;
    document.getElementById('discount-amount-input').value = discount;
    document.getElementById('final-amount-input').value = finalTotal;
    document.getElementById('payment-method-input').value = document.getElementById('payment-method').value;
    document.getElementById('customer-name-input').value = document.getElementById('customer-name').value;
    document.getElementById('customer-phone-input').value = document.getElementById('customer-phone').value;
    document.getElementById('notes-input').value = document.getElementById('sale-notes').value;
    
    // Soumettre le formulaire
    document.getElementById('saleForm').submit();
});

// Formatage de l'argent
function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0
    }).format(amount);
}

// Support du code-barres (simulation)
document.addEventListener('keydown', function(e) {
    // Si l'utilisateur tape dans le champ de recherche, ne pas traiter comme code-barres
    if (document.activeElement.id === 'productSearch') return;
    
    // Simulation de lecture de code-barres (commence par un chiffre)
    if (e.key >= '0' && e.key <= '9') {
        // Logique de scan de code-barres à implémenter
    }
});
";
?>

<?php require_once 'includes/footer.php'; ?>
