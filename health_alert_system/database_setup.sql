-- Health Alert System Database Setup
-- This script creates the complete database schema and inserts sample data
-- Run this script in MySQL/phpMyAdmin to set up the system

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS health_alert_system;
USE health_alert_system;

-- Drop existing tables to ensure clean setup (in reverse dependency order)
DROP TABLE IF EXISTS doctor_patients;
DROP TABLE IF EXISTS alerts;
DROP TABLE IF EXISTS health_data;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL,
    status ENUM('pending', 'approved') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create health_data table
CREATE TABLE health_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    systolic_bp INT NOT NULL,
    diastolic_bp INT NOT NULL,
    sugar_level DECIMAL(5,2) NOT NULL,
    heart_rate INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create alerts table
CREATE TABLE alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'seen') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create doctor_patients table for patient assignments
CREATE TABLE doctor_patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (doctor_id, patient_id)
);

-- Insert sample users
-- Default admin account (password: admin123)
INSERT INTO users (name, email, password, role, status) VALUES
('System Administrator', 'admin@healthalert.com', 'admin123', 'admin', 'approved');

-- Sample approved doctors (password: doctor123)
INSERT INTO users (name, email, password, role, status) VALUES
('Dr. Sarah Johnson', 'sarah.johnson@hospital.com', 'doctor123', 'doctor', 'approved'),
('Dr. Michael Chen', 'michael.chen@clinic.com', 'doctor123', 'doctor', 'approved'),
('Dr. Emily Rodriguez', 'emily.rodriguez@medical.com', 'doctor123', 'doctor', 'approved');

-- Sample pending doctors (password: doctor123)
INSERT INTO users (name, email, password, role, status) VALUES
('Dr. James Wilson', 'james.wilson@newdoc.com', 'doctor123', 'doctor', 'pending'),
('Dr. Lisa Thompson', 'lisa.thompson@healthcare.com', 'doctor123', 'doctor', 'pending');

-- Sample patients (password: patient123)
INSERT INTO users (name, email, password, role, status) VALUES
('John Smith', 'john.smith@email.com', 'patient123', 'patient', 'approved'),
('Mary Johnson', 'mary.johnson@email.com', 'patient123', 'patient', 'approved'),
('Robert Brown', 'robert.brown@email.com', 'patient123', 'patient', 'approved'),
('Jennifer Davis', 'jennifer.davis@email.com', 'patient123', 'patient', 'approved'),
('William Miller', 'william.miller@email.com', 'patient123', 'patient', 'approved'),
('Elizabeth Wilson', 'elizabeth.wilson@email.com', 'patient123', 'patient', 'approved'),
('David Anderson', 'david.anderson@email.com', 'patient123', 'patient', 'approved'),
('Susan Taylor', 'susan.taylor@email.com', 'patient123', 'patient', 'approved');

-- Create doctor-patient assignments
-- Dr. Sarah Johnson (id: 2) assigned to patients 6, 7, 8
INSERT INTO doctor_patients (doctor_id, patient_id) VALUES
(2, 6),  -- Dr. Sarah Johnson -> John Smith
(2, 7),  -- Dr. Sarah Johnson -> Mary Johnson
(2, 8);  -- Dr. Sarah Johnson -> Robert Brown

-- Dr. Michael Chen (id: 3) assigned to patients 9, 10, 11
INSERT INTO doctor_patients (doctor_id, patient_id) VALUES
(3, 9),   -- Dr. Michael Chen -> Jennifer Davis
(3, 10),  -- Dr. Michael Chen -> William Miller
(3, 11);  -- Dr. Michael Chen -> Elizabeth Wilson

-- Dr. Emily Rodriguez (id: 4) assigned to patients 12, 13
INSERT INTO doctor_patients (doctor_id, patient_id) VALUES
(4, 12),  -- Dr. Emily Rodriguez -> David Anderson
(4, 13);  -- Dr. Emily Rodriguez -> Susan Taylor

-- Insert sample health data for patients
-- John Smith (patient_id: 6) - Normal readings
INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) VALUES
(6, 120, 80, 95.5, 72, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 118, 78, 92.0, 75, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 122, 82, 98.2, 70, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(6, 115, 75, 89.8, 73, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 119, 79, 94.1, 71, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Mary Johnson (patient_id: 7) - Some elevated readings
INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) VALUES
(7, 135, 85, 110.5, 78, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(7, 140, 90, 125.0, 82, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 138, 88, 118.2, 80, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(7, 132, 84, 105.8, 76, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(7, 128, 82, 102.1, 74, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Robert Brown (patient_id: 8) - Mixed readings
INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) VALUES
(8, 125, 83, 88.5, 68, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 130, 85, 95.0, 72, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(8, 128, 84, 91.2, 70, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(8, 124, 81, 87.8, 69, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Jennifer Davis (patient_id: 9) - Recent readings
INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) VALUES
(9, 116, 76, 92.5, 74, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(9, 118, 78, 96.0, 76, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(9, 120, 80, 94.2, 75, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- William Miller (patient_id: 10) - Concerning readings
INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) VALUES
(10, 145, 95, 140.5, 88, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(10, 150, 98, 155.0, 92, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(10, 148, 96, 148.2, 90, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(10, 142, 92, 135.8, 86, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Elizabeth Wilson (patient_id: 11) - Good control
INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) VALUES
(11, 112, 72, 85.5, 65, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(11, 115, 75, 88.0, 67, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(11, 118, 78, 91.2, 69, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- David Anderson (patient_id: 12) - Variable readings
INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) VALUES
(12, 128, 84, 102.5, 77, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(12, 132, 86, 108.0, 79, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(12, 125, 82, 98.2, 75, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Susan Taylor (patient_id: 13) - Recent data
INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) VALUES
(13, 122, 81, 96.5, 73, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(13, 119, 79, 93.0, 71, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Insert sample alerts from doctors to patients
-- Dr. Sarah Johnson alerts to her patients
INSERT INTO alerts (doctor_id, patient_id, message, status, created_at) VALUES
(2, 6, 'Your blood pressure readings look great! Keep up the good work with your exercise routine.', 'seen', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 7, 'I noticed your blood pressure has been elevated recently. Please schedule an appointment to discuss medication adjustments.', 'sent', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 7, 'Please monitor your sugar levels more closely and reduce carbohydrate intake as we discussed.', 'sent', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 8, 'Your health metrics are within normal range. Continue your current lifestyle and medication regimen.', 'seen', DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Dr. Michael Chen alerts to his patients
INSERT INTO alerts (doctor_id, patient_id, message, status, created_at) VALUES
(3, 9, 'Excellent health data! Your commitment to healthy living is showing great results.', 'seen', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 10, 'URGENT: Your blood pressure and sugar levels are concerning. Please contact the office immediately to schedule an appointment.', 'sent', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 10, 'Please start taking your medication as prescribed and avoid high-sodium foods.', 'sent', NOW()),
(3, 11, 'Your readings are excellent. Keep maintaining your current diet and exercise routine.', 'seen', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Dr. Emily Rodriguez alerts to her patients
INSERT INTO alerts (doctor_id, patient_id, message, status, created_at) VALUES
(4, 12, 'Your blood pressure is slightly elevated. Let\'s monitor it closely over the next week.', 'sent', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 13, 'Great job maintaining healthy levels! Continue your current routine.', 'seen', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 12, 'Please increase your physical activity and reduce salt intake as we discussed in your last visit.', 'sent', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Display setup completion message
SELECT 'Database setup completed successfully!' as Status;
SELECT 'Default admin login: admin@healthalert.com / admin123' as AdminLogin;
SELECT 'Sample doctor login: sarah.johnson@hospital.com / doctor123' as DoctorLogin;
SELECT 'Sample patient login: john.smith@email.com / patient123' as PatientLogin;

-- Display summary statistics
SELECT 
    'Users Created' as Category,
    COUNT(*) as Count
FROM users
UNION ALL
SELECT 
    'Health Records' as Category,
    COUNT(*) as Count
FROM health_data
UNION ALL
SELECT 
    'Alerts Sent' as Category,
    COUNT(*) as Count
FROM alerts
UNION ALL
SELECT 
    'Doctor-Patient Assignments' as Category,
    COUNT(*) as Count
FROM doctor_patients;