<?php
require_once 'config/database.php';

// Enregistrer la fermeture de session avant de détruire la session
if (isset($_SESSION['user_id'])) {
    logSession('logout', $_SESSION['user_id']);
    
    // Calculer la durée de la session
    if (isset($_SESSION['login_time'])) {
        $login_time = new DateTime($_SESSION['login_time']);
        $logout_time = new DateTime();
        $duration = $logout_time->diff($login_time);
        
        // Optionnel: enregistrer la durée dans un log ou session
        error_log("Session utilisateur {$_SESSION['user_id']} durée: " . $duration->format('%H:%I:%S'));
    }
}

session_destroy();
header('Location: login.php');
exit();
?>
