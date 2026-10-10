CREATE DATABASE IF NOT EXISTS smart_campus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_campus;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(150) UNIQUE NOT NULL,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','staff','student') NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_token VARCHAR(30) UNIQUE NOT NULL,
 student_id INT NOT NULL,
 created_by INT NULL,
 service VARCHAR(100) NOT NULL,
 base_amount DECIMAL(10,2) NOT NULL,
 emergency_fee DECIMAL(10,2) NOT NULL DEFAULT 00.00,
 amount DECIMAL(10,2) NOT NULL,
 order_type ENUM('Normal','Emergency') NOT NULL DEFAULT 'Normal',
 file_name VARCHAR(255),
 file_data LONGBLOB NULL,
 payment_status ENUM('Pending','Paid','Failed') DEFAULT 'Pending',
 payment_txn_id VARCHAR(100),
 status ENUM('Pending','Processing','Ready','Completed','Cancelled') DEFAULT 'Pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users(name,email,password,role) VALUES
 ('Administrator','admin@smartcampus.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC9kz7vKQ7dM9y3eK3C6','admin'),
 ('Staff Member','staff@smartcampus.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC9kz7vKQ7dM9y3eK3C6','staff'),
 ('Demo Student','student@smartcampus.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC9kz7vKQ7dM9y3eK3C6','student');

-- Stationery shop
CREATE TABLE IF NOT EXISTS stationery_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stationery_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_token VARCHAR(30) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('Pending','Paid','Processing','Ready','Completed','Cancelled') DEFAULT 'Pending',
    payment_status ENUM('Pending','Paid','Failed') DEFAULT 'Pending',
    payment_txn_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stationery_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (order_id) REFERENCES stationery_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES stationery_products(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO stationery_products(name,description,price,stock) VALUES
 ('A4 Copy Paper (500 sheets)','Premium white 80gsm A4 paper, ream wrapped',120.00,200),
 ('Ballpoint Pen (12 pack)','Smooth-writing blue ink ballpoint pens',85.00,300),
 ('Notebook A5 200 pages','Spiral-bound notebook with ruled pages',95.00,150),
 ('Sticky Notes (100 sheets)','Assorted colour self-stick note cubes',60.00,250),
 ('Highlighter Set (4 colours)','Chisel-tip fluorescent highlighters',55.00,180),
 ('Pencil Set HB (12 pieces)','Pre-sharpened graphite pencils with erasers',40.00,200),
 ('Glue Stick 40g','Strong adhesive clear glue stick',30.00,120),
 ('Scissors 13cm','Stainless steel office scissors',110.00,90),
 ('File Folder A4 (10 pack)','Manila file folders with tabs',75.00,140),
 ('Stapler Heavy Duty','Metal stapler with 24/6 staples',180.00,60);

