<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        redirect('login.php?error=missing&username=' . urlencode($username));
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] != 'active') {
            if ($user['status'] == 'pending') {
                redirect('login.php?error=pending');
            } else {
                redirect('login.php?error=inactive');
            }
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_username'] = $user['username'];

        logActivity($user['id'], 'Login', 'User logged in successfully');
        redirect('dashboard.php');
    }

    logActivity(0, 'Failed Login', "Failed login attempt for: $username");
    redirect('login.php?error=invalid&username=' . urlencode($username));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #00bcd4;
            --primary-dark: #00838f;
            --primary-soft: rgba(0, 188, 212, 0.1);
            --border-color: #b2dfdb;
        }

        body {
            background: linear-gradient(135deg, var(--primary-soft) 0%, rgba(240, 249, 251, 0.9) 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 188, 212, 0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: fadeInUp 0.6s ease;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .login-header i {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            padding: 12px 20px;
            margin-bottom: 20px;
        }
        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            width: 100%;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 188, 156, 0.2);
        }
        .btn-register-link {
            background: none;
            border: 2px solid var(--primary);
            border-radius: 10px;
            padding: 10px;
            color: var(--primary);
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-register-link:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-hotel"></i>
            <h2>Hotel Management System</h2>
            <p class="mb-0">Cambodia</p>
        </div>
        <div class="login-body">
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    if ($_GET['error'] === 'pending') {
                        echo 'Your account is pending admin approval. Please try again later.';
                    } elseif ($_GET['error'] === 'inactive') {
                        echo 'Your account has been suspended. Please contact the administrator.';
                    } elseif ($_GET['error'] === 'missing') {
                        echo 'Please enter both your username/email and password.';
                    } elseif ($_GET['error'] === 'invalid') {
                        echo 'Invalid username or password!';
                    } else {
                        echo 'An error occurred. Please try again.';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'pending'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Registration successful! Your account is pending approval by Admin.
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['registered'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Registration successful! Please login.
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Username or Email" value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>" required>
                </div>
                <div class="input-group mb-4">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-login mb-3">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <hr>
                
                <p class="text-center mb-3">Don't have an account?</p>
                <a href="register.php" class="btn btn-register-link">
                    <i class="fas fa-user-plus"></i> Create New Account
                </a>
            </form>
            
            <hr>
            <div class="text-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Demo: admin/Admin@123<br>
                    <i class="fas fa-users"></i> Register to create your account
                </small>
            </div>
        </div>
    </div>
</body>
</html>