<?php
require_once '../config/database.php';
if (!isLoggedIn() || !isManager()) redirect('../login.php');

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$report = ['total_bookings' => 0, 'total_checked_in' => 0, 'total_checked_out' => 0, 'total_cancelled' => 0, 'occupancy_rate' => 0, 'total_revenue_usd' => 0, 'total_revenue_khr' => 0];

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_bookings, SUM(status='checked_in') AS total_checked_in, SUM(status='checked_out') AS total_checked_out, SUM(status='cancelled') AS total_cancelled FROM bookings WHERE DATE(booking_date) = ?");
$stmt->execute([$selectedDate]);
$summary = $stmt->fetch();
if ($summary) {
    $report['total_bookings'] = $summary['total_bookings'];
    $report['total_checked_in'] = $summary['total_checked_in'];
    $report['total_checked_out'] = $summary['total_checked_out'];
    $report['total_cancelled'] = $summary['total_cancelled'];
}

$stmt = $pdo->prepare("SELECT SUM(amount_usd) AS total_revenue_usd, SUM(amount_khr) AS total_revenue_khr FROM payments WHERE DATE(payment_date) = ?");
$stmt->execute([$selectedDate]);
$revenue = $stmt->fetch();
$report['total_revenue_usd'] = $revenue['total_revenue_usd'] ?? 0;
$report['total_revenue_khr'] = $revenue['total_revenue_khr'] ?? 0;

$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupiedRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
$report['occupancy_rate'] = $totalRooms ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report</title>
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
                    <h1 class="h2">Daily Report</h1>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Report Date</label>
                                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">View</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5>Total Bookings</h5>
                                <h3><?php echo $report['total_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5>Checked In</h5>
                                <h3><?php echo $report['total_checked_in']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5>Checked Out</h5>
                                <h3><?php echo $report['total_checked_out']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <h5>Cancelled</h5>
                                <h3><?php echo $report['total_cancelled']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5>Occupancy Rate</h5>
                                <h3><?php echo $report['occupancy_rate']; ?>%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body">
                                <h5>Revenue USD</h5>
                                <h3>$<?php echo number_format($report['total_revenue_usd'], 2); ?></h3>
                                <p class="mb-0">KHR <?php echo number_format($report['total_revenue_khr']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
