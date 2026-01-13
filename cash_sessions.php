<?php
require_once 'config/database.php';
requireLogin();

$page_title = 'Historique des Sessions de Caisse';
require_once 'includes/header.php';

// Récupérer l'historique des sessions
if (isAdmin()) {
    $sessions = getAllCashSessions(100);
} else {
    $sessions = getCashSessionsHistory(50);
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-history me-3"></i>Historique des Sessions de Caisse
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (empty($sessions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Aucune session de caisse trouvée</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                    <th><i class="fas fa-user me-1"></i>Caissier</th>
                                    <?php if (isAdmin()): ?>
                                    <th><i class="fas fa-map-marker-alt me-1"></i>Localité</th>
                                    <?php endif; ?>
                                    <th><i class="fas fa-clock me-1"></i>Ouverture</th>
                                    <th><i class="fas fa-clock me-1"></i>Fermeture</th>
                                    <th><i class="fas fa-money-bill-wave me-1"></i>Fonds Initial</th>
                                    <th><i class="fas fa-shopping-cart me-1"></i>Ventes</th>
                                    <th><i class="fas fa-receipt me-1"></i>Dépenses</th>
                                    <th><i class="fas fa-calculator me-1"></i>Fonds Fin</th>
                                    <th><i class="fas fa-balance-scale me-1"></i>Écart</th>
                                    <th><i class="fas fa-info-circle me-1"></i>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td><span class="badge bg-primary">#<?php echo $session['id']; ?></span></td>
                                    <td><?php echo htmlspecialchars($session['cashier_name']); ?></td>
                                    <?php if (isAdmin()): ?>
                                    <td>
                                        <?php if ($session['location_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($session['location_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Non assignée</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td><?php echo formatDate($session['opening_time'], true); ?></td>
                                    <td>
                                        <?php if ($session['closing_time']): ?>
                                            <?php echo formatDate($session['closing_time'], true); ?>
                                        <?php else: ?>
                                            <span class="text-muted">En cours</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-success fw-bold"><?php echo formatMoney($session['opening_amount']); ?></td>
                                    <td class="text-info fw-bold"><?php echo formatMoney($session['total_sales']); ?></td>
                                    <td class="text-warning fw-bold"><?php echo formatMoney($session['total_expenses']); ?></td>
                                    <td class="fw-bold">
                                        <?php if ($session['closing_amount']): ?>
                                            <?php echo formatMoney($session['closing_amount']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold">
                                        <?php 
                                        if ($session['difference'] !== null) {
                                            if ($session['difference'] == 0) {
                                                echo '<span class="text-success">' . formatMoney($session['difference']) . '</span>';
                                            } elseif ($session['difference'] > 0) {
                                                echo '<span class="text-success">+' . formatMoney($session['difference']) . '</span>';
                                            } else {
                                                echo '<span class="text-danger">' . formatMoney($session['difference']) . '</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($session['status'] == 'open'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-lock-open me-1"></i>Ouverte
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-lock me-1"></i>Fermée
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Statistiques résumées -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Total Sessions</h6>
                                    <h3><?php echo count($sessions); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Total Ventes</h6>
                                    <h3><?php echo formatMoney(array_sum(array_column($sessions, 'total_sales'))); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h6>Total Dépenses</h6>
                                    <h3><?php echo formatMoney(array_sum(array_column($sessions, 'total_expenses'))); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6>Écart Total</h6>
                                    <h3>
                                        <?php 
                                        $total_diff = array_sum(array_filter(array_column($sessions, 'difference'), function($diff) {
                                            return $diff !== null;
                                        }));
                                        if ($total_diff >= 0) {
                                            echo '+' . formatMoney($total_diff);
                                        } else {
                                            echo formatMoney($total_diff);
                                        }
                                        ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
