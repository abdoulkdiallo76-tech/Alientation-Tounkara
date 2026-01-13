<?php
require_once 'config/database.php';
$page_title = 'Commandes fournisseurs';

// Vérifier les droits d'accès AVANT d'inclure le header
if (isCashier()) {
    header('Location: pos.php');
    exit();
}

// Traitement des actions AVANT d'inclure le header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_order']) || isset($_POST['edit_order'])) {
        requireLogin();
        
        $supplier_id = intval($_POST['supplier_id']);
        $order_date = $_POST['order_date'];
        $delivery_date = $_POST['delivery_date'];
        $status = cleanInput($_POST['status']);
        $notes = cleanInput($_POST['notes']);
        $products = json_decode($_POST['products'], true);
        
        try {
            $pdo->beginTransaction();
            
            if (isset($_POST['add_order'])) {
                $stmt = $pdo->prepare("INSERT INTO supplier_orders (supplier_id, order_date, delivery_date, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$supplier_id, $order_date, $delivery_date, $status, $notes, $_SESSION['user_id']]);
                $order_id = $pdo->lastInsertId();
            } else {
                $order_id = intval($_POST['order_id']);
                // Supprimer les anciens détails
                $stmt = $pdo->prepare("DELETE FROM supplier_order_details WHERE order_id = ?");
                $stmt->execute([$order_id]);
                
                $stmt = $pdo->prepare("UPDATE supplier_orders SET supplier_id=?, order_date=?, delivery_date=?, status=?, notes=? WHERE id=?");
                $stmt->execute([$supplier_id, $order_date, $delivery_date, $status, $notes, $order_id]);
            }
            
            // Insérer les détails de la commande
            $total_amount = 0;
            foreach ($products as $product) {
                $total_price = $product['quantity'] * $product['unit_price'];
                $total_amount += $total_price;
                
                $stmt = $pdo->prepare("INSERT INTO supplier_order_details (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$order_id, $product['id'], $product['quantity'], $product['unit_price'], $total_price]);
            }
            
            // Mettre à jour le montant total
            $stmt = $pdo->prepare("UPDATE supplier_orders SET total_amount = ? WHERE id = ?");
            $stmt->execute([$total_amount, $order_id]);
            
            // Si la commande est livrée, mettre à jour le stock
            if ($status === 'delivered') {
                foreach ($products as $product) {
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $stmt->execute([$product['quantity'], $product['id']]);
                    
                    // Ajouter mouvement de stock
                    $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason, reference_id, reference_type, created_by) VALUES (?, 'in', ?, 'Réception commande', ?, 'supplier_order', ?)");
                    $stmt->execute([$product['id'], $product['quantity'], $order_id, $_SESSION['user_id']]);
                }
            }
            
            $pdo->commit();
            
            header('Location: supplier_orders.php');
            exit();
            
        } catch(PDOException $e) {
            $pdo->rollback();
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
            header('Location: supplier_orders.php');
            exit();
        }
    }
    
    if (isset($_POST['delete_order'])) {
        requireLogin();
        
        $order_id = intval($_POST['order_id']);
        try {
            $pdo->beginTransaction();
            
            // Vérifier que la commande n'est pas déjà livrée
            $stmt = $pdo->prepare("SELECT status FROM supplier_orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if ($order && $order['status'] === 'delivered') {
                $_SESSION['error'] = 'Impossible de supprimer une commande déjà livrée';
            } else {
                // Supprimer les détails
                $stmt = $pdo->prepare("DELETE FROM supplier_order_details WHERE order_id = ?");
                $stmt->execute([$order_id]);
                
                // Supprimer la commande
                $stmt = $pdo->prepare("DELETE FROM supplier_orders WHERE id = ?");
                $stmt->execute([$order_id]);
                
                $pdo->commit();
                $_SESSION['success'] = 'Commande supprimée avec succès';
            }
            
        } catch(PDOException $e) {
            $pdo->rollback();
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
        header('Location: supplier_orders.php');
        exit();
    }
    
    if (isset($_POST['update_status'])) {
        requireLogin();
        
        $order_id = intval($_POST['order_id']);
        $new_status = cleanInput($_POST['new_status']);
        
        try {
            $pdo->beginTransaction();
            
            // Récupérer l'ancien statut
            $stmt = $pdo->prepare("SELECT status FROM supplier_orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if ($order) {
                // Mettre à jour le statut
                $stmt = $pdo->prepare("UPDATE supplier_orders SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $order_id]);
                
                // Si le nouveau statut est "livrée", mettre à jour le stock
                if ($new_status === 'delivered' && $order['status'] !== 'delivered') {
                    $stmt = $pdo->prepare("SELECT product_id, quantity FROM supplier_order_details WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                    $order_details = $stmt->fetchAll();
                    
                    foreach ($order_details as $detail) {
                        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                        $stmt->execute([$detail['quantity'], $detail['product_id']]);
                        
                        // Ajouter mouvement de stock
                        $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason, reference_id, reference_type, created_by) VALUES (?, 'in', ?, 'Réception commande', ?, 'supplier_order', ?)");
                        $stmt->execute([$detail['product_id'], $detail['quantity'], $order_id, $_SESSION['user_id']]);
                    }
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Statut de la commande mis à jour avec succès';
        } catch(PDOException $e) {
            $pdo->rollback();
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
        header('Location: supplier_orders.php');
        exit();
    }
}

require_once 'includes/header.php';

$action = $_GET['action'] ?? 'list';
$order_id = $_GET['id'] ?? 0;
$supplier_id = $_GET['supplier_id'] ?? 0;

// Récupération des données
if ($action === 'edit' && $order_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM supplier_orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            header('Location: supplier_orders.php');
            exit();
        }
        
        // Récupérer les détails de la commande
        $stmt = $pdo->prepare("SELECT sod.*, p.name as product_name FROM supplier_order_details sod LEFT JOIN products p ON sod.product_id = p.id WHERE sod.order_id = ?");
        $stmt->execute([$order_id]);
        $order_details = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

if ($action === 'list') {
    try {
        $search = cleanInput($_GET['search'] ?? '');
        $status_filter = cleanInput($_GET['status'] ?? '');
        $supplier_filter = intval($_GET['supplier'] ?? 0);
        
        $query = "SELECT so.*, s.name as supplier_name, u.full_name as created_by_name 
                 FROM supplier_orders so 
                 LEFT JOIN suppliers s ON so.supplier_id = s.id 
                 LEFT JOIN users u ON so.created_by = u.id 
                 WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (so.notes LIKE ? OR s.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($status_filter)) {
            $query .= " AND so.status = ?";
            $params[] = $status_filter;
        }
        
        if ($supplier_filter > 0) {
            $query .= " AND so.supplier_id = ?";
            $params[] = $supplier_filter;
        }
        
        $query .= " ORDER BY so.order_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        // Récupérer les fournisseurs pour le filtre
        $stmt = $pdo->prepare("SELECT * FROM suppliers ORDER BY name ASC");
        $stmt->execute();
        $suppliers = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

// Récupérer les produits pour les formulaires
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC");
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

// Handle view action
if ($action === 'view' && $order_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT so.*, s.name as supplier_name, u.full_name as created_by_name 
                 FROM supplier_orders so 
                 LEFT JOIN suppliers s ON so.supplier_id = s.id 
                 LEFT JOIN users u ON so.created_by = u.id 
                 WHERE so.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            header('Location: supplier_orders.php');
            exit();
        }
        
        // Récupérer les détails de la commande
        $stmt = $pdo->prepare("SELECT sod.*, p.name as product_name FROM supplier_order_details sod LEFT JOIN products p ON sod.product_id = p.id WHERE sod.order_id = ?");
        $stmt->execute([$order_id]);
        $order_details = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}
?>

<!-- Handle add/edit actions -->
<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-clipboard-list me-2"></i>
            <?php echo $action === 'add' ? 'Nouvelle commande' : 'Modifier commande'; ?>
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
                
                <form method="POST" id="orderForm">
                    <input type="hidden" name="order_id" value="<?php echo $order['id'] ?? 0; ?>">
                    <input type="hidden" name="products" id="products-input">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_id" class="form-label">Fournisseur *</label>
                            <select class="form-select" id="supplier_id" name="supplier_id" required>
                                <option value="">Sélectionner un fournisseur</option>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT * FROM suppliers ORDER BY name ASC");
                                    $stmt->execute();
                                    $suppliers_list = $stmt->fetchAll();
                                    
                                    foreach ($suppliers_list as $supplier):
                                ?>
                                    <option value="<?php echo $supplier['id']; ?>" <?php echo (isset($order) && $order['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php } catch(PDOException $e) { /* Ignorer l'erreur */ } ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="order_date" class="form-label">Date de commande *</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" value="<?php echo $order['order_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="delivery_date" class="form-label">Date de livraison prévue</label>
                            <input type="date" class="form-control" id="delivery_date" name="delivery_date" value="<?php echo $order['delivery_date'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo (isset($order) && $order['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                                <option value="delivered" <?php echo (isset($order) && $order['status'] == 'delivered') ? 'selected' : ''; ?>>Livrée</option>
                                <option value="cancelled" <?php echo (isset($order) && $order['status'] == 'cancelled') ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Produits de la commande -->
                    <div class="mb-3">
                        <h6>Produits de la commande</h6>
                        <div id="order-products" class="border rounded p-3">
                            <div id="products-list">
                                <?php if (isset($order_details)): ?>
                                    <?php foreach ($order_details as $detail): ?>
                                        <div class="product-row mb-2">
                                            <div class="row g-2">
                                                <div class="col-md-5">
                                                    <select class="form-select product-select" required>
                                                        <?php foreach ($products as $product): ?>
                                                            <option value="<?php echo $product['id']; ?>" <?php echo $product['id'] == $detail['product_id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($product['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" class="form-control quantity" placeholder="Qté" value="<?php echo $detail['quantity']; ?>" min="1" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" class="form-control unit-price" placeholder="Prix unitaire" value="<?php echo $detail['unit_price']; ?>" min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" class="form-control total-price" readonly value="<?php echo formatMoney($detail['total_price']); ?>">
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-product">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-product">
                                <i class="fas fa-plus me-2"></i>Ajouter un produit
                            </button>
                        </div>
                        
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-9 text-end">
                                    <strong>Total:</strong>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control text-end fw-bold" id="order-total" readonly value="0 FCFA">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="supplier_orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <div>
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_order' : 'edit_order'; ?>" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $action === 'add' ? 'Créer' : 'Modifier'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($action === 'view' && $order_id > 0): ?>
<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-eye me-2"></i>Détails de la commande #<?php echo $order['id']; ?>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informations de la commande</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Fournisseur:</strong><br>
                        <?php echo htmlspecialchars($order['supplier_name']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Statut:</strong><br>
                        <span class="badge <?php 
                            $status_classes = [
                                'pending' => 'bg-warning',
                                'delivered' => 'bg-success', 
                                'cancelled' => 'bg-danger'
                            ];
                            echo $status_classes[$order['status']] ?? 'bg-secondary'; 
                        ?>">
                            <?php 
                            $status_labels = [
                                'pending' => 'En attente',
                                'delivered' => 'Livrée',
                                'cancelled' => 'Annulée'
                            ];
                            echo $status_labels[$order['status']] ?? $order['status']; 
                        ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Date commande:</strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Date livraison:</strong><br>
                        <?php echo $order['delivery_date'] ? date('d/m/Y', strtotime($order['delivery_date'])) : 'N/A'; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Montant total:</strong><br>
                        <?php echo formatMoney($order['total_amount']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Créée par:</strong><br>
                        <?php echo htmlspecialchars($order['created_by_name']); ?>
                    </div>
                </div>
                
                <?php if ($order['notes']): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Notes:</strong><br>
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Produits commandés</h5>
            </div>
            <div class="card-body">
                <?php if (empty($order_details)): ?>
                    <p class="text-muted text-center">Aucun produit trouvé</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Prix unitaire</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_details as $detail): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detail['product_name']); ?></td>
                                    <td><?php echo $detail['quantity']; ?></td>
                                    <td><?php echo formatMoney($detail['unit_price']); ?></td>
                                    <td><?php echo formatMoney($detail['total_price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3">Total:</td>
                                    <td><?php echo formatMoney($order['total_amount']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <a href="supplier_orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
            </a>
            <?php if ($order['status'] === 'pending'): ?>
                <a href="supplier_orders.php?action=edit&id=<?php echo $order['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Modifier
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">
                <i class="fas fa-clipboard-list me-2"></i>Commandes fournisseurs
            </h1>
            <a href="supplier_orders.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nouvelle commande
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

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Recherche</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Notes ou fournisseur">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Statut</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tous les statuts</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                    <option value="delivered" <?php echo (isset($_GET['status']) && $_GET['status'] == 'delivered') ? 'selected' : ''; ?>>Livrée</option>
                    <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>Annulée</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="supplier" class="form-label">Fournisseur</label>
                <select class="form-select" id="supplier" name="supplier">
                    <option value="">Tous les fournisseurs</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>" <?php echo (isset($_GET['supplier']) && $_GET['supplier'] == $supplier['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Liste des commandes -->
<div class="card">
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <p class="text-muted text-center">Aucune commande trouvée</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fournisseur</th>
                            <th>Date commande</th>
                            <th>Date livraison</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Créée par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $order['delivery_date'] ? date('d/m/Y', strtotime($order['delivery_date'])) : 'N/A'; ?></td>
                            <td class="fw-bold"><?php echo formatMoney($order['total_amount']); ?></td>
                            <td>
                                <?php
                                $status_classes = [
                                    'pending' => 'bg-warning',
                                    'delivered' => 'bg-success',
                                    'cancelled' => 'bg-danger'
                                ];
                                $status_labels = [
                                    'pending' => 'En attente',
                                    'delivered' => 'Livrée',
                                    'cancelled' => 'Annulée'
                                ];
                                ?>
                                <span class="badge <?php echo $status_classes[$order['status']]; ?>">
                                    <?php echo $status_labels[$order['status']]; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($order['created_by_name']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="supplier_orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn btn-outline-info" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                        <a href="supplier_orders.php?action=edit&id=<?php echo $order['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')" class="btn btn-outline-success" title="Marquer comme livrée">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')" class="btn btn-outline-danger" title="Annuler">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
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
<?php endif; ?>

<!-- Formulaires cachés -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_order">
    <input type="hidden" name="order_id" id="deleteOrderId">
</form>

<form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="update_status">
    <input type="hidden" name="order_id" id="statusOrderId">
    <input type="hidden" name="new_status" id="newStatus">
</form>

<?php
$page_script = "
// Products disponibles globalement
const availableProducts = " . json_encode($products ?? []) . ";

// Gestion des produits dans la commande
function addProductRow(product = null) {
    console.log('addProductRow called with product:', product);
    console.log('Available products:', availableProducts);
    
    const productsList = document.getElementById('products-list');
    const productRow = document.createElement('div');
    productRow.className = 'product-row mb-2';
    
    productRow.innerHTML = 
        '<div class=\"row g-2\">' +
            '<div class=\"col-md-5\">' +
                '<select class=\"form-select product-select\" required>' +
                    '<option value=\"\">Sélectionner un produit</option>' +
                    availableProducts.map(p => '<option value=\"' + p.id + '\"' + (product && product.id == p.id ? ' selected' : '') + '>' + p.name + '</option>').join('') +
                '</select>' +
            '</div>' +
            '<div class=\"col-md-2\">' +
                '<input type=\"number\" class=\"form-control quantity\" placeholder=\"Qté\" value=\"' + (product ? product.quantity : '') + '\" min=\"1\" required>' +
            '</div>' +
            '<div class=\"col-md-3\">' +
                '<input type=\"number\" class=\"form-control unit-price\" placeholder=\"Prix unitaire\" value=\"' + (product ? product.unit_price : '') + '\" min=\"0\" step=\"0.01\" required>' +
            '</div>' +
            '<div class=\"col-md-2\">' +
                '<input type=\"text\" class=\"form-control total-price\" readonly value=\"' + (product ? formatMoney(product.total_price) : '0 FCFA') + '\">' +
            '</div>' +
            '<div class=\"col-md-1\">' +
                '<button type=\"button\" class=\"btn btn-outline-danger btn-sm remove-product\">' +
                    '<i class=\"fas fa-trash\"></i>' +
                '</button>' +
            '</div>' +
        '</div>'
    ;
    
    productsList.appendChild(productRow);
    attachProductEvents(productRow);
}

function attachProductEvents(row) {
    const productSelect = row.querySelector('.product-select');
    const quantityInput = row.querySelector('.quantity');
    const unitPriceInput = row.querySelector('.unit-price');
    const totalPriceInput = row.querySelector('.total-price');
    const removeBtn = row.querySelector('.remove-product');
    
    function calculateTotal() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const total = quantity * unitPrice;
        totalPriceInput.value = formatMoney(total);
        updateOrderTotal();
    }
    
    productSelect.addEventListener('change', calculateTotal);
    quantityInput.addEventListener('input', calculateTotal);
    unitPriceInput.addEventListener('input', calculateTotal);
    
    removeBtn.addEventListener('click', function() {
        row.remove();
        updateOrderTotal();
    });
}

function updateOrderTotal() {
    const rows = document.querySelectorAll('.product-row');
    let total = 0;
    
    rows.forEach(row => {
        const totalPriceInput = row.querySelector('.total-price');
        const value = totalPriceInput.value.replace(/[^0-9]/g, '');
        total += parseFloat(value) || 0;
    });
    
    document.getElementById('order-total').value = formatMoney(total);
}

function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0
    }).format(amount);
}

// Initialisation
document.getElementById('add-product').addEventListener('click', function() {
    console.log('Add product button clicked');
    addProductRow();
});

// Soumission du formulaire
document.getElementById('orderForm').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('.product-row');
    const products = [];
    
    rows.forEach(row => {
        const productSelect = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity');
        const unitPriceInput = row.querySelector('.unit-price');
        
        if (productSelect.value && quantityInput.value && unitPriceInput.value) {
            products.push({
                id: parseInt(productSelect.value),
                quantity: parseInt(quantityInput.value),
                unit_price: parseFloat(unitPriceInput.value)
            });
        }
    });
    
    document.getElementById('products-input').value = JSON.stringify(products);
});

// Fonctions de gestion
function confirmDeleteOrder(orderId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')) {
        document.getElementById('deleteOrderId').value = orderId;
        document.getElementById('deleteForm').submit();
    }
}

function updateOrderStatus(orderId, newStatus) {
    const statusLabels = {
        'delivered': 'livrée',
        'cancelled': 'annulée'
    };
    
    if (confirm('Êtes-vous sûr de vouloir marquer cette commande comme ' + statusLabels[newStatus] + ' ?')) {
        document.getElementById('statusOrderId').value = orderId;
        document.getElementById('newStatus').value = newStatus;
        document.getElementById('statusForm').submit();
    }
}

// Attacher les événements aux lignes existantes
document.querySelectorAll('.product-row').forEach(attachProductEvents);
";
?>

<?php require_once 'includes/footer.php'; ?>
