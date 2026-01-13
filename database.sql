-- Base de données pour Alimentation Tounkara
CREATE DATABASE IF NOT EXISTS alimentation_tounkara;
USE alimentation_tounkara;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'manager', 'cashier', 'employee') DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Table des catégories de produits
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des produits
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(50) UNIQUE,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    purchase_price DECIMAL(10,2) DEFAULT 0,
    selling_price DECIMAL(10,2) DEFAULT 0,
    stock_quantity INT DEFAULT 0,
    min_stock_alert INT DEFAULT 5,
    unit VARCHAR(20) DEFAULT 'unité',
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Table des fournisseurs
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des commandes fournisseurs
CREATE TABLE supplier_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT,
    order_date DATE NOT NULL,
    delivery_date DATE,
    status ENUM('pending', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Détails des commandes fournisseurs
CREATE TABLE supplier_order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES supplier_orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Table des ventes
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'mobile') DEFAULT 'cash',
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    cashier_id INT,
    notes TEXT,
    FOREIGN KEY (cashier_id) REFERENCES users(id)
);

-- Détails des ventes
CREATE TABLE sale_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT,
    product_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Table des dépenses
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(200) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) DEFAULT 'Autres',
    expense_date DATE NOT NULL,
    created_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Table des mouvements de stock
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    movement_type ENUM('in', 'out') NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(100),
    reference_id INT,
    reference_type VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insertion des données initiales
-- Utilisateur admin par défaut
INSERT INTO users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'admin@tounkara.com', 'admin');

-- Catégories de base
INSERT INTO categories (name, description) VALUES 
('Boissons', 'Toutes sortes de boissons'),
('Alimentation', 'Produits alimentaires de base'),
('Produits laitiers', 'Lait, yaourts, fromages'),
('Boulangerie', 'Pain, viennoiseries'),
('Conserves', 'Produits en conserve'),
('Hygiène', 'Produits d\'hygiène personnelle'),
('Entretien', 'Produits d\'entretien ménager');

-- Fournisseurs exemples
INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES 
('Fournisseur A', 'M. Koné', '01-23-45-67', 'fournisseur@exemple.com', 'Abidjan, Cocody'),
('Fournisseur B', 'Mme. Traoré', '02-34-56-78', 'contact@fournisseurb.com', 'Abidjan, Plateau');

-- Produits exemples
INSERT INTO products (barcode, name, category_id, purchase_price, selling_price, stock_quantity, unit) VALUES 
('1234567890', 'Coca-Cola 33cl', 1, 150, 200, 50, 'bouteille'),
('1234567891', 'Pain de mie', 4, 250, 350, 20, 'paquet'),
('1234567892', 'Lait 1L', 3, 400, 550, 30, 'carton'),
('1234567893', 'Riz 1kg', 2, 500, 750, 40, 'sachet'),
('1234567894', 'Savon', 6, 100, 150, 60, 'pièce');
