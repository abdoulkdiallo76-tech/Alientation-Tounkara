<?php
require_once 'config/database.php';
$page_title = 'Gestion des fournisseurs';

// Vérifier les droits d'accès AVANT d'inclure le header
if (isCashier()) {
    header('Location: pos.php');
    exit();
}

require_once 'includes/header.php';

$action = $_GET['action'] ?? 'list';
$supplier_id = $_GET['id'] ?? 0;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_supplier']) || isset($_POST['edit_supplier'])) {
        require_once 'config/database.php';
        requireLogin();
        
        $name = cleanInput($_POST['name']);
        $contact_person = cleanInput($_POST['contact_person']);
        $phone = cleanInput($_POST['phone']);
        $email = cleanInput($_POST['email']);
        $address = cleanInput($_POST['address']);
        
        try {
            if (isset($_POST['add_supplier'])) {
                $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $contact_person, $phone, $email, $address]);
                $success = 'Fournisseur ajouté avec succès';
            } else {
                $supplier_id = intval($_POST['supplier_id']);
                $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?");
                $stmt->execute([$name, $contact_person, $phone, $email, $address, $supplier_id]);
                $success = 'Fournisseur modifié avec succès';
            }
            
            header('Location: suppliers.php');
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
            header('Location: suppliers.php');
            exit();
        }
    }
    
    if (isset($_POST['delete_supplier'])) {
        require_once 'config/database.php';
        requireLogin();
        
        $supplier_id = intval($_POST['supplier_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$supplier_id]);
            $success = 'Fournisseur supprimé avec succès';
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
        header('Location: suppliers.php');
        exit();
    }
}

require_once 'includes/header.php';

// Check for session errors from POST processing
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Récupération des données
if ($action === 'edit' && $supplier_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        $supplier = $stmt->fetch();
        
        if (!$supplier) {
            header('Location: suppliers.php');
            exit();
        }
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

if ($action === 'list') {
    try {
        $search = cleanInput($_GET['search'] ?? '');
        
        $query = "SELECT s.*, 
                 (SELECT COUNT(*) FROM supplier_orders WHERE supplier_id = s.id) as orders_count,
                 (SELECT SUM(total_amount) FROM supplier_orders WHERE supplier_id = s.id AND status = 'delivered') as total_purchased
                 FROM suppliers s WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (s.name LIKE ? OR s.contact_person LIKE ? OR s.phone LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $query .= " ORDER BY s.name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $suppliers = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}
?>

<?php if ($action === 'list'): ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">
                <i class="fas fa-users me-2"></i>Gestion des fournisseurs
            </h1>
            <a href="suppliers.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Ajouter un fournisseur
            </a>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label for="search" class="form-label">Recherche</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Nom, contact ou téléphone">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Rechercher
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="suppliers.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-redo me-2"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des fournisseurs -->
<div class="card">
    <div class="card-body">
        <?php if (empty($suppliers)): ?>
            <p class="text-muted text-center">Aucun fournisseur trouvé</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Contact</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Commandes</th>
                            <th>Total achats</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                <?php if ($supplier['address']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($supplier['address']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($supplier['contact_person'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($supplier['phone'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($supplier['email'] ?: 'N/A'); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $supplier['orders_count']; ?></span>
                            </td>
                            <td class="fw-bold"><?php echo formatMoney($supplier['total_purchased'] ?: 0); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="supplier_orders.php?supplier_id=<?php echo $supplier['id']; ?>" class="btn btn-outline-info" title="Voir les commandes">
                                        <i class="fas fa-clipboard-list"></i>
                                    </a>
                                    <a href="suppliers.php?action=edit&id=<?php echo $supplier['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDeleteSupplier(<?php echo $supplier['id']; ?>)" class="btn btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-users me-2"></i>
            <?php echo $action === 'add' ? 'Ajouter un fournisseur' : 'Modifier un fournisseur'; ?>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="supplier_id" value="<?php echo $supplier['id'] ?? 0; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom du fournisseur *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($supplier['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Personne de contact</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($supplier['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Adresse</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="suppliers.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <div>
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_supplier' : 'edit_supplier'; ?>" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $action === 'add' ? 'Ajouter' : 'Modifier'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulaire de suppression caché -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_supplier">
    <input type="hidden" name="supplier_id" id="deleteSupplierId">
</form>

<?php
$page_script = "
function confirmDeleteSupplier(supplierId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?')) {
        document.getElementById('deleteSupplierId').value = supplierId;
        document.getElementById('deleteForm').submit();
    }
}
";
?>

<?php require_once 'includes/footer.php'; ?>
