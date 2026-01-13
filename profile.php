<?php
require_once 'config/database.php';
requireLogin();

$page_title = 'Mon Profil';
require_once 'includes/header.php';

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$user = null;

try {
    $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = cleanInput($_POST['full_name']);
    $email = cleanInput($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($full_name) || empty($email)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } else {
        try {
            // Modification des informations de base
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
            
            // Modification du mot de passe si fourni
            if (!empty($current_password) && !empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = 'Les nouveaux mots de passe ne correspondent pas';
                } else {
                    // Vérifier le mot de passe actuel
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();
                    
                    if ($user_data && password_verify($current_password, $user_data['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $user_id]);
                        $success = 'Mot de passe mis à jour avec succès';
                    } else {
                        $error = 'Mot de passe actuel incorrect';
                    }
                }
            }
            
            // Rafraîchir les données utilisateur
            $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, created_at FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!isset($error)) {
                $_SESSION['full_name'] = $full_name;
                $success = 'Profil mis à jour avec succès';
            }
            
        } catch(PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-user me-3"></i>Mon Profil
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger fade-in-up">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success fade-in-up">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($user): ?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Informations du profil
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            <small class="text-muted">Le nom d'utilisateur ne peut pas être modifié</small>
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Rôle</label>
                            <input type="text" class="form-control" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly>
                            <small class="text-muted">Votre rôle dans le système</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Nom complet *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="created_at" class="form-label">Date de création</label>
                            <input type="text" class="form-control" id="created_at" value="<?php echo formatDate($user['created_at']); ?>" readonly>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">
                        <i class="fas fa-lock me-2"></i>Changement de mot de passe
                    </h6>
                    <p class="text-muted mb-3">Laissez vide si vous ne voulez pas changer votre mot de passe</p>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="col-md-4">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informations
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="fas fa-user me-2"></i>Profil</h6>
                    <p class="text-muted">Gérez vos informations personnelles et votre mot de passe.</p>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fas fa-shield-alt me-2"></i>Sécurité</h6>
                    <p class="text-muted">Changez régulièrement votre mot de passe pour sécuriser votre compte.</p>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fas fa-clock me-2"></i>Activité</h6>
                    <p class="text-muted">Votre compte a été créé le <?php echo formatDate($user['created_at']); ?>.</p>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="mb-3">
                    <h6><i class="fas fa-users-cog me-2"></i>Administration</h6>
                    <p class="text-muted">En tant qu'administrateur, vous avez accès à toutes les fonctionnalités.</p>
                </div>
                <?php elseif (isCashier()): ?>
                <div class="mb-3">
                    <h6><i class="fas fa-cash-register me-2"></i>Caisse</h6>
                    <p class="text-muted">En tant que caissier, vous pouvez gérer les ventes et consulter l'historique.</p>
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <h6><i class="fas fa-tasks me-2"></i>Manager</h6>
                    <p class="text-muted">En tant que manager, vous avez accès à la gestion et aux rapports.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Utilisateur non trouvé.
        </div>
    </div>
</div>
<?php endif; ?>

</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
