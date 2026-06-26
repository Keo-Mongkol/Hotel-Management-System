<?php
require_once '../config/database.php';
if (!isLoggedIn()) redirect('../login.php');

$available_rooms = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    
    $stmt = $pdo->prepare("
        SELECT r.*, rt.type_name, rt.price_usd, rt.price_khr 
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.id 
        WHERE r.status = 'available' 
        AND r.id NOT IN (
            SELECT room_id FROM bookings 
            WHERE (check_in_date <= ? AND check_out_date >= ?)
            AND status IN ('confirmed', 'checked_in')
        )
    ");
    $stmt->execute([$check_out, $check_in]);
    $available_rooms = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Availability</title>
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
                    <h1 class="h2">Check Room Availability</h1>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Check-in Date</label>
                                <input type="date" name="check_in" class="form-control" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Check-out Date</label>
                                <input type="date" name="check_out" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Check
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if(!empty($available_rooms)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Available Rooms</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>Room No.</th><th>Type</th><th>Price (USD)</th><th>Price (KHR)</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($available_rooms as $room): ?>
                                    <tr>
                                        <td><?php echo $room['room_number']; ?></td>
                                        <td><?php echo $room['type_name']; ?></td>
                                        <td>$<?php echo number_format($room['price_usd'], 2); ?></td>
                                        <td><?php echo number_format($room['price_khr']); ?> KHR</td>
                                        <td>
                                            <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-book"></i> Book Now
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php elseif($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <div class="alert alert-warning mt-4">No rooms available for selected dates.</div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>