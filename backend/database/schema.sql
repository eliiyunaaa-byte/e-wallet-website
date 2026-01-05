-- =====================================================
-- SIENA COLLEGE E-WALLET DATABASE SCHEMA
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS ewallet_db;
USE ewallet_db;

-- =====================================================
-- 1. STUDENTS TABLE
-- =====================================================
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    school_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(200),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    grade_section VARCHAR(50),
    password_hash VARCHAR(255) NOT NULL,
    balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. TRANSACTIONS TABLE
-- =====================================================
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    transaction_type ENUM('PURCHASE', 'CASH_IN', 'REFUND', 'ADJUSTMENT') DEFAULT 'PURCHASE',
    amount DECIMAL(10, 2) NOT NULL,
    previous_balance DECIMAL(10, 2),
    new_balance DECIMAL(10, 2),
    location VARCHAR(100),
    item_name VARCHAR(100),
    reference_number VARCHAR(50),
    status ENUM('PENDING', 'COMPLETED', 'FAILED') DEFAULT 'COMPLETED',
    description TEXT,
    transaction_date DATE,
    transaction_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. CASH IN REQUESTS TABLE
-- =====================================================
CREATE TABLE cash_in_requests (
    cash_in_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reference_number VARCHAR(50),
    gcash_number VARCHAR(20),
    status ENUM('PENDING', 'APPROVED', 'REJECTED', 'COMPLETED') DEFAULT 'PENDING',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. PASSWORD RESET REQUESTS TABLE
-- =====================================================
CREATE TABLE password_resets (
    reset_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    reset_token VARCHAR(64) NULL COMMENT 'Unique token for security',
    otp_code VARCHAR(6) NOT NULL COMMENT '6-digit verification code',
    is_used BOOLEAN DEFAULT FALSE COMMENT 'Prevents OTP reuse',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL COMMENT 'OTP valid for 15 minutes',
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    INDEX idx_otp_lookup (student_id, otp_code, is_used, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. CANTEEN VENDORS TABLE
-- =====================================================
CREATE TABLE canteen_vendors (
    vendor_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. MENU ITEMS TABLE
-- =====================================================
CREATE TABLE menu_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES canteen_vendors(vendor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. ADMIN USERS TABLE
-- =====================================================
CREATE TABLE admin_users (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'ACCOUNTANT', 'SUPPORT') DEFAULT 'ADMIN',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. AUDIT LOG TABLE
-- =====================================================
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    action_type VARCHAR(50),
    user_id INT,
    user_type ENUM('STUDENT', 'ADMIN') DEFAULT 'STUDENT',
    description TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_student_school_id ON students(school_id);
CREATE INDEX idx_transaction_student_id ON transactions(student_id);
CREATE INDEX idx_transaction_date ON transactions(transaction_date);
CREATE INDEX idx_cash_in_student_id ON cash_in_requests(student_id);
CREATE INDEX idx_cash_in_status ON cash_in_requests(status);
CREATE INDEX idx_password_reset_student_id ON password_resets(student_id);

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert sample canteen vendors
INSERT INTO canteen_vendors (vendor_name, location) VALUES
('SDB Canteen', 'San Domingo Building'),
('STB Canteen', 'St. Theresa Building'),
('Holy Rosary Canteen', 'Holy Rosary Building');

-- Insert sample menu items
INSERT INTO menu_items (vendor_id, item_name, price, category) VALUES
(1, 'Siomai & Rice', 50.00, 'Main Course'),
(2, 'Stick-O', 30.00, 'Snacks'),
(3, 'Fish Fillet & Rice', 75.00, 'Main Course'),
(1, 'Scramble', 50.00, 'Main Course'),
(1, 'Burger Steak', 60.00, 'Main Course'),
(2, 'Juice', 25.00, 'Beverages'),
(3, 'Pork Sisig', 65.00, 'Main Course'),
(1, 'Pancit', 45.00, 'Main Course');

-- Insert sample students (password for all: password123)
-- Plain text passwords for debugging - CHANGE TO BCRYPT IN PRODUCTION
INSERT INTO students (school_id, first_name, last_name, full_name, email, phone, grade_section, password_hash, balance) VALUES
('123456', 'Juan', 'Dela Cruz', 'Juan Dela Cruz', 'klalbaytar1913ant@student.fatima.edu.ph', '09518982328', '10-Eucharist Centered', 'password123', 1000.00),
('123457', 'Maria', 'Santos', 'Maria Santos', 'maria.santos@siena.edu.ph', NULL, '11-Christ Centered', 'password123', 750.50),
('123458', 'Pedro', 'Reyes', 'Pedro Reyes', 'pedro.reyes@siena.edu.ph', NULL, '10-Eucharist Centered', 'password123', 500.00);

-- Insert sample transactions
INSERT INTO transactions (student_id, transaction_type, amount, previous_balance, new_balance, location, item_name, transaction_date, transaction_time, status) VALUES
(1, 'PURCHASE', 50.00, 1050.00, 1000.00, 'SDB Canteen', 'Siomai & Rice', '2026-01-03', '11:20:00', 'COMPLETED'),
(1, 'PURCHASE', 30.00, 1030.00, 1000.00, 'STB Canteen', 'Stick-O', '2026-01-02', '16:00:00', 'COMPLETED'),
(2, 'PURCHASE', 75.00, 825.50, 750.50, 'Holy Rosary Canteen', 'Fish Fillet & Rice', '2026-01-03', '09:15:00', 'COMPLETED'),
(1, 'PURCHASE', 50.00, 1080.00, 1030.00, 'SDB Canteen', 'Scramble', '2026-01-01', '12:00:00', 'COMPLETED');

-- Insert sample admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, email, password_hash, role) VALUES
('admin', 'admin@siena.edu.ph', 'admin123', 'ADMIN');

