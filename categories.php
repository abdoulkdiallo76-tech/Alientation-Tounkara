<?php
require_once 'config/database.php';
$page_title = 'Gestion des catégories';

// Vérifier les droits d'accès AVANT d'inclure le header
if (isCashier()) {
    header('Location: pos.php');
    exit();
}

require_once 'includes/header.php';

$action = $_GET['action'] ?? 'list';
$category_id = $_GET['id'] ?? 0;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category']) || isset($_POST['edit_category'])) {
        $name = cleanInput($_POST['name']);
        $description = cleanInput($_POST['description']);
        
        try {
            if (isset($_POST['add_category'])) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $success = 'Catégorie ajoutée avec succès';
            } else {
                $category_id = intval($_POST['category_id']);
                $stmt = $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
                $stmt->execute([$name, $description, $category_id]);
                $success = 'Catégorie modifiée avec succès';
            }
            
            header('Location: categories.php');
            exit();
        } catch(PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $category_id = intval($_POST['category_id']);
        try {
            // Vérifier si la catégorie a des produits
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $products_count = $stmt->fetch();
            
            if ($products_count['count'] > 0) {
                $error = 'Cette catégorie ne peut pas être supprimée car elle contient des produits';
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $success = 'Catégorie supprimée avec succès';
                header('Location: categories.php');
                exit();
            }
        } catch(PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Récupération des données
if ($action === 'edit' && $category_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch();
        
        if (!$category) {
            header('Location: categories.php');
            exit();
        }
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

if ($action === 'list') {
    try {
        $search = cleanInput($_GET['search'] ?? '');
        
        $query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1) as products_count 
                 FROM categories c WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (c.name LIKE ? OR c.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $query .= " ORDER BY c.name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
        
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
                <i class="fas fa-tags me-2"></i>Gestion des catégories
            </h1>
            <a href="categories.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Ajouter une catégorie
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
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Nom ou description">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Rechercher
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="categories.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-redo me-2"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des catégories -->
<div class="card">
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <p class="text-muted text-center">Aucune catégorie trouvée</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Produits</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($category['description'] ?: 'N/A'); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $category['products_count']; ?></span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-outline-info" title="Voir les produits">
                                        <i class="fas fa-cube"></i>
                                    </a>
                                    <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDeleteCategory(<?php echo $category['id']; ?>)" class="btn btn-outline-danger" title="Supprimer">
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
            <i class="fas fa-tags me-2"></i>
            <?php echo $action === 'add' ? 'Ajouter une catégorie' : 'Modifier une catégorie'; ?>
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
                    <input type="hidden" name="category_id" value="<?php echo $category['id'] ?? 0; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom de la catégorie *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="categories.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <div>
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_category' : 'edit_category'; ?>" class="btn btn-primary">
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
    <input type="hidden" name="delete_category">
    <input type="hidden" name="category_id" id="deleteCategoryId">
</form>

<?php
$page_script = "
function confirmDeleteCategory(categoryId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        document.getElementById('deleteCategoryId').value = categoryId;
        document.getElementById('deleteForm').submit();
    }
}
";
?>

<?php require_once 'includes/footer.php'; ?>
