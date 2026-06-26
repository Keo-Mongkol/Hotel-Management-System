<?php
$host = 'localhost:3308';
$dbname = 'hotel_management_cambodias';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
}

function hasAnyRole($roles) {
    if (!isset($_SESSION['user_role'])) return false;
    return in_array($_SESSION['user_role'], $roles);
}

function isAdmin() {
    return hasRole('Admin');
}

function isManager() {
    return hasRole('Manager') || hasRole('Admin');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generateUniqueCode($prefix, $table, $column) {
    global $pdo;
    $code = $prefix . date('Ymd') . rand(1000, 9999);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
    $stmt->execute([$code]);
    while($stmt->fetchColumn() > 0) {
        $code = $prefix . date('Ymd') . rand(1000, 9999);
        $stmt->execute([$code]);
    }
    return $code;
}

function logActivity($user_id, $action, $description) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $description, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}

function getExchangeRate() {
    // For demo, using fixed rate. In production, call API
    return 4100; // 1 USD = 4100 KHR
}
