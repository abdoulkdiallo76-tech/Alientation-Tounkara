<?php
require_once 'config/database.php';
$page_title = 'Gestion du stock';

// Vérifier les droits d'accès AVANT d'inclure le header
if (isCashier()) {
    header('Location: pos.php');
    exit();
}

require_once 'includes/header.php';

// Récupérer les produits avec leur stock
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name,
               CASE 
                   WHEN p.stock_quantity <= p.min_stock_alert THEN 'critical'
                   WHEN p.stock_quantity <= (p.min_stock_alert * 2) THEN 'low'
                   ELSE 'good'
               END as stock_status
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1 
        ORDER BY 
               CASE 
                   WHEN p.stock_quantity <= p.min_stock_alert THEN 1
                   ELSE 2
               END,
               p.name ASC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Statistiques de stock
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $stmt->execute();
    $total_products = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock_alert AND is_active = 1");
    $stmt->execute();
    $low_stock_count = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT SUM(stock_quantity * purchase_price) as total_value FROM products WHERE is_active = 1");
    $stmt->execute();
    $stock_value = $stmt->fetch();
    
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-warehouse me-2"></i>Gestion du stock
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Statistiques du stock -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total produits</h6>
                        <h3 class="mb-0"><?php echo $total_products['total'] ?? 0; ?></h3>
                        <small>En catalogue</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-cube fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Stock critique</h6>
                        <h3 class="mb-0"><?php echo $low_stock_count['count'] ?? 0; ?></h3>
                        <small>À réapprovisionner</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Valeur du stock</h6>
                        <h3 class="mb-0"><?php echo formatMoney($stock_value['total_value'] ?? 0); ?></h3>
                        <small>Valeur d'achat</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Actions</h6>
                        <div class="btn-group w-100">
                            <a href="products.php?action=add" class="btn btn-sm btn-light">
                                <i class="fas fa-plus"></i>
                            </a>
                            <a href="supplier_orders.php?action=add" class="btn btn-sm btn-light">
                                <i class="fas fa-truck"></i>
                            </a>
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-cogs fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Liste des produits avec stock -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>État du stock
        </h5>
        <div>
            <a href="products.php" class="btn btn-sm btn-outline-primary">Gérer les produits</a>
            <a href="supplier_orders.php?action=add" class="btn btn-sm btn-outline-success">
                <i class="fas fa-plus me-1"></i>Commander
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <p class="text-muted text-center">Aucun produit trouvé</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Stock actuel</th>
                            <th>Stock minimum</th>
                            <th>Statut</th>
                            <th>Valeur</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($product['unit'] ?? 'Unité'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Non catégorisé'); ?></td>
                            <td>
                                <span class="fw-bold <?php echo $product['stock_status'] === 'critical' ? 'text-danger' : ($product['stock_status'] === 'low' ? 'text-warning' : 'text-success'); ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </span>
                            </td>
                            <td><?php echo $product['min_stock_alert']; ?></td>
                            <td>
                                <?php if ($product['stock_status'] === 'critical'): ?>
                                    <span class="badge bg-danger">Critique</span>
                                <?php elseif ($product['stock_status'] === 'low'): ?>
                                    <span class="badge bg-warning">Faible</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Bon</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatMoney($product['stock_quantity'] * $product['purchase_price']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="supplier_orders.php?action=add&product_id=<?php echo $product['id']; ?>" class="btn btn-outline-success" title="Réapprovisionner">
                                        <i class="fas fa-truck"></i>
                                    </a>
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

<?php require_once 'includes/footer.php'; ?>
