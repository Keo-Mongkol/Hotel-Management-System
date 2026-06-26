-- Create Database
CREATE DATABASE IF NOT EXISTS hotel_management_cambodias;
USE hotel_management_cambodias;


CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('Admin', 'Manager', 'Receptionist', 'Cashier') NOT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    registration_token VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Room Types Table
CREATE TABLE room_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    price_usd DECIMAL(10,2),
    price_khr DECIMAL(15,2),
    capacity INT DEFAULT 2,
    amenities TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Rooms Table
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10) NOT NULL,
    room_type_id INT,
    floor INT,
    status ENUM('available', 'occupied', 'maintenance', 'reserved') DEFAULT 'available',
    notes TEXT,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Customers Table
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(20) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    passport_id VARCHAR(50),
    khmer_id VARCHAR(50),
    nationality VARCHAR(50),
    address TEXT,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Bookings Table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_no VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT,
    room_id INT,
    user_id INT,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    adults INT DEFAULT 1,
    children INT DEFAULT 0,
    total_nights INT,
    room_price_usd DECIMAL(10,2),
    room_price_khr DECIMAL(15,2),
    total_amount_usd DECIMAL(10,2),
    total_amount_khr DECIMAL(15,2),
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancelled_by INT,
    cancel_reason TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id)
);

-- Payments Table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_no VARCHAR(20) UNIQUE NOT NULL,
    booking_id INT,
    user_id INT,
    amount_usd DECIMAL(10,2),
    amount_khr DECIMAL(15,2),
    payment_method ENUM('ABA Pay', 'Wing', 'ACLEDA', 'Cash') NOT NULL,
    currency ENUM('USD', 'KHR') NOT NULL,
    exchange_rate DECIMAL(10,4),
    transaction_id VARCHAR(100),
    reference_no VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Receipts Table
CREATE TABLE receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    receipt_no VARCHAR(20) UNIQUE NOT NULL,
    booking_id INT,
    payment_id INT,
    user_id INT,
    receipt_data TEXT,
    receipt_html TEXT,
    language ENUM('khmer', 'english') DEFAULT 'english',
    printed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    printed_by INT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (printed_by) REFERENCES users(id)
);

-- Daily Reports Table
CREATE TABLE daily_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL,
    total_bookings INT,
    total_checked_in INT,
    total_checked_out INT,
    total_cancelled INT,
    occupancy_rate DECIMAL(5,2),
    total_revenue_usd DECIMAL(10,2),
    total_revenue_khr DECIMAL(15,2),
    payment_aba_usd DECIMAL(10,2),
    payment_wing_usd DECIMAL(10,2),
    payment_acleda_usd DECIMAL(10,2),
    payment_cash_usd DECIMAL(10,2),
    payment_aba_khr DECIMAL(15,2),
    payment_wing_khr DECIMAL(15,2),
    payment_acleda_khr DECIMAL(15,2),
    payment_cash_khr DECIMAL(15,2),
    report_data TEXT,
    generated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Activity Logs Table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert Sample Data
-- Insert Admin User (password: Admin@123)
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'admin@hotel.com', '012345678', 'Admin', 'active'),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Sophea Chea', 'sophea@hotel.com', '012345679', 'Manager', 'active'),
('reception1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ms. Dara Kim', 'dara@hotel.com', '012345680', 'Receptionist', 'active'),
('cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Rithy Sok', 'rithy@hotel.com', '012345681', 'Cashier', 'active');

-- Insert Room Types
INSERT INTO room_types (type_name, description, price_usd, price_khr, capacity, amenities) VALUES
('Standard', 'Comfortable standard room with basic amenities', 50.00, 200000, 2, 'Air Conditioning, TV, WiFi, Mini Fridge'),
('VIP', 'Premium VIP room with luxury amenities', 100.00, 400000, 2, 'Air Conditioning, TV, WiFi, Mini Fridge, Jacuzzi, Balcony'),
('Family', 'Spacious family room for 4-6 persons', 150.00, 600000, 6, 'Air Conditioning, 2 TVs, WiFi, Kitchenette, Living Area'),
('Suite', 'Luxury suite with separate living area', 250.00, 1000000, 4, 'Air Conditioning, 3 TVs, WiFi, Full Kitchen, Jacuzzi, Balcony, Ocean View');

-- Insert Rooms
INSERT INTO rooms (room_number, room_type_id, floor) VALUES
('101', 1, 1), ('102', 1, 1), ('103', 1, 1), ('104', 1, 1), ('105', 1, 1),
('201', 2, 2), ('202', 2, 2), ('203', 2, 2), ('204', 2, 2),
('301', 3, 3), ('302', 3, 3),
('401', 4, 4), ('402', 4, 4);