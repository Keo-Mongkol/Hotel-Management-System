<?php
require_once '../config/database.php';
if (!isLoggedIn()) redirect('../login.php');

if (isset($_GET['action']) && $_GET['action'] === 'checkin' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    if ($booking) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
        $stmt->execute([$id]);
        $roomUpdate = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
        $roomUpdate->execute([$booking['room_id']]);
        logActivity($_SESSION['user_id'], 'Check In', "Checked in booking ID: $id");
        redirect('checkin.php?msg=checked_in');
    }
}

$today = date('Y-m-d');
// Modified query to include pending bookings from website
$stmt = $pdo->prepare("
    SELECT b.*, c.full_name, r.room_number 
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE (b.status = 'confirmed' OR b.status = 'pending') 
    AND b.check_in_date <= ? 
    ORDER BY 
        CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END,
        b.check_in_date ASC
");
$stmt->execute([$today]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .website-booking {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .badge-website {
            background-color: #17a2b8;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Check In</h1>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] === 'checked_in'): ?>
                    <div class="alert alert-success alert-dismissible fade show">Guest checked in successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Booking</th>
                                        <th>Customer</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Source</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($bookings)): ?>
                                        <tr><td colspan="8" class="text-center">No upcoming check-ins.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach($bookings as $booking): 
                                        $is_website = ($booking['status'] == 'pending');
                                    ?>
                                    <tr class="<?php echo $is_website ? 'website-booking' : ''; ?>">
                                        <td><?php echo $booking['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['booking_no']); ?>
                                            <?php if($is_website): ?>
                                                <br><small class="badge badge-website"><i class="fas fa-globe"></i> Website</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_in_date']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_out_date']); ?></td>
                                        <td>
                                            <?php if($is_website): ?>
                                                <span class="badge bg-warning">Pending Confirmation</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Confirmed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($is_website): ?>
                                                <a href="confirm_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-check-double"></i> Confirm First
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=checkin&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Confirm guest check-in?');">
                                                    <i class="fas fa-sign-in-alt"></i> Check In
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