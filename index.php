<?php
require_once 'config/database.php';

$page_title = isCashier() ? 'Tableau de bord caissier' : 'Tableau de bord';
require_once 'includes/header.php';

// Statistiques du jour
$today = date('Y-m-d');
try {
    // Ventes du jour
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(final_amount) as total FROM sales WHERE DATE(sale_date) = ?");
    $stmt->execute([$today]);
    $today_sales = $stmt->fetch();
    
    // S'assurer que les valeurs ne sont pas null
    $today_sales['count'] = $today_sales['count'] ?? 0;
    $today_sales['total'] = $today_sales['total'] ?? 0;
    
    // Dernières ventes
    $stmt = $pdo->prepare("SELECT s.*, u.full_name as cashier_name FROM sales s LEFT JOIN users u ON s.cashier_id = u.id ORDER BY s.sale_date DESC LIMIT 10");
    $stmt->execute();
    $recent_sales = $stmt->fetchAll();
    
    // Pour les non-caissiers: statistiques avancées
    if (!isCashier()) {
        // Total produits
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
        $stmt->execute();
        $total_products = $stmt->fetch();
        $total_products['count'] = $total_products['count'] ?? 0;
        
        // Produits en stock faible
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock_alert AND is_active = 1");
        $stmt->execute();
        $low_stock = $stmt->fetch();
        $low_stock['count'] = $low_stock['count'] ?? 0;
        
        // Dépenses du mois
        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE) AND YEAR(expense_date) = YEAR(CURRENT_DATE)");
        $stmt->execute();
        $month_expenses = $stmt->fetch();
        $month_expenses['total'] = $month_expenses['total'] ?? 0;
        
        // Produits à réapprovisionner
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock_quantity <= p.min_stock_alert AND p.is_active = 1 ORDER BY p.stock_quantity ASC LIMIT 5");
        $stmt->execute();
        $products_to_restock = $stmt->fetchAll();
    }
    
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
    
    // Valeurs par défaut en cas d'erreur
    $today_sales = ['count' => 0, 'total' => 0];
    $recent_sales = [];
    if (!isCashier()) {
        $total_products = ['count' => 0];
        $low_stock = ['count' => 0];
        $month_expenses = ['total' => 0];
        $products_to_restock = [];
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-tachometer-alt me-3"></i><?php echo isCashier() ? 'Tableau de bord caissier' : 'Tableau de bord'; ?>
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger fade-in-up">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Message si aucune donnée -->
<?php if ($today_sales['count'] == 0 && empty($recent_sales) && (!isCashier() ? $total_products['count'] == 0 : true)): ?>
<div class="alert alert-info fade-in-up">
    <div class="text-center">
        <i class="fas fa-info-circle fa-3x mb-3"></i>
        <h5><i class="fas fa-database me-2"></i>Aucune donnée disponible</h5>
        <p class="mb-3">Votre base de données semble être vide. Voici quelques actions pour commencer:</p>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="list-group">
                    <?php if (!isCashier()): ?>
                    <a href="products.php?action=add" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle text-success me-2"></i>
                        <strong>Ajouter des produits</strong> - Commencez par créer votre catalogue
                    </a>
                    <a href="categories.php?action=add" class="list-group-item list-group-item-action">
                        <i class="fas fa-tags text-info me-2"></i>
                        <strong>Créer des catégories</strong> - Organisez vos produits par catégories
                    </a>
                    <a href="suppliers.php?action=add" class="list-group-item list-group-item-action">
                        <i class="fas fa-users text-warning me-2"></i>
                        <strong>Ajouter des fournisseurs</strong> - Configurez vos fournisseurs
                    </a>
                    <?php endif; ?>
                    <a href="pos.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cash-register text-primary me-2"></i>
                        <strong>Commencer une vente</strong> - Enregistrez votre première vente
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cartes de statistiques -->
<div class="row mb-5">
    <?php if (isCashier()): ?>
    <!-- Interface caissier simplifiée -->
    <div class="col-md-4 mb-4 fade-in-up" style="animation-delay: 0.1s;">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Ventes du jour</h6>
                        <h2 class="mb-2"><?php echo $today_sales['count']; ?></h2>
                        <p class="mb-0"><?php echo formatMoney($today_sales['total']); ?></p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-shopping-cart fa-3x text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <?php if ($today_sales['count'] > 0): ?>
                        <i class="fas fa-check-circle text-success me-1"></i>Activité en cours
                    <?php else: ?>
                        <i class="fas fa-info-circle text-info me-1"></i>Aucune vente aujourd'hui
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4 fade-in-up" style="animation-delay: 0.2s;">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Moyenne par vente</h6>
                        <h2 class="mb-2"><?php echo $today_sales['count'] > 0 ? formatMoney(($today_sales['total']) / $today_sales['count']) : formatMoney(0); ?></h2>
                        <p class="mb-0">Montant moyen</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-chart-line fa-3x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <?php if ($today_sales['count'] > 0): ?>
                        <i class="fas fa-calculator text-primary me-1"></i>Calcul automatique
                    <?php else: ?>
                        <i class="fas fa-info-circle text-info me-1"></i>En attente de ventes
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4 fade-in-up" style="animation-delay: 0.3s;">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Total ventes</h6>
                        <h2 class="mb-2"><?php echo count($recent_sales); ?></h2>
                        <p class="mb-0">Dernières ventes</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-list fa-3x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <?php if (!empty($recent_sales)): ?>
                        <i class="fas fa-database text-primary me-1"></i><?php echo count($recent_sales); ?> enregistrements
                    <?php else: ?>
                        <i class="fas fa-info-circle text-info me-1"></i>Aucun enregistrement
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Interface admin/manager complète -->
    <div class="col-md-3 col-sm-6 mb-4 fade-in-up" style="animation-delay: 0.1s;">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Ventes du jour</h6>
                        <h2 class="mb-2"><?php echo $today_sales['count']; ?></h2>
                        <p class="mb-0"><?php echo formatMoney($today_sales['total']); ?></p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-shopping-cart fa-3x text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <?php if ($today_sales['count'] > 0): ?>
                        <i class="fas fa-check-circle text-success me-1"></i>Activité en cours
                    <?php else: ?>
                        <i class="fas fa-info-circle text-info me-1"></i>Aucune vente aujourd'hui
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4 fade-in-up" style="animation-delay: 0.2s;">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Total produits</h6>
                        <h2 class="mb-2"><?php echo $total_products['count']; ?></h2>
                        <p class="mb-0">En catalogue</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-cube fa-3x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <?php if ($total_products['count'] > 0): ?>
                        <i class="fas fa-check-circle text-success me-1"></i>Catalogue actif
                    <?php else: ?>
                        <i class="fas fa-info-circle text-info me-1"></i>Aucun produit
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4 fade-in-up" style="animation-delay: 0.3s;">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Stock faible</h6>
                        <h2 class="mb-2"><?php echo $low_stock['count']; ?></h2>
                        <p class="mb-0">Produits à réapprovisionner</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <?php if ($low_stock['count'] > 0): ?>
                        <i class="fas fa-bell text-warning me-1"></i><?php echo $low_stock['count']; ?> alertes
                    <?php else: ?>
                        <i class="fas fa-check-circle text-success me-1"></i>Stock suffisant
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4 fade-in-up" style="animation-delay: 0.4s;">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Dépenses du mois</h6>
                        <h2 class="mb-2"><?php echo formatMoney($month_expenses['total']); ?></h2>
                        <p class="mb-0">Total mensuel</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-money-bill-wave fa-3x text-danger opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <?php if ($month_expenses['total'] > 0): ?>
                        <i class="fas fa-receipt text-danger me-1"></i>Dépenses enregistrées
                    <?php else: ?>
                        <i class="fas fa-info-circle text-info me-1"></i>Aucune dépense
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Dernières ventes -->
<div class="row mb-5">
    <div class="col-12 fade-in-up" style="animation-delay: 0.5s;">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Dernières ventes
                    </h5>
                    <a href="sales.php" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-right me-1"></i>Voir tout
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($recent_sales)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Aucune vente aujourd'hui</p>
                        <a href="pos.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-2"></i>Commencer une vente
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                    <th><i class="fas fa-calendar me-1"></i>Date</th>
                                    <th><i class="fas fa-money-bill me-1"></i>Montant</th>
                                    <?php if (!isCashier()): ?>
                                    <th><i class="fas fa-user me-1"></i>Caissier</th>
                                    <?php endif; ?>
                                    <th><i class="fas fa-cog me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_sales as $sale): ?>
                                <tr>
                                    <td><span class="badge bg-primary">#<?php echo $sale['id']; ?></span></td>
                                    <td><i class="fas fa-clock text-muted me-1"></i><?php echo formatDate($sale['sale_date']); ?></td>
                                    <td class="fw-bold text-success"><?php echo formatMoney($sale['final_amount']); ?></td>
                                    <?php if (!isCashier()): ?>
                                    <td><i class="fas fa-user-tie text-muted me-1"></i><?php echo $sale['cashier_name'] ?? 'N/A'; ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <a href="sale_details.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> Détails
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!isCashier()): ?>
<!-- Sections supplémentaires pour les admins/managers -->
<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Stock faible
                </h5>
                <a href="stock.php" class="btn btn-sm btn-outline-warning">Voir tout</a>
            </div>
            <div class="card-body">
                <?php if (empty($products_to_restock)): ?>
                    <p class="text-muted text-center">Tous les produits sont en stock</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($products_to_restock as $product): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $product['name']; ?></h6>
                                    <small class="text-muted"><?php echo $product['category_name']; ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger rounded-pill"><?php echo $product['stock_quantity']; ?></span>
                                    <small class="d-block text-muted">Min: <?php echo $product['min_stock_alert']; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Actions rapides -->
<div class="row mb-5">
    <div class="col-12 fade-in-up" style="animation-delay: 0.6s;">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Actions rapides
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="pos.php" class="btn btn-primary w-100 py-4 h-100 d-flex flex-column justify-content-center align-items-center text-white">
                            <i class="fas fa-cash-register fa-3x mb-3"></i>
                            <span class="fw-bold">Nouvelle vente</span>
                            <small class="opacity-75">Commencer maintenant</small>
                        </a>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="sales.php" class="btn btn-info w-100 py-4 h-100 d-flex flex-column justify-content-center align-items-center text-white">
                            <i class="fas fa-list fa-3x mb-3"></i>
                            <span class="fw-bold">Historique complet</span>
                            <small class="opacity-75">Toutes les ventes</small>
                        </a>
                    </div>
                    
                    <?php if (!isCashier()): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="products.php?action=add" class="btn btn-success w-100 py-4 h-100 d-flex flex-column justify-content-center align-items-center text-white">
                            <i class="fas fa-plus-circle fa-3x mb-3"></i>
                            <span class="fw-bold">Ajouter produit</span>
                            <small class="opacity-75">Nouvel article</small>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="expenses.php?action=add" class="btn btn-danger w-100 py-4 h-100 d-flex flex-column justify-content-center align-items-center text-white">
                            <i class="fas fa-receipt fa-3x mb-3"></i>
                            <span class="fw-bold">Ajouter dépense</span>
                            <small class="opacity-75">Nouvelle dépense</small>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="supplier_orders.php?action=add" class="btn btn-warning w-100 py-4 h-100 d-flex flex-column justify-content-center align-items-center text-white">
                            <i class="fas fa-truck fa-3x mb-3"></i>
                            <span class="fw-bold">Commander stock</span>
                            <small class="opacity-75">Réapprovisionnement</small>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ajouter des effets interactifs
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des cartes au survol
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Animation des boutons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Solution simple pour les liens de navigation
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Navigation loaded');
        
        // Forcer la cliquabilité de tous les liens
        setTimeout(function() {
            const allLinks = document.querySelectorAll('a');
            allLinks.forEach(link => {
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
                
                // Ajouter un gestionnaire de clic simple
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href && href !== '#' && href !== 'javascript:void(0)') {
                        console.log('Navigation to:', href);
                        window.location.href = href;
                    }
                });
            });
        }, 100);
    });
</script>
