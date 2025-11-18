<?php
// Define database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'restaurant_system');

// Start a session if not started 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to the database;
function getConnection(): mysqli
{
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    if ($connection->connect_error) {
        die("Connecting to DB failed: " . $connection->connect_error);
    }

    return $connection;
}

// Check if user is logged in
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check a user's role
function hasRole($role): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if a user is not logged in 
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if role is wrong
function requireRole($role)
{
    // First check if user is logged in
    requireLogin();

    // Check if it has required role
    if (!hasRole($role)) {
        header('Location: login.php');
        exit();
    }
}