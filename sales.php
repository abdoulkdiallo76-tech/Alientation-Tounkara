<?php
require_once 'config/database.php';
requireLogin();

$page_title = 'Historique des Ventes';
require_once 'includes/header.php';

// Récupération des données
try {
    $search = cleanInput($_GET['search'] ?? '');
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $payment_method = cleanInput($_GET['payment_method'] ?? '');
    $cashier_filter = isset($_GET['cashier_id']) ? (int)$_GET['cashier_id'] : null;
    $location_filter = isset($_GET['location_id']) ? (int)$_GET['location_id'] : null;
    $session_filter = isset($_GET['session_id']) ? (int)$_GET['session_id'] : null;
    
    // Déterminer si l'utilisateur peut voir toutes les ventes
    $can_view_all_sales = isAdmin();
    
    // Vérifier si la table locations existe
    $locations_table_exists = false;
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'locations'");
        $stmt->execute();
        $locations_table_exists = $stmt->fetch() !== false;
    } catch (Exception $e) {
        $locations_table_exists = false;
    }
    
    // Vérifier si la colonne cash_session_id existe
    $cash_session_column_exists = false;
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM sales LIKE 'cash_session_id'");
        $stmt->execute();
        $cash_session_column_exists = $stmt->fetch() !== false;
    } catch (Exception $e) {
        $cash_session_column_exists = false;
    }
    
    // Récupérer la liste des caissiers pour le filtre (admin seulement)
    $cashiers = [];
    if ($can_view_all_sales) {
        $stmt = $pdo->prepare("SELECT id, full_name, username FROM users WHERE is_active = 1 AND role IN ('cashier', 'admin', 'manager') ORDER BY full_name");
        $stmt->execute();
        $cashiers = $stmt->fetchAll();
    }
    
    // Récupérer la liste des localités seulement si la table existe
    $locations = [];
    if ($locations_table_exists) {
        $locations = getAllLocations();
    }
    
    // Récupérer les sessions de caisse pour le filtre
    $sessions = [];
    if ($cash_session_column_exists) {
        $stmt = $pdo->prepare("SELECT cs.id, DATE(cs.opening_time) as session_date, cs.opening_time, cs.status, u.full_name as cashier_name
                                FROM cash_sessions cs 
                                LEFT JOIN users u ON cs.cashier_id = u.id 
                                ORDER BY cs.opening_time DESC LIMIT 50");
        $stmt->execute();
        $sessions = $stmt->fetchAll();
    }
    
    // Construire la requête en fonction des tables disponibles
    if ($locations_table_exists && $cash_session_column_exists) {
        $query = "SELECT s.*, u.full_name as cashier_name, l.name as location_name, 
                         cs.opening_time as session_date,
                         CASE WHEN cs.id IS NOT NULL THEN CONCAT('Session du ', DATE(cs.opening_time)) ELSE 'Sans session' END as session_info,
                         COUNT(sd.id) as items_count 
                  FROM sales s 
                  LEFT JOIN users u ON s.cashier_id = u.id 
                  LEFT JOIN locations l ON s.location_id = l.id 
                  LEFT JOIN cash_sessions cs ON s.cash_session_id = cs.id 
                  LEFT JOIN sale_details sd ON s.id = sd.sale_id 
                  WHERE 1=1";
    } elseif ($cash_session_column_exists) {
        $query = "SELECT s.*, u.full_name as cashier_name, 
                         cs.opening_time as session_date,
                         CASE WHEN cs.id IS NOT NULL THEN CONCAT('Session du ', DATE(cs.opening_time)) ELSE 'Sans session' END as session_info,
                         COUNT(sd.id) as items_count 
                  FROM sales s 
                  LEFT JOIN users u ON s.cashier_id = u.id 
                  LEFT JOIN cash_sessions cs ON s.cash_session_id = cs.id 
                  LEFT JOIN sale_details sd ON s.id = sd.sale_id 
                  WHERE 1=1";
    } elseif ($locations_table_exists) {
        $query = "SELECT s.*, u.full_name as cashier_name, l.name as location_name, 
                         'Sans session' as session_info,
                         COUNT(sd.id) as items_count 
                  FROM sales s 
                  LEFT JOIN users u ON s.cashier_id = u.id 
                  LEFT JOIN locations l ON s.location_id = l.id 
                  LEFT JOIN sale_details sd ON s.id = sd.sale_id 
                  WHERE 1=1";
    } else {
        $query = "SELECT s.*, u.full_name as cashier_name, 
                         'Sans session' as session_info,
                         COUNT(sd.id) as items_count 
                  FROM sales s 
                  LEFT JOIN users u ON s.cashier_id = u.id 
                  LEFT JOIN sale_details sd ON s.id = sd.sale_id 
                  WHERE 1=1";
    }
    
    $params = [];
    
    // Restreindre aux ventes de l'utilisateur si ce n'est pas un admin
    if (!$can_view_all_sales) {
        $query .= " AND s.cashier_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    // Filtre par localité (admin seulement et seulement si table existe)
    if ($can_view_all_sales && $locations_table_exists && $location_filter) {
        $query .= " AND s.location_id = ?";
        $params[] = $location_filter;
    }
    
    // Filtre par session de caisse
    if ($cash_session_column_exists && $session_filter) {
        $query .= " AND s.cash_session_id = ?";
        $params[] = $session_filter;
    }
    
    // Filtre par caissier (admin seulement)
    if ($can_view_all_sales && $cashier_filter) {
        $query .= " AND s.cashier_id = ?";
        $params[] = $cashier_filter;
    }
    
    if (!empty($search)) {
        $query .= " AND (s.customer_name LIKE ? OR s.customer_phone LIKE ? OR s.notes LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($payment_method)) {
        $query .= " AND s.payment_method = ?";
        $params[] = $payment_method;
    }
    
    $query .= " AND DATE(s.sale_date) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    
    $query .= " GROUP BY s.id ORDER BY s.sale_date DESC LIMIT 200";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
    
    // Statistiques - adapter selon le rôle et filtres
    $stats_query = "SELECT COUNT(*) as count, SUM(final_amount) as total FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?";
    $stats_params = [$date_from, $date_to];
    
    if (!$can_view_all_sales) {
        $stats_query .= " AND cashier_id = ?";
        $stats_params[] = $_SESSION['user_id'];
    }
    
    if ($cash_session_column_exists && $session_filter) {
        $stats_query .= " AND cash_session_id = ?";
        $stats_params[] = $session_filter;
    }
    
    $stmt = $pdo->prepare($stats_query);
    $stmt->execute($stats_params);
    $stats = $stmt->fetch();
    
    // Ventes par méthode de paiement - adapter selon le rôle et filtres
    $payment_query = "SELECT payment_method, COUNT(*) as count, SUM(final_amount) as total FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?";
    $payment_params = [$date_from, $date_to];
    
    if (!$can_view_all_sales) {
        $payment_query .= " AND cashier_id = ?";
        $payment_params[] = $_SESSION['user_id'];
    }
    
    if ($can_view_all_sales && $cashier_filter) {
        $payment_query .= " AND cashier_id = ?";
        $payment_params[] = $cashier_filter;
    }
    
    if ($cash_session_column_exists && $session_filter) {
        $payment_query .= " AND cash_session_id = ?";
        $payment_params[] = $session_filter;
    }
    
    $payment_query .= " GROUP BY payment_method";
    $stmt = $pdo->prepare($payment_query);
    $stmt->execute($payment_params);
    $payment_stats = $stmt->fetchAll();
    
    // Récupérer les ventes du jour si session ouverte
    $today_sales = [];
    if ($cash_session_column_exists && hasOpenCashSession()) {
        $today_sales = getTodaySalesBySession();
    }
    
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
    $sales = [];
    $stats = ['count' => 0, 'total' => 0];
    $payment_stats = [];
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-list me-3"></i>Historique des Ventes
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger fade-in-up">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Statistiques du jour -->
<?php if (!empty($today_sales)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-success bg-opacity-10 border-success">
            <div class="card-header">
                <h5 class="mb-0 text-success">
                    <i class="fas fa-calendar-day me-2"></i>Ventes du Jour
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h6>Nombre de ventes</h6>
                        <h4 class="text-success"><?php echo count($today_sales); ?></h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Total ventes</h6>
                        <h4 class="text-success"><?php echo formatMoney(array_sum(array_column($today_sales, 'final_amount'))); ?></h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Moyenne par vente</h6>
                        <h4 class="text-success"><?php echo formatMoney(array_sum(array_column($today_sales, 'final_amount')) / count($today_sales)); ?></h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Session active</h6>
                        <h4 class="text-success">Ouverte</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filtres -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filtres
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date début</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <?php if ($cash_session_column_exists): ?>
                    <div class="col-md-2">
                        <label for="session_id" class="form-label">Session de caisse</label>
                        <select class="form-select" id="session_id" name="session_id">
                            <option value="">Toutes les sessions</option>
                            <?php foreach ($sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>" <?php echo $session_filter == $session['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($session['session_info']); ?> - <?php echo htmlspecialchars($session['cashier_name']); ?>
                                <?php if ($session['status'] === 'open'): ?>
                                    <span class="badge bg-success ms-2">Ouverte</span>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($can_view_all_sales): ?>
                    <div class="col-md-2">
                        <label for="cashier_id" class="form-label">Caissier</label>
                        <select class="form-select" id="cashier_id" name="cashier_id">
                            <option value="">Tous les caissiers</option>
                            <?php foreach ($cashiers as $cashier): ?>
                            <option value="<?php echo $cashier['id']; ?>" <?php echo $cashier_filter == $cashier['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cashier['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($locations_table_exists && $can_view_all_sales): ?>
                    <div class="col-md-2">
                        <label for="location_id" class="form-label">Localité</label>
                        <select class="form-select" id="location_id" name="location_id">
                            <option value="">Toutes les localités</option>
                            <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['id']; ?>" <?php echo $location_filter == $location['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label for="payment_method" class="form-label">Méthode paiement</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">Toutes</option>
                            <option value="cash" <?php echo $payment_method == 'cash' ? 'selected' : ''; ?>>Espèces</option>
                            <option value="card" <?php echo $payment_method == 'card' ? 'selected' : ''; ?>>Carte</option>
                            <option value="mobile" <?php echo $payment_method == 'mobile' ? 'selected' : ''; ?>>Mobile Money</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="search" class="form-label">Recherche</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Client, téléphone...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                        <a href="sales.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times me-2"></i>Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques générales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-receipt fa-3x text-primary mb-3"></i>
                <h5>Total Ventes</h5>
                <h3 class="text-primary"><?php echo $stats['count']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                <h5>Chiffre d'Affaires</h5>
                <h3 class="text-success"><?php echo formatMoney($stats['total']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                <h5>Moyenne</h5>
                <h3 class="text-info"><?php echo $stats['count'] > 0 ? formatMoney($stats['total'] / $stats['count']) : formatMoney(0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-filter fa-3x text-warning mb-3"></i>
                <h5>Filtrés</h5>
                <h3 class="text-warning"><?php echo count($sales); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Liste des ventes -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Liste des Ventes
                    </h5>
                    <span class="badge bg-primary">
                        <?php echo count($sales); ?> ventes
                    </span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($sales)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Aucune vente trouvée avec ces filtres</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar me-1"></i>Date</th>
                                    <th><i class="fas fa-user me-1"></i>Caissier</th>
                                    <?php if ($cash_session_column_exists): ?>
                                    <th><i class="fas fa-cash-register me-1"></i>Session</th>
                                    <?php endif; ?>
                                    <?php if ($locations_table_exists): ?>
                                    <th><i class="fas fa-map-marker-alt me-1"></i>Localité</th>
                                    <?php endif; ?>
                                    <th><i class="fas fa-user me-1"></i>Client</th>
                                    <th><i class="fas fa-money-bill-wave me-1"></i>Total</th>
                                    <th><i class="fas fa-percentage me-1"></i>Remise</th>
                                    <th><i class="fas fa-hand-holding-usd me-1"></i>Final</th>
                                    <th><i class="fas fa-credit-card me-1"></i>Paiement</th>
                                    <th><i class="fas fa-cog me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-clock text-muted me-1"></i>
                                        <?php 
                                        $date = new DateTime($sale['sale_date']);
                                        echo $date->format('d/m/Y H:i');
                                        ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($sale['cashier_name']); ?></strong>
                                        </div>
                                    </td>
                                    <?php if ($cash_session_column_exists): ?>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($sale['session_info']); ?></small>
                                    </td>
                                    <?php endif; ?>
                                    <?php if ($locations_table_exists): ?>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($sale['location_name'] ?? 'Non définie'); ?>
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($sale['customer_name'] ?: 'Comptant'); ?></strong>
                                            <?php if ($sale['customer_phone']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo formatMoney($sale['total_amount']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <?php echo formatMoney($sale['discount_amount']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo formatMoney($sale['final_amount']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $payment_icons = [
                                            'cash' => 'fa-money-bill-wave',
                                            'card' => 'fa-credit-card',
                                            'mobile' => 'fa-mobile-alt'
                                        ];
                                        $icon = $payment_icons[$sale['payment_method']] ?? 'fa-question';
                                        ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas <?php echo $icon; ?> me-1"></i>
                                            <?php echo ucfirst($sale['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="sale_details.php?id=<?php echo $sale['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php 
                                            // Autoriser la suppression seulement dans les 24 heures et pour le caissier
                                            $sale_time = strtotime($sale['sale_date']);
                                            $time_diff = time() - $sale_time;
                                            if ($time_diff < 86400 && ($sale['cashier_id'] == $_SESSION['user_id'] || isAdmin())): 
                                            ?>
                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $sale['id']; ?>)">
                                                <i class="fas fa-trash"></i>
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
    </div>
</div>

</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(saleId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette vente ?\n\nLe stock sera automatiquement restauré.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="delete_sale" value="1"><input type="hidden" name="sale_id" value="' + saleId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
