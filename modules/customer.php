<?php
require_once '../config/database.php';
if (!isLoggedIn()) redirect('../login.php');

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], 'Delete Customer', "Deleted customer ID: $id");
    redirect('customer.php?msg=deleted');
}

$editing = false;
$customer = [
    'id' => '', 'full_name' => '', 'phone' => '', 'email' => '', 'passport_id' => '',
    'khmer_id' => '', 'nationality' => '', 'address' => '', 'emergency_contact' => '', 'emergency_phone' => ''
];

// Load for edit
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $editing = true;
        $customer = $row;
    }
}

// Save (insert/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer'])) {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $passport_id = $_POST['passport_id'] ?? '';
    $khmer_id = $_POST['khmer_id'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $address = $_POST['address'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';

    if ($id) {
        $stmt = $pdo->prepare("UPDATE customers SET full_name=?, phone=?, email=?, passport_id=?, khmer_id=?, nationality=?, address=?, emergency_contact=?, emergency_phone=? WHERE id = ?");
        $stmt->execute([$full_name,$phone,$email,$passport_id,$khmer_id,$nationality,$address,$emergency_contact,$emergency_phone,$id]);
        logActivity($_SESSION['user_id'], 'Update Customer', "Updated customer ID: $id");
        redirect('customer.php?msg=updated');
    } else {
        $code = generateUniqueCode('CUST', 'customers', 'customer_code');
        $stmt = $pdo->prepare("INSERT INTO customers (customer_code, full_name, phone, email, passport_id, khmer_id, nationality, address, emergency_contact, emergency_phone, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code,$full_name,$phone,$email,$passport_id,$khmer_id,$nationality,$address,$emergency_contact,$emergency_phone,$_SESSION['user_id']]);
        logActivity($_SESSION['user_id'], 'Add Customer', "Added customer: $full_name");
        redirect('customer.php?msg=added');
    }
}

// Fetch all customers
$stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>textarea{min-height:80px}</style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Customer Management</h1>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php
                        $messages = ['added'=>'Customer added.','updated'=>'Customer updated.','deleted'=>'Customer deleted.'];
                        echo $messages[$_GET['msg']] ?? 'Done.';
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($customer['id']); ?>">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($customer['full_name']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($customer['phone']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Passport ID</label>
                                <input type="text" name="passport_id" class="form-control" value="<?php echo htmlspecialchars($customer['passport_id']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Khmer ID</label>
                                <input type="text" name="khmer_id" class="form-control" value="<?php echo htmlspecialchars($customer['khmer_id']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?php echo htmlspecialchars($customer['nationality']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="form-control" value="<?php echo htmlspecialchars($customer['emergency_contact']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Phone</label>
                                <input type="text" name="emergency_phone" class="form-control" value="<?php echo htmlspecialchars($customer['emergency_phone']); ?>">
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" name="save_customer" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $editing ? 'Update' : 'Add'; ?> Customer
                                </button>
                                <?php if($editing): ?>
                                    <a href="customer.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Code</th>
                                        <th>Full Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Nationality</th>
                                        <th>Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($customers as $c): ?>
                                    <tr>
                                        <td><?php echo $c['id']; ?></td>
                                        <td><?php echo htmlspecialchars($c['customer_code']); ?></td>
                                        <td><?php echo htmlspecialchars($c['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($c['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($c['email']); ?></td>
                                        <td><?php echo htmlspecialchars($c['nationality']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this customer?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
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
