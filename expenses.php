<?php
require_once 'config/database.php';
requireLogin();

$page_title = 'Gestion des dépenses';

// Vérifier les droits d'accès
if (isCashier()) {
    header('Location: pos.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$expense_id = $_GET['id'] ?? 0;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_expense']) || isset($_POST['edit_expense'])) {
        $description = cleanInput($_POST['description']);
        $amount = floatval($_POST['amount']);
        $category = cleanInput($_POST['category']);
        $expense_date = $_POST['expense_date'];
        $notes = cleanInput($_POST['notes']);
        
        try {
            if (isset($_POST['add_expense'])) {
                $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, category, expense_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$description, $amount, $category, $expense_date, $notes, $_SESSION['user_id']]);
                $_SESSION['success'] = 'Dépense ajoutée avec succès';
            } else {
                $expense_id = intval($_POST['expense_id']);
                $stmt = $pdo->prepare("UPDATE expenses SET description=?, amount=?, category=?, expense_date=?, notes=? WHERE id=?");
                $stmt->execute([$description, $amount, $category, $expense_date, $notes, $expense_id]);
                $_SESSION['success'] = 'Dépense modifiée avec succès';
            }
            
            header('Location: expenses.php');
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
            header('Location: expenses.php');
            exit();
        }
    }
    
    if (isset($_POST['delete_expense'])) {
        $expense_id = intval($_POST['expense_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id=?");
            $stmt->execute([$expense_id]);
            $_SESSION['success'] = 'Dépense supprimée avec succès';
            header('Location: expenses.php');
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
            header('Location: expenses.php');
            exit();
        }
    }
}

// Maintenant inclure le header après tous les traitements
require_once 'includes/header.php';

// Check for session errors from POST processing
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Récupération des données
if ($action === 'edit' && $expense_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
        $stmt->execute([$expense_id]);
        $expense = $stmt->fetch();
        
        if (!$expense) {
            header('Location: expenses.php');
            exit();
        }
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

if ($action === 'list') {
    try {
        $search = cleanInput($_GET['search'] ?? '');
        $category_filter = cleanInput($_GET['category'] ?? '');
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        
        $query = "SELECT e.*, u.full_name as created_by_name FROM expenses e LEFT JOIN users u ON e.created_by = u.id WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (e.description LIKE ? OR e.notes LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($category_filter)) {
            $query .= " AND e.category = ?";
            $params[] = $category_filter;
        }
        
        $query .= " AND e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date DESC";
        $params[] = $date_from;
        $params[] = $date_to;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();
        
        // Statistiques
        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ?");
        $stmt->execute([$date_from, $date_to]);
        $total_expenses = $stmt->fetch();
        
        // Dépenses par catégorie
        $stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
        $stmt->execute([$date_from, $date_to]);
        $expenses_by_category = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

// Catégories prédéfinies
$categories = [
    'Loyer' => 'Loyer',
    'Salaires' => 'Salaires',
    'Électricité' => 'Électricité',
    'Eau' => 'Eau',
    'Téléphone' => 'Téléphone',
    'Internet' => 'Internet',
    'Transport' => 'Transport',
    'Fournitures' => 'Fournitures',
    'Entretien' => 'Entretien',
    'Marketing' => 'Marketing',
    'Assurance' => 'Assurance',
    'Impôts' => 'Impôts',
    'Autres' => 'Autres'
];
?>

<?php if ($action === 'list'): ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">
                <i class="fas fa-money-bill-wave me-2"></i>Gestion des dépenses
            </h1>
            <a href="expenses.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Ajouter une dépense
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

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total des dépenses</h6>
                <h3 class="mb-0"><?php echo formatMoney($total_expenses['total'] ?? 0); ?></h3>
                <small>Période sélectionnée</small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Dépenses par catégorie</h6>
            </div>
            <div class="card-body">
                <?php if (empty($expenses_by_category)): ?>
                    <p class="text-muted text-center">Aucune dépense</p>
                <?php else: ?>
                    <?php foreach ($expenses_by_category as $cat): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?php echo htmlspecialchars($cat['category']); ?></span>
                            <span class="badge bg-primary"><?php echo formatMoney($cat['total']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Recherche</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Description ou notes">
            </div>
            <div class="col-md-2">
                <label for="category" class="form-label">Catégorie</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Toutes</option>
                    <?php foreach ($categories as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $key) ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">Du</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? date('Y-m-01')); ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">Au</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? date('Y-m-d')); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Liste des dépenses -->
<div class="card">
    <div class="card-body">
        <?php if (empty($expenses)): ?>
            <p class="text-muted text-center">Aucune dépense trouvée</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Catégorie</th>
                            <th>Montant</th>
                            <th>Ajouté par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($expense['description']); ?></strong>
                                <?php if ($expense['notes']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($expense['notes']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($expense['category']); ?></span>
                            </td>
                            <td class="fw-bold text-danger"><?php echo formatMoney($expense['amount']); ?></td>
                            <td><?php echo htmlspecialchars($expense['created_by_name'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="expenses.php?action=edit&id=<?php echo $expense['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDeleteExpense(<?php echo $expense['id']; ?>)" class="btn btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary fw-bold">
                            <td colspan="3">Total</td>
                            <td><?php echo formatMoney(array_sum(array_column($expenses, 'amount'))); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-money-bill-wave me-2"></i>
            <?php echo $action === 'add' ? 'Ajouter une dépense' : 'Modifier une dépense'; ?>
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
                    <input type="hidden" name="expense_id" value="<?php echo $expense['id'] ?? 0; ?>">
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($expense['description'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Montant *</label>
                            <input type="number" class="form-control" id="amount" name="amount" value="<?php echo $expense['amount'] ?? 0; ?>" min="0" step="0.01" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Catégorie *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo (isset($expense) && $expense['category'] == $key) ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="expense_date" class="form-label">Date *</label>
                        <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo $expense['expense_date'] ?? date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($expense['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="expenses.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <div>
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_expense' : 'edit_expense'; ?>" class="btn btn-primary">
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
    <input type="hidden" name="delete_expense">
    <input type="hidden" name="expense_id" id="deleteExpenseId">
</form>

<?php
$page_script = "
function confirmDeleteExpense(expenseId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette dépense ?')) {
        document.getElementById('deleteExpenseId').value = expenseId;
        document.getElementById('deleteForm').submit();
    }
}
";
?>

<?php require_once 'includes/footer.php'; ?>
