/*
 *   This file showcases the schema and relationships between tables for the restaurant system.
 *   As well as some initial sample data.
 *   
 *   It was also imported into the MySQL administration tool to create the database.
 *
 */

CREATE DATABASE IF NOT EXISTS restaurant_system;

USE restaurant_system;

-- System Users;
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    role ENUM('admin', 'waiter', 'kitchen') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Items on the Menu
CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image_url VARCHAR(255),
    available BOOLEAN DEFAULT TRUE
);

-- Restaurant Tables
CREATE TABLE tables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_number INT UNIQUE NOT NULL,
    status ENUM(
        'available',
        'occupied',
        'reserved'
    ) DEFAULT 'available' NOT NULL
);

-- Customer orders created by the waiter
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_id INT NOT NULL,
    waiter_id INT NOT NULL,
    status ENUM(
        'pending',
        'preparing',
        'completed',
        'delivered'
    ) DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (waiter_id) REFERENCES users (id),
    FOREIGN KEY (table_id) REFERENCES tables (id)
);

-- Items included in each order
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders (id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items (id)
);

-- Sample data insertion
INSERT INTO
    users (username, password, role)
VALUES (
        'admin',
        '$2a$12$kx731fFk/UQ7JK2ohLGn9O9AT24nNX8vCNYyobUGDLbZy5YytUP42',
        'admin'
    ),
    (
        'waiter1',
        '$2a$12$kx731fFk/UQ7JK2ohLGn9O9AT24nNX8vCNYyobUGDLbZy5YytUP42',
        'waiter'
    ),
    (
        'kitchen1',
        '$2a$12$kx731fFk/UQ7JK2ohLGn9O9AT24nNX8vCNYyobUGDLbZy5YytUP42',
        'kitchen'
    );

INSERT INTO
    tables (table_number, status)
VALUES (1, 'available'),
    (2, 'available'),
    (3, 'available'),
    (4, 'available'),
    (5, 'available'),
    (6, 'available'),
    (7, 'available'),
    (8, 'available'),
    (9, 'available'),
    (10, 'available');

INSERT INTO
    menu_items (
        name,
        description,
        price,
        category,
        image_url
    )
VALUES (
        'Margherita Pizza',
        'Classic pizza with tomato, mozzarella, and basil',
        12.99,
        'Main Course',
        'pizza.jpg'
    ),
    (
        'Caesar Salad',
        'Fresh romaine lettuce with Caesar dressing and croutons',
        8.99,
        'Appetizer',
        'salad.jpg'
    ),
    (
        'Grilled Salmon',
        'Fresh Atlantic salmon with herbs and lemon',
        18.99,
        'Main Course',
        'salmon.jpg'
    ),
    (
        'Pasta Carbonara',
        'Creamy pasta with bacon and parmesan',
        14.99,
        'Main Course',
        'pasta.jpg'
    ),
    (
        'Chocolate Lava Cake',
        'Warm chocolate cake with molten center',
        6.99,
        'Dessert',
        'cake.jpg'
    ),
    (
        'Tiramisu',
        'Classic Italian dessert with coffee and mascarpone',
        7.99,
        'Dessert',
        'tiramisu.jpg'
    ),
    (
        'Fresh Lemonade',
        'Homemade lemonade with mint',
        3.99,
        'Beverage',
        'lemonade.jpg'
    ),
    (
        'Cappuccino',
        'Espresso with steamed milk foam',
        4.50,
        'Beverage',
        'cappuccino.jpg'
    );