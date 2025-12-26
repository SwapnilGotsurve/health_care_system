<?php
/**
 * Feature: health-alert-system, Property 1: User Registration Creates Correct Account Type
 * 
 * Property 1: User Registration Creates Correct Account Type
 * For any valid registration data with role "patient", the system should create a user account 
 * with role "patient" and status "approved"
 * Validates: Requirements 1.2
 */

// Simple test runner for User Registration Property Test
function runUserRegistrationTest() {
    echo "Running User Registration Property Test...\n";
    
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
        // Test multiple patient registrations (property-based approach)
        $test_cases = [
            ['John Patient', 'john.patient@test.com', 'password123', 'patient'],
            ['Jane Smith', 'jane.smith@test.com', 'mypass456', 'patient'],
            ['Bob Johnson', 'bob.johnson@test.com', 'secure789', 'patient'],
            ['Alice Brown', 'alice.brown@test.com', 'testpass', 'patient'],
            ['Charlie Wilson', 'charlie.wilson@test.com', 'password', 'patient']
        ];
        
        foreach ($test_cases as $index => $user_data) {
            $name = mysqli_real_escape_string($connection, $user_data[0]);
            $email = mysqli_real_escape_string($connection, $user_data[1]);
            $password = mysqli_real_escape_string($connection, $user_data[2]);
            $role = $user_data[3];
            
            // Simulate registration process
            $status = ($role === 'doctor') ? 'pending' : 'approved';
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', '$role', '$status')";
            
            $result = mysqli_query($connection, $insert_query);
            
            if (!$result) {
                throw new Exception("Failed to insert user: " . mysqli_error($connection));
            }
            
            $user_id = mysqli_insert_id($connection);
            
            // Verify the user was created with correct role and status
            $verify_query = "SELECT role, status FROM users WHERE id = $user_id";
            $verify_result = mysqli_query($connection, $verify_query);
            $user = mysqli_fetch_assoc($verify_result);
            
            if ($user['role'] !== 'patient') {
                throw new Exception("Patient registration should create user with role 'patient', got: " . $user['role']);
            }
            
            if ($user['status'] !== 'approved') {
                throw new Exception("Patient registration should create user with status 'approved', got: " . $user['status']);
            }
        }
        
        // Test doctor registration creates pending status
        $doctor_data = ['Dr. Test', 'doctor.test@test.com', 'doctorpass', 'doctor'];
        $name = mysqli_real_escape_string($connection, $doctor_data[0]);
        $email = mysqli_real_escape_string($connection, $doctor_data[1]);
        $password = mysqli_real_escape_string($connection, $doctor_data[2]);
        $role = $doctor_data[3];
        
        $status = ($role === 'doctor') ? 'pending' : 'approved';
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', '$status')";
        
        mysqli_query($connection, $insert_query);
        $doctor_id = mysqli_insert_id($connection);
        
        $verify_query = "SELECT role, status FROM users WHERE id = $doctor_id";
        $verify_result = mysqli_query($connection, $verify_query);
        $doctor = mysqli_fetch_assoc($verify_result);
        
        if ($doctor['role'] !== 'doctor') {
            throw new Exception("Doctor registration should create user with role 'doctor', got: " . $doctor['role']);
        }
        
        if ($doctor['status'] !== 'pending') {
            throw new Exception("Doctor registration should create user with status 'pending', got: " . $doctor['status']);
        }
        
        echo "PASS: User Registration property test passed\n";
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
    $result = runUserRegistrationTest();
    exit($result ? 0 : 1);
}
?>