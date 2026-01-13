<?php
require_once 'config/database.php';
$page_title = 'Paramètres';

// Vérifier les droits d'accès AVANT d'inclure le header
if (isCashier()) {
    header('Location: pos.php');
    exit();
}

require_once 'includes/header.php';

// Vérifier si l'utilisateur a les droits d'administration
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        try {
            // Mettre à jour les paramètres de l'entreprise
            $company_name = cleanInput($_POST['company_name']);
            $company_address = cleanInput($_POST['company_address']);
            $company_phone = cleanInput($_POST['company_phone']);
            $company_email = cleanInput($_POST['company_email']);
            $tax_rate = floatval($_POST['tax_rate']);
            $currency = cleanInput($_POST['currency']);
            $low_stock_threshold = intval($_POST['low_stock_threshold']);
            
            // Mettre à jour ou insérer les paramètres
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?), (?, ?), (?, ?), (?, ?), (?, ?), (?, ?), (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            $stmt->execute([
                'company_name', $company_name,
                'company_address', $company_address,
                'company_phone', $company_phone,
                'company_email', $company_email,
                'tax_rate', $tax_rate,
                'currency', $currency,
                'low_stock_threshold', $low_stock_threshold
            ]);
            
            $success = 'Paramètres mis à jour avec succès';
            
        } catch(PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['backup_database'])) {
        try {
            // Créer une sauvegarde de la base de données
            $backup_file = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
            $command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backup_file;
            
            // Créer le répertoire de sauvegarde s'il n'existe pas
            if (!is_dir('backups')) {
                mkdir('backups', 0755, true);
            }
            
            // Exécuter la commande de sauvegarde
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                $success = 'Sauvegarde créée avec succès: ' . $backup_file;
            } else {
                $error = 'Erreur lors de la création de la sauvegarde';
            }
            
        } catch(Exception $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['clear_cache'])) {
        try {
            // Vider le cache
            $cache_files = glob('cache/*');
            foreach ($cache_files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $success = 'Cache vidé avec succès';
            
        } catch(Exception $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Récupérer les paramètres actuels
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch(PDOException $e) {
    // Si la table settings n'existe pas, créer les valeurs par défaut
    $settings = [
        'company_name' => 'Alimentation Tounkara',
        'company_address' => '',
        'company_phone' => '',
        'company_email' => '',
        'tax_rate' => 0,
        'currency' => 'XOF',
        'low_stock_threshold' => 5
    ];
}

// Récupérer les informations système
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => 'MySQL',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Récupérer les statistiques de la base de données
try {
    $stmt = $pdo->query("SHOW TABLE STATUS");
    $tables = $stmt->fetchAll();
    
    $total_size = 0;
    foreach ($tables as $table) {
        $total_size += $table['Data_length'] + $table['Index_length'];
    }
    
    $db_stats = [
        'total_tables' => count($tables),
        'total_size' => $total_size,
        'total_size_formatted' => formatBytes($total_size)
    ];
} catch(PDOException $e) {
    $db_stats = [
        'total_tables' => 0,
        'total_size' => 0,
        'total_size_formatted' => '0 B'
    ];
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-cog me-2"></i>Paramètres
        </h1>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Onglets de paramètres -->
<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
            <i class="fas fa-building me-2"></i>Général
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
            <i class="fas fa-server me-2"></i>Système
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
            <i class="fas fa-tools me-2"></i>Maintenance
        </button>
    </li>
</ul>

<div class="tab-content" id="settingsTabsContent">
    <!-- Onglet Général -->
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-building me-2"></i>Informations de l'entreprise
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="company_name" class="form-label">Nom de l'entreprise</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="company_phone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="company_phone" name="company_phone" 
                                           value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="company_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email" 
                                           value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="currency" class="form-label">Devise</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="XOF" <?php echo ($settings['currency'] ?? '') === 'XOF' ? 'selected' : ''; ?>>XOF (FCFA)</option>
                                        <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                                        <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD (Dollar)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_address" class="form-label">Adresse</label>
                                <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tax_rate" class="form-label">Taux de taxe (%)</label>
                                    <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                           value="<?php echo htmlspecialchars($settings['tax_rate'] ?? 0); ?>" step="0.01" min="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="low_stock_threshold" class="form-label">Seuil d'alerte de stock par défaut</label>
                                    <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" 
                                           value="<?php echo htmlspecialchars($settings['low_stock_threshold'] ?? 5); ?>" min="1">
                                </div>
                            </div>
                            
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informations
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Configurez les informations générales de votre entreprise qui apparaîtront sur les documents et rapports.
                        </p>
                        <hr>
                        <h6>Paramètres configurés:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Nom de l'entreprise</li>
                            <li><i class="fas fa-check text-success me-2"></i>Coordonnées</li>
                            <li><i class="fas fa-check text-success me-2"></i>Devise</li>
                            <li><i class="fas fa-check text-success me-2"></i>Taux de taxe</li>
                            <li><i class="fas fa-check text-success me-2"></i>Alertes de stock</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Onglet Système -->
    <div class="tab-pane fade" id="system" role="tabpanel">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-server me-2"></i>Informations système
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Version PHP</th>
                                <td><?php echo $system_info['php_version']; ?></td>
                            </tr>
                            <tr>
                                <th>Serveur web</th>
                                <td><?php echo $system_info['server_software']; ?></td>
                            </tr>
                            <tr>
                                <th>Base de données</th>
                                <td><?php echo $system_info['database_version']; ?></td>
                            </tr>
                            <tr>
                                <th>Limite d'upload</th>
                                <td><?php echo $system_info['upload_max_filesize']; ?></td>
                            </tr>
                            <tr>
                                <th>Limite POST</th>
                                <td><?php echo $system_info['post_max_size']; ?></td>
                            </tr>
                            <tr>
                                <th>Limite mémoire</th>
                                <td><?php echo $system_info['memory_limit']; ?></td>
                            </tr>
                            <tr>
                                <th>Temps d'exécution max</th>
                                <td><?php echo $system_info['max_execution_time']; ?>s</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-database me-2"></i>Statistiques de la base de données
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Nombre de tables</th>
                                <td><?php echo $db_stats['total_tables']; ?></td>
                            </tr>
                            <tr>
                                <th>Taille totale</th>
                                <td><?php echo $db_stats['total_size_formatted']; ?></td>
                            </tr>
                        </table>
                        
                        <h6 class="mt-3">Liste des tables:</h6>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Table</th>
                                        <th>Taille</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tables as $table): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($table['Name']); ?></td>
                                        <td><?php echo formatBytes($table['Data_length'] + $table['Index_length']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Onglet Maintenance -->
    <div class="tab-pane fade" id="maintenance" role="tabpanel">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>Outils de maintenance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-download fa-3x text-primary mb-3"></i>
                                        <h6>Sauvegarder la base de données</h6>
                                        <p class="text-muted small">Créer une sauvegarde complète de la base de données</p>
                                        <form method="POST" style="display: inline;">
                                            <button type="submit" name="backup_database" class="btn btn-primary btn-sm">
                                                <i class="fas fa-download me-1"></i>Sauvegarder
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <i class="fas fa-broom fa-3x text-warning mb-3"></i>
                                        <h6>Vider le cache</h6>
                                        <p class="text-muted small">Supprimer les fichiers temporaires et le cache</p>
                                        <form method="POST" style="display: inline;">
                                            <button type="submit" name="clear_cache" class="btn btn-warning btn-sm">
                                                <i class="fas fa-broom me-1"></i>Vider
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                                        <h6>Optimiser la base de données</h6>
                                        <p class="text-muted small">Optimiser les tables pour améliorer les performances</p>
                                        <button class="btn btn-info btn-sm" disabled>
                                            <i class="fas fa-chart-line me-1"></i>Optimiser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6>Informations de maintenance:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Dernière sauvegarde:</strong> 
                                    <?php
                                    $backup_files = glob('backups/*.sql');
                                    if (!empty($backup_files)) {
                                        $latest_backup = max($backup_files);
                                        echo date('d/m/Y H:i', filemtime($latest_backup));
                                    } else {
                                        echo 'Aucune sauvegarde trouvée';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Espace disque disponible:</strong> 
                                    <?php
                                    $free_space = disk_free_space('.');
                                    echo formatBytes($free_space);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Sécurité
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Recommandations:</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Effectuez des sauvegardes régulières
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Maintenez votre système à jour
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Surveillez les accès utilisateurs
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Vérifiez les permissions des fichiers
                            </li>
                        </ul>
                        
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i>
                            Contactez votre administrateur système pour toute question concernant la sécurité.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
