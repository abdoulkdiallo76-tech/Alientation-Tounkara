<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation des Localités - Alimentation Tounkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>Installation des Localités
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once 'config/database.php';
                        
                        $errors = [];
                        $success = [];
                        
                        try {
                            // 1. Créer la table locations
                            $stmt = $pdo->prepare("SHOW TABLES LIKE 'locations'");
                            $stmt->execute();
                            $table_exists = $stmt->fetch();
                            
                            if (!$table_exists) {
                                $sql = "CREATE TABLE locations (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    name VARCHAR(100) NOT NULL UNIQUE,
                                    code VARCHAR(20) NOT NULL UNIQUE,
                                    address TEXT,
                                    phone VARCHAR(20),
                                    email VARCHAR(100),
                                    is_active TINYINT(1) DEFAULT 1,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    INDEX idx_name (name),
                                    INDEX idx_code (code),
                                    INDEX idx_active (is_active)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                                
                                $pdo->exec($sql);
                                $success[] = "✅ Table 'locations' créée avec succès";
                            } else {
                                $success[] = "ℹ️ Table 'locations' existe déjà";
                            }
                            
                            // 2. Ajouter la colonne location_id à la table users
                            $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'location_id'");
                            $stmt->execute();
                            $column_exists = $stmt->fetch();
                            
                            if (!$column_exists) {
                                $sql = "ALTER TABLE users ADD COLUMN location_id INT NULL AFTER role";
                                $pdo->exec($sql);
                                $success[] = "✅ Colonne 'location_id' ajoutée à la table 'users'";
                                
                                // Ajouter la contrainte de clé étrangère
                                try {
                                    $sql = "ALTER TABLE users ADD CONSTRAINT fk_users_location 
                                            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
                                    $pdo->exec($sql);
                                    $success[] = "✅ Contrainte foreign key ajoutée à users.location_id";
                                } catch (Exception $e) {
                                    $errors[] = "⚠️ Contrainte FK users non ajoutée: " . $e->getMessage();
                                }
                            } else {
                                $success[] = "ℹ️ Colonne 'location_id' existe déjà dans users";
                            }
                            
                            // 3. Ajouter la colonne location_id à la table sales
                            $stmt = $pdo->prepare("SHOW COLUMNS FROM sales LIKE 'location_id'");
                            $stmt->execute();
                            $column_exists = $stmt->fetch();
                            
                            if (!$column_exists) {
                                $sql = "ALTER TABLE sales ADD COLUMN location_id INT NULL AFTER cashier_id";
                                $pdo->exec($sql);
                                $success[] = "✅ Colonne 'location_id' ajoutée à la table 'sales'";
                                
                                // Ajouter la contrainte de clé étrangère
                                try {
                                    $sql = "ALTER TABLE sales ADD CONSTRAINT fk_sales_location 
                                            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
                                    $pdo->exec($sql);
                                    $success[] = "✅ Contrainte foreign key ajoutée à sales.location_id";
                                } catch (Exception $e) {
                                    $errors[] = "⚠️ Contrainte FK sales non ajoutée: " . $e->getMessage();
                                }
                            } else {
                                $success[] = "ℹ️ Colonne 'location_id' existe déjà dans sales";
                            }
                            
                            // 4. Ajouter la colonne location_id à la table products
                            $stmt = $pdo->prepare("SHOW COLUMNS FROM products LIKE 'location_id'");
                            $stmt->execute();
                            $column_exists = $stmt->fetch();
                            
                            if (!$column_exists) {
                                $sql = "ALTER TABLE products ADD COLUMN location_id INT NULL AFTER stock_quantity";
                                $pdo->exec($sql);
                                $success[] = "✅ Colonne 'location_id' ajoutée à la table 'products'";
                                
                                // Ajouter la contrainte de clé étrangère
                                try {
                                    $sql = "ALTER TABLE products ADD CONSTRAINT fk_products_location 
                                            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
                                    $pdo->exec($sql);
                                    $success[] = "✅ Contrainte foreign key ajoutée à products.location_id";
                                } catch (Exception $e) {
                                    $errors[] = "⚠️ Contrainte FK products non ajoutée: " . $e->getMessage();
                                }
                            } else {
                                $success[] = "ℹ️ Colonne 'location_id' existe déjà dans products";
                            }
                            
                            // 5. Insérer des localités de démonstration
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM locations");
                            $stmt->execute();
                            $count = $stmt->fetch()['count'];
                            
                            if ($count == 0) {
                                $locations = [
                                    ['Siège Principal', 'SIEGE', '123 Avenue Principale, Dakar', '+221 33 123 45 67', 'contact@alimentation-tounkara.sn'],
                                    ['Boutique Plateau', 'PLATEAU', '45 Rue du Commerce, Dakar', '+221 33 234 56 78', 'plateau@alimentation-tounkara.sn'],
                                    ['Boutique Mermoz', 'MERMOZ', '78 Avenue Cheikh Anta Diop, Dakar', '+221 33 345 67 89', 'mermoz@alimentation-tounkara.sn'],
                                    ['Boutique Yoff', 'YOFF', '90 Route de la Corniche, Dakar', '+221 33 456 78 90', 'yoff@alimentation-tounkara.sn'],
                                    ['Boutique Ouakam', 'OUAKAM', '12 Rue des Pêcheurs, Dakar', '+221 33 567 89 01', 'ouakam@alimentation-tounkara.sn']
                                ];
                                
                                $stmt = $pdo->prepare("INSERT INTO locations (name, code, address, phone, email) VALUES (?, ?, ?, ?, ?)");
                                foreach ($locations as $location) {
                                    $stmt->execute($location);
                                }
                                
                                $success[] = "✅ 5 localités de démonstration insérées";
                            } else {
                                $success[] = "ℹ️ Localités existent déjà ($count trouvées)";
                            }
                            
                        } catch(PDOException $e) {
                            $errors[] = "❌ Erreur SQL: " . $e->getMessage();
                        }
                        
                        // 6. Assigner une localité par défaut aux utilisateurs existants
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE location_id IS NULL AND is_active = 1");
                            $stmt->execute();
                            $users_without_location = $stmt->fetch()['count'];
                            
                            if ($users_without_location > 0) {
                                // Assigner la première localité disponible
                                $stmt = $pdo->prepare("SELECT id FROM locations WHERE is_active = 1 LIMIT 1");
                                $stmt->execute();
                                $first_location = $stmt->fetch();
                                
                                if ($first_location) {
                                    $stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE location_id IS NULL AND is_active = 1");
                                    $stmt->execute([$first_location['id']]);
                                    $success[] = "✅ $users_without_location utilisateur(s) assigné(s) à la localité par défaut";
                                }
                            } else {
                                $success[] = "ℹ️ Tous les utilisateurs ont déjà une localité assignée";
                            }
                        } catch(PDOException $e) {
                            $errors[] = "⚠️ Erreur lors de l'assignation des localités: " . $e->getMessage();
                        }
                        ?>
                        
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Configuration des localités</h5>
                            <p>Cette page installe le système de gestion des localités pour votre application Alimentation Tounkara.</p>
                        </div>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Succès</h5>
                                <ul class="mb-0">
                                    <?php foreach ($success as $msg): ?>
                                        <li><?php echo $msg; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Avertissements</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $msg): ?>
                                        <li><?php echo $msg; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <h6><i class="fas fa-list me-2"></i>Prochaines étapes</h6>
                            <ol>
                                <li><strong>Configurer les localités:</strong> Accédez à <a href="locations.php" target="_blank">Gestion des Localités</a> pour modifier ou ajouter des localités</li>
                                <li><strong>Assigner les utilisateurs:</strong> Modifiez les profils utilisateurs pour leur assigner leurs localités respectives</li>
                                <li><strong>Vérifier l'accès:</strong> Testez l'accès avec différents comptes pour valider les restrictions par localité</li>
                            </ol>
                        </div>
                        
                        <div class="mt-4 d-flex gap-2">
                            <a href="locations.php" class="btn btn-primary">
                                <i class="fas fa-map-marked-alt me-2"></i>Gérer les Localités
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Pour des raisons de sécurité, supprimez ce fichier (install_locations.php) après l'installation.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
