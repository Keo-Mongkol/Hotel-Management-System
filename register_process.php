<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($role) || empty($password)) {
        redirect('register.php?error=required');
    }
    
    if ($password !== $confirm_password) {
        redirect('register.php?error=match');
    }
    
    if (strlen($password) < 6) {
        redirect('register.php?error=password');
    }
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        redirect('register.php?error=username');
    }
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirect('register.php?error=email');
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Determine status (Admin/Manager need approval, others active)
    $status = ($role == 'Admin' || $role == 'Manager') ? 'pending' : 'active';
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, full_name, email, phone, address, role, status, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$username, $hashed_password, $full_name, $email, $phone, $address, $role, $status, $created_by])) {
        // Log activity if registered by existing user
        if ($created_by) {
            logActivity($created_by, 'User Registration', "Registered new user: $username ($role)");
        }
        
        if ($status == 'pending') {
            // Redirect with pending message
            redirect('login.php?msg=pending');
        } else {
            // Auto login for active users
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_role'] = $role;
            $_SESSION['user_username'] = $username;
            
            logActivity($user_id, 'Registration', "User registered successfully");
            redirect('dashboard.php');
        }
    } else {
        redirect('register.php?error=general');
    }
} else {
    redirect('register.php');
}
?>