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
    available BOOLEAN DEFAULT TRUE
);

-- Restaurant Tables
CREATE TABLE tables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_number INT UNIQUE NOT NULL
);

-- Customer orders created by the waiter
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_id INT NOT NULL,
    waiter_id INT NOT NULL,
    status ENUM(
        'pending',
        'preparing',
        'completed'
    ) DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (waiter_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES tables (id) ON DELETE CASCADE
);

-- Items included in each order
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
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
    tables (table_number)
VALUES (1),
    (2),
    (3),
    (4),
    (5),
    (6),
    (7),
    (8),
    (9),
    (10);

INSERT INTO
    menu_items (
        name,
        description,
        price,
        category
    )
VALUES (
        'Margherita Pizza',
        'Classic pizza with tomato, mozzarella, and basil',
        12.99,
        'Main Course'
    ),
    (
        'Caesar Salad',
        'Fresh romaine lettuce with Caesar dressing and croutons',
        8.99,
        'Appetizer'
    ),
    (
        'Grilled Salmon',
        'Fresh Atlantic salmon with herbs and lemon',
        18.99,
        'Main Course'
    ),
    (
        'Pasta Carbonara',
        'Creamy pasta with bacon and parmesan',
        14.99,
        'Main Course'
    ),
    (
        'Chocolate Lava Cake',
        'Warm chocolate cake with molten center',
        6.99,
        'Dessert'
    ),
    (
        'Tiramisu',
        'Classic Italian dessert with coffee and mascarpone',
        7.99,
        'Dessert'
    ),
    (
        'Fresh Lemonade',
        'Homemade lemonade with mint',
        3.99,
        'Beverage'
    ),
    (
        'Cappuccino',
        'Espresso with steamed milk foam',
        4.50,
        'Beverage'
    );