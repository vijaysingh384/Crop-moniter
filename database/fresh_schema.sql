-- Fresh Database schema for Multi-Module Government Web Application
USE crop_monitoring_system;

-- Users table (common for all types of users)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    user_type ENUM('farmer', 'citizen', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Farmer profiles table
CREATE TABLE IF NOT EXISTS farmer_profiles (
    farmer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    farm_latitude DECIMAL(10,6) NULL,
    farm_longitude DECIMAL(10,6) NULL,
    location_name VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Crop data table
CREATE TABLE IF NOT EXISTS crops (
    crop_id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    crop_name VARCHAR(100) NOT NULL,
    crop_type VARCHAR(100) NOT NULL,
    planting_date DATE NOT NULL,
    field_location TEXT,
    field_size FLOAT,
    expected_harvest_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmer_profiles(farmer_id) ON DELETE CASCADE
);

-- Crop images table
CREATE TABLE IF NOT EXISTS crop_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    growth_stage VARCHAR(50),
    notes TEXT,
    FOREIGN KEY (crop_id) REFERENCES crops(crop_id) ON DELETE CASCADE
);

-- Crop analysis table
CREATE TABLE IF NOT EXISTS crop_analysis (
    analysis_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    image_id INT,
    soil_health_score FLOAT,
    water_needs_score FLOAT,
    growth_rate_score FLOAT,
    disease_detection TEXT,
    analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recommendations TEXT,
    FOREIGN KEY (crop_id) REFERENCES crops(crop_id) ON DELETE CASCADE,
    FOREIGN KEY (image_id) REFERENCES crop_images(image_id) ON DELETE SET NULL
);

-- Weather data cache
CREATE TABLE IF NOT EXISTS weather_data (
    weather_id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    forecast_data JSON NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Citizen profiles table
CREATE TABLE IF NOT EXISTS citizen_profiles (
    citizen_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    aadhaar_number VARCHAR(12) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    date_of_birth DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Admin profiles table
CREATE TABLE IF NOT EXISTS admin_profiles (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Pension schemes table
CREATE TABLE IF NOT EXISTS pension_schemes (
    scheme_id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_name VARCHAR(255) NOT NULL,
    scheme_provider VARCHAR(100) NOT NULL,
    scheme_code VARCHAR(50) UNIQUE,
    description TEXT,
    eligibility_criteria TEXT,
    benefit_amount VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pension claims table
CREATE TABLE IF NOT EXISTS pension_claims (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    citizen_id INT NOT NULL,
    scheme_id INT NOT NULL,
    claim_status ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'pending',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approval_date TIMESTAMP NULL,
    approved_by INT NULL,
    notes TEXT,
    FOREIGN KEY (citizen_id) REFERENCES citizen_profiles(citizen_id) ON DELETE CASCADE,
    FOREIGN KEY (scheme_id) REFERENCES pension_schemes(scheme_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Citizen pension enrollments
CREATE TABLE IF NOT EXISTS citizen_pensions (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    citizen_id INT NOT NULL,
    scheme_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    verification_status ENUM('Pending', 'Verified', 'Rejected', 'Flagged') DEFAULT 'Pending',
    monthly_amount DECIMAL(10,2),
    account_number VARCHAR(50),
    bank_name VARCHAR(100),
    ifsc_code VARCHAR(20),
    last_verified TIMESTAMP NULL,
    FOREIGN KEY (citizen_id) REFERENCES citizen_profiles(citizen_id) ON DELETE CASCADE,
    FOREIGN KEY (scheme_id) REFERENCES pension_schemes(scheme_id) ON DELETE CASCADE
);

-- Flagged duplicate cases
CREATE TABLE IF NOT EXISTS flagged_duplicates (
    flag_id INT AUTO_INCREMENT PRIMARY KEY,
    citizen_id INT NOT NULL,
    duplicate_description TEXT NOT NULL,
    flagged_enrollments TEXT NOT NULL, -- JSON of enrollment IDs that conflict
    flagged_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolution_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    resolved_by INT, -- admin user_id
    resolution_notes TEXT,
    resolution_date TIMESTAMP NULL,
    FOREIGN KEY (citizen_id) REFERENCES citizen_profiles(citizen_id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Admin actions log
CREATE TABLE IF NOT EXISTS admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    affected_entity VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    action_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_profiles(admin_id) ON DELETE CASCADE
);
