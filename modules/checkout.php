<?php
require_once '../config/database.php';
if (!isLoggedIn()) redirect('../login.php');

if (isset($_GET['action']) && $_GET['action'] === 'checkout' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    if ($booking) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
        $stmt->execute([$id]);
        $roomUpdate = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $roomUpdate->execute([$booking['room_id']]);
        logActivity($_SESSION['user_id'], 'Check Out', "Checked out booking ID: $id");
        redirect('checkout.php?msg=checked_out');
    }
}

$stmt = $pdo->query("SELECT b.*, c.full_name, r.room_number FROM bookings b JOIN customers c ON b.customer_id = c.id JOIN rooms r ON b.room_id = r.id WHERE b.status = 'checked_in' ORDER BY b.check_out_date ASC");
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Out</title>
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
                    <h1 class="h2">Check Out</h1>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] === 'checked_out'): ?>
                    <div class="alert alert-success alert-dismissible fade show">Guest checked out successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>#</th><th>Booking</th><th>Customer</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($bookings)): ?>
                                        <tr><td colspan="7" class="text-center">No guests currently checked in.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo $booking['id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['booking_no']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_in_date']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_out_date']); ?></td>
                                        <td><a href="?action=checkout&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Confirm check-out?');"><i class="fas fa-sign-out-alt"></i> Check Out</a></td>
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
