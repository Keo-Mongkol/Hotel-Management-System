<?php
// index.php - Complete Guest Home Page with Guest Booking Integration
session_start();
require_once 'config/database.php';

// Handle booking status check
$booking_info = null;
$check_error = null;
// index.php - Complete Guest Home Page with Guest Booking Integration
session_start();
require_once 'config/database.php';

// Handle booking status check
$booking_info = null;
$check_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_booking'])) {
    $booking_no = trim($_POST['booking_no']);
    $email = trim($_POST['email']);
    
    if (!empty($booking_no) && !empty($email)) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    b.*, 
                    c.full_name, 
                    c.email, 
                    c.phone,
                    r.room_number,
                    rt.type_name as room_type,
                    rt.price_usd as price_per_night
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                JOIN rooms r ON b.room_id = r.id
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE b.booking_no = ? AND c.email = ?
            ");
            $stmt->execute([$booking_no, $email]);
            $booking_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking_info) {
                $check_error = "No booking found with these details. Please check your booking number and email.";
            }
        } catch (PDOException $e) {
            $check_error = "Error checking booking. Please try again later.";
        }
    } else {
        $check_error = "Please enter both booking number and email.";
    }
}

// Get available rooms with their types for display
$available_rooms = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            rt.type_name,
            rt.description as room_description,
            rt.price_usd,
            rt.price_khr,
            rt.capacity,
            rt.amenities,
            COUNT(DISTINCT r.id) as total_rooms,
            SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_count
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE rt.status = 'active'
        GROUP BY rt.id
        ORDER BY rt.price_usd ASC
    ");
    $stmt->execute();
    $available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error silently
}

// Get some statistics for the homepage
$stats = [];
try {
    // Total rooms
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
    $stats['available_rooms'] = $stmt->fetch()['total'];
    
    // Total room types
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM room_types WHERE status = 'active'");
    $stats['room_types'] = $stmt->fetch()['total'];
    
    // Happy customers (total unique customers)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
    $stats['customers'] = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $stats = ['available_rooms' => 0, 'room_types' => 0, 'customers' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Hotel Cambodia - Your Perfect Stay</title>
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
        
        /* Hero Section */
        .hero {
            background-color: #2c3e50;
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80') center/cover;
            opacity: 0.3;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }
        
        /* Stats Counter */
        .stats-section {
            background: white;
            padding: 60px 0;
            margin-top: -40px;
            position: relative;
            z-index: 2;
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #3498db;
        }
        
        .stat-label {
            color: #666;
            margin-top: 10px;
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
        
        .btn-book-nav {
            background: #3498db;
            color: white !important;
            padding: 8px 25px !important;
            border-radius: 25px;
        }
        
        .btn-book-nav:hover {
            background: #2980b9;
            color: white !important;
        }
        
        /* Room Cards */
        .room-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .room-image {
            height: 250px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .room-price {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .room-availability {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .room-info {
            padding: 20px;
        }
        
        .room-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }
        
        .room-features {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        
        .room-features li {
            display: inline-block;
            margin-right: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .room-features i {
            color: #3498db;
            margin-right: 5px;
        }
        
        /* Services Section */
        .services {
            background: white;
            padding: 80px 0;
        }
        
        .service-item {
            text-align: center;
            padding: 30px;
            transition: all 0.3s;
            border-radius: 15px;
        }
        
        .service-item:hover {
            background: #f8f9fa;
            transform: translateY(-5px);
        }
        
        .service-icon {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 20px;
        }
        
        .service-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        /* Booking Check Section */
        .booking-check {
            background: #ecf0f1;
            padding: 60px 0;
        }
        
        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            color: #333;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        /* CTA Section */
        .cta-section {
            background: #3498db;
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .btn-cta {
            background: white;
            color: #3498db;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: #3498db;
        }
        
        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer a {
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer a:hover {
            color: #3498db;
        }
        
        .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background: #3498db;
            transform: translateY(-3px);
        }
        
        /* Buttons */
        .btn-primary-custom {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-custom:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.4);
            color: white;
        }
        
        .btn-outline-custom {
            border: 2px solid #3498db;
            background: transparent;
            color: #3498db;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-outline-custom:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        /* Animations */
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
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
        }
        
        .badge-custom {
            background-color: #3498db;
        }
        
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .testimonial-text {
            font-style: italic;
            color: #666;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: #3498db;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-hotel"></i> Luxury Hotel Cambodia
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#rooms">Rooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#services">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-book-nav" href="guest_booking.php">
                        <i class="fas fa-book"></i> Book
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-custom" href="login.php" style="padding: 8px 20px;">
                        <i class="fas fa-user"></i> Login
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section id="home" class="hero" style="margin-top: 70px;">
    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-6 fade-in-up">
                <h1>Welcome to Luxury Hotel Cambodia</h1>
                <p>Experience the perfect blend of luxury, comfort, and Cambodian hospitality. Book your dream stay with us today!</p>
                <div>
                    <a href="guest_booking.php" class="btn btn-primary-custom btn-lg me-3">
                        <i class="fas fa-calendar-check"></i> Book Now
                    </a>
                    <a href="#booking-check" class="btn btn-outline-custom btn-lg">
                        <i class="fas fa-search"></i> Check Booking
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="booking-card">
                    <h4 class="mb-3">Quick Availability Check</h4>
                    <form action="guest_booking.php" method="GET">
                        <div class="mb-3">
                            <label class="form-label">Check In Date</label>
                            <input type="date" name="check_in" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Check Out Date</label>
                            <input type="date" name="check_out" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guests</label>
                            <select name="adults" class="form-select" required>
                                <option value="1">1 Guest</option>
                                <option value="2">2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                                <option value="5">5 Guests</option>
                                <option value="6">6 Guests</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search"></i> Check Availability
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['room_types']; ?></div>
                    <div class="stat-label">Room Types</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['available_rooms']; ?></div>
                    <div class="stat-label">Available Rooms</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($stats['customers']); ?>+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Rooms Section -->
<section id="rooms" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-3">Our Luxury Rooms</h2>
            <p class="lead text-muted">Experience comfort and elegance in every room</p>
        </div>
        <div class="row">
            <?php
            if (count($available_rooms) > 0) {
                $room_type_images = [
                    'Standard' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                    'VIP' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                    'Family' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                    'Suite' => 'https://www.admiralhotelmanila.com/wp-content/uploads/sites/224/2021/11/Executive-Suite.jpg'
                ];
                
                foreach($available_rooms as $room):
                    $image_url = $room_type_images[$room['type_name']] ?? $room_type_images['Standard'];
                    $amenities_array = explode(',', $room['amenities']);
                    $amenities_display = array_slice($amenities_array, 0, 3);
                    $available_count = $room['available_count'] ?? 0;
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="room-card">
                    <div class="room-image" style="background-image: url('<?php echo $image_url; ?>');">
                        <div class="room-price">
                            $<?php echo number_format($room['price_usd'], 2); ?> / night
                        </div>
                        <div class="room-availability">
                            <i class="fas fa-bed"></i> <?php echo $available_count; ?> rooms available
                        </div>
                    </div>
                    <div class="room-info">
                        <h3 class="room-title"><?php echo htmlspecialchars($room['type_name']); ?> Room</h3>
                        <p>Max <?php echo htmlspecialchars($room['capacity']); ?> guests</p>
                        <ul class="room-features">
                            <?php foreach($amenities_display as $amenity): ?>
                                <?php if(trim($amenity)): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($amenity)); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <div class="amenities-list">
                            <small class="text-muted">
                                <?php echo htmlspecialchars(substr($room['room_description'], 0, 100)); ?>...
                            </small>
                        </div>
                        <a href="guest_booking.php?room_type=<?php echo urlencode($room['type_name']); ?>" class="btn btn-primary-custom w-100 mt-3">
                            <i class="fas fa-calendar-check"></i> Book Now
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            } else {
                echo '<div class="col-12 text-center"><p>No rooms available at the moment. Please check back later.</p></div>';
            }
            ?>
        </div>
        <div class="text-center mt-4">
            <a href="guest_booking.php" class="btn btn-primary-custom btn-lg">
                <i class="fas fa-book"></i> View All Rooms & Book
            </a>
        </div>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="services">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-3">Our Premium Services</h2>
            <p class="lead text-muted">Making your stay unforgettable</p>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="service-title">Fine Dining</h3>
                    <p class="text-muted">Experience exquisite Cambodian and international cuisine prepared by world-class chefs.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h3 class="service-title">Spa & Wellness</h3>
                    <p class="text-muted">Relax and rejuvenate with traditional Khmer massage and modern spa treatments.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-swimmer"></i>
                    </div>
                    <h3 class="service-title">Swimming Pool</h3>
                    <p class="text-muted">Infinity pool with stunning views of the city skyline and sunset.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3 class="service-title">Fitness Center</h3>
                    <p class="text-muted">State-of-the-art gym equipment available 24/7 for our guests.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3 class="service-title">Airport Transfer</h3>
                    <p class="text-muted">Luxury car service for seamless airport pickup and drop-off.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h3 class="service-title">24/7 Concierge</h3>
                    <p class="text-muted">Our dedicated team is always ready to assist with any request.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready for an Unforgettable Experience?</h2>
        <p class="lead mb-4">Book your stay today and enjoy luxury at its finest</p>
        <a href="guest_booking.php" class="btn btn-cta">
            <i class="fas fa-calendar-check"></i> Book Your Stay Now
        </a>
    </div>
</section>

<!-- Booking Check Section -->
<section id="booking-check" class="booking-check">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <h2 class="display-4 fw-bold">Check Your Booking</h2>
                    <p class="lead">Enter your booking details to view status and information</p>
                </div>
                
                <div class="booking-card">
                    <?php if ($check_error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $check_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($booking_info): ?>
                        <div class="alert alert-success">
                            <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Booking Found!</h4>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-ticket-alt"></i> Booking Number:</strong><br> 
                                    <span class="h5"><?php echo htmlspecialchars($booking_info['booking_no']); ?></span></p>
                                    <p><strong><i class="fas fa-user"></i> Guest Name:</strong><br> 
                                    <?php echo htmlspecialchars($booking_info['full_name']); ?></p>
                                    <p><strong><i class="fas fa-envelope"></i> Email:</strong><br> 
                                    <?php echo htmlspecialchars($booking_info['email']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-bed"></i> Room Type:</strong><br> 
                                    <?php echo htmlspecialchars($booking_info['room_type']); ?> (Room <?php echo htmlspecialchars($booking_info['room_number']); ?>)</p>
                                    <p><strong><i class="fas fa-calendar-alt"></i> Check In:</strong><br> 
                                    <?php echo date('F d, Y', strtotime($booking_info['check_in_date'])); ?></p>
                                    <p><strong><i class="fas fa-calendar-times"></i> Check Out:</strong><br> 
                                    <?php echo date('F d, Y', strtotime($booking_info['check_out_date'])); ?></p>
                                </div>
                                <div class="col-12">
                                    <hr>
                                    <p><strong><i class="fas fa-info-circle"></i> Booking Status:</strong><br>
                                    <span class="badge bg-<?php 
                                        echo $booking_info['status'] == 'confirmed' ? 'success' : 
                                             ($booking_info['status'] == 'pending' ? 'warning' : 
                                             ($booking_info['status'] == 'checked_in' ? 'info' : 
                                             ($booking_info['status'] == 'checked_out' ? 'secondary' : 'danger'))); 
                                    ?>">
                                        <?php echo strtoupper(str_replace('_', ' ', $booking_info['status'])); ?>
                                    </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Booking Number</label>
                                <input type="text" name="booking_no" class="form-control" 
                                       placeholder="e.g., BK-2024-00123" required>
                                <small class="text-muted">Enter your booking number</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control" 
                                       placeholder="your@email.com" required>
                                <small class="text-muted">Enter the email used when booking</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="check_booking" class="btn btn-primary-custom w-100">
                                    <i class="fas fa-search"></i> Check Booking Status
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5 bg-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" 
                     alt="Hotel Exterior" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6">
                <h2 class="display-4 fw-bold mb-4">About Our Hotel</h2>
                <p class="lead">Experience the best of Cambodian hospitality in the heart of the city.</p>
                <p>Located in the vibrant capital city, Luxury Hotel Cambodia offers world-class amenities, 
                   exceptional service, and unforgettable experiences. Whether you're here for business or leisure, 
                   our dedicated team ensures your stay is nothing short of perfect.</p>
                <div class="row mt-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-star text-warning fs-4 me-2"></i>
                            <div>
                                <h5 class="mb-0">5-Star Rating</h5>
                                <small class="text-muted">Luxury certified</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-trophy fs-4 me-2" style="color: #3498db;"></i>
                            <div>
                                <h5 class="mb-0">Award Winning</h5>
                                <small class="text-muted">Best Hotel 2024</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-3">What Our Guests Say</h2>
            <p class="lead text-muted">Real experiences from real customers</p>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <i class="fas fa-quote-left fa-2x" style="color: #3498db; opacity: 0.3;"></i>
                    <p class="testimonial-text mt-3">"Amazing hotel with excellent service! The staff was very friendly and helpful. The room was clean and comfortable. Will definitely come back!"</p>
                    <div class="testimonial-author">
                        <i class="fas fa-user-circle"></i> John Smith
                        <div><small class="text-muted">United States</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <i class="fas fa-quote-left fa-2x" style="color: #3498db; opacity: 0.3;"></i>
                    <p class="testimonial-text mt-3">"Beautiful hotel with great location. The pool is amazing and the breakfast buffet is fantastic. Highly recommended for anyone visiting Phnom Penh!"</p>
                    <div class="testimonial-author">
                        <i class="fas fa-user-circle"></i> Maria Garcia
                        <div><small class="text-muted">Spain</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <i class="fas fa-quote-left fa-2x" style="color: #3498db; opacity: 0.3;"></i>
                    <p class="testimonial-text mt-3">"Excellent value for money. The staff went above and beyond to make our stay special. The spa treatment was incredible!"</p>
                    <div class="testimonial-author">
                        <i class="fas fa-user-circle"></i> Chen Wei
                        <div><small class="text-muted">China</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-3">Contact Us</h2>
            <p class="lead text-muted">Get in touch with us for any