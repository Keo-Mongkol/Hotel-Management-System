<?php
// guest_rooms.php - Complete Guest Rooms Page
session_start();
require_once 'config/database.php';

// Get filter parameters
$selected_type = $_GET['room_type'] ?? '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 500;
$capacity = isset($_GET['capacity']) ? (int)$_GET['capacity'] : 0;
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';

// Build query conditions
$conditions = ["rt.status = 'active'"];
$params = [];

if ($selected_type) {
    $conditions[] = "rt.type_name = ?";
    $params[] = $selected_type;
}

if ($min_price > 0) {
    $conditions[] = "rt.price_usd >= ?";
    $params[] = $min_price;
}

if ($max_price < 500) {
    $conditions[] = "rt.price_usd <= ?";
    $params[] = $max_price;
}

if ($capacity > 0) {
    $conditions[] = "rt.capacity >= ?";
    $params[] = $capacity;
}

// Get all room types with availability
$where_clause = implode(" AND ", $conditions);
$query = "
    SELECT 
        rt.*,
        COUNT(DISTINCT r.id) as total_rooms,
        SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_count,
        GROUP_CONCAT(DISTINCT r.room_number ORDER BY r.room_number) as room_numbers
    FROM room_types rt
    LEFT JOIN rooms r ON rt.id = r.room_type_id
    WHERE $where_clause
    GROUP BY rt.id
    ORDER BY rt.price_usd ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique room types for filter
$stmt = $pdo->query("SELECT DISTINCT type_name FROM room_types WHERE status = 'active' ORDER BY type_name");
$all_room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get min and max prices
$stmt = $pdo->query("SELECT MIN(price_usd) as min_price, MAX(price_usd) as max_price FROM room_types WHERE status = 'active'");
$price_range = $stmt->fetch();
$min_available_price = $price_range['min_price'] ?? 0;
$max_available_price = $price_range['max_price'] ?? 500;

// Handle room type selection for booking
$selected_room_type_id = null;
if (isset($_GET['book_id'])) {
    $selected_room_type_id = (int)$_GET['book_id'];
    // Redirect to booking page with room type
    header("Location: guest_booking.php?room_type_id=" . $selected_room_type_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Rooms - Luxury Hotel Cambodia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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
        
        .btn-book-nav {
            background: #3498db;
            color: white !important;
            padding: 8px 25px !important;
            border-radius: 25px;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 100px 0 60px;
            margin-top: 70px;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        /* Filter Sidebar */
        .filter-sidebar {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 100px;
        }
        
        .filter-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .filter-group {
            margin-bottom: 25px;
        }
        
        .filter-label {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .price-range {
            width: 100%;
        }
        
        .price-values {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Room Cards */
        .room-detail-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 30px;
        }
        
        .room-detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .room-gallery {
            position: relative;
            overflow: hidden;
        }
        
        .room-main-image {
            height: 300px;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s;
        }
        
        .room-detail-card:hover .room-main-image {
            transform: scale(1.05);
        }
        
        .room-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #3498db;
            color: white;
            padding: 5px 15px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 1;
        }
        
        .room-price-badge {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.3rem;
            z-index: 1;
        }
        
        .room-price-badge small {
            font-size: 0.8rem;
            font-weight: normal;
        }
        
        .room-content {
            padding: 25px;
        }
        
        .room-name {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .room-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .room-specs {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .room-spec {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .room-spec i {
            font-size: 1.2rem;
            color: #3498db;
        }
        
        .room-spec span {
            font-size: 0.9rem;
            color: #555;
        }
        
        .room-amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .amenity-tag {
            background: #f0f0f0;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #555;
        }
        
        .amenity-tag i {
            color: #3498db;
            margin-right: 5px;
        }
        
        .room-availability {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .availability-high {
            background: #d4edda;
            color: #155724;
        }
        
        .availability-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .availability-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-book-room {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            border: none;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-book-room:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        /* Room Comparison Table */
        .comparison-section {
            background: #ecf0f1;
            padding: 60px 0;
        }
        
        .comparison-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .comparison-table table {
            margin-bottom: 0;
        }
        
        .comparison-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .comparison-table td {
            padding: 12px;
            text-align: center;
            vertical-align: middle;
        }
        
        .comparison-table tr:hover {
            background: #f8f9fa;
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
        }
        
        .btn-filter {
            background: #3498db;
            color: white;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-reset {
            background: #95a5a6;
            color: white;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            .filter-sidebar {
                margin-bottom: 30px;
                position: relative;
                top: 0;
            }
            .room-name {
                font-size: 1.3rem;
            }
            .room-price-badge {
                font-size: 1rem;
                padding: 5px 15px;
            }
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
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
        
        /* Custom Range Slider */
        input[type="range"] {
            -webkit-appearance: none;
            width: 100%;
            height: 5px;
            background: #ddd;
            border-radius: 5px;
            outline: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            background: #3498db;
            border-radius: 50%;
            cursor: pointer;
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
                        <i class="fas fa-user"></i> Login
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 class="fade-in-up">Our Luxury Rooms</h1>
        <p class="lead fade-in-up">Discover comfort, elegance, and breathtaking views</p>
    </div>
</section>

<div class="container py-5">
    <div class="row">
        <!-- Filter Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="filter-sidebar">
                <div class="filter-title">
                    <i class="fas fa-filter"></i> Filter Rooms
                </div>
                
                <form method="GET" action="">
                    <div class="filter-group">
                        <div class="filter-label">Room Type</div>
                        <select name="room_type" class="form-select">
                            <option value="">All Room Types</option>
                            <?php foreach ($all_room_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $selected_type == $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <div class="filter-label">Price Range (USD/night)</div>
                        <input type="range" class="price-range" name="max_price" min="<?php echo $min_available_price; ?>" max="<?php echo $max_available_price; ?>" step="10" value="<?php echo $max_price; ?>" oninput="updatePriceValue(this.value)">
                        <div class="price-values">
                            <span>$<?php echo $min_available_price; ?></span>
                            <span>$<span id="priceValue"><?php echo $max_price; ?></span></span>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <div class="filter-label">Guest Capacity</div>
                        <select name="capacity" class="form-select">
                            <option value="0">Any</option>
                            <option value="2" <?php echo $capacity == 2 ? 'selected' : ''; ?>>2 Guests (Standard)</option>
                            <option value="4" <?php echo $capacity == 4 ? 'selected' : ''; ?>>4 Guests (Family)</option>
                            <option value="6" <?php echo $capacity == 6 ? 'selected' : ''; ?>>6 Guests (Family+)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button type="button" class="btn-reset" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Room Results -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="fas fa-bed"></i> 
                    <?php echo count($room_types); ?> Room Types Available
                </h4>
                <div class="text-muted">
                    <i class="fas fa-check-circle" style="color: #27ae60;"></i> Real-time availability
                </div>
            </div>
            
            <?php if (count($room_types) > 0): ?>
                <?php foreach ($room_types as $room_type): 
                    $available_percentage = ($room_type['available_count'] / $room_type['total_rooms']) * 100;
                    $availability_class = $available_percentage >= 50 ? 'availability-high' : ($available_percentage >= 20 ? 'availability-medium' : 'availability-low');
                    $availability_text = $available_percentage >= 50 ? 'High Availability' : ($available_percentage >= 20 ? 'Limited Availability' : 'Few Rooms Left');
                    $amenities_array = explode(',', $room_type['amenities']);
                ?>
                <div class="room-detail-card" data-aos="fade-up">
                    <div class="row g-0">
                        <div class="col-md-5">
                            <div class="room-gallery">
                                <div class="room-main-image" style="background-image: url('<?php 
                                    $images = [
                                        'Standard' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a',
                                        'VIP' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461',
                                        'Family' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b',
                                        'Suite' => 'https://images.unsplash.com/photo-1571008887538-b36bb32f4571'
                                    ];
                                    echo $images[$room_type['type_name']] ?? $images['Standard'];
                                ?>?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');">
                                </div>
                                <div class="room-badge"><?php echo htmlspecialchars($room_type['type_name']); ?></div>
                                <div class="room-price-badge">
                                    $<?php echo number_format($room_type['price_usd'], 2); ?>
                                    <small>/night</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="room-content">
                                <h3 class="room-name"><?php echo htmlspecialchars($room_type['type_name']); ?> Room</h3>
                                <p class="room-description"><?php echo htmlspecialchars($room_type['description']); ?></p>
                                
                                <div class="room-specs">
                                    <div class="room-spec">
                                        <i class="fas fa-users"></i>
                                        <span>Max <?php echo $room_type['capacity']; ?> guests</span>
                                    </div>
                                    <div class="room-spec">
                                        <i class="fas fa-bed"></i>
                                        <span><?php echo $room_type['total_rooms']; ?> rooms total</span>
                                    </div>
                                    <div class="room-spec">
                                        <i class="fas fa-door-open"></i>
                                        <span>Rooms: <?php echo $room_type['room_numbers']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="room-amenities">
                                    <?php foreach ($amenities_array as $amenity): ?>
                                        <?php if(trim($amenity)): ?>
                                        <span class="amenity-tag">
                                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($amenity)); ?>
                                        </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="room-availability <?php echo $availability_class; ?>">
                                    <i class="fas <?php echo $available_percentage >= 50 ? 'fa-check-circle' : ($available_percentage >= 20 ? 'fa-exclamation-circle' : 'fa-times-circle'); ?>"></i>
                                    <?php echo $availability_text; ?> (<?php echo $room_type['available_count']; ?> rooms available)
                                </div>
                                
                                <form action="guest_booking.php" method="GET" class="mt-3">
                                    <input type="hidden" name="room_type_id" value="<?php echo $room_type['id']; ?>">
                                    <?php if ($check_in): ?>
                                        <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>">
                                    <?php endif; ?>
                                    <?php if ($check_out): ?>
                                        <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
                                    <?php endif; ?>
                                    <button type="submit" class="btn-book-room" <?php echo $room_type['available_count'] == 0 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-calendar-check"></i> 
                                        <?php echo $room_type['available_count'] > 0 ? 'Book This Room' : 'Sold Out'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded shadow-sm">
                    <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                    <h4>No rooms found</h4>
                    <p class="text-muted">Please try adjusting your filters</p>
                    <button class="btn btn-primary-custom" onclick="resetFilters()">Reset Filters</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Room Comparison Section -->
<section class="comparison-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-3">Room Comparison</h2>
            <p class="lead text-muted">Compare our room types to find your perfect match</p>
        </div>
        
        <div class="comparison-table table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Features</th>
                        <?php foreach ($room_types as $room_type): ?>
                        <th><?php echo htmlspecialchars($room_type['type_name']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Price (USD/night)</strong></td>
                        <?php foreach ($room_types as $room_type): ?>
                        <td>$<?php echo number_format($room_type['price_usd'], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td><strong>Max Capacity</strong></td>
                        <?php foreach ($room_types as $room_type): ?>
                        <td><?php echo $room_type['capacity']; ?> guests</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td><strong>Total Rooms</strong></td>
                        <?php foreach ($room_types as $room_type): ?>
                        <td><?php echo $room_type['total_rooms']; ?> rooms</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td><strong>Available Now</strong></td>
                        <?php foreach ($room_types as $room_type): ?>
                        <td>
                            <span class="badge bg-<?php echo $room_type['available_count'] > 0 ? 'success' : 'danger'; ?>">
                                <?php echo $room_type['available_count']; ?> available
                            </span>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-white">
    <div class="container text-center">
        <h2 class="display-4 fw-bold mb-3">Ready to Experience Luxury?</h2>
        <p class="lead mb-4">Book your stay today and enjoy our special welcome package</p>
        <a href="guest_booking.php" class="btn btn-primary-custom btn-lg">
            <i class="fas fa-calendar-check"></i> Book Your Stay Now
        </a>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-3">Contact Us</h2>
            <p class="lead text-muted">Get in touch with us for any inquiries</p>
        </div>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4 bg-white rounded shadow-sm">
                    <i class="fas fa-map-marker-alt fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>Address</h5>
                    <p class="text-muted">123 Street, Sangkat,<br>Phnom Penh, Cambodia</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4 bg-white rounded shadow-sm">
                    <i class="fas fa-phone fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>Phone</h5>
                    <p class="text-muted">+855 23 123 456<br>+855 12 345 678</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4 bg-white rounded shadow-sm">
                    <i class="fas fa-envelope fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>Email</h5>
                    <p class="text-muted">info@luxuryhotel.com<br>reservations@luxuryhotel.com</p>
                </div>
            </div>
        </div>
    </div>
</section>

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
                    <li><a href="guest_rooms.php">Rooms</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="guest_booking.php">Book Now</a></li>
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
                <h5>Newsletter</h5>
                <p>Subscribe for exclusive offers</p>
                <form>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your email">
                        <button class="btn btn-primary-custom" type="button">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
        <hr class="mt-3" style="border-color: rgba(255,255,255,0.1);">
        <div class="text-center pt-3">
            <p class="mb-0">&copy; 2024 Luxury Hotel Cambodia. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });
    
    // Price range slider
    function updatePriceValue(value) {
        document.getElementById('priceValue').innerText = value;
    }
    
    // Reset filters
    function resetFilters() {
        window.location.href = 'guest_rooms.php';
    }
    
    // Smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'white !important';
            navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        } else {
            navbar.style.background = 'white !important';
        }
    });
</script>
</body>
</html>