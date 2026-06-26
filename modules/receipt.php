<?php
require_once '../config/database.php';
if (!isLoggedIn()) redirect('../login.php');

$message = '';
$error = '';
$payments = $pdo->query("SELECT p.*, b.booking_no, c.full_name FROM payments p JOIN bookings b ON p.booking_id = b.id JOIN customers c ON b.customer_id = c.id LEFT JOIN receipts r ON p.id = r.payment_id WHERE r.id IS NULL ORDER BY p.payment_date DESC")->fetchAll();
$receipts = $pdo->query("SELECT r.*, b.booking_no, c.full_name FROM receipts r JOIN payments p ON r.payment_id = p.id JOIN bookings b ON p.booking_id = b.id JOIN customers c ON b.customer_id = c.id ORDER BY r.printed_at DESC")->fetchAll();

if (isset($_GET['action']) && $_GET['action'] === 'generate' && isset($_GET['payment_id'])) {
    $payment_id = (int)$_GET['payment_id'];
    
    // Check if receipt already exists for this payment
    $check = $pdo->prepare("SELECT id FROM receipts WHERE payment_id = ?");
    $check->execute([$payment_id]);
    if ($check->fetch()) {
        $error = 'Receipt already generated for this payment!';
    } else {
        $stmt = $pdo->prepare("SELECT p.*, b.booking_no, b.check_in_date, b.check_out_date, c.full_name AS customer_name FROM payments p JOIN bookings b ON p.booking_id = b.id JOIN customers c ON b.customer_id = c.id WHERE p.id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch();
        if ($payment) {
            $receiptNo = generateUniqueCode('RCPT', 'receipts', 'receipt_no');
            $receiptHtml = '<h2>Hotel Receipt</h2><p><strong>Receipt No:</strong> '.htmlspecialchars($receiptNo).'</p><p><strong>Booking No:</strong> '.htmlspecialchars($payment['booking_no']).'</p><p><strong>Customer:</strong> '.htmlspecialchars($payment['customer_name']).'</p><p><strong>Check-in:</strong> '.htmlspecialchars($payment['check_in_date']).'</p><p><strong>Check-out:</strong> '.htmlspecialchars($payment['check_out_date']).'</p><p><strong>Amount USD:</strong> $'.number_format($payment['amount_usd'], 2).'</p><p><strong>Amount KHR:</strong> '.number_format($payment['amount_khr']).' KHR</p><p><strong>Payment Method:</strong> '.htmlspecialchars($payment['payment_method']).'</p><p><strong>Transaction:</strong> '.htmlspecialchars($payment['transaction_id']).'</p>';
            $insert = $pdo->prepare("INSERT INTO receipts (receipt_no, booking_id, payment_id, user_id, receipt_data, receipt_html, language, printed_by) VALUES (?, ?, ?, ?, ?, ?, 'english', ?)");
            $insert->execute([$receiptNo, $payment['booking_id'], $payment_id, $_SESSION['user_id'], 'Receipt generated', $receiptHtml, $_SESSION['user_id']]);
            logActivity($_SESSION['user_id'], 'Generate Receipt', "Generated receipt $receiptNo for payment ID $payment_id");
            $message = 'Receipt generated successfully.';
            // Refresh data
            $payments = $pdo->query("SELECT p.*, b.booking_no, c.full_name FROM payments p JOIN bookings b ON p.booking_id = b.id JOIN customers c ON b.customer_id = c.id LEFT JOIN receipts r ON p.id = r.payment_id WHERE r.id IS NULL ORDER BY p.payment_date DESC")->fetchAll();
            $receipts = $pdo->query("SELECT r.*, b.booking_no, c.full_name FROM receipts r JOIN payments p ON r.payment_id = p.id JOIN bookings b ON p.booking_id = b.id JOIN customers c ON b.customer_id = c.id ORDER BY r.printed_at DESC")->fetchAll();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipts</title>
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
                    <h1 class="h2">Receipts</h1>
                </div>

                <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Create Receipt</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>#</th><th>Payment</th><th>Booking</th><th>Customer</th><th>Amount USD</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php if(empty($payments)): ?>
                                        <tr><td colspan="6" class="text-center">No payments available for receipt generation.<?php endif; ?>
                                    <?php foreach($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_no']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['booking_no']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['full_name']); ?></?php ?>
                                        <td>$<?php echo number_format($payment['amount_usd'], 2); ?></?php ?>
                                        <td><a href="?action=generate&payment_id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('Generate receipt for this payment?')">Generate</a></?php ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                             </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Generated Receipts</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>#</th><th>Receipt No</th><th>Booking</th><th>Customer</th><th>Printed At</th><th>View</th>?</thead>
                                <tbody>
                                    <?php if(empty($receipts)): ?>
                                        <tr><td colspan="6" class="text-center">No receipts generated yet.?</php ?>
                                    <?php endif; ?>
                                    <?php foreach($receipts as $receipt): ?>
                                    <tr>
                                        <td><?php echo $receipt['id']; ?></?php ?>
                                        <td><?php echo htmlspecialchars($receipt['receipt_no']); ?></?php ?>
                                        <td><?php echo htmlspecialchars($receipt['booking_no']); ?></?php ?>
                                        <td><?php echo htmlspecialchars($receipt['full_name']); ?></?php ?>
                                        <td><?php echo date('d/m/Y H:i', strtotime($receipt['printed_at'])); ?></?php ?>
                                        <td>
                                            <button class="btn btn-sm btn-info" type="button" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo $receipt['id']; ?>">
                                                View
                                            </button>
                                         </?php ?>
                                     ?>
                                    <div class="modal fade" id="receiptModal<?php echo $receipt['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Receipt <?php echo htmlspecialchars($receipt['receipt_no']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php echo $receipt['receipt_html']; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
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