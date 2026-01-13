# Alimentation Tounkara - Système de Gestion

Une application web complète de gestion pour supérette développée en PHP, HTML et CSS avec une interface responsive pour smartphone.

## Fonctionnalités

### 🏪 Gestion de la caisse
- Interface de caisse intuitive et rapide
- Recherche de produits par nom ou code-barres
- Gestion des remises
- Support multiple méthodes de paiement
- Impression de tickets de vente

### 📦 Gestion des stocks
- Catalogue de produits complet
- Suivi en temps réel du stock
- Alertes de stock faible
- Mouvements de stock automatiques
- Gestion des catégories

### 💰 Gestion financière
- Suivi des ventes et revenus
- Gestion des dépenses
- Rapports financiers
- Statistiques en temps réel

### 📋 Gestion des commandes
- Gestion des fournisseurs
- Commandes d'approvisionnement
- Suivi des livraisons

### 👥 Gestion des utilisateurs
- Rôles et permissions
- Authentification sécurisée
- Suivi des activités

## Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache recommandé)

### Étapes d'installation

1. **Télécharger les fichiers**
   ```bash
   Copier tous les fichiers dans le répertoire de votre serveur web
   ```

2. **Créer la base de données**
   - Importez le fichier `database.sql` dans votre base de données MySQL
   - Ou exécutez les commandes SQL manuellement

3. **Configurer la connexion**
   - Ouvrez le fichier `config/database.php`
   - Modifiez les paramètres de connexion à la base de données:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'alimentation_tounkara');
   define('DB_USER', 'votre_utilisateur');
   define('DB_PASS', 'votre_mot_de_passe');
   ```

4. **Accéder à l'application**
   - Ouvrez votre navigateur et accédez à `http://localhost/alimentation-tounkara/`
   - Identifiant par défaut: `admin`
   - Mot de passe par défaut: `password`

## Structure des fichiers

```
alimentation-tounkara/
├── config/
│   └── database.php          # Configuration de la base de données
├── includes/
│   ├── header.php            # En-tête commun
│   └── footer.php            # Pied de page commun
├── pages/
│   ├── index.php             # Tableau de bord
│   ├── login.php             # Page de connexion
│   ├── pos.php               # Interface de caisse
│   ├── products.php          # Gestion des produits
│   ├── expenses.php          # Gestion des dépenses
│   └── ...                   # Autres pages
├── assets/
│   ├── css/
│   │   └── style.css         # Styles personnalisés
│   ├── js/
│   │   └── main.js           # Scripts JavaScript
│   └── images/               # Images
├── api/                      # API endpoints (à développer)
├── database.sql              # Script de création de la base de données
└── README.md                 # Documentation
```

## Utilisation

### Connexion
1. Accédez à l'application via votre navigateur
2. Utilisez les identifiants par défaut ou créez un nouveau compte
3. Le tableau de bord s'affiche avec les statistiques principales

### Processus de vente
1. Allez dans "Caisse" depuis le menu
2. Ajoutez des produits au panier en cliquant dessus
3. Appliquez une remise si nécessaire
4. Sélectionnez la méthode de paiement
5. Cliquez sur "Traiter la vente"

### Gestion des produits
1. Allez dans "Stock" → "Produits"
2. Cliquez sur "Ajouter un produit"
3. Remplissez les informations du produit
4. Le stock est automatiquement mis à jour lors des ventes

### Suivi des dépenses
1. Allez dans "Dépenses"
2. Cliquez sur "Ajouter une dépense"
3. Remplissez les détails de la dépense
4. Les dépenses apparaissent dans les rapports

## Personnalisation

### Modification des couleurs
- Ouvrez `assets/css/style.css`
- Modifiez les variables CSS dans `:root`

### Ajout de nouvelles fonctionnalités
- Créez de nouvelles pages dans le dossier `pages/`
- Ajoutez les routes dans le menu (header.php)
- Créez les tables nécessaires dans la base de données

### Configuration du logo
- Remplacez le logo dans `assets/images/logo.png`
- Modifiez le nom du site dans `config/database.php`

## Sécurité

- Les mots de passe sont hashés avec bcrypt
- Protection contre les injections SQL avec PDO
- Validation des entrées utilisateur
- Session sécurisée

## Support et maintenance

### Sauvegarde
- Sauvegardez régulièrement la base de données
- Exportez les données via les rapports disponibles

### Mises à jour
- Conservez une copie de vos données avant les mises à jour
- Testez les mises à jour dans un environnement de développement

## Dépannage

### Problèmes courants

**Erreur de connexion à la base de données**
- Vérifiez les identifiants dans `config/database.php`
- Assurez-vous que le serveur MySQL est démarré

**Page blanche**
- Activez l'affichage des erreurs PHP
- Vérifiez les logs du serveur web

**Produits n'apparaissent pas**
- Vérifiez que les produits sont actifs (`is_active = 1`)
- Vérifiez le stock disponible

## Contact

Pour toute question ou amélioration, contactez le développeur.

---

**Note:** Cette application est conçue pour les petites et moyennes supérettes. Pour des besoins plus complexes, des fonctionnalités supplémentaires peuvent être ajoutées.
