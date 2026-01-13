<?php
$page_title = 'Gestion des utilisateurs';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    if (isset($_POST['add_user']) || isset($_POST['edit_user'])) {
        $username = cleanInput($_POST['username']);
        $full_name = cleanInput($_POST['full_name']);
        $email = cleanInput($_POST['email']);
        $role = cleanInput($_POST['role']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $user_id = intval($_POST['user_id'] ?? 0);
        
        try {
            if (isset($_POST['add_user'])) {
                // Vérifier si le nom d'utilisateur existe déjà
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Ce nom d\'utilisateur existe déjà';
                    header('Location: users.php');
                    exit();
                }
                
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $password, $full_name, $email, $role, $is_active]);
                $_SESSION['success'] = 'Utilisateur ajouté avec succès';
            } else {
                // Modification
                $update_fields = "username = ?, full_name = ?, email = ?, role = ?, is_active = ?";
                $params = [$username, $full_name, $email, $role, $is_active];
                
                // Si un nouveau mot de passe est fourni
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $update_fields .= ", password = ?";
                    $params[] = $password;
                }
                
                $params[] = $user_id;
                
                $stmt = $pdo->prepare("UPDATE users SET $update_fields WHERE id=?");
                $stmt->execute($params);
                $_SESSION['success'] = 'Utilisateur modifié avec succès';
            }
            
            header('Location: users.php');
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        try {
            // Empêcher la suppression de son propre compte
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['error'] = 'Vous ne pouvez pas supprimer votre propre compte';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = 'Utilisateur désactivé avec succès';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['toggle_user'])) {
        $user_id = intval($_POST['user_id']);
        try {
            // Empêcher la désactivation de son propre compte
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['error'] = 'Vous ne pouvez pas désactiver votre propre compte';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = 'Statut de l\'utilisateur modifié avec succès';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
    }
    
    // Rediriger après traitement POST
    header('Location: users.php');
    exit();
}

require_once 'includes/header.php';

// Vérifier si l'utilisateur a les droits d'administration
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? 0;

// Récupérer les utilisateurs
try {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

// Récupérer un utilisateur spécifique pour l'édition
if ($action === 'edit' && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-users me-2"></i>Gestion des utilisateurs
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Formulaire d'ajout/modification -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        <?php echo $action === 'add' ? 'Ajouter un utilisateur' : 'Modifier un utilisateur'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Rôle *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Sélectionner un rôle</option>
                                    <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                    <option value="manager" <?php echo ($user['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Gestionnaire</option>
                                    <option value="cashier" <?php echo ($user['role'] ?? '') === 'cashier' ? 'selected' : ''; ?>>Caissier</option>
                                    <option value="employee" <?php echo ($user['role'] ?? '') === 'employee' ? 'selected' : ''; ?>>Employé</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    <?php echo $action === 'add' ? 'Mot de passe *' : 'Mot de passe (laisser vide pour ne pas modifier)'; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo $action === 'add' ? 'required' : ''; ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo ($user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Compte actif
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_user' : 'edit_user'; ?>" 
                                    class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $action === 'add' ? 'Ajouter' : 'Modifier'; ?>
                            </button>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
<?php else: ?>
    <!-- Liste des utilisateurs -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Liste des utilisateurs
                    </h5>
                    <a href="users.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Ajouter un utilisateur
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <p class="text-muted text-center">Aucun utilisateur trouvé</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom d'utilisateur</th>
                                        <th>Nom complet</th>
                                        <th>Email</th>
                                        <th>Rôle</th>
                                        <th>Statut</th>
                                        <th>Date de création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>#<?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $role_labels = [
                                                'admin' => 'Administrateur',
                                                'manager' => 'Gestionnaire',
                                                'cashier' => 'Caissier',
                                                'employee' => 'Employé'
                                            ];
                                            echo $role_labels[$user['role']] ?? $user['role'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="toggle_user" 
                                                                class="btn btn-outline-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>" 
                                                                title="<?php echo $user['is_active'] ? 'Désactiver' : 'Activer'; ?>">
                                                            <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                        </button>
                                                    </form>
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
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
