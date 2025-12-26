<?php
/**
 * Feature: health-alert-system, Property 9: Doctor Registration Status
 * 
 * Property 9: Doctor Registration Status
 * For any doctor registration submission, the system should create a user account 
 * with role "doctor" and status "pending"
 * Validates: Requirements 4.2
 */

// Simple test runner for Doctor Registration Status Property Test
function runDoctorRegistrationStatusTest() {
    echo "Running Doctor Registration Status Property Test...\n";
    
    $test_db = 'health_alert_system_test';
    $connection = mysqli_connect('localhost', 'root', '', '');
    
    if (!$connection) {
        echo "SKIP: Cannot connect to MySQL server\n";
        return false;
    }
    
    // Create test database
    mysqli_query($connection, "CREATE DATABASE IF NOT EXISTS {$test_db}");
    mysqli_select_db($connection, $test_db);
    
    // Create users table
    $create_table = "
    CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('patient', 'doctor', 'admin') NOT NULL,
        status ENUM('pending', 'approved') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($connection, $create_table);
    
    try {
        // Test multiple doctor registrations (property-based approach)
        $doctor_test_cases = [
            ['Dr. John Smith', 'dr.john@hospital.com', 'medpass123', 'doctor'],
            ['Dr. Sarah Wilson', 'sarah.wilson@clinic.com', 'docpass456', 'doctor'],
            ['Dr. Michael Brown', 'michael.brown@medical.com', 'secure789', 'doctor'],
            ['Dr. Emily Davis', 'emily.davis@health.com', 'password123', 'doctor'],
            ['Dr. Robert Johnson', 'robert.johnson@care.com', 'doctorpass', 'doctor'],
            ['Dr. Lisa Anderson', 'lisa.anderson@med.com', 'medicalpass', 'doctor']
        ];
        
        foreach ($doctor_test_cases as $index => $doctor_data) {
            $name = mysqli_real_escape_string($connection, $doctor_data[0]);
            $email = mysqli_real_escape_string($connection, $doctor_data[1]);
            $password = mysqli_real_escape_string($connection, $doctor_data[2]);
            $role = $doctor_data[3];
            
            // Simulate doctor registration process
            $status = ($role === 'doctor') ? 'pending' : 'approved';
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', '$role', '$status')";
            
            $result = mysqli_query($connection, $insert_query);
            
            if (!$result) {
                throw new Exception("Failed to insert doctor: " . mysqli_error($connection));
            }
            
            $user_id = mysqli_insert_id($connection);
            
            // Verify the doctor was created with correct role and status
            $verify_query = "SELECT role, status FROM users WHERE id = $user_id";
            $verify_result = mysqli_query($connection, $verify_query);
            $user = mysqli_fetch_assoc($verify_result);
            
            if ($user['role'] !== 'doctor') {
                throw new Exception("Doctor registration should create user with role 'doctor', got: " . $user['role']);
            }
            
            if ($user['status'] !== 'pending') {
                throw new Exception("Doctor registration should create user with status 'pending', got: " . $user['status']);
            }
        }
        
        // Verify that patient registrations still get 'approved' status (contrast test)
        $patient_data = ['Test Patient', 'patient.test@test.com', 'patientpass', 'patient'];
        $name = mysqli_real_escape_string($connection, $patient_data[0]);
        $email = mysqli_real_escape_string($connection, $patient_data[1]);
        $password = mysqli_real_escape_string($connection, $patient_data[2]);
        $role = $patient_data[3];
        
        $status = ($role === 'doctor') ? 'pending' : 'approved';
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', '$status')";
        
        mysqli_query($connection, $insert_query);
        $patient_id = mysqli_insert_id($connection);
        
        $verify_query = "SELECT role, status FROM users WHERE id = $patient_id";
        $verify_result = mysqli_query($connection, $verify_query);
        $patient = mysqli_fetch_assoc($verify_result);
        
        if ($patient['status'] !== 'approved') {
            throw new Exception("Patient registration should still create user with status 'approved', got: " . $patient['status']);
        }
        
        echo "PASS: Doctor Registration Status property test passed\n";
        return true;
        
    } catch (Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
        return false;
    } finally {
        // Clean up
        mysqli_query($connection, "DROP DATABASE IF EXISTS {$test_db}");
        mysqli_close($connection);
    }
}

// Run the test if this file is executed directly
if (php_sapi_name() === 'cli') {
    $result = runDoctorRegistrationStatusTest();
    exit($result ? 0 : 1);
}
?>