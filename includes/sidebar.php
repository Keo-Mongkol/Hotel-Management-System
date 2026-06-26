<?php
$prefix = strpos($_SERVER['PHP_SELF'], '/modules/') !== false ? '../' : '';
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $prefix . 'dashboard.php'; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/availability.php'; ?>">
                    <i class="fas fa-search"></i> Check Availability
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/booking.php'; ?>">
                    <i class="fas fa-book"></i> Book Room
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/customer.php'; ?>">
                    <i class="fas fa-user"></i> Register Customer
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/checkin.php'; ?>">
                    <i class="fas fa-sign-in-alt"></i> Check In
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/checkout.php'; ?>">
                    <i class="fas fa-sign-out-alt"></i> Check Out
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/payment.php'; ?>">
                    <i class="fas fa-credit-card"></i> Make Payment
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/receipt.php'; ?>">
                    <i class="fas fa-receipt"></i> Print Receipt
                </a>
            </li>
            <?php if(isManager()): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/report.php'; ?>">
                    <i class="fas fa-chart-line"></i> View Report
                </a>
            </li>
            <?php endif; ?>
            <?php if(isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/rooms.php'; ?>">
                    <i class="fas fa-door-open"></i> Manage Rooms
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $prefix . 'modules/users.php'; ?>">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-4">
                <div class="border-top pt-3">
                    <div class="text-center text-muted small">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?><br>
                        <span class="badge bg-secondary"><?php echo $_SESSION['user_role']; ?></span>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>