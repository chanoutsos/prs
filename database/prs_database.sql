
-- Create Database
CREATE DATABASE IF NOT EXISTS prs_database;
USE prs_database;

-- Roles Table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    national_id VARCHAR(50) UNIQUE NOT NULL,
    prs_id VARCHAR(50) UNIQUE NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE
);

-- Vaccination Records Table
CREATE TABLE vaccination_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    vaccine_name VARCHAR(255) NOT NULL,
    date_administered DATE NOT NULL,
    dose_number INT NOT NULL,
    provider VARCHAR(255),
    lot_number VARCHAR(50),
    expiration_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Documents Table
CREATE TABLE documents (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    file_type VARCHAR(50) NOT NULL,
    file_path TEXT NOT NULL,
    uploaded_by INT NOT NULL,
    related_record_id INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (related_record_id) REFERENCES vaccination_records(record_id) ON DELETE SET NULL
);

-- Audit Logs Table
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    entity_affected VARCHAR(100),
    record_id INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Encryption Keys Table
CREATE TABLE encryption_keys (
    key_id INT PRIMARY KEY AUTO_INCREMENT,
    key_type VARCHAR(50) NOT NULL,
    key_value TEXT NOT NULL,
    owner VARCHAR(50),
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Sample Roles
INSERT INTO roles (role_name, description) VALUES
('Government Official', 'Can monitor vaccination and supply chain'),
('Merchant', 'Can manage pandemic-related supplies'),
('Public Member', 'Can track vaccination records');

-- Insert Sample Users
INSERT INTO users (full_name, email, password_hash, phone, national_id, prs_id, role_id) VALUES
('Alice Johnson', 'alice@gov.org', SHA2('securepassword', 256), '+1234567890', 'GOV12345', 'PRS001', 1),
('Bob Merchant', 'bob@merchant.com', SHA2('merchantpass', 256), '+1987654321', 'MER12345', 'PRS002', 2);

-- Insert Sample Vaccination Record
INSERT INTO vaccination_records (user_id, vaccine_name, date_administered, dose_number, provider, lot_number, expiration_date) VALUES
(1, 'COVID-19 Vaccine AstraZeneca', '2023-01-10', 1, 'City Health Clinic', 'AZ-12345', '2023-12-31');
