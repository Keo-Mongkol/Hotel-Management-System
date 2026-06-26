<?php
require_once '../config/database.php';
if (!isLoggedIn()) redirect('../login.php');

$error = '';
$exchangeRate = getExchangeRate();
$payments = $pdo->query("SELECT p.*, b.booking_no, c.full_name FROM payments p JOIN bookings b ON p.booking_id = b.id JOIN customers c ON b.customer_id = c.id ORDER BY p.payment_date DESC")->fetchAll();
$bookings = $pdo->query("SELECT b.*, c.full_name FROM bookings b JOIN customers c ON b.customer_id = c.id LEFT JOIN payments p ON b.id = p.booking_id WHERE p.id IS NULL AND b.status IN ('confirmed','checked_in','checked_out') ORDER BY b.booking_date DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $currency = $_POST['currency'] ?? 'USD';
    $amount = floatval($_POST['amount'] ?? 0);
    
    // Auto-generate transaction ID and reference number
    $transaction_id = 'TXN' . date('ymd') . rand(100, 999);
    $reference_no = 'REF' . date('ymd') . rand(100, 999);

    if (!$booking_id || !$payment_method || !$amount) {
        $error = 'Please select a booking, method, currency, and amount.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        if (!$booking) {
            $error = 'Selected booking not found.';
        } else {
            $amountUsd = $currency === 'USD' ? $amount : round($amount / $exchangeRate, 2);
            $amountKhr = $currency === 'KHR' ? $amount : round($amount * $exchangeRate, 2);
            $paymentNo = generateUniqueCode('PAY', 'payments', 'payment_no');
            $stmt = $pdo->prepare("INSERT INTO payments (payment_no, booking_id, user_id, amount_usd, amount_khr, payment_method, currency, exchange_rate, transaction_id, reference_no, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')");
            $stmt->execute([$paymentNo, $booking_id, $_SESSION['user_id'], $amountUsd, $amountKhr, $payment_method, $currency, $exchangeRate, $transaction_id, $reference_no]);
            logActivity($_SESSION['user_id'], 'Make Payment', "Payment $paymentNo for booking ID $booking_id");
            redirect('payment.php?msg=completed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment</title>
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
                    <h1 class="h2">Make Payment</h1>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] === 'completed'): ?>
                    <div class="alert alert-success alert-dismissible fade show">Payment recorded successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Booking</label>
                                <select name="booking_id" class="form-select" required>
                                    <option value="">Select booking</option>
                                    <?php foreach($bookings as $booking): ?>
                                    <option value="<?php echo $booking['id']; ?>"><?php echo htmlspecialchars($booking['booking_no'] . ' - ' . $booking['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="ABA Pay">ABA Pay</option>
                                    <option value="Wing">Wing</option>
                                    <option value="ACLEDA">ACLEDA</option>
                                    <option value="Cash">Cash</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-select">
                                    <option value="USD">USD</option>
                                    <option value="KHR">KHR</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" class="form-control" value="Auto-generated" disabled>
                                <small class="text-muted">Will be generated automatically</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reference No</label>
                                <input type="text" class="form-control" value="Auto-generated" disabled>
                                <small class="text-muted">Will be generated automatically</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Exchange Rate</label>
                                <input type="text" class="form-control" value="<?php echo $exchangeRate; ?>" disabled>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" name="save_payment" class="btn btn-primary"><i class="fas fa-credit-card"></i> Record Payment</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Payments</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>#</th><th>Payment No</th><th>Booking</th><th>Customer</th><th>Amount USD</th><th>Amount KHR</th><th>Method</th><th>Transaction ID</th><th>Reference No</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($payments)): ?>
                                        <tr><td colspan="10" class="text-center">No payments recorded.</php ?></td></tr>
                                    <?php endif; ?>
                                    <?php foreach($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_no']); ?></php ?>
                                        <td><?php echo htmlspecialchars($payment['booking_no']); ?></php ?>
                                        <td><?php echo htmlspecialchars($payment['full_name']); ?></php ?>
                                        <td>$<?php echo number_format($payment['amount_usd'], 2); ?></php ?>
                                        <td><?php echo number_format($payment['amount_khr']); ?> KHR</php ?>
                                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></php ?>
                                        <td><small><?php echo htmlspecialchars($payment['transaction_id']); ?></small></php ?>
                                        <td><small><?php echo htmlspecialchars($payment['reference_no']); ?></small></php ?>
                                        <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></php ?>
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