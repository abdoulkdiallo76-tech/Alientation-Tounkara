<?php
require_once 'config/database.php';
requireLogin();

// Seuls les admins peuvent gérer les localités
if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

$page_title = 'Gestion des Localités';
require_once 'includes/header.php';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_location'])) {
        $name = cleanInput($_POST['name']);
        $code = cleanInput($_POST['code']);
        $address = cleanInput($_POST['address']);
        $phone = cleanInput($_POST['phone']);
        $email = cleanInput($_POST['email']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO locations (name, code, address, phone, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $address, $phone, $email]);
            $success = 'Localité ajoutée avec succès';
        } catch(PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['edit_location'])) {
        $id = (int)$_POST['id'];
        $name = cleanInput($_POST['name']);
        $code = cleanInput($_POST['code']);
        $address = cleanInput($_POST['address']);
        $phone = cleanInput($_POST['phone']);
        $email = cleanInput($_POST['email']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE locations SET name = ?, code = ?, address = ?, phone = ?, email = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $code, $address, $phone, $email, $is_active, $id]);
            $success = 'Localité modifiée avec succès';
        } catch(PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_location'])) {
        $id = (int)$_POST['id'];
        
        try {
            // Vérifier si des utilisateurs sont assignés à cette localité
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE location_id = ?");
            $stmt->execute([$id]);
            $users_count = $stmt->fetch()['count'];
            
            if ($users_count > 0) {
                $error = 'Impossible de supprimer cette localité car des utilisateurs y sont assignés';
            } else {
                $stmt = $pdo->prepare("UPDATE locations SET is_active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Localité désactivée avec succès';
            }
        } catch(PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Récupérer toutes les localités
try {
    $stmt = $pdo->prepare("SELECT l.*, 
                          (SELECT COUNT(*) FROM users WHERE location_id = l.id AND is_active = 1) as users_count,
                          (SELECT COUNT(*) FROM sales WHERE location_id = l.id) as sales_count
                          FROM locations l 
                          ORDER BY l.name");
    $stmt->execute();
    $locations = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
    $locations = [];
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-map-marked-alt me-3"></i>Gestion des Localités
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

<!-- Bouton d'ajout -->
<div class="row mb-4">
    <div class="col-12">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
            <i class="fas fa-plus me-2"></i>Ajouter une localité
        </button>
    </div>
</div>

<!-- Liste des localités -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Liste des Localités
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($locations)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Aucune localité trouvée</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                    <th><i class="fas fa-tag me-1"></i>Nom</th>
                                    <th><i class="fas fa-code me-1"></i>Code</th>
                                    <th><i class="fas fa-map-marker-alt me-1"></i>Adresse</th>
                                    <th><i class="fas fa-phone me-1"></i>Téléphone</th>
                                    <th><i class="fas fa-envelope me-1"></i>Email</th>
                                    <th><i class="fas fa-users me-1"></i>Utilisateurs</th>
                                    <th><i class="fas fa-shopping-cart me-1"></i>Ventes</th>
                                    <th><i class="fas fa-toggle-on me-1"></i>Statut</th>
                                    <th><i class="fas fa-cog me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($locations as $location): ?>
                                <tr>
                                    <td><span class="badge bg-primary">#<?php echo $location['id']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($location['name']); ?></strong></td>
                                    <td><code class="text-muted"><?php echo htmlspecialchars($location['code']); ?></code></td>
                                    <td><?php echo $location['address'] ? htmlspecialchars($location['address']) : '<span class="text-muted">Non spécifiée</span>'; ?></td>
                                    <td><?php echo $location['phone'] ? htmlspecialchars($location['phone']) : '<span class="text-muted">Non spécifié</span>'; ?></td>
                                    <td><?php echo $location['email'] ? htmlspecialchars($location['email']) : '<span class="text-muted">Non spécifié</span>'; ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $location['users_count']; ?> utilisateur(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo $location['sales_count']; ?> vente(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $location['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $location['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editLocation(<?php echo $location['id']; ?>)" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="viewUsers(<?php echo $location['id']; ?>)" title="Voir les utilisateurs">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <?php if ($location['users_count'] == 0): ?>
                                                <button class="btn btn-outline-danger" onclick="deleteLocation(<?php echo $location['id']; ?>)" title="Supprimer">
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

<!-- Modal Ajout -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une localité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="code" class="form-label">Code *</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Adresse</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="add_location" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modification -->
<div class="modal fade" id="editLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier une localité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_code" class="form-label">Code *</label>
                        <input type="text" class="form-control" id="edit_code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Adresse</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="edit_phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Localité active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="edit_location" class="btn btn-primary">Modifier</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editLocation(id) {
    // Récupérer les données de la localité
    const locations = <?php echo json_encode($locations); ?>;
    const location = locations.find(l => l.id == id);
    
    if (location) {
        document.getElementById('edit_id').value = location.id;
        document.getElementById('edit_name').value = location.name;
        document.getElementById('edit_code').value = location.code;
        document.getElementById('edit_address').value = location.address || '';
        document.getElementById('edit_phone').value = location.phone || '';
        document.getElementById('edit_email').value = location.email || '';
        document.getElementById('edit_is_active').checked = location.is_active == 1;
        
        new bootstrap.Modal(document.getElementById('editLocationModal')).show();
    }
}

function deleteLocation(id) {
    if (confirm('Êtes-vous sûr de vouloir désactiver cette localité ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="delete_location" value="1"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function viewUsers(locationId) {
    window.location.href = 'users.php?location_id=' + locationId;
}
</script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
