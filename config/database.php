<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'alimentation_tounkara');
define('DB_USER', 'root');
define('DB_PASS', '');

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
    if ($amount === null || $amount === '') {
        return '0 ' . CURRENCY;
    }
    return number_format((float)$amount, 0, ',', ' ') . ' ' . CURRENCY;
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

function isCashier() {
    return getUserRole() === 'cashier';
}

function canAccessManagement() {
    return getUserRole() === 'admin' || getUserRole() === 'manager';
}

function logSession($action, $user_id = null) {
    global $pdo;
    
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        error_log("logSession: user_id manquant");
        return false;
    }
    
    // S'assurer que la table existe
    if (!createSessionTable()) {
        error_log("logSession: Impossible de créer la table user_sessions");
        return false;
    }
    
    try {
        $session_id = session_id(); // ID unique de session PHP
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, action, session_date, ip_address, user_agent, session_id) VALUES (?, ?, NOW(), ?, ?, ?)");
        $stmt->execute([$user_id, $action, $ip_address, $user_agent, $session_id]);
        
        error_log("Session logged: user_id=$user_id, action=$action, session_id=$session_id");
    } catch(PDOException $e) {
        error_log("Error logging session: " . $e->getMessage());
    }
}

function createSessionTable() {
    global $pdo;
    
    try {
        // Vérifier si la table existe déjà
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_sessions'");
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        if (!$table_exists) {
            $sql = "CREATE TABLE user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action ENUM('login', 'logout') NOT NULL,
                session_date DATETIME NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                session_id VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, session_date),
                INDEX idx_action_date (action, session_date),
                INDEX idx_session_id (session_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // Ajouter la contrainte de clé étrangère si la table users existe
            try {
                $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
                $stmt->execute();
                $users_exists = $stmt->fetch();
                
                if ($users_exists) {
                    $sql = "ALTER TABLE user_sessions ADD CONSTRAINT fk_user_sessions_user 
                                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE";
                    $pdo->exec($sql);
                }
            } catch (Exception $e) {
                // La contrainte peut être ajoutée plus tard
                error_log("Contrainte FK non ajoutée: " . $e->getMessage());
            }
            
            error_log("Table user_sessions créée avec succès");
            return true;
        }
        
        return true;
    } catch(PDOException $e) {
        error_log("Erreur création table sessions: " . $e->getMessage());
        return false;
    }
}

function getSessionHistory($user_id = null, $limit = 50) {
    global $pdo;
    
    try {
        // S'assurer que limit est un entier
        $limit = (int)$limit;
        
        $sql = "SELECT us.*, u.full_name, u.username 
                FROM user_sessions us 
                LEFT JOIN users u ON us.user_id = u.id";
        
        $params = [];
        
        if ($user_id) {
            $sql .= " WHERE us.user_id = ?";
            $params[] = $user_id;
        }
        
        $sql .= " ORDER BY us.session_date DESC LIMIT " . $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur historique sessions: " . $e->getMessage());
        error_log("SQL: $sql");
        error_log("Params: " . json_encode($params ?? []));
        return [];
    }
}

function getActiveSessionDuration($user_id) {
    global $pdo;
    
    try {
        $sql = "SELECT session_date 
                FROM user_sessions 
                WHERE user_id = ? AND action = 'login' 
                ORDER BY session_date DESC 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            $login_time = new DateTime($result['session_date']);
            $now = new DateTime();
            return $now->diff($login_time);
        }
        
        return null;
    } catch(PDOException $e) {
        error_log("Erreur durée session: " . $e->getMessage());
        return null;
    }
}

// Fonctions de gestion des localités - Désactivées jusqu'à installation
function getUserLocation() {
    // Temporairement désactivé pour éviter les erreurs SQL
    return null;
}

function getUserLocationId() {
    // Temporairement désactivé pour éviter les erreurs SQL
    return null;
}

function getUserLocationName() {
    // Temporairement désactivé pour éviter les erreurs SQL
    return 'Non assigné';
}

function canAccessLocation($location_id) {
    // Temporairement désactivé pour éviter les erreurs SQL
    return true; // Autoriser tout par défaut
}

function getLocationFilter($table_alias = '') {
    // Temporairement désactivé pour éviter les erreurs SQL
    return ''; // Pas de restriction pour l'instant
}

function getAllLocations() {
    // Temporairement désactivé pour éviter les erreurs SQL
    return []; // Retourner tableau vide
}

function getLocationById($location_id) {
    // Temporairement désactivé pour éviter les erreurs SQL
    return null;
}

function updateUserLocation($user_id, $location_id) {
    // Temporairement désactivé pour éviter les erreurs SQL
    return false;
}

// Fonction pour vérifier si l'utilisateur a accès à une vente
function canAccessSale($sale_id) {
    // Temporairement désactivé pour éviter les erreurs SQL
    return true; // Autoriser tout par défaut
}

// Fonction pour vérifier si l'utilisateur a accès à un produit
function canAccessProduct($product_id) {
    // Temporairement désactivé pour éviter les erreurs SQL
    return true; // Autoriser tout par défaut
}

// Fonctions de gestion des sessions de caisse journalières
function hasOpenCashSession() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM cash_sessions WHERE cashier_id = ? AND status = 'open' AND DATE(opening_time) = CURDATE() LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() !== false;
    } catch(PDOException $e) {
        error_log("Erreur hasOpenCashSession: " . $e->getMessage());
        return false;
    }
}

function getCurrentCashSession() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM cash_sessions WHERE cashier_id = ? AND status = 'open' AND DATE(opening_time) = CURDATE() LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Erreur getCurrentCashSession: " . $e->getMessage());
        return null;
    }
}

function canMakeSales() {
    // Vérifier si l'utilisateur a une session de caisse ouverte pour aujourd'hui
    return hasOpenCashSession();
}

function getTodaySession() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM cash_sessions WHERE cashier_id = ? AND DATE(opening_time) = CURDATE() ORDER BY opening_time DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Erreur getTodaySession: " . $e->getMessage());
        return null;
    }
}

function isDaySessionAlreadyOpen() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM cash_sessions WHERE cashier_id = ? AND DATE(opening_time) = CURDATE() AND status = 'open' LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() !== false;
    } catch(PDOException $e) {
        error_log("Erreur isDaySessionAlreadyOpen: " . $e->getMessage());
        return false;
    }
}

function openDayCashSession($opening_amount, $password) {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    // Vérifier si une session est déjà ouverte aujourd'hui
    if (isDaySessionAlreadyOpen()) {
        return ['success' => false, 'message' => 'Une session de caisse est déjà ouverte aujourd\'hui'];
    }
    
    // Vérifier le mot de passe
    if (!verifyPassword($_SESSION['user_id'], $password)) {
        return ['success' => false, 'message' => 'Mot de passe incorrect'];
    }
    
    try {
        $location_id = getUserLocationId();
        
        $stmt = $pdo->prepare("INSERT INTO cash_sessions (cashier_id, location_id, opening_amount, status, opening_time) VALUES (?, ?, ?, 'open', NOW())");
        $result = $stmt->execute([$_SESSION['user_id'], $location_id, $opening_amount]);
        
        if ($result) {
            $session_id = $pdo->lastInsertId();
            return ['success' => true, 'session_id' => $session_id, 'message' => 'Caisse journalière ouverte avec succès'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de l\'ouverture de la caisse'];
        }
    } catch(PDOException $e) {
        error_log("Erreur openDayCashSession: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur de base de données'];
    }
}

function closeDayCashSession($closing_amount, $notes = '') {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $session = getCurrentCashSession();
    if (!$session) {
        return ['success' => false, 'message' => 'Aucune session de caisse ouverte aujourd\'hui'];
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE cash_sessions SET 
            closing_time = NOW(),
            closing_amount = ?,
            status = 'closed',
            notes = ?
            WHERE id = ?");
        
        $result = $stmt->execute([
            $closing_amount,
            $notes,
            $session['id']
        ]);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'Caisse journalière fermée avec succès'
            ];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la fermeture de la caisse'];
        }
    } catch(PDOException $e) {
        error_log("Erreur closeDayCashSession: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur de base de données'];
    }
}

function attachSaleToSession($sale_id) {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $session = getCurrentCashSession();
    if (!$session) {
        return false; // Pas de session ouverte, impossible d'attacher
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE sales SET cash_session_id = ? WHERE id = ?");
        return $stmt->execute([$session['id'], $sale_id]);
    } catch(PDOException $e) {
        error_log("Erreur attachSaleToSession: " . $e->getMessage());
        return false;
    }
}

function getSalesBySession($session_id, $limit = 100) {
    global $pdo;
    
    if (!isAdmin() && !isCashier()) {
        return [];
    }
    
    try {
        $sql = "SELECT s.*, u.full_name as cashier_name 
                 FROM sales s 
                 LEFT JOIN users u ON s.cashier_id = u.id 
                 WHERE s.cash_session_id = ? 
                 ORDER BY s.sale_date DESC 
                 LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$session_id, $limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur getSalesBySession: " . $e->getMessage());
        return [];
    }
}

function getTodaySalesBySession() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return [];
    }
    
    $session = getTodaySession();
    if (!$session) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE cash_session_id = ? ORDER BY sale_date DESC");
        $stmt->execute([$session['id']]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur getTodaySalesBySession: " . $e->getMessage());
        return [];
    }
}

function checkDaySessionStatus() {
    if (!isLoggedIn()) {
        return ['status' => 'not_logged', 'message' => 'Utilisateur non connecté'];
    }
    
    $session = getTodaySession();
    
    if (!$session) {
        return ['status' => 'no_session', 'message' => 'Aucune session aujourd\'hui'];
    }
    
    if ($session['status'] === 'open') {
        return ['status' => 'open', 'session' => $session, 'message' => 'Caisse ouverte'];
    } else {
        return ['status' => 'closed', 'session' => $session, 'message' => 'Caisse fermée'];
    }
}

function getCashSessionsHistory($limit = 50) {
    global $pdo;
    
    if (!isLoggedIn()) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT cs.*, u.full_name as cashier_name, l.name as location_name
            FROM cash_sessions cs
            LEFT JOIN users u ON cs.cashier_id = u.id
            LEFT JOIN locations l ON cs.location_id = l.id
            WHERE cs.cashier_id = ? 
            ORDER BY cs.opening_time DESC
            LIMIT ?");
        $stmt->execute([$_SESSION['user_id'], $limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur getCashSessionsHistory: " . $e->getMessage());
        return [];
    }
}

function getAllCashSessions($limit = 100) {
    global $pdo;
    
    if (!isAdmin()) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT cs.*, u.full_name as cashier_name, l.name as location_name
            FROM cash_sessions cs
            LEFT JOIN users u ON cs.cashier_id = u.id
            LEFT JOIN locations l ON cs.location_id = l.id
            ORDER BY cs.opening_time DESC
            LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur getAllCashSessions: " . $e->getMessage());
        return [];
    }
}

function verifyPassword($user_id, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erreur verifyPassword: " . $e->getMessage());
        return false;
    }
}

// Fonctions utilitaires - éviter les déclarations doubles
if (!function_exists('formatMoney')) {
    function formatMoney($amount) {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date, $include_time = false, $short = false) {
        if (empty($date)) return 'N/A';
        
        $datetime = new DateTime($date);
        
        if ($short) {
            return $datetime->format('d/m/Y');
        } elseif ($include_time) {
            return $datetime->format('d/m/Y H:i:s');
        } else {
            return $datetime->format('d/m/Y');
        }
    }
}

if (!function_exists('formatDuration')) {
    function formatDuration($start_time) {
        if (empty($start_time)) return 'N/A';
        
        $start = new DateTime($start_time);
        $now = new DateTime();
        $diff = $now->diff($start);
        
        if ($diff->days > 0) {
            return $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        } else {
            return 'Quelques secondes';
        }
    }
}

if (!function_exists('cleanInput')) {
    function cleanInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

?>
