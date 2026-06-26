<?php
require_once '../config/database.php';
if (!isLoggedIn()) redirect('../login.php');

$error = '';
$customers = $pdo->query("SELECT * FROM customers ORDER BY full_name")->fetchAll();
$available_rooms = $pdo->query("SELECT r.*, rt.type_name, rt.price_usd, rt.price_khr FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.status = 'available' ORDER BY r.room_number")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_booking'])) {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $room_id = (int)($_POST['room_id'] ?? 0);
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $adults = (int)($_POST['adults'] ?? 1);
    $children = (int)($_POST['children'] ?? 0);

    if (!$customer_id || !$room_id || !$check_in || !$check_out) {
        $error = 'Please complete customer, room, and date fields.';
    } elseif ($check_in >= $check_out) {
        $error = 'Check-out date must be after check-in date.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND (check_in_date <= ? AND check_out_date >= ?) AND status IN ('confirmed', 'checked_in')");
        $stmt->execute([$room_id, $check_out, $check_in]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Selected room is not available for those dates.';
        } else {
            $nightCount = (new DateTime($check_in))->diff(new DateTime($check_out))->days;
            $roomStmt = $pdo->prepare("SELECT r.*, rt.price_usd, rt.price_khr FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
            $roomStmt->execute([$room_id]);
            $room = $roomStmt->fetch();
            if (!$room) {
                $error = 'Selected room was not found.';
            } else {
                $totalAmountUsd = $room['price_usd'] * $nightCount;
                $totalAmountKhr = $room['price_khr'] * $nightCount;
                $bookingNo = generateUniqueCode('BOOK', 'bookings', 'booking_no');
                $insert = $pdo->prepare("INSERT INTO bookings (booking_no, customer_id, room_id, user_id, check_in_date, check_out_date, adults, children, total_nights, room_price_usd, room_price_khr, total_amount_usd, total_amount_khr, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
                $insert->execute([$bookingNo, $customer_id, $room_id, $_SESSION['user_id'], $check_in, $check_out, $adults, $children, $nightCount, $room['price_usd'], $room['price_khr'], $totalAmountUsd, $totalAmountKhr]);
                $updateRoom = $pdo->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ?");
                $updateRoom->execute([$room_id]);
                logActivity($_SESSION['user_id'], 'Create Booking', "Created booking $bookingNo for customer ID $customer_id");
                redirect('booking.php?msg=added');
            }
        }
    }
}

$bookings = $pdo->query("SELECT b.*, c.full_name, r.room_number, rt.type_name FROM bookings b JOIN customers c ON b.customer_id = c.id JOIN rooms r ON b.room_id = r.id JOIN room_types rt ON r.room_type_id = rt.id ORDER BY b.booking_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room</title>
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
                    <h1 class="h2">Book Room</h1>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Booking created successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Customer</label>
                                <select name="customer_id" class="form-select" required>
                                    <option value="">Select customer</option>
                                    <?php foreach($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-select" required>
                                    <option value="">Select available room</option>
                                    <?php foreach($available_rooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['type_name'] . ' ($' . number_format($room['price_usd'],2) . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Check-in</label>
                                <input type="date" name="check_in" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Check-out</label>
                                <input type="date" name="check_out" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Adults</label>
                                <input type="number" name="adults" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Children</label>
                                <input type="number" name="children" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" name="save_booking" class="btn btn-success"><i class="fas fa-book"></i> Create Booking</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Bookings</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>#</th><th>Booking No</th><th>Customer</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo $booking['id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['booking_no']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_number'] . ' (' . $booking['type_name'] . ')'); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_in_date']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_out_date']); ?></td>
                                        <td><?php echo ucfirst($booking['status']); ?></td>
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
