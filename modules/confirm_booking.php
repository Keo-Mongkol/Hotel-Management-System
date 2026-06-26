<?php
// modules/confirm_booking.php
require_once '../config/database.php';
if (!isLoggedIn()) redirect('../login.php');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, c.full_name, r.room_number 
        FROM bookings b 
        JOIN customers c ON b.customer_id = c.id 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.id = ? AND b.status = 'pending'
    ");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['confirm'])) {
            // Confirm booking
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ?");
            $stmt->execute([$booking['room_id']]);
            logActivity($_SESSION['user_id'], 'Confirm Booking', "Confirmed booking ID: $id");
            redirect('checkin.php?msg=confirmed');
        } elseif (isset($_POST['cancel'])) {
            // Cancel booking
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$id]);
            redirect('checkin.php?msg=cancelled');
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Website Booking</title>
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
                        <h1 class="h2">Confirm Website Booking</h1>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h5><i class="fas fa-globe"></i> Website Booking</h5>
                        </div>
                        <div class="card-body">
                            <?php if($booking): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> This booking was made from the hotel website and needs confirmation.
                                </div>
                                
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Booking Number</th>
                                        <td><?php echo htmlspecialchars($booking['booking_no']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Guest Name</th>
                                        <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Room Number</th>
                                        <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Check In Date</th>
                                        <td><?php echo htmlspecialchars($booking['check_in_date']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Check Out Date</th>
                                        <td><?php echo htmlspecialchars($booking['check_out_date']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Total Amount</th>
                                        <td>$<?php echo number_format($booking['total_amount_usd'], 2); ?> USD</td>
                                    </tr>
                                </table>
                                
                                <form method="POST" class="mt-3">
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="confirm" class="btn btn-success" onclick="return confirm('Confirm this booking?')">
                                            <i class="fas fa-check"></i> Confirm Booking
                                        </button>
                                        <button type="submit" name="cancel" class="btn btn-danger" onclick="return confirm('Cancel this booking?')">
                                            <i class="fas fa-times"></i> Cancel Booking
                                        </button>
                                        <a href="checkin.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back
                                        </a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-danger">Booking not found or already confirmed.</div>
                                <a href="checkin.php" class="btn btn-primary">Back to Check In</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
} else {
    redirect('checkin.php');
}
?>