<?php
require_once 'config/database.php';
requireLogin();

$page_title = 'Gestion des produits';

// Vérifier les droits d'accès
if (isCashier()) {
    header('Location: pos.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$product_id = $_GET['id'] ?? 0;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product']) || isset($_POST['edit_product'])) {
        $name = cleanInput($_POST['name']);
        $barcode = cleanInput($_POST['barcode']);
        
        // Générer un barcode automatique si vide
        if (empty($barcode)) {
            $barcode = 'AUTO_' . time() . '_' . rand(1000, 9999);
        }
        
        $description = cleanInput($_POST['description']);
        $category_id = intval($_POST['category_id']);
        $purchase_price = floatval($_POST['purchase_price']);
        $selling_price = floatval($_POST['selling_price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $min_stock_alert = intval($_POST['min_stock_alert']);
        $unit = cleanInput($_POST['unit']);
        
        try {
            if (isset($_POST['add_product'])) {
                $stmt = $pdo->prepare("INSERT INTO products (name, barcode, description, category_id, purchase_price, selling_price, stock_quantity, min_stock_alert, unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $barcode, $description, $category_id, $purchase_price, $selling_price, $stock_quantity, $min_stock_alert, $unit]);
                
                // Ajouter mouvement de stock initial
                $product_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason, created_by) VALUES (?, 'in', ?, 'Stock initial', ?)");
                $stmt->execute([$product_id, $stock_quantity, $_SESSION['user_id']]);
                
                $_SESSION['success'] = 'Produit ajouté avec succès';
            } else {
                $product_id = intval($_POST['product_id']);
                $stmt = $pdo->prepare("UPDATE products SET name=?, barcode=?, description=?, category_id=?, purchase_price=?, selling_price=?, stock_quantity=?, min_stock_alert=?, unit=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$name, $barcode, $description, $category_id, $purchase_price, $selling_price, $stock_quantity, $min_stock_alert, $unit, $product_id]);
                $_SESSION['success'] = 'Produit modifié avec succès';
            }
            
            header('Location: products.php');
            exit();
        } catch(PDOException $e) {
            // Gérer spécifiquement l'erreur de duplication de barcode
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'barcode') !== false) {
                $_SESSION['error'] = 'Ce code-barres existe déjà. Veuillez utiliser un autre code-barres ou laissez le champ vide pour en générer un automatiquement.';
            } else {
                $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
            }
            header('Location: products.php');
            exit();
        }
    }
    
    if (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);
        try {
            $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $stmt->execute([$product_id]);
            $_SESSION['success'] = 'Produit supprimé avec succès';
            header('Location: products.php');
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
            header('Location: products.php');
            exit();
        }
    }
}

// Maintenant inclure le header après tous les traitements
require_once 'includes/header.php';

// Récupération des données
if ($action === 'edit' && $product_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            header('Location: products.php');
            exit();
        }
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

if ($action === 'list') {
    try {
        $search = cleanInput($_GET['search'] ?? '');
        $category_filter = intval($_GET['category'] ?? 0);
        
        $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (p.name LIKE ? OR p.barcode LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($category_filter > 0) {
            $query .= " AND p.category_id = ?";
            $params[] = $category_filter;
        }
        
        $query .= " ORDER BY p.name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // Récupérer les catégories pour le filtre
        $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}
?>

<?php if ($action === 'list'): ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">
                <i class="fas fa-cube me-2"></i>Gestion des produits
            </h1>
            <a href="products.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Ajouter un produit
            </a>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Filtres et recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Recherche</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Nom ou code-barres">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Catégorie</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Rechercher
                </button>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="products.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-redo me-2"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des produits -->
<div class="card">
    <div class="card-body">
        <?php if (empty($products)): ?>
            <p class="text-muted text-center">Aucun produit trouvé</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code-barres</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Prix d'achat</th>
                            <th>Prix de vente</th>
                            <th>Stock</th>
                            <th>Alerte</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr class="<?php echo $product['stock_quantity'] <= $product['min_stock_alert'] ? 'table-warning' : ''; ?>">
                            <td><?php echo htmlspecialchars($product['barcode'] ?: 'N/A'); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                <?php if ($product['description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($product['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?: 'N/A'); ?></td>
                            <td><?php echo formatMoney($product['purchase_price']); ?></td>
                            <td class="fw-bold"><?php echo formatMoney($product['selling_price']); ?></td>
                            <td>
                                <span class="badge <?php echo $product['stock_quantity'] <= $product['min_stock_alert'] ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                </span>
                            </td>
                            <td><?php echo $product['min_stock_alert']; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDeleteProduct(<?php echo $product['id']; ?>)" class="btn btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-cube me-2"></i>
            <?php echo $action === 'add' ? 'Ajouter un produit' : 'Modifier un produit'; ?>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id'] ?? 0; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nom du produit *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="barcode" class="form-label">Code-barres</label>
                            <input type="text" class="form-control" id="barcode" name="barcode" value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="category_id" class="form-label">Catégorie *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
                                    $stmt->execute();
                                    $categories_list = $stmt->fetchAll();
                                    
                                    foreach ($categories_list as $category):
                                ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($product) && $product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php } catch(PDOException $e) { /* Ignorer l'erreur */ } ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="unit" class="form-label">Unité</label>
                            <select class="form-select" id="unit" name="unit">
                                <option value="unité" <?php echo (isset($product) && $product['unit'] === 'unité') ? 'selected' : ''; ?>>Unité</option>
                                <option value="kg" <?php echo (isset($product) && $product['unit'] === 'kg') ? 'selected' : ''; ?>>Kg</option>
                                <option value="litre" <?php echo (isset($product) && $product['unit'] === 'litre') ? 'selected' : ''; ?>>Litre</option>
                                <option value="bouteille" <?php echo (isset($product) && $product['unit'] === 'bouteille') ? 'selected' : ''; ?>>Bouteille</option>
                                <option value="sachet" <?php echo (isset($product) && $product['unit'] === 'sachet') ? 'selected' : ''; ?>>Sachet</option>
                                <option value="carton" <?php echo (isset($product) && $product['unit'] === 'carton') ? 'selected' : ''; ?>>Carton</option>
                                <option value="paquet" <?php echo (isset($product) && $product['unit'] === 'paquet') ? 'selected' : ''; ?>>Paquet</option>
                                <option value="pièce" <?php echo (isset($product) && $product['unit'] === 'pièce') ? 'selected' : ''; ?>>Pièce</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="stock_quantity" class="form-label">Quantité en stock *</label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo $product['stock_quantity'] ?? 0; ?>" min="0" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="purchase_price" class="form-label">Prix d'achat *</label>
                            <input type="number" class="form-control" id="purchase_price" name="purchase_price" value="<?php echo $product['purchase_price'] ?? 0; ?>" min="0" step="0.01" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="selling_price" class="form-label">Prix de vente *</label>
                            <input type="number" class="form-control" id="selling_price" name="selling_price" value="<?php echo $product['selling_price'] ?? 0; ?>" min="0" step="0.01" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="min_stock_alert" class="form-label">Alerte stock bas</label>
                            <input type="number" class="form-control" id="min_stock_alert" name="min_stock_alert" value="<?php echo $product['min_stock_alert'] ?? 5; ?>" min="0">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <div>
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_product' : 'edit_product'; ?>" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $action === 'add' ? 'Ajouter' : 'Modifier'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulaire de suppression caché -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_product">
    <input type="hidden" name="product_id" id="deleteProductId">
</form>

<?php
$page_script = "
function confirmDeleteProduct(productId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        document.getElementById('deleteProductId').value = productId;
        document.getElementById('deleteForm').submit();
    }
}
";
?>

<?php require_once 'includes/footer.php'; ?>
