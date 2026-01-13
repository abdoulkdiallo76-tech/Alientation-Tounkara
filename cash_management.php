<?php
require_once 'config/database.php';
requireLogin();

$page_title = 'Gestion de Caisse Journalière';

// Vérifier le statut de la session du jour
$day_status = checkDaySessionStatus();
$current_session = null;

if ($day_status['status'] === 'open') {
    $current_session = $day_status['session'];
}

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['open_cash'])) {
        $opening_amount = floatval($_POST['opening_amount']);
        $password = $_POST['password'];
        
        $result = openDayCashSession($opening_amount, $password);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: cash_management.php');
            exit();
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    if (isset($_POST['close_cash'])) {
        $closing_amount = floatval($_POST['closing_amount']);
        $notes = $_POST['notes'] ?? '';
        
        $result = closeDayCashSession($closing_amount, $notes);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: cash_management.php');
            exit();
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
}

// Maintenant inclure le header après les traitements
require_once 'includes/header.php';

// Afficher les messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success fade-in-up">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger fade-in-up">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-cash-register me-3"></i>Gestion de Caisse Journalière
        </h1>
    </div>
</div>

<!-- Statut de la journée -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-day me-2"></i>État de la Journée
                </h5>
            </div>
            <div class="card-body">
                <?php if ($day_status['status'] === 'no_session'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Aucune session de caisse aujourd'hui</strong><br>
                        Vous devez ouvrir la caisse pour commencer vos ventes.
                    </div>
                <?php elseif ($day_status['status'] === 'open'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-lock-open me-2"></i>
                        <strong>Caisse ouverte</strong><br>
                        Session ouverte depuis le <?php echo date('d/m/Y H:i', strtotime($current_session['opening_time'])); ?>
                    </div>
                <?php elseif ($day_status['status'] === 'closed'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-lock me-2"></i>
                        <strong>Caisse fermée</strong><br>
                        Session du jour terminée à <?php echo date('d/m/Y H:i', strtotime($day_status['session']['closing_time'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if ($day_status['status'] === 'no_session' || $day_status['status'] === 'closed'): ?>
    <!-- Formulaire d'ouverture -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-lock-open me-2"></i>Ouverture de Caisse
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="open_cash" value="1">
                    
                    <div class="mb-3">
                        <label for="opening_amount" class="form-label">Fonds Initial</label>
                        <div class="input-group">
                            <span class="input-group-text">FCFA</span>
                            <input type="number" class="form-control" id="opening_amount" name="opening_amount" 
                                   step="0.01" min="0" required>
                        </div>
                        <div class="form-text">Montant initial dans la caisse pour la journée</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Confirmez votre identité pour ouvrir la caisse</div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-lock-open me-2"></i>Ouvrir la Caisse du Jour
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($day_status['status'] === 'open'): ?>
    <!-- Formulaire de fermeture -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-lock me-2"></i>Fermeture de Caisse
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="close_cash" value="1">
                    
                    <div class="mb-3">
                        <label for="closing_amount" class="form-label">Fonds Final</label>
                        <div class="input-group">
                            <span class="input-group-text">FCFA</span>
                            <input type="number" class="form-control" id="closing_amount" name="closing_amount" 
                                   step="0.01" min="0" required>
                        </div>
                        <div class="form-text">Montant total dans la caisse en fin de journée</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        <div class="form-text">Observations sur la journée (optionnel)</div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-lock me-2"></i>Fermer la Caisse du Jour
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Informations de la session actuelle -->
    <?php if ($day_status['status'] === 'open' && $current_session): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informations de Session
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">Ouverture</small>
                        <strong><?php echo date('d/m/Y H:i', strtotime($current_session['opening_time'])); ?></strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Fonds Initial</small>
                        <strong><?php echo formatMoney($current_session['opening_amount']); ?></strong>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <small class="text-muted">Durée</small>
                        <strong><?php echo formatDuration($current_session['opening_time']); ?></strong>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Toutes les ventes sont automatiquement rattachées à cette session</strong>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="pos.php" class="btn btn-primary">
                        <i class="fas fa-cash-register me-2"></i>Aller aux Ventes
                    </a>
                    <a href="sales.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>Voir les Ventes du Jour
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Validation des formulaires
(function () {
    'use strict'
    
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
