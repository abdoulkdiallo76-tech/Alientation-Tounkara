<?php
require_once 'config/database.php';
requireLogin();

// Seuls les admins peuvent voir les sessions de caisse
if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

$page_title = 'Sessions de Caisse';
require_once 'includes/header.php';

// Récupérer les sessions de caisse
$cashier_filter = isset($_GET['cashier_id']) ? (int)$_GET['cashier_id'] : null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Construire la requête SQL
$sql = "SELECT cs.*, u.full_name as cashier_name, u.username as cashier_username
        FROM cash_sessions cs
        LEFT JOIN users u ON cs.cashier_id = u.id
        WHERE 1=1";

$params = [];

if ($cashier_filter) {
    $sql .= " AND cs.cashier_id = ?";
    $params[] = $cashier_filter;
}

if ($status_filter && in_array($status_filter, ['open', 'closed'])) {
    $sql .= " AND cs.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY cs.opening_time DESC LIMIT 100";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll();
} catch(PDOException $e) {
    $sessions = [];
    $error = 'Erreur: ' . $e->getMessage();
}

// Récupérer tous les caissiers pour le filtre
try {
    $stmt = $pdo->prepare("SELECT id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name");
    $stmt->execute();
    $cashiers = $stmt->fetchAll();
} catch(PDOException $e) {
    $cashiers = [];
    $error = 'Erreur: ' . $e->getMessage();
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-cash-register me-3"></i>Sessions de Caisse
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger fade-in-up">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
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
                    <div class="col-md-4">
                        <label for="cashier_id" class="form-label">Caissier</label>
                        <select class="form-select" id="cashier_id" name="cashier_id">
                            <option value="">Tous les caissiers</option>
                            <?php foreach ($cashiers as $cashier): ?>
                            <option value="<?php echo $cashier['id']; ?>" <?php echo $cashier_filter == $cashier['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cashier['full_name']); ?> (<?php echo htmlspecialchars($cashier['username']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>Ouvertes</option>
                            <option value="closed" <?php echo $status_filter == 'closed' ? 'selected' : ''; ?>>Fermées</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="sessions.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-times me-2"></i>Réinitialiser
                        </a>
                        <a href="cash_management.php" class="btn btn-outline-primary">
                            <i class="fas fa-cash-register me-2"></i>Gérer Caisse
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Sessions de caisse -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Liste des Sessions de Caisse
                    </h5>
                    <span class="badge bg-primary">
                        <?php echo count($sessions); ?> sessions
                    </span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($sessions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-cash-register fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Aucune session de caisse trouvée</p>
                        <a href="cash_management.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Ouvrir une Session
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-1"></i>Caissier</th>
                                    <th><i class="fas fa-calendar me-1"></i>Ouverture</th>
                                    <th><i class="fas fa-calendar-check me-1"></i>Fermeture</th>
                                    <th><i class="fas fa-money-bill-wave me-1"></i>Fonds Initial</th>
                                    <th><i class="fas fa-money-bill-wave me-1"></i>Fonds Final</th>
                                    <th><i class="fas fa-info-circle me-1"></i>Statut</th>
                                    <th><i class="fas fa-clock me-1"></i>Durée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($session['cashier_name'] ?? 'Inconnu'); ?></strong>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($session['cashier_username'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-clock text-muted me-1"></i>
                                        <?php 
                                        $date = new DateTime($session['opening_time']);
                                        echo $date->format('d/m/Y H:i:s');
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($session['closing_time']): ?>
                                            <i class="fas fa-clock text-muted me-1"></i>
                                            <?php 
                                            $date = new DateTime($session['closing_time']);
                                            echo $date->format('d/m/Y H:i:s');
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">En cours</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo formatMoney($session['opening_amount']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($session['closing_amount']): ?>
                                            <span class="badge bg-info">
                                                <?php echo formatMoney($session['closing_amount']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($session['status'] === 'open'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-lock-open me-1"></i>Ouverte
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-lock me-1"></i>Fermée
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($session['closing_time']) {
                                            $opening = new DateTime($session['opening_time']);
                                            $closing = new DateTime($session['closing_time']);
                                            $duration = $closing->diff($opening);
                                            echo $duration->format('%h h %i min');
                                        } else {
                                            $opening = new DateTime($session['opening_time']);
                                            $now = new DateTime();
                                            $duration = $now->diff($opening);
                                            echo $duration->format('%h h %i min');
                                        }
                                        ?>
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

<!-- Statistiques -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-lock-open fa-3x text-success mb-3"></i>
                <h5>Sessions ouvertes</h5>
                <h3 class="text-success">
                    <?php 
                    $open_sessions = array_filter($sessions, function($s) {
                        return $s['status'] === 'open';
                    });
                    echo count($open_sessions);
                    ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h5>Caissiers actifs</h5>
                <h3 class="text-primary">
                    <?php 
                    $active_cashiers = array_unique(array_column(array_filter($sessions, function($s) {
                        return $s['status'] === 'open';
                    }), 'cashier_id'));
                    echo count($active_cashiers);
                    ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-3x text-info mb-3"></i>
                <h5>Dernière session</h5>
                <h6 class="text-info">
                    <?php 
                    if (!empty($sessions)) {
                        $last_session = new DateTime($sessions[0]['opening_time']);
                        echo $last_session->format('d/m H:i');
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </h6>
            </div>
        </div>
    </div>
</div>

</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>