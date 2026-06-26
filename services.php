<?php
// services.php - Complete Hotel Services Page for Guests
session_start();
require_once 'config/database.php';

// Get service categories from database (you can expand this)
$services = [
    'dining' => [
        'title' => 'Dining & Restaurant',
        'icon' => 'fa-utensils',
        'color' => '#e74c3c',
        'items' => [
            [
                'name' => 'Main Restaurant "Mekong"',
                'description' => 'Enjoy authentic Khmer and international cuisine with stunning river views. Open daily for breakfast, lunch, and dinner.',
                'hours' => '6:00 AM - 10:00 PM',
                'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Buffet Breakfast', 'A La Carte Menu', 'Seafood Specialties', 'Vegetarian Options']
            ],
            [
                'name' => 'Sky Lounge & Bar',
                'description' => 'Rooftop bar offering panoramic city views, signature cocktails, and live entertainment in the evening.',
                'hours' => '5:00 PM - 12:00 AM',
                'image' => 'https://images.unsplash.com/photo-1566411520896-01c9c6b2b33b?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Happy Hour 5-7 PM', 'Live Music', 'Pool Tables', 'Premium Spirits']
            ],
            [
                'name' => 'Coffee Shop "Angkor"',
                'description' => 'Cozy café serving freshly brewed coffee, pastries, and light snacks throughout the day.',
                'hours' => '24/7',
                'image' => 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Specialty Coffee', 'Fresh Pastries', 'Free WiFi', 'Takeaway Available']
            ]
        ]
    ],
    'wellness' => [
        'title' => 'Spa & Wellness',
        'icon' => 'fa-spa',
        'color' => '#27ae60',
        'items' => [
            [
                'name' => 'Traditional Khmer Massage',
                'description' => 'Experience authentic Cambodian massage techniques passed down through generations. Relieves muscle tension and improves circulation.',
                'duration' => '60/90/120 min',
                'price' => '$35 - $65',
                'image' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Traditional Techniques', 'Herbal Compress', 'Aromatherapy Oils', 'Private Rooms']
            ],
            [
                'name' => 'Aromatherapy Massage',
                'description' => 'Relaxing massage using essential oils to promote mental and physical well-being. Customized to your needs.',
                'duration' => '60/90 min',
                'price' => '$45 - $75',
                'image' => 'https://images.unsplash.com/photo-1600334089648-b0f9d3f5b9e0?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Essential Oils', 'Hot Towels', 'Scalp Massage', 'Soothing Music']
            ],
            [
                'name' => 'Facial Treatments',
                'description' => 'Rejuvenating facial treatments using premium products to restore your skin\'s natural glow.',
                'duration' => '45/60 min',
                'price' => '$40 - $60',
                'image' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Deep Cleansing', 'Anti-Aging', 'Hydrating', 'Organic Products']
            ]
        ]
    ],
    'recreation' => [
        'title' => 'Recreation & Activities',
        'icon' => 'fa-swimmer',
        'color' => '#3498db',
        'items' => [
            [
                'name' => 'Swimming Pool',
                'description' => 'Olympic-sized swimming pool with separate children\'s area. Perfect for relaxation and exercise.',
                'hours' => '6:00 AM - 9:00 PM',
                'image' => 'https://images.unsplash.com/photo-1575429198097-0414ec08e8cd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Pool Bar', 'Sun Loungers', 'Pool Towels', 'Lifeguard on Duty']
            ],
            [
                'name' => 'Fitness Center',
                'description' => 'State-of-the-art gym equipment with personal trainers available. Open 24/7 for hotel guests.',
                'hours' => '24/7',
                'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Cardio Equipment', 'Free Weights', 'Yoga Studio', 'Personal Training']
            ],
            [
                'name' => 'City Tours',
                'description' => 'Guided tours to famous temples, markets, and cultural sites. Customized itineraries available.',
                'duration' => 'Half/Full Day',
                'price' => '$25 - $80',
                'image' => 'https://images.unsplash.com/photo-1559312856-6f7a7bd4b1c6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Professional Guide', 'Air-conditioned Transport', 'Entrance Fees', 'Refreshments']
            ]
        ]
    ],
    'business' => [
        'title' => 'Business Services',
        'icon' => 'fa-briefcase',
        'color' => '#f39c12',
        'items' => [
            [
                'name' => 'Business Center',
                'description' => 'Fully-equipped business center with computers, printers, and secretarial services.',
                'hours' => '24/7',
                'image' => 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Printing & Scanning', 'High-speed Internet', 'Meeting Rooms', 'Catering Services']
            ],
            [
                'name' => 'Conference Rooms',
                'description' => 'Modern conference facilities for meetings, seminars, and corporate events.',
                'capacity' => '10 - 200 people',
                'price' => '$200 - $1000/day',
                'image' => 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Projector & Screen', 'Sound System', 'Video Conferencing', 'Coffee Breaks']
            ]
        ]
    ],
    'family' => [
        'title' => 'Family Services',
        'icon' => 'fa-child',
        'color' => '#9b59b6',
        'items' => [
            [
                'name' => 'Kids Club',
                'description' => 'Supervised activities and play area for children ages 4-12. Daily programs with educational and fun activities.',
                'hours' => '9:00 AM - 6:00 PM',
                'image' => 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Professional Nannies', 'Arts & Crafts', 'Games Room', 'Outdoor Play Area']
            ],
            [
                'name' => 'Babysitting Service',
                'description' => 'Professional babysitting services available upon request. All sitters are certified and background checked.',
                'hours' => '24/7',
                'price' => '$10/hour',
                'image' => 'https://images.unsplash.com/photo-1516627145497-ae69b6d8d8f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Certified Sitters', 'Toys & Games', 'Baby Monitor', 'Emergency Contact']
            ]
        ]
    ],
    'transport' => [
        'title' => 'Transportation',
        'icon' => 'fa-car',
        'color' => '#16a085',
        'items' => [
            [
                'name' => 'Airport Transfer',
                'description' => 'Luxury car service for seamless airport pickup and drop-off. Flight tracking included.',
                'price' => '$25 - $50',
                'image' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Meet & Greet', 'Luggage Assistance', 'Free WiFi', 'Bottled Water']
            ],
            [
                'name' => 'Car Rental',
                'description' => 'Rent a car for independent exploration. Various models available including luxury vehicles.',
                'price' => '$35 - $150/day',
                'image' => 'https://images.unsplash.com/photo-1550355291-bbee04a92027?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'features' => ['Full Insurance', 'GPS Navigation', 'Child Seats', '24/7 Roadside Assistance']
            ]
        ]
    ]
];

// Handle booking inquiry form
$inquiry_sent = null;
$inquiry_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $inquiry_error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $inquiry_error = "Please enter a valid email address.";
    } else {
        // Here you would typically save to database or send email
        // For now, just show success message
        $inquiry_sent = true;
        
        // You can add database insertion here
        /*
        try {
            $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, phone, service, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $phone, $service, $message]);
            $inquiry_sent = true;
        } catch (PDOException $e) {
            $inquiry_error = "Unable to send inquiry. Please try again later.";
        }
        */
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Luxury Hotel Cambodia</title>
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
        
        .btn-book-nav {
            background: #3498db;
            color: white !important;
            padding: 8px 25px !important;
            border-radius: 25px;
        }
        
        /* Page Header */
        .page-header {
            background-color: #2c3e50;
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
        
        /* Service Category */
        .service-category {
            padding: 60px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .service-category:last-child {
            border-bottom: none;
        }
        
        .category-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .category-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .category-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .service-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .service-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .service-content {
            padding: 20px;
        }
        
        .service-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .service-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .service-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .service-meta-item {
            font-size: 0.9rem;
            color: #666;
        }
        
        .service-meta-item i {
            color: #3498db;
            margin-right: 5px;
        }
        
        .service-features {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        
        .service-features li {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            background: #f0f0f0;
            padding: 4px 10px;
            border-radius: 15px;
            color: #555;
        }
        
        .service-features i {
            color: #3498db;
            margin-right: 5px;
        }
        
        /* Inquiry Section */
        .inquiry-section {
            background: #ecf0f1;
            padding: 60px 0;
        }
        
        .inquiry-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
        }
        
        .btn-submit {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background: #2980b9;
            transform: translateY(-2px);
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
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            .category-title {
                font-size: 1.5rem;
            }
            .inquiry-card {
                padding: 20px;
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
                        <i class="fas fa-book"></i> Now
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

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 class="fade-in-up">Our Premium Services</h1>
        <p class="lead fade-in-up">Experience world-class amenities and exceptional hospitality</p>
    </div>
</section>

<!-- Services Sections -->
<?php 
$bg_alternate = false;
foreach ($services as $category_key => $category): 
?>
<section class="service-category" style="background: <?php echo $bg_alternate ? '#f8f9fa' : 'white'; ?>">
    <div class="container">
        <div class="category-header">
            <div class="category-icon" style="color: <?php echo $category['color']; ?>">
                <i class="fas <?php echo $category['icon']; ?>"></i>
            </div>
            <h2 class="category-title"><?php echo $category['title']; ?></h2>
            <p class="text-muted">Discover our exceptional <?php echo strtolower($category['title']); ?> services</p>
        </div>
        <div class="row">
            <?php foreach ($category['items'] as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-image" style="background-image: url('<?php echo $item['image']; ?>');">
                        <?php if (isset($item['price'])): ?>
                        <div class="service-badge">
                            <i class="fas fa-tag"></i> <?php echo $item['price']; ?>
                        </div>
                        <?php elseif (isset($item['hours'])): ?>
                        <div class="service-badge">
                            <i class="fas fa-clock"></i> <?php echo $item['hours']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="service-content">
                        <h3 class="service-title"><?php echo $item['name']; ?></h3>
                        <p class="service-description"><?php echo $item['description']; ?></p>
                        
                        <div class="service-meta">
                            <?php if (isset($item['hours'])): ?>
                            <div class="service-meta-item">
                                <i class="fas fa-clock"></i> <?php echo $item['hours']; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($item['duration'])): ?>
                            <div class="service-meta-item">
                                <i class="fas fa-hourglass-half"></i> <?php echo $item['duration']; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($item['capacity'])): ?>
                            <div class="service-meta-item">
                                <i class="fas fa-users"></i> <?php echo $item['capacity']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($item['features'])): ?>
                        <ul class="service-features">
                            <?php foreach ($item['features'] as $feature): ?>
                            <li><i class="fas fa-check-circle"></i> <?php echo $feature; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php 
$bg_alternate = !$bg_alternate;
endforeach; 
?>

<!-- Why Choose Us Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-3">Why Choose Us</h2>
            <p class="lead text-muted">What makes our hotel special</p>
        </div>
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="text-center">
                    <i class="fas fa-medal fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>Award Winning Service</h5>
                    <p class="text-muted">Recognized for excellence in hospitality</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="text-center">
                    <i class="fas fa-clock fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>24/7 Support</h5>
                    <p class="text-muted">Round-the-clock guest assistance</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="text-center">
                    <i class="fas fa-shield-alt fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>Safety First</h5>
                    <p class="text-muted">Strict safety and hygiene protocols</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="text-center">
                    <i class="fas fa-smile fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>Best Price Guarantee</h5>
                    <p class="text-muted">Competitive rates with great value</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Service Inquiry Section -->
<section id="inquiry" class="inquiry-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="inquiry-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope fa-3x" style="color: #3498db;"></i>
                        <h2 class="mt-3">Have Questions?</h2>
                        <p class="text-muted">Contact us for more information about our services</p>
                    </div>
                    
                    <?php if ($inquiry_sent): ?>
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h5>Thank You!</h5>
                            <p>Your inquiry has been sent. We'll get back to you within 24 hours.</p>
                        </div>
                    <?php else: ?>
                        <?php if ($inquiry_error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $inquiry_error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Full Name *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Service Interested In</label>
                                    <select name="service" class="form-select">
                                        <option value="">All Services</option>
                                        <option value="Dining">Dining & Restaurant</option>
                                        <option value="Spa">Spa & Wellness</option>
                                        <option value="Recreation">Recreation</option>
                                        <option value="Business">Business Services</option>
                                        <option value="Family">Family Services</option>
                                        <option value="Transport">Transportation</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Message *</label>
                                    <textarea name="message" class="form-control" rows="5" required></textarea>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" name="send_inquiry" class="btn btn-submit">
                                        <i class="fas fa-paper-plane"></i> Send Inquiry
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-3">Contact Us</h2>
            <p class="lead text-muted">Get in touch with us for any inquiries</p>
        </div>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4 bg-light rounded shadow-sm">
                    <i class="fas fa-map-marker-alt fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>Address</h5>
                    <p class="text-muted">123 Street, Sangkat,<br>Phnom Penh, Cambodia</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4 bg-light rounded shadow-sm">
                    <i class="fas fa-phone fa-3x mb-3" style="color: #3498db;"></i>
                    <h5>Phone</h5>
                    <p class="text-muted">+855 23 123 456<br>+855 12 345 678</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4 bg-light rounded shadow-sm">
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
                    <li><a href="index.php#rooms">Rooms</a></li>
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
<script>
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
    
    // Animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.service-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease-out';
        observer.observe(el);
    });
</script>
</body>
</html>