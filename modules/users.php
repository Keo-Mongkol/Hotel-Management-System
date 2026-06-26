<?php
require_once '../config/database.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// Handle user status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($_GET['action'] == 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($_SESSION['user_id'], 'Approve User', "Approved user ID: $id");
        redirect('users.php?msg=approved');
    } elseif ($_GET['action'] == 'suspend') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($_SESSION['user_id'], 'Suspend User', "Suspended user ID: $id");
        redirect('users.php?msg=suspended');
    } elseif ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'Admin'");
        $stmt->execute([$id]);
        logActivity($_SESSION['user_id'], 'Delete User', "Deleted user ID: $id");
        redirect('users.php?msg=deleted');
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">User Management</h1>
                    <div class="btn-toolbar">
                        <a href="../register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                    </div>
                </div>
                
                <?php if(isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        $messages = [
                            'approved' => 'User approved successfully!',
                            'suspended' => 'User suspended successfully!',
                            'deleted' => 'User deleted successfully!'
                        ];
                        echo $messages[$_GET['msg']];
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $user['role'] == 'Admin' ? 'danger' : 
                                                    ($user['role'] == 'Manager' ? 'warning' : 
                                                    ($user['role'] == 'Receptionist' ? 'info' : 'secondary')); 
                                            ?>">
                                                <?php echo $user['role']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : ($user['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if($user['status'] == 'pending'): ?>
                                                <a href="?action=approve&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this user?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if($user['status'] == 'active' && $user['role'] != 'Admin'): ?>
                                                <a href="?action=suspend&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Suspend this user?')">
                                                    <i class="fas fa-ban"></i> Suspend
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if($user['role'] != 'Admin'): ?>
                                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user permanently?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>