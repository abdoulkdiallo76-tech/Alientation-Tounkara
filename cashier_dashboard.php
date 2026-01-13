<?php
require_once 'config/database.php';
requireLogin();

// Vérifier si c'est un caissier
if (!isCashier()) {
    header('Location: index.php');
    exit();
}

$page_title = 'Espace Caissier';
require_once 'includes/header.php';

// Récupérer les statistiques du caissier
$today = date('Y-m-d');
try {
    // Ventes du jour pour ce caissier
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(final_amount) as total FROM sales WHERE DATE(sale_date) = ? AND cashier_id = ?");
    $stmt->execute([$today, $_SESSION['user_id']]);
    $today_sales = $stmt->fetch();
    
    // Dernières ventes du caissier
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE cashier_id = ? ORDER BY sale_date DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_sales = $stmt->fetchAll();
    
    // Moyenne par vente
    $avg_sale = $today_sales['count'] > 0 ? ($today_sales['total'] / $today_sales['count']) : 0;
    
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
    $today_sales = ['count' => 0, 'total' => 0];
    $recent_sales = [];
    $avg_sale = 0;
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-cash-register me-3"></i>Espace Caissier
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger fade-in-up">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Message de bienvenue -->
<div class="row mb-5">
    <div class="col-12">
        <div class="card text-center fade-in-up">
            <div class="card-body py-5">
                <i class="fas fa-cash-register fa-5x text-primary mb-4"></i>
                <h3 class="mb-3">Bienvenue, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h3>
                <p class="text-muted mb-4">Espace caissier - Gestion des ventes</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="pos.php" class="btn btn-success btn-lg px-5 py-3">
                        <i class="fas fa-cash-register me-2"></i>
                        <div>
                            <div class="fw-bold">Nouvelle vente</div>
                            <small>Commencer une vente</small>
                        </div>
                    </a>
                    <a href="sales.php" class="btn btn-info btn-lg px-5 py-3">
                        <i class="fas fa-list me-2"></i>
                        <div>
                            <div class="fw-bold">Historique</div>
                            <small>Voir les ventes</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="row mb-5">
    <div class="col-md-4 mb-4 fade-in-up" style="animation-delay: 0.1s;">
        <div class="card text-white h-100">
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
                <small class="text-white-50">
                    <?php if ($today_sales['count'] > 0): ?>
                        <i class="fas fa-check-circle me-1"></i>Activité en cours
                    <?php else: ?>
                        <i class="fas fa-info-circle me-1"></i>Aucune vente aujourd'hui
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4 fade-in-up" style="animation-delay: 0.2s;">
        <div class="card text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Moyenne par vente</h6>
                        <h2 class="mb-2"><?php echo formatMoney($avg_sale); ?></h2>
                        <p class="mb-0">Montant moyen</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-chart-line fa-3x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-white-50">
                    <?php if ($today_sales['count'] > 0): ?>
                        <i class="fas fa-calculator me-1"></i>Calcul automatique
                    <?php else: ?>
                        <i class="fas fa-info-circle me-1"></i>En attente de ventes
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4 fade-in-up" style="animation-delay: 0.3s;">
        <div class="card text-white h-100">
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
                <small class="text-white-50">
                    <?php if (!empty($recent_sales)): ?>
                        <i class="fas fa-database me-1"></i><?php echo count($recent_sales); ?> enregistrements
                    <?php else: ?>
                        <i class="fas fa-info-circle me-1"></i>Aucun enregistrement
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row mb-5">
    <div class="col-12 fade-in-up" style="animation-delay: 0.4s;">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Actions rapides
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <a href="pos.php" class="btn btn-success w-100 py-4 h-100 d-flex flex-column justify-content-center align-items-center text-white">
                            <i class="fas fa-cash-register fa-3x mb-3"></i>
                            <span class="fw-bold">Nouvelle vente</span>
                            <small class="opacity-75">Commencer une vente</small>
                        </a>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <a href="sales.php" class="btn btn-info w-100 py-4 h-100 d-flex flex-column justify-content-center align-items-center text-white">
                            <i class="fas fa-list fa-3x mb-3"></i>
                            <span class="fw-bold">Historique des ventes</span>
                            <small class="opacity-75">Voir toutes les ventes</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dernières ventes -->
<div class="row">
    <div class="col-12 fade-in-up" style="animation-delay: 0.5s;">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Vos dernières ventes
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
                                    <th><i class="fas fa-cog me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_sales as $sale): ?>
                                <tr>
                                    <td><span class="badge bg-primary">#<?php echo $sale['id']; ?></span></td>
                                    <td><i class="fas fa-clock text-muted me-1"></i><?php echo formatDate($sale['sale_date']); ?></td>
                                    <td class="fw-bold text-success"><?php echo formatMoney($sale['final_amount']); ?></td>
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

</div>
</main>

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
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
