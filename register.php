<?php require_once 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Hotel Management System</title>
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
            background: linear-gradient(135deg, var(--primary-soft) 0%, rgba(240, 249, 251, 0.95) 100%);
            min-height: 100vh;
            padding: 50px 0;
            font-family: 'Poppins', sans-serif;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 188, 212, 0.2);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
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
        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }
        .register-body {
            padding: 40px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            padding: 10px 15px;
        }
        .btn-register {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            width: 100%;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 188, 156, 0.2);
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .role-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <i class="fas fa-user-plus"></i>
            <h2>Create Account</h2>
            <p>Join Hotel Management System Cambodia</p>
        </div>
        <div class="register-body">
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    $errors = [
                        'required' => 'Please fill in all required fields!',
                        'username' => 'Username already exists!',
                        'email' => 'Email already registered!',
                        'password' => 'Password must be at least 6 characters!',
                        'match' => 'Passwords do not match!',
                        'general' => 'Registration failed. Please try again!'
                    ];
                    echo $errors[$_GET['error']] ?? 'Registration failed!';
                    ?>
                </div>
            <?php endif; ?>
            
            <form action="register_process.php" method="POST" onsubmit="return validateForm()">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="012345678">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Select Role *</label>
                    <select name="role" class="form-select" required id="roleSelect">
                        <option value="">Choose Role</option>
                        <option value="Receptionist">Receptionist</option>
                        <option value="Cashier">Cashier</option>
                        <option value="Manager">Manager</option>
                        <option value="Admin">Admin</option>
                    </select>
                    <div class="role-info" id="roleInfo"></div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" id="password" class="form-control" required onkeyup="checkPasswordStrength()">
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#">Terms and Conditions</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                
                <hr>
                
                <div class="text-center">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function checkPasswordStrength() {
            let password = document.getElementById('password').value;
            let strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            let width = (strength / 5) * 100;
            let color = '#dc3545';
            if (strength >= 3) color = '#ffc107';
            if (strength >= 4) color = '#28a745';
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = color;
        }
        
        function validateForm() {
            let password = document.getElementById('password').value;
            let confirm = document.getElementById('confirmPassword').value;
            
            if (password !== confirm) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters!');
                return false;
            }
            
            return true;
        }
        
        document.getElementById('roleSelect').addEventListener('change', function() {
            let roleInfo = document.getElementById('roleInfo');
            let role = this.value;
            
            let info = {
                'Receptionist': 'Can manage check-in/out, bookings, and customer registration',
                'Cashier': 'Can process payments and print receipts',
                'Manager': 'Can view reports, manage rooms, and oversee operations',
                'Admin': 'Full system access including user management'
            };
            
            roleInfo.innerHTML = info[role] || '';
        });
    </script>
</body>
</html>