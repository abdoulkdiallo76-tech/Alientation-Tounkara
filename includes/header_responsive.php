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
    <script src="assets/js/navigation.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --dark-gradient: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            background: var(--primary-gradient) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
            flex-wrap: wrap;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: -0.5px;
            transition: transform 0.3s ease;
            padding: 10px 0;
        }
        
        .navbar-brand:hover {
            transform: translateY(-2px);
        }
        
        .navbar-brand i {
            margin-right: 12px;
            font-size: 2rem;
        }
        
        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
            font-size: 0.9rem;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateY(-1px);
        }
        
        .nav-link i {
            margin-right: 6px;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 20px;
            flex-wrap: wrap;
        }
        
        .user-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .user-link:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .user-link i {
            margin-right: 5px;
        }
        
        .logout-link {
            background: rgba(220,53,69,0.2);
        }
        
        .logout-link:hover {
            background: rgba(220,53,69,0.3);
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
            transition: all 0.3s ease;
            overflow: hidden;
            color: #000 !important;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 20px 20px 0 0 !important;
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .card-body {
            color: #000 !important;
        }
        
        .card-title {
            color: #000 !important;
        }
        
        .card-text {
            color: #000 !important;
        }
        
        .btn {
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 20px;
            border: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            box-shadow: 0 4px 15px rgba(102,126,234,0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102,126,234,0.6);
        }
        
        .btn-success {
            background: var(--success-gradient);
            box-shadow: 0 4px 15px rgba(79,172,254,0.4);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79,172,254,0.6);
        }
        
        .btn-danger {
            background: var(--secondary-gradient);
            box-shadow: 0 4px 15px rgba(240,147,251,0.4);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(240,147,251,0.6);
        }
        
        .table {
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .table thead {
            background: var(--primary-gradient);
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background: rgba(102,126,234,0.1);
            transform: scale(1.01);
            transition: all 0.3s ease;
        }
        
        .alert {
            border: none;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .form-control {
            border-radius: 15px;
            border: 2px solid rgba(102,126,234,0.2);
            background: rgba(255,255,255,0.9);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
            background: white;
        }
        
        .badge {
            border-radius: 20px;
            padding: 5px 12px;
            font-weight: 600;
        }
        
        .main-content {
            padding: 30px 15px;
            min-height: calc(100vh - 76px);
        }
        
        .page-title {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            margin-bottom: 30px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .navbar-container {
                padding: 0 10px;
            }
            
            .nav-link {
                padding: 8px 15px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 992px) {
            .navbar-content {
                height: auto;
                padding: 10px 0;
            }
            
            .navbar-brand {
                font-size: 1.3rem;
            }
            
            .navbar-brand i {
                font-size: 1.5rem;
            }
            
            .nav-link {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .navbar-user {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                height: auto;
                padding: 15px 0;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
                margin-bottom: 15px;
            }
            
            .navbar-brand i {
                font-size: 1.3rem;
            }
            
            .navbar-nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 6px;
                margin-bottom: 15px;
            }
            
            .nav-link {
                padding: 8px 12px;
                font-size: 0.75rem;
            }
            
            .navbar-user {
                margin-left: 0;
                margin-top: 0;
                width: 100%;
                justify-content: center;
                gap: 8px;
            }
            
            .user-link {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .main-content {
                padding: 20px 10px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-container {
                padding: 0 5px;
            }
            
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-brand i {
                font-size: 1.2rem;
                margin-right: 8px;
            }
            
            .nav-link {
                padding: 6px 10px;
                font-size: 0.7rem;
            }
            
            .nav-link i {
                margin-right: 4px;
                font-size: 0.8rem;
            }
            
            .user-link {
                padding: 5px 10px;
                font-size: 0.75rem;
            }
            
            .user-link i {
                margin-right: 3px;
                font-size: 0.7rem;
            }
            
            .main-content {
                padding: 15px 5px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
        
        /* Menu mobile toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
                position: absolute;
                top: 15px;
                right: 15px;
            }
            
            .navbar-nav {
                display: none;
                width: 100%;
                text-align: center;
            }
            
            .navbar-nav.active {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Responsive -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-content">
                <!-- Logo -->
                <a href="<?php echo isCashier() ? 'cashier_dashboard.php' : 'index.php'; ?>" class="navbar-brand">
                    <i class="fas fa-store"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
                
                <!-- Navigation Links -->
                <div class="navbar-nav" id="navbarNav">
                    <?php if (isCashier()): ?>
                        <!-- Navigation caissier -->
                        <?php if (hasOpenCashSession()): ?>
                        <a href="sales.php" class="nav-link">
                            <i class="fas fa-cash-register"></i>Ventes
                        </a>
                        <?php else: ?>
                        <a href="cash_management.php" class="nav-link">
                            <i class="fas fa-cash-register"></i>Ouvrir Caisse
                        </a>
                        <?php endif; ?>
                        <a href="sales.php" class="nav-link">
                            <i class="fas fa-list"></i>Historique
                        </a>
                    <?php else: ?>
                        <!-- Navigation admin -->
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>Tableau de bord
                        </a>
                        <a href="sales.php" class="nav-link">
                            <i class="fas fa-cash-register"></i>Ventes
                        </a>
                        <a href="cash_sessions.php" class="nav-link">
                            <i class="fas fa-list"></i>Historique
                        </a>
                        <a href="products.php" class="nav-link">
                            <i class="fas fa-cube"></i>Produits
                        </a>
                        <a href="expenses.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i>Dépenses
                        </a>
                        <a href="sessions.php" class="nav-link">
                            <i class="fas fa-history"></i>Sessions
                        </a>
                        <?php if (isAdmin()): ?>
                        <a href="locations.php" class="nav-link">
                            <i class="fas fa-map-marked-alt"></i>Localités
                        </a>
                        <a href="sessions_admin.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>Admin Sessions
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Menu utilisateur -->
                <div class="navbar-user">
                    <a href="profile.php" class="user-link">
                        <i class="fas fa-user"></i>Profil
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="users.php" class="user-link">
                        <i class="fas fa-users-cog"></i>Admin
                    </a>
                    <?php endif; ?>
                    <a href="logout.php" class="user-link logout-link">
                        <i class="fas fa-sign-out-alt"></i>Déconnexion
                    </a>
                </div>
                
                <!-- Bouton menu mobile -->
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <script>
        function toggleMobileMenu() {
            const nav = document.getElementById('navbarNav');
            nav.classList.toggle('active');
        }
        
        // Fermer le menu mobile en cliquant à l'extérieur
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('navbarNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!nav.contains(event.target) && !toggle.contains(event.target)) {
                nav.classList.remove('active');
            }
        });
    </script>
    
    <main class="main-content">
