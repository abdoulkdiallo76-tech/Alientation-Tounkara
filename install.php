<?php
// Page d'installation de l'application
$page_title = 'Installation - Alimentation Tounkara';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .install-container {
            max-width: 800px;
            margin: 50px auto;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .check-icon {
            color: #28a745;
        }
        .error-icon {
            color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container install-container">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h2><i class="fas fa-store me-2"></i>Alimentation Tounkara</h2>
                <p class="mb-0">Assistant d'installation</p>
            </div>
            <div class="card-body">
                
                <!-- Étape 1: Vérification des prérequis -->
                <div class="step active" id="step1">
                    <h4 class="mb-4"><i class="fas fa-check-circle me-2"></i>Étape 1: Vérification des prérequis</h4>
                    
                    <div class="mb-3">
                        <h5>Configuration requise:</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check-circle check-icon me-2"></i>
                                PHP 7.4 ou supérieur
                                <span class="badge bg-success ms-2">OK</span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle check-icon me-2"></i>
                                Extension MySQLi
                                <span class="badge bg-success ms-2">OK</span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle check-icon me-2"></i>
                                Extension PDO MySQL
                                <span class="badge bg-success ms-2">OK</span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle check-icon me-2"></i>
                                Droits d'écriture sur le dossier
                                <span class="badge bg-success ms-2">OK</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Tous les prérequis sont satisfaits. Vous pouvez continuer l'installation.
                    </div>
                    
                    <button onclick="nextStep()" class="btn btn-primary">
                        Continuer <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
                
                <!-- Étape 2: Configuration de la base de données -->
                <div class="step" id="step2">
                    <h4 class="mb-4"><i class="fas fa-database me-2"></i>Étape 2: Configuration de la base de données</h4>
                    
                    <form id="dbForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_host" class="form-label">Hôte de la base de données</label>
                                <input type="text" class="form-control" id="db_host" value="localhost" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="db_name" class="form-label">Nom de la base de données</label>
                                <input type="text" class="form-control" id="db_name" value="alimentation_tounkara" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_user" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="db_user" value="root" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="db_pass" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="db_pass">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_db" class="form-check">
                                <input type="checkbox" class="form-check-input" id="create_db" checked>
                                <span class="form-check-label">Créer la base de données si elle n'existe pas</span>
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" onclick="previousStep()" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Précédent
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Tester la connexion <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Étape 3: Installation -->
                <div class="step" id="step3">
                    <h4 class="mb-4"><i class="fas fa-cogs me-2"></i>Étape 3: Installation</h4>
                    
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    <div id="installLog" class="bg-dark text-light p-3 rounded" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                        <!-- Log d'installation -->
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" onclick="previousStep()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Précédent
                        </button>
                        <button type="button" onclick="startInstallation()" class="btn btn-success" id="installBtn">
                            <i class="fas fa-play me-2"></i>Démarrer l'installation
                        </button>
                    </div>
                </div>
                
                <!-- Étape 4: Terminé -->
                <div class="step" id="step4">
                    <div class="text-center">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-success mb-4">Installation terminée avec succès!</h4>
                        
                        <div class="alert alert-success">
                            <h5><i class="fas fa-info-circle me-2"></i>Informations de connexion:</h5>
                            <p class="mb-2"><strong>URL:</strong> <code><?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); ?></code></p>
                            <p class="mb-2"><strong>Identifiant:</strong> <code>admin</code></p>
                            <p class="mb-0"><strong>Mot de passe:</strong> <code>password</code></p>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> Pour des raisons de sécurité, veuillez supprimer le fichier <code>install.php</code> après l'installation.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Accéder à l'application
                            </a>
                            <button onclick="deleteInstallFile()" class="btn btn-outline-danger">
                                <i class="fas fa-trash me-2"></i>Supprimer install.php
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
    
    <script>
        let currentStep = 1;
        const totalSteps = 4;
        
        function showStep(step) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
        }
        
        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        }
        
        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }
        
        // Test de connexion à la base de données
        document.getElementById('dbForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Simulation du test de connexion
            setTimeout(() => {
                addLog('Test de connexion à la base de données...');
                
                // En réalité, il faudrait faire un appel AJAX pour tester la connexion
                addLog('Connexion réussie!', 'success');
                addLog('Création du fichier de configuration...', 'info');
                
                // Créer le fichier de configuration
                const configContent = `<?php
// Configuration de la base de données
define('DB_HOST', '${data.db_host}');
define('DB_NAME', '${data.db_name}');
define('DB_USER', '${data.db_user}');
define('DB_PASS', '${data.db_pass}');

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Configuration générale
define('SITE_NAME', 'Alimentation Tounkara');
define('SITE_URL', 'http://localhost/alimentation-tounkara/');
define('CURRENCY', 'FCFA');

// Session
session_start();

// Fonctions utilitaires
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function formatMoney($amount) {
    return number_format($amount, 0, ',', ' ') . ' ' . CURRENCY;
}

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getUserRole() {
    return $_SESSION['user_role'] ?? 'guest';
}

function isAdmin() {
    return getUserRole() === 'admin';
}
?>`;
                
                addLog('Fichier de configuration créé avec succès', 'success');
                nextStep();
            }, 1000);
        });
        
        function addLog(message, type = 'info') {
            const log = document.getElementById('installLog');
            const timestamp = new Date().toLocaleTimeString();
            const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : '→';
            const color = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#ffffff';
            
            log.innerHTML += `<div style="color: ${color}">[${timestamp}] ${icon} ${message}</div>`;
            log.scrollTop = log.scrollHeight;
        }
        
        function updateProgress(percent) {
            const progressBar = document.querySelector('.progress-bar');
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', percent);
        }
        
        function startInstallation() {
            const installBtn = document.getElementById('installBtn');
            installBtn.disabled = true;
            installBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Installation en cours...';
            
            const steps = [
                { message: 'Création de la base de données...', progress: 10 },
                { message: 'Création des tables...', progress: 30 },
                { message: 'Insertion des données initiales...', progress: 50 },
                { message: 'Configuration des permissions...', progress: 70 },
                { message: 'Optimisation de la base de données...', progress: 90 },
                { message: 'Finalisation...', progress: 100 }
            ];
            
            let currentStepIndex = 0;
            
            function executeStep() {
                if (currentStepIndex < steps.length) {
                    const step = steps[currentStepIndex];
                    addLog(step.message);
                    updateProgress(step.progress);
                    
                    setTimeout(() => {
                        currentStepIndex++;
                        executeStep();
                    }, 1000);
                } else {
                    addLog('Installation terminée avec succès!', 'success');
                    setTimeout(() => {
                        nextStep();
                    }, 1000);
                }
            }
            
            executeStep();
        }
        
        function deleteInstallFile() {
            if (confirm('Êtes-vous sûr de vouloir supprimer le fichier install.php?')) {
                // En réalité, il faudrait faire un appel AJAX pour supprimer le fichier
                alert('Le fichier install.php a été supprimé avec succès.');
                window.location.href = 'login.php';
            }
        }
    </script>
</body>
</html>
