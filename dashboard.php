<?php
require_once 'config/database.php';

// Debug: Check session status
error_log("Dashboard - Session ID: " . session_id());
error_log("Dashboard - Session data: " . print_r($_SESSION, true));
error_log("Dashboard - isLoggedIn: " . (isLoggedIn() ? 'true' : 'false'));

if (!isLoggedIn()) redirect('login.php');

// Get statistics
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms WHERE status = 'available'");
$stats['available_rooms'] = $stmt->fetch()['total_rooms'];

$stmt = $pdo->query("SELECT COUNT(*) as today_checkins FROM bookings WHERE check_in_date = CURDATE()");
$stats['today_checkins'] = $stmt->fetch()['today_checkins'];

$stmt = $pdo->query("SELECT COUNT(*) as today_checkouts FROM bookings WHERE check_out_date = CURDATE()");
$stats['today_checkouts'] = $stmt->fetch()['today_checkouts'];

$stmt = $pdo->query("SELECT SUM(amount_usd) as total_revenue FROM payments WHERE DATE(payment_date) = CURDATE()");
$stats['today_revenue'] = $stmt->fetch()['total_revenue'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card" style="background: linear-gradient(135deg, #00bcd4 0%, #00838f 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <h3><?php echo $stats['available_rooms']; ?></h3>
                                <p>Available Rooms</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card" style="background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h3><?php echo $stats['today_checkins']; ?></h3>
                                <p>Today's Check-ins</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-user-minus"></i>
                                </div>
                                <h3><?php echo $stats['today_checkouts']; ?></h3>
                                <p>Today's Check-outs</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <h3>$<?php echo number_format($stats['today_revenue'], 2); ?></h3>
                                <p>Today's Revenue (USD)</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Quick Functions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <a href="modules/availability.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-search"></i> Check Availability
                                        </a>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <a href="modules/booking.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-book"></i> Book Room
                                        </a>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <a href="modules/checkin.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-sign-in-alt"></i> Check In
                                        </a>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <a href="modules/checkout.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-sign-out-alt"></i> Check Out
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Bookings</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr><th>Booking #</th><th>Customer</th><th>Check In</th><th>Status</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $pdo->query("SELECT b.*, c.full_name FROM bookings b JOIN customers c ON b.customer_id = c.id ORDER BY b.booking_date DESC LIMIT 5");
                                            while($row = $stmt->fetch()):
                                            ?>
                                            <tr>
                                                <td><?php echo $row['booking_no']; ?></td>
                                                <td><?php echo $row['full_name']; ?></td>
                                                <td><?php echo $row['check_in_date']; ?></td>
                                                <td><span class="badge bg-<?php echo $row['status'] == 'confirmed' ? 'success' : 'warning'; ?>"><?php echo $row['status']; ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>