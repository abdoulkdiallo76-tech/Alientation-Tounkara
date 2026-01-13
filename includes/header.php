<?php
require_once 'config/database.php';
requireLogin();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #4facfe;
            --danger-color: #f5576c;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 70px;
        }
        
        /* Header Principal */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        
        .header-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .header-brand:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .header-brand i {
            margin-right: 12px;
            font-size: 1.8rem;
        }
        
        .header-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            height: 100%;
            flex: 1;
            justify-content: center;
        }
        
        .nav-item {
            display: inline-flex;
            align-items: center;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
            white-space: nowrap;
            border: 1px solid transparent;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.3);
        }
        
        .nav-item i {
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 15px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .action-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
        }
        
        .action-btn i {
            margin-right: 5px;
            font-size: 0.8rem;
        }
        
        .logout-btn {
            background: rgba(220,53,69,0.8);
            border-color: rgba(220,53,69,0.5);
        }
        
        .logout-btn:hover {
            background: rgba(220,53,69,0.9);
        }
        
        /* Menu Mobile */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .header-container {
                padding: 0 15px;
            }
            
            .nav-item {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
            
            .nav-item i {
                margin-right: 6px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 992px) {
            .main-header {
                padding: 0 15px;
            }
            
            .header-brand {
                font-size: 1.3rem;
            }
            
            .header-brand i {
                font-size: 1.5rem;
                margin-right: 10px;
            }
            
            .header-nav {
                gap: 10px;
            }
            
            .nav-item {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
            
            .nav-item i {
                margin-right: 5px;
                font-size: 0.75rem;
            }
            
            .action-btn {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
            
            .action-btn i {
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 768px) {
            .main-header {
                padding: 0 10px;
                height: auto;
                min-height: 70px;
            }
            
            .header-container {
                flex-direction: column;
                padding: 10px;
                height: auto;
            }
            
            .header-brand {
                font-size: 1.2rem;
                margin-bottom: 10px;
                width: 100%;
                justify-content: center;
            }
            
            .header-brand i {
                font-size: 1.3rem;
            }
            
            .header-nav {
                display: none;
                width: 100%;
                order: 3;
                margin-top: 10px;
                flex-direction: column;
                gap: 8px;
            }
            
            .header-nav.active {
                display: flex;
            }
            
            .nav-item {
                width: 100%;
                justify-content: center;
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            
            .header-actions {
                order: 2;
                margin-top: 10px;
                width: 100%;
                justify-content: center;
                gap: 8px;
            }
            
            .action-btn {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .mobile-toggle {
                display: block;
                position: absolute;
                top: 15px;
                right: 15px;
                order: 1;
            }
        }
        
        @media (max-width: 576px) {
            .main-header {
                padding: 0 5px;
            }
            
            .header-container {
                padding: 8px;
            }
            
            .header-brand {
                font-size: 1.1rem;
            }
            
            .header-brand i {
                font-size: 1.2rem;
                margin-right: 8px;
            }
            
            .nav-item {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .nav-item i {
                margin-right: 4px;
                font-size: 0.7rem;
            }
            
            .action-btn {
                padding: 5px 8px;
                font-size: 0.75rem;
            }
            
            .action-btn i {
                font-size: 0.65rem;
            }
        }
        
        /* Styles pour le contenu */
        .main-content {
            padding: 30px 20px;
            min-height: calc(100vh - 70px);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background: white;
            margin-bottom: 20px;
        }
        
        .btn {
            border-radius: 20px;
            font-weight: 600;
            padding: 10px 20px;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.3);
        }
        
        .page-title {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <!-- Header Horizontal Responsive -->
    <header class="main-header">
        <div class="header-container">
            <!-- Logo -->
            <a href="<?php echo isCashier() ? 'cashier_dashboard.php' : 'index.php'; ?>" class="header-brand">
                <i class="fas fa-store"></i>
                <span><?php echo SITE_NAME; ?></span>
            </a>
            
            <!-- Navigation Principale -->
            <nav class="header-nav" id="mainNav">
                <?php if (isCashier()): ?>
                    <!-- Navigation caissier -->
                    <?php if (hasOpenCashSession()): ?>
                    <a href="pos.php" class="nav-item">
                        <i class="fas fa-cash-register"></i>Ventes
                    </a>
                    <?php else: ?>
                    <a href="cash_management.php" class="nav-item">
                        <i class="fas fa-cash-register"></i>Ouvrir Caisse
                    </a>
                    <?php endif; ?>
                    <a href="sales.php" class="nav-item">
                        <i class="fas fa-list"></i>Historique
                    </a>
                <?php else: ?>
                    <!-- Navigation admin -->
                    <a href="pos.php" class="nav-item">
                        <i class="fas fa-cash-register"></i>Ventes
                    </a>
                    <a href="sales.php" class="nav-item">
                        <i class="fas fa-list"></i>Historique
                    </a>
                    <a href="products.php" class="nav-item">
                        <i class="fas fa-cube"></i>Produits
                    </a>
                    <a href="expenses.php" class="nav-item">
                        <i class="fas fa-money-bill-wave"></i>Dépenses
                    </a>
                    <a href="sessions.php" class="nav-item">
                        <i class="fas fa-history"></i>Sessions
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="locations.php" class="nav-item">
                        <i class="fas fa-map-marked-alt"></i>Localités
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
            
            <!-- Actions Utilisateur -->
            <div class="header-actions">
                <a href="profile.php" class="action-btn">
                    <i class="fas fa-user"></i>Profil
                </a>
                <?php if (isAdmin()): ?>
                <a href="users.php" class="action-btn">
                    <i class="fas fa-users-cog"></i>Admin
                </a>
                <?php endif; ?>
                <a href="logout.php" class="action-btn logout-btn">
                    <i class="fas fa-sign-out-alt"></i>Déconnexion
                </a>
                
                <!-- Bouton Menu Mobile -->
                <button class="mobile-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Script Menu Mobile -->
    <script>
        function toggleMobileMenu() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('active');
        }
        
        // Fermer le menu mobile en cliquant à l'extérieur
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('mainNav');
            const toggle = document.querySelector('.mobile-toggle');
            
            if (!nav.contains(event.target) && !toggle.contains(event.target)) {
                nav.classList.remove('active');
            }
        });
        
        // Fermer le menu au redimensionnement
        window.addEventListener('resize', function() {
            const nav = document.getElementById('mainNav');
            if (window.innerWidth > 768) {
                nav.classList.remove('active');
            }
        });
        
        // Forcer le logo à s'ouvrir dans la même fenêtre
        document.addEventListener('DOMContentLoaded', function() {
            const logo = document.querySelector('.header-brand');
            if (logo) {
                logo.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = this.href;
                });
            }
        });
    </script>

    <!-- Contenu Principal -->
    <main class="main-content">
