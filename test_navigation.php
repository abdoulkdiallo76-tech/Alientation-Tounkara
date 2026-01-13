<!DOCTYPE html>
<html>
<head>
    <title>Test Navigation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Test de Navigation</h1>
        
        <h3>Liens simples (test de base)</h3>
        <a href="index.php" class="btn btn-primary me-2">Index</a>
        <a href="login.php" class="btn btn-success me-2">Login</a>
        <a href="sessions.php" class="btn btn-info">Sessions</a>
        
        <hr>
        
        <h3>Navigation Bootstrap (comme dans le header)</h3>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Test App</a>
                
                <div class="navbar-nav">
                    <a class="nav-link" href="index.php">Tableau de bord</a>
                    <a class="nav-link" href="pos.php">Caisse</a>
                    <a class="nav-link" href="sales.php">Historique</a>
                    <a class="nav-link" href="sessions.php">Sessions</a>
                </div>
            </div>
        </nav>
        
        <hr>
        
        <h3>Test JavaScript</h3>
        <button onclick="testClick()" class="btn btn-warning">Test Click JS</button>
        <button id="testBtn" class="btn btn-danger">Test Event Listener</button>
        
        <hr>
        
        <h3>Debug Information</h3>
        <div id="debugInfo" class="alert alert-info">
            <p>URL actuelle: <span id="currentUrl"></span></p>
            <p>User Agent: <span id="userAgent"></span></p>
            <p>Nombre de liens: <span id="linkCount"></span></p>
        </div>
        
        <div id="clickLog" class="alert alert-secondary">
            <h4>Log des clics:</h4>
            <div id="logContent"></div>
        </div>
    </div>

    <script>
        // Log function
        function log(message) {
            const logContent = document.getElementById('logContent');
            const time = new Date().toLocaleTimeString();
            logContent.innerHTML += `<div>[${time}] ${message}</div>`;
            console.log(message);
        }
        
        // Test click
        function testClick() {
            log('Test click JS appelé');
            alert('JavaScript fonctionne!');
        }
        
        // Event listener test
        document.getElementById('testBtn').addEventListener('click', function() {
            log('Event listener test appelé');
            alert('Event listener fonctionne!');
        });
        
        // Debug info
        document.getElementById('currentUrl').textContent = window.location.href;
        document.getElementById('userAgent').textContent = navigator.userAgent;
        document.getElementById('linkCount').textContent = document.querySelectorAll('a').length;
        
        // Test tous les liens
        document.addEventListener('DOMContentLoaded', function() {
            log('DOM chargé');
            
            const links = document.querySelectorAll('a');
            log(`Trouvé ${links.length} liens`);
            
            links.forEach((link, index) => {
                const href = link.getAttribute('href');
                log(`Lien ${index}: ${href}`);
                
                // Ajouter event listener
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    log(`Clic sur: ${href}`);
                    
                    if (href && href !== '#') {
                        log(`Navigation vers: ${href}`);
                        setTimeout(() => {
                            window.location.href = href;
                        }, 100);
                    }
                });
                
                // Forcer styles
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
            });
            
            log('Configuration des liens terminée');
        });
    </script>
</body>
</html>
