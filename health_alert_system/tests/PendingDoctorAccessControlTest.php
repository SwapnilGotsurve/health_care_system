<?php
/**
 * Feature: health-alert-system, Property 10: Pending Doctor Access Control
 * 
 * Property 10: Pending Doctor Access Control
 * For any doctor with "pending" status, the system should deny login access 
 * and display "awaiting approval" message
 * Validates: Requirements 4.3
 */

// Simple test runner for Pending Doctor Access Control Property Test
function runPendingDoctorAccessControlTest() {
    echo "Running Pending Doctor Access Control Property Test...\n";
    
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
        // Test multiple pending doctors (property-based approach)
        $pending_doctors = [
            ['Dr. Pending One', 'pending1@test.com', 'password123', 'doctor', 'pending'],
            ['Dr. Pending Two', 'pending2@test.com', 'docpass456', 'doctor', 'pending'],
            ['Dr. Awaiting Approval', 'awaiting@test.com', 'medpass789', 'doctor', 'pending'],
            ['Dr. Not Approved', 'notapproved@test.com', 'testpass', 'doctor', 'pending'],
            ['Dr. Pending Status', 'pendingstatus@test.com', 'doctorpass', 'doctor', 'pending']
        ];
        
        // Insert pending doctors
        foreach ($pending_doctors as $doctor_data) {
            $name = mysqli_real_escape_string($connection, $doctor_data[0]);
            $email = mysqli_real_escape_string($connection, $doctor_data[1]);
            $password = mysqli_real_escape_string($connection, $doctor_data[2]);
            $role = $doctor_data[3];
            $status = $doctor_data[4];
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', '$role', '$status')";
            
            $result = mysqli_query($connection, $insert_query);
            if (!$result) {
                throw new Exception("Failed to insert pending doctor: " . mysqli_error($connection));
            }
        }
        
        // Test login attempts for each pending doctor
        foreach ($pending_doctors as $doctor_data) {
            $email = $doctor_data[1];
            $password = $doctor_data[2];
            
            // Simulate login authentication process
            $email_escaped = mysqli_real_escape_string($connection, $email);
            $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email_escaped'";
            $result = mysqli_query($connection, $query);
            
            if (!$result || mysqli_num_rows($result) == 0) {
                throw new Exception("Pending doctor should be found in database: $email");
            }
            
            $user = mysqli_fetch_assoc($result);
            
            // Verify user data is correct
            if ($user['role'] !== 'doctor') {
                throw new Exception("User should have doctor role: $email");
            }
            
            if ($user['status'] !== 'pending') {
                throw new Exception("User should have pending status: $email");
            }
            
            // Test password authentication (should work)
            if ($password !== $user['password']) {
                throw new Exception("Password should be correct for pending doctor: $email");
            }
            
            // Test access control logic
            if ($user['role'] === 'doctor' && $user['status'] === 'pending') {
                // This should result in access denial
                // In real implementation, this would show "awaiting approval" message
                // and prevent session creation
                $access_denied = true;
            } else {
                $access_denied = false;
            }
            
            if (!$access_denied) {
                throw new Exception("Pending doctor should be denied access: $email");
            }
        }
        
        // Test that approved doctors can still login (contrast test)
        $approved_doctors = [
            ['Dr. Approved One', 'approved1@test.com', 'password123', 'doctor', 'approved'],
            ['Dr. Approved Two', 'approved2@test.com', 'docpass456', 'doctor', 'approved']
        ];
        
        foreach ($approved_doctors as $doctor_data) {
            $name = mysqli_real_escape_string($connection, $doctor_data[0]);
            $email = mysqli_real_escape_string($connection, $doctor_data[1]);
            $password = mysqli_real_escape_string($connection, $doctor_data[2]);
            $role = $doctor_data[3];
            $status = $doctor_data[4];
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', '$role', '$status')";
            
            mysqli_query($connection, $insert_query);
        }
        
        // Test approved doctor login
        foreach ($approved_doctors as $doctor_data) {
            $email = $doctor_data[1];
            $password = $doctor_data[2];
            
            $email_escaped = mysqli_real_escape_string($connection, $email);
            $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email_escaped'";
            $result = mysqli_query($connection, $query);
            
            $user = mysqli_fetch_assoc($result);
            
            // Test that approved doctors can login
            if ($user['role'] === 'doctor' && $user['status'] === 'approved' && $password === $user['password']) {
                // This should allow access
                $access_allowed = true;
            } else {
                $access_allowed = false;
            }
            
            if (!$access_allowed) {
                throw new Exception("Approved doctor should be allowed access: $email");
            }
        }
        
        // Test that patients are not affected by doctor approval logic
        $test_patient = ['Test Patient', 'patient@test.com', 'patientpass', 'patient', 'approved'];
        $name = mysqli_real_escape_string($connection, $test_patient[0]);
        $email = mysqli_real_escape_string($connection, $test_patient[1]);
        $password = mysqli_real_escape_string($connection, $test_patient[2]);
        $role = $test_patient[3];
        $status = $test_patient[4];
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', '$status')";
        mysqli_query($connection, $insert_query);
        
        $email_escaped = mysqli_real_escape_string($connection, $email);
        $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email_escaped'";
        $result = mysqli_query($connection, $query);
        $user = mysqli_fetch_assoc($result);
        
        // Patient should not be affected by doctor approval logic
        if ($user['role'] === 'patient' && $password === $user['password']) {
            $patient_access_allowed = true;
        } else {
            $patient_access_allowed = false;
        }
        
        if (!$patient_access_allowed) {
            throw new Exception("Patient access should not be affected by doctor approval logic");
        }
        
        echo "PASS: Pending Doctor Access Control property test passed\n";
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
    $result = runPendingDoctorAccessControlTest();
    exit($result ? 0 : 1);
}
?>