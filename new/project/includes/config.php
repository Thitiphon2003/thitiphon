<?php
session_start();

// Database configuration for server 103.114.201.254
define('DB_HOST', '103.114.201.254');
define('DB_USER', 'root');
define('DB_PASS', 'r669109');
define('DB_NAME', '4140db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("ระบบอยู่ระหว่างการปรับปรุง กรุณาลองใหม่อีกครั้งในภายหลัง");
}

// Set charset
$conn->set_charset("utf8");

// Site configuration
define('SITE_NAME', 'ShopHub');
define('SITE_URL', 'http://103.114.201.254/thitiphon/new/project/');

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to redirect
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'));
}

// Function to get base URL
function base_url($path = '') {
    return SITE_URL . ltrim($path, '/');
}

// Function to display error messages
function showError($message) {
    return "<div style='background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #dc3545;'>
                <i class='fas fa-exclamation-circle'></i> " . $message . "
            </div>";
}

// Function to display success messages
function showSuccess($message) {
    return "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #28a745;'>
                <i class='fas fa-check-circle'></i> " . $message . "
            </div>";
}

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>