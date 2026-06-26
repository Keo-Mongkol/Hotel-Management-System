<?php
// guest_booking.php - Complete Guest Booking Page
session_start();
require_once 'config/database.php';

// Initialize variables
$error = null;
$success = null;
$available_rooms = [];
$selected_room_type = null;
$check_in = null;
$check_out = null;
$guests = 1;

// Get available room types with their rooms
try {
    // First get all available room types
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            rt.*,
            COUNT(r.id) as total_rooms,
            SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_rooms_count
        FROM room_types rt
        LEFT JOIN rooms r ON rt.id = r.room_type_id
        WHERE rt.status = 'active'
        GROUP BY rt.id
        ORDER BY rt.price_usd ASC
    ");
    $stmt->execute();
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Unable to load room information.";
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    // Get and validate form data
    $room_type_id = filter_input(INPUT_POST, 'room_type_id', FILTER_VALIDATE_INT);
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $adults = filter_input(INPUT_POST, 'adults', FILTER_VALIDATE_INT) ?: 1;
    $children = filter_input(INPUT_POST, 'children', FILTER_VALIDATE_INT) ?: 0;
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passport_id = trim($_POST['passport_id'] ?? '');
    $khmer_id = trim($_POST['khmer_id'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $emergency_phone = trim($_POST['emergency_phone'] ?? '');
    $special_requests = trim($_POST['special_requests'] ?? '');
    
    // Validate dates
    if (empty($check_in) || empty($check_out)) {
        $error = "Please select check-in and check-out dates.";
    } elseif (strtotime($check_in) < strtotime(date('Y-m-d'))) {
        $error = "Check-in date cannot be in the past.";
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error = "Check-out date must be after check-in date.";
    }
    
    // Validate customer info
    if (empty($full_name)) {
        $error = "Please enter your full name.";
    } elseif (empty($phone)) {
        $error = "Please enter your phone number.";
    } elseif (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    
    // If no errors, proceed with booking
    if (!$error) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Calculate total nights
            $datetime1 = new DateTime($check_in);
            $datetime2 = new DateTime($check_out);
            $total_nights = $datetime1->diff($datetime2)->days;
            
            // Get room type details
            $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ? AND status = 'active'");
            $stmt->execute([$room_type_id]);
            $room_type = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$room_type) {
                throw new Exception("Invalid room type selected.");
            }
            
            // Find an available room of this type
            $stmt = $pdo->prepare("
                SELECT r.* 
                FROM rooms r
                LEFT JOIN bookings b ON r.id = b.room_id 
                    AND b.status NOT IN ('cancelled', 'checked_out')
                    AND (
                        (b.check_in_date <= ? AND b.check_out_date >= ?) OR
                        (b.check_in_date <= ? AND b.check_out_date >= ?) OR
                        (b.check_in_date >= ? AND b.check_out_date <= ?)
                    )
                WHERE r.room_type_id = ? 
                    AND r.status = 'available'
                    AND b.id IS NULL
                LIMIT 1
            ");
            $stmt->execute([$check_out, $check_in, $check_in, $check_in, $check_in, $check_out, $room_type_id]);
            $available_room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$available_room) {
                throw new Exception("No rooms available for the selected dates. Please try different dates.");
            }
            
            // Check if customer exists or create new
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? OR phone = ?");
            $stmt->execute([$email, $phone]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                $customer_id = $customer['id'];
                // Update customer information
                $stmt = $pdo->prepare("
                    UPDATE customers 
                    SET full_name = ?, phone = ?, passport_id = ?, khmer_id = ?, 
                        nationality = ?, address = ?, emergency_contact = ?, emergency_phone = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $full_name, $phone, $passport_id, $khmer_id, 
                    $nationality, $address, $emergency_contact, $emergency_phone,
                    $customer_id
                ]);
            } else {
                // Generate customer code
                $customer_code = 'CUST' . date('Ymd') . rand(1000, 9999);
                $stmt = $pdo->prepare("
                    INSERT INTO customers (customer_code, full_name, phone, email, passport_id, khmer_id, nationality, address, emergency_contact, emergency_phone)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $customer_code, $full_name, $phone, $email, $passport_id, 
                    $khmer_id, $nationality, $address, $emergency_contact, $emergency_phone
                ]);
                $customer_id = $pdo->lastInsertId();
            }
            
            // Generate booking number
            $booking_no = 'BK' . date('Ymd') . rand(10000, 99999);
            
            // Calculate total amount
            $total_amount_usd = $room_type['price_usd'] * $total_nights;
            $total_amount_khr = $room_type['price_khr'] * $total_nights;
            
            // Create booking
            $stmt = $pdo->prepare("
                INSERT INTO bookings (
                    booking_no, customer_id, room_id, check_in_date, check_out_date,
                    adults, children, total_nights, room_price_usd, room_price_khr,
                    total_amount_usd, total_amount_khr, special_requests, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $booking_no, $customer_id, $available_room['id'], $check_in, $check_out,
                $adults, $children, $total_nights, $room_type['price_usd'], $room_type['price_khr'],
                $total_amount_usd, $total_amount_khr, $special_requests
            ]);
            
            $booking_id = $pdo->lastInsertId();
            
            // Update room status to reserved
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ?");
            $stmt->execute([$available_room['id']]);
            
            // Commit transaction
            $pdo->commit();
            
            // Store booking info in session for confirmation
            $_SESSION['last_booking'] = [
                'booking_no' => $booking_no,
                'customer_name' => $full_name,
                'room_type' => $room_type['type_name'],
                'room_number' => $available_room['room_number'],
                'check_in' => $check_in,
                'check_out' => $check_out,
                'total_nights' => $total_nights,
                'total_amount' => $total_amount_usd,
                'email' => $email
            ];
            
            $success = true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error occurred. Please try again later.";
        }
    }
}

// Handle check availability AJAX request
if (isset($_GET['check_availability']) && isset($_GET['room_type_id']) && isset($_GET['check_in']) && isset($_GET['check_out'])) {
    header('Content-Type: application/json');
    try {
        $room_type_id = filter_input(INPUT_GET, 'room_type_id', FILTER_VALIDATE_INT);
        $check_in = $_GET['check_in'];
        $check_out = $_GET['check_out'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(r.id) as available_count
            FROM rooms r
            LEFT JOIN bookings b ON r.id = b.room_id 
                AND b.status NOT IN ('cancelled', 'checked_out')
                AND (
                    (b.check_in_date <= ? AND b.check_out_date >= ?) OR
                    (b.check_in_date <= ? AND b.check_out_date >= ?) OR
                    (b.check_in_date >= ? AND b.check_out_date <= ?)
                )
            WHERE r.room_type_id = ? 
                AND r.status = 'available'
                AND b.id IS NULL
        ");
        $stmt->execute([$check_out, $check_in, $check_in, $check_in, $check_in, $check_out, $room_type_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['available' => $result['available_count'] > 0, 'count' => $result['available_count']]);
    } catch (Exception $e) {
        echo json_encode(['available' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Stay - Luxury Hotel Cambodia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
        }
        
        /* Navbar */
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: #2c3e50 !important;
        }
        
        .nav-link {
            font-weight: 500;
            color: #333 !important;
            transition: all 0.3s;
            margin: 0 10px;
        }
        
        .nav-link:hover {
            color: #3498db !important;
        }
        
        /* Booking Section */
        .booking-header {
            background-color: #2c3e50;
            color: white;
            padding: 60px 0;
            margin-top: 70px;
        }
        
        .booking-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-top: -30px;
            margin-bottom: 50px;
            overflow: hidden;
        }
        
        .booking-form {
            padding: 40px;
        }
        
        .room-selector {
            background: #f8f9fa;
            padding: 30px;
            border-bottom: 3px solid #3498db;
        }
        
        .room-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
        }
        
        .room-card.selected {
            border-color: #3498db;
            background: #f0f8ff;
        }
        
        .room-card input[type="radio"] {
            display: none;
        }
        
        .room-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3498db;
        }
        
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        
        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            color: #2c3e50;
        }
        
        .btn-book {
            background: #3498db;
            color: white;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            transition: all 0.3s;
        }
        
        .btn-book:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.4);
        }
        
        .availability-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .availability-available {
            background: #d4edda;
            color: #155724;
        }
        
        .availability-unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        
        .success-animation {
            text-align: center;
            padding: 50px;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer a {
            color: #ccc;
            text-decoration: none;
        }
        
        .footer a:hover {
            color: #3498db;
        }
        
        @media (max-width: 768px) {
            .booking-form {
                padding: 20px;
            }
            .room-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-hotel"></i> Luxury Hotel Cambodia
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="guest_rooms.php">Rooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="services.php">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="guest_booking.php" class="btn btn-primary-custom">
                        <i class="fas fa-book"></i> Book
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-primary-custom" href="login.php" style="padding: 8px 20px;">
                        <i class="fas fa-user"></i>Login 
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Booking Header -->
<section class="booking-header">
    <div class="container text-center">
        <h1 class="display-4 fw-bold">Book Your Stay</h1>
        <p class="lead">Experience luxury and comfort at the best rates</p>
    </div>
</section>

<div class="container">
    <div class="booking-container">
        <?php if ($success === true): ?>
            <!-- Success Message -->
            <div class="success-animation">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="mb-3">Booking Confirmed!</h2>
                <p class="lead">Your booking has been successfully created.</p>
                <div class="alert alert-success mt-4">
                    <h4>Booking Details:</h4>
                    <p><strong>Booking Number:</strong> <?php echo $_SESSION['last_booking']['booking_no']; ?></p>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($_SESSION['last_booking']['customer_name']); ?></p>
                    <p><strong>Room:</strong> <?php echo htmlspecialchars($_SESSION['last_booking']['room_type']); ?> (Room <?php echo $_SESSION['last_booking']['room_number']; ?>)</p>
                    <p><strong>Check-in:</strong> <?php echo date('F d, Y', strtotime($_SESSION['last_booking']['check_in'])); ?></p>
                    <p><strong>Check-out:</strong> <?php echo date('F d, Y', strtotime($_SESSION['last_booking']['check_out'])); ?></p>
                    <p><strong>Total Nights:</strong> <?php echo $_SESSION['last_booking']['total_nights']; ?></p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($_SESSION['last_booking']['total_amount'], 2); ?> USD</p>
                </div>
                <p class="mt-3">A confirmation email has been sent to <?php echo htmlspecialchars($_SESSION['last_booking']['email']); ?></p>
                <a href="index.php" class="btn btn-primary-custom me-2">
                    <i class="fas fa-home"></i> Return Home
                </a>
                <a href="guest_booking.php" class="btn btn-outline-custom">
                    <i class="fas fa-plus"></i> New Booking
                </a>
            </div>
        <?php else: ?>
            <!-- Booking Form -->
            <form method="POST" action="" id="bookingForm">
                <!-- Room Selection -->
                <div class="room-selector">
                    <h3 class="mb-4">1. Select Room Type</h3>
                    <div class="row">
                        <?php foreach ($room_types as $room_type): ?>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="room-card card h-100 <?php echo ($selected_room_type == $room_type['id']) ? 'selected' : ''; ?>" onclick="selectRoom(<?php echo $room_type['id']; ?>)">
                                <div class="card-body">
                                    <input type="radio" name="room_type_id" value="<?php echo $room_type['id']; ?>" 
                                           id="room_<?php echo $room_type['id']; ?>" 
                                           <?php echo ($selected_room_type == $room_type['id']) ? 'checked' : ''; ?> required>
                                    <h5 class="card-title"><?php echo htmlspecialchars($room_type['type_name']); ?></h5>
                                    <p class="card-text small"><?php echo htmlspecialchars(substr($room_type['description'], 0, 80)); ?>...</p>
                                    <div class="room-price">$<?php echo number_format($room_type['price_usd'], 2); ?></div>
                                    <small class="text-muted">per night</small>
                                    <div class="mt-2">
                                        <span class="availability-badge <?php echo $room_type['available_rooms_count'] > 0 ? 'availability-available' : 'availability-unavailable'; ?>">
                                            <i class="fas <?php echo $room_type['available_rooms_count'] > 0 ? 'fa-check' : 'fa-times'; ?>"></i>
                                            <?php echo $room_type['available_rooms_count']; ?> rooms available
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="booking-form">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Date Selection -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-calendar-alt"></i> 2. Select Dates
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Check-in Date *</label>
                                <input type="date" name="check_in" id="check_in" class="form-control" 
                                       value="<?php echo htmlspecialchars($check_in); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Check-out Date *</label>
                                <input type="date" name="check_out" id="check_out" class="form-control" 
                                       value="<?php echo htmlspecialchars($check_out); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Adults *</label>
                                <select name="adults" class="form-select">
                                    <?php for($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($guests == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Adult<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Children</label>
                                <select name="children" class="form-select">
                                    <?php for($i = 0; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> Child<?php echo $i != 1 ? 'ren' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Guest Information -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user"></i> 3. Guest Information
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email Address *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nationality</label>
                                <input type="text" name="nationality" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['nationality'] ?? ''); ?>" placeholder="Cambodian">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Passport ID</label>
                                <input type="text" name="passport_id" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['passport_id'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Khmer ID</label>
                                <input type="text" name="khmer_id" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['khmer_id'] ?? ''); ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Emergency Contact -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-ambulance"></i> 4. Emergency Contact
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Emergency Contact Phone</label>
                                <input type="text" name="emergency_phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Special Requests -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-clipboard-list"></i> 5. Special Requests (Optional)
                        </div>
                        <textarea name="special_requests" class="form-control" rows="3" 
                                  placeholder="Any special requests or additional information..."><?php echo htmlspecialchars($_POST['special_requests'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="text-center">
                        <button type="submit" name="book_room" class="btn btn-book">
                            <i class="fas fa-check-circle"></i> Confirm Booking
                        </button>
                        <p class="text-muted mt-3 small">
                            <i class="fas fa-lock"></i> Your information is secure and will only be used for booking purposes.
                        </p>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h4 class="mb-3"><i class="fas fa-hotel"></i> Luxury Hotel Cambodia</h4>
                <p>Experience luxury, comfort, and exceptional service in the heart of Cambodia.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="guest_booking.php">Book Now</a></li>
                    <li><a href="guest_rooms.php">Rooms</a></li>
                    <li><a href="services.php#services">Services</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Support</h5>
                <ul class="list-unstyled">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                    <li><a href="#">Cancellation Policy</a></li>
                </ul>
            </div>
            <div class="col-lg-3 mb-4">
                <h5>Contact</h5>
                <p><i class="fas fa-phone"></i> +855 23 123 456</p>
                <p><i class="fas fa-envelope"></i> reservations@luxuryhotel.com</p>
            </div>
        </div>
        <hr class="mt-3" style="border-color: rgba(255,255,255,0.1);">
        <div class="text-center pt-3">
            <p class="mb-0">&copy; 2024 Luxury Hotel Cambodia. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Room selection
    function selectRoom(roomTypeId) {
        document.querySelectorAll('.room-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.querySelector(`.room-card:has(input[value="${roomTypeId}"])`).classList.add('selected');
        document.getElementById(`room_${roomTypeId}`).checked = true;
    }
    
    // Date validation
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('check_in').min = today;
    document.getElementById('check_out').min = today;
    
    document.getElementById('check_in').addEventListener('change', function() {
        document.getElementById('check_out').min = this.value;
        if (document.getElementById('check_out').value && document.getElementById('check_out').value <= this.value) {
            document.getElementById('check_out').value = '';
        }
    });
    
    // Auto-calculate nights (optional feature)
    function calculateNights() {
        const checkIn = document.getElementById('check_in').value;
        const checkOut = document.getElementById('check_out').value;
        if (checkIn && checkOut) {
            const start = new Date(checkIn);
            const end = new Date(checkOut);
            const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            if (nights > 0) {
                // Could display nights count somewhere
                console.log(`Total nights: ${nights}`);
            }
        }
    }
    
    document.getElementById('check_in').addEventListener('change', calculateNights);
    document.getElementById('check_out').addEventListener('change', calculateNights);
    
    // Form validation
    document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
        const roomSelected = document.querySelector('input[name="room_type_id"]:checked');
        if (!roomSelected) {
            e.preventDefault();
            alert('Please select a room type.');
            return false;
        }
        
        const checkIn = document.getElementById('check_in').value;
        const checkOut = document.getElementById('check_out').value;
        
        if (!checkIn || !checkOut) {
            e.preventDefault();
            alert('Please select check-in and check-out dates.');
            return false;
        }
        
        return true;
    });
</script>
</body>
</html>