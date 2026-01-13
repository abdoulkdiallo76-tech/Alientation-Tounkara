<?php
require_once 'config/database.php';
$page_title = 'Rapports';

// Vérifier les droits d'accès AVANT d'inclure le header
if (isCashier()) {
    header('Location: pos.php');
    exit();
}

require_once 'includes/header.php';

// Vérifier si l'utilisateur a les droits d'accès aux rapports
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: index.php');
    exit();
}

// Récupérer les paramètres de filtre
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'sales';

// Traitement des données de rapports
try {
    // Rapport des ventes
    if ($report_type === 'sales') {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(sale_date) as date,
                COUNT(*) as total_sales,
                SUM(total_amount) as total_revenue,
                SUM(discount_amount) as total_discount,
                SUM(final_amount) as net_revenue
            FROM sales 
            WHERE DATE(sale_date) BETWEEN ? AND ?
            GROUP BY DATE(sale_date)
            ORDER BY date DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $sales_report = $stmt->fetchAll();
        
        // Produits les plus vendus
        $stmt = $pdo->prepare("
            SELECT 
                p.name,
                SUM(sd.quantity) as total_quantity,
                SUM(sd.total_price) as total_revenue
            FROM sale_details sd
            JOIN sales s ON sd.sale_id = s.id
            JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY p.id, p.name
            ORDER BY total_quantity DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date, $end_date]);
        $top_products = $stmt->fetchAll();
        
        // Ventes par caissier
        $stmt = $pdo->prepare("
            SELECT 
                u.full_name,
                COUNT(s.id) as total_sales,
                SUM(s.final_amount) as total_revenue
            FROM sales s
            LEFT JOIN users u ON s.cashier_id = u.id
            WHERE DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY s.cashier_id, u.full_name
            ORDER BY total_revenue DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $sales_by_cashier = $stmt->fetchAll();
    }
    
    // Rapport des dépenses
    if ($report_type === 'expenses') {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(expense_date) as date,
                category,
                COUNT(*) as total_expenses,
                SUM(amount) as total_amount
            FROM expenses 
            WHERE DATE(expense_date) BETWEEN ? AND ?
            GROUP BY DATE(expense_date), category
            ORDER BY date DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $expenses_report = $stmt->fetchAll();
        
        // Dépenses par catégorie
        $stmt = $pdo->prepare("
            SELECT 
                category,
                COUNT(*) as count,
                SUM(amount) as total
            FROM expenses 
            WHERE DATE(expense_date) BETWEEN ? AND ?
            GROUP BY category
            ORDER BY total DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $expenses_by_category = $stmt->fetchAll();
    }
    
    // Rapport de stock
    if ($report_type === 'stock') {
        $stmt = $pdo->prepare("
            SELECT 
                p.name,
                c.name as category_name,
                p.stock_quantity,
                p.min_stock_alert,
                p.purchase_price,
                p.selling_price,
                (p.stock_quantity * p.purchase_price) as stock_value,
                (p.stock_quantity * p.selling_price) as potential_revenue
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1
            ORDER BY p.stock_quantity ASC
        ");
        $stmt->execute();
        $stock_report = $stmt->fetchAll();
        
        // Mouvements de stock récents
        $stmt = $pdo->prepare("
            SELECT 
                sm.*,
                p.name as product_name,
                u.full_name as user_name
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            LEFT JOIN users u ON sm.created_by = u.id
            WHERE DATE(sm.created_at) BETWEEN ? AND ?
            ORDER BY sm.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$start_date, $end_date]);
        $stock_movements = $stmt->fetchAll();
    }
    
    // Rapport des fournisseurs
    if ($report_type === 'suppliers') {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                COUNT(so.id) as total_orders,
                COALESCE(SUM(so.total_amount), 0) as total_purchases
            FROM suppliers s
            LEFT JOIN supplier_orders so ON s.id = so.supplier_id
            WHERE DATE(so.created_at) BETWEEN ? AND ? OR so.id IS NULL
            GROUP BY s.id
            ORDER BY total_purchases DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $suppliers_report = $stmt->fetchAll();
    }
    
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-chart-bar me-2"></i>Rapports
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="report_type" class="form-label">Type de rapport</label>
                <select class="form-select" id="report_type" name="report_type" onchange="this.form.submit()">
                    <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Ventes</option>
                    <option value="expenses" <?php echo $report_type === 'expenses' ? 'selected' : ''; ?>>Dépenses</option>
                    <option value="stock" <?php echo $report_type === 'stock' ? 'selected' : ''; ?>>Stock</option>
                    <option value="suppliers" <?php echo $report_type === 'suppliers' ? 'selected' : ''; ?>>Fournisseurs</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Date début</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Date fin</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filtrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($report_type === 'sales'): ?>
    <!-- Rapport des ventes -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>Ventes par jour
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Nb ventes</th>
                                    <th>Chiffre d'affaires</th>
                                    <th>Remises</th>
                                    <th>Net à payer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales_report)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">Aucune vente trouvée</td></tr>
                                <?php else: ?>
                                    <?php foreach ($sales_report as $sale): ?>
                                    <tr>
                                        <td><?php echo formatDate($sale['date']); ?></td>
                                        <td><?php echo $sale['total_sales']; ?></td>
                                        <td><?php echo formatMoney($sale['total_revenue']); ?></td>
                                        <td><?php echo formatMoney($sale['total_discount']); ?></td>
                                        <td class="fw-bold"><?php echo formatMoney($sale['net_revenue']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-primary fw-bold">
                                        <td>Total</td>
                                        <td><?php echo array_sum(array_column($sales_report, 'total_sales')); ?></td>
                                        <td><?php echo formatMoney(array_sum(array_column($sales_report, 'total_revenue'))); ?></td>
                                        <td><?php echo formatMoney(array_sum(array_column($sales_report, 'total_discount'))); ?></td>
                                        <td><?php echo formatMoney(array_sum(array_column($sales_report, 'net_revenue'))); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>Top 10 produits
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Revenu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_products)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Aucune vente</td></tr>
                                <?php else: ?>
                                    <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo $product['total_quantity']; ?></td>
                                        <td><?php echo formatMoney($product['total_revenue']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Ventes par caissier
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Caissier</th>
                                    <th>Ventes</th>
                                    <th>Revenu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales_by_cashier)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Aucune vente</td></tr>
                                <?php else: ?>
                                    <?php foreach ($sales_by_cashier as $cashier): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cashier['full_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo $cashier['total_sales']; ?></td>
                                        <td><?php echo formatMoney($cashier['total_revenue']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($report_type === 'expenses'): ?>
    <!-- Rapport des dépenses -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>Dépenses par jour
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Catégorie</th>
                                    <th>Nb dépenses</th>
                                    <th>Montant total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses_report)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">Aucune dépense trouvée</td></tr>
                                <?php else: ?>
                                    <?php foreach ($expenses_report as $expense): ?>
                                    <tr>
                                        <td><?php echo formatDate($expense['date']); ?></td>
                                        <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                        <td><?php echo $expense['total_expenses']; ?></td>
                                        <td><?php echo formatMoney($expense['total_amount']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Dépenses par catégorie
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Nb</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses_by_category)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Aucune dépense</td></tr>
                                <?php else: ?>
                                    <?php foreach ($expenses_by_category as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category']); ?></td>
                                        <td><?php echo $category['count']; ?></td>
                                        <td><?php echo formatMoney($category['total']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($report_type === 'stock'): ?>
    <!-- Rapport de stock -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-warehouse me-2"></i>État du stock
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th>Stock</th>
                                    <th>Alerte</th>
                                    <th>Valeur stock</th>
                                    <th>Revenu potentiel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stock_report)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">Aucun produit trouvé</td></tr>
                                <?php else: ?>
                                    <?php foreach ($stock_report as $product): ?>
                                    <tr class="<?php echo $product['stock_quantity'] <= $product['min_stock_alert'] ? 'table-warning' : ''; ?>">
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="<?php echo $product['stock_quantity'] <= $product['min_stock_alert'] ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $product['min_stock_alert']; ?></td>
                                        <td><?php echo formatMoney($product['stock_value']); ?></td>
                                        <td><?php echo formatMoney($product['potential_revenue']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-primary fw-bold">
                                        <td colspan="4">Total</td>
                                        <td><?php echo formatMoney(array_sum(array_column($stock_report, 'stock_value'))); ?></td>
                                        <td><?php echo formatMoney(array_sum(array_column($stock_report, 'potential_revenue'))); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exchange-alt me-2"></i>Mouvements récents
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Type</th>
                                    <th>Quantité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stock_movements)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Aucun mouvement</td></tr>
                                <?php else: ?>
                                    <?php foreach ($stock_movements as $movement): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $movement['movement_type'] === 'in' ? 'success' : 'danger'; ?>">
                                                <?php echo $movement['movement_type'] === 'in' ? 'Entrée' : 'Sortie'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $movement['quantity']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($report_type === 'suppliers'): ?>
    <!-- Rapport des fournisseurs -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-truck me-2"></i>Performance des fournisseurs
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fournisseur</th>
                                    <th>Contact</th>
                                    <th>Téléphone</th>
                                    <th>Nb commandes</th>
                                    <th>Total achats</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suppliers_report)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">Aucun fournisseur trouvé</td></tr>
                                <?php else: ?>
                                    <?php foreach ($suppliers_report as $supplier): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($supplier['name']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['phone'] ?? ''); ?></td>
                                        <td><?php echo $supplier['total_orders']; ?></td>
                                        <td class="fw-bold"><?php echo formatMoney($supplier['total_purchases']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
