<?php
/**
 * Simple test runner for Database Referential Integrity Property Test
 * Feature: health-alert-system, Property 18: Database Referential Integrity
 * 
 * This test validates that foreign key constraints maintain referential integrity
 * Validates: Requirements 8.5
 */

// Test configuration
$test_db = 'health_alert_system_test';
$connection = null;

function runDatabaseIntegrityTest() {
    global $connection, $test_db;
    
    echo "Running Database Referential Integrity Property Test...\n";
    
    // Create test database connection
    $connection = mysqli_connect('localhost', 'root', '', '');
    
    if (!$connection) {
        echo "SKIP: Cannot connect to MySQL server\n";
        return false;
    }
    
    // Create test database
    mysqli_query($connection, "CREATE DATABASE IF NOT EXISTS {$test_db}");
    mysqli_select_db($connection, $test_db);
    
    // Create tables with foreign key constraints
    createTestTables();
    
    try {
        // Run the property test
        testDatabaseReferentialIntegrity();
        echo "PASS: Database referential integrity property test passed\n";
        return true;
    } catch (Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
        return false;
    } finally {
        // Clean up
        if ($connection) {
            mysqli_query($connection, "DROP DATABASE IF EXISTS {$test_db}");
            mysqli_close($connection);
        }
    }
}

function createTestTables() {
    global $connection;
    
    $sql = "
    CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('patient', 'doctor', 'admin') NOT NULL,
        status ENUM('pending', 'approved') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
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
    
    CREATE TABLE doctor_patients (
        id INT PRIMARY KEY AUTO_INCREMENT,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (doctor_id, patient_id)
    );
    ";
    
    // Execute each statement separately
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $result = mysqli_query($connection, $statement);
            if (!$result) {
                throw new Exception("Failed to create table: " . mysqli_error($connection));
            }
        }
    }
}

function testDatabaseReferentialIntegrity() {
    global $connection;
    
    // Test data for multiple iterations (property-based approach)
    $test_cases = [
        ['John Doe', 'john@test.com', 'password123', 'patient'],
        ['Dr. Smith', 'smith@test.com', 'doctor123', 'doctor'],
        ['Jane Patient', 'jane@test.com', 'pass456', 'patient'],
        ['Dr. Wilson', 'wilson@test.com', 'doc789', 'doctor'],
        ['Admin User', 'admin@test.com', 'admin123', 'admin']
    ];
    
    foreach ($test_cases as $index => $user_data) {
        // Insert valid user
        $name = mysqli_real_escape_string($connection, $user_data[0]);
        $email = mysqli_real_escape_string($connection, $user_data[1]);
        $password = mysqli_real_escape_string($connection, $user_data[2]);
        $role = $user_data[3];
        
        $user_query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
        $user_result = mysqli_query($connection, $user_query);
        
        if (!$user_result) {
            throw new Exception("Should be able to insert valid user: " . mysqli_error($connection));
        }
        
        $user_id = mysqli_insert_id($connection);
        
        if ($role === 'patient') {
            // Test health_data foreign key constraint
            $health_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                            VALUES ($user_id, 120, 80, 95.5, 72)";
            $health_result = mysqli_query($connection, $health_query);
            
            if (!$health_result) {
                throw new Exception("Should be able to insert health data with valid patient_id");
            }
            
            // Test invalid patient_id (should fail)
            $invalid_patient_id = $user_id + 1000; // Non-existent user ID
            $invalid_health_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                                    VALUES ($invalid_patient_id, 120, 80, 95.5, 72)";
            $invalid_health_result = mysqli_query($connection, $invalid_health_query);
            
            if ($invalid_health_result) {
                throw new Exception("Should not be able to insert health data with invalid patient_id");
            }
        }
        
        if ($role === 'doctor') {
            $doctor_id = $user_id;
            
            // Find a patient to create valid relationships
            $patient_query = "SELECT id FROM users WHERE role = 'patient' LIMIT 1";
            $patient_result = mysqli_query($connection, $patient_query);
            
            if ($patient_result && mysqli_num_rows($patient_result) > 0) {
                $patient = mysqli_fetch_assoc($patient_result);
                $patient_id = $patient['id'];
                
                // Test alerts foreign key constraints
                $alert_query = "INSERT INTO alerts (doctor_id, patient_id, message) 
                               VALUES ($doctor_id, $patient_id, 'Test alert message')";
                $alert_result = mysqli_query($connection, $alert_query);
                
                if (!$alert_result) {
                    throw new Exception("Should be able to insert alert with valid doctor_id and patient_id");
                }
                
                // Test invalid doctor_id in alerts (should fail)
                $invalid_doctor_id = $doctor_id + 1000;
                $invalid_alert_query = "INSERT INTO alerts (doctor_id, patient_id, message) 
                                       VALUES ($invalid_doctor_id, $patient_id, 'Invalid alert')";
                $invalid_alert_result = mysqli_query($connection, $invalid_alert_query);
                
                if ($invalid_alert_result) {
                    throw new Exception("Should not be able to insert alert with invalid doctor_id");
                }
            }
        }
    }
    
    // Test CASCADE DELETE behavior
    testCascadeDelete();
}

function testCascadeDelete() {
    global $connection;
    
    // Create a user and related data
    $user_query = "INSERT INTO users (name, email, password, role) VALUES ('Test User', 'test@cascade.com', 'pass123', 'patient')";
    mysqli_query($connection, $user_query);
    $user_id = mysqli_insert_id($connection);
    
    // Add health data for this user
    $health_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                    VALUES ($user_id, 130, 85, 100.0, 75)";
    mysqli_query($connection, $health_query);
    
    // Verify health data exists
    $check_health = "SELECT COUNT(*) as count FROM health_data WHERE patient_id = $user_id";
    $health_result = mysqli_query($connection, $check_health);
    $health_count = mysqli_fetch_assoc($health_result)['count'];
    
    if ($health_count == 0) {
        throw new Exception("Health data should exist before user deletion");
    }
    
    // Delete the user
    $delete_user = "DELETE FROM users WHERE id = $user_id";
    mysqli_query($connection, $delete_user);
    
    // Verify health data was cascaded (deleted)
    $check_health_after = "SELECT COUNT(*) as count FROM health_data WHERE patient_id = $user_id";
    $health_result_after = mysqli_query($connection, $check_health_after);
    $health_count_after = mysqli_fetch_assoc($health_result_after)['count'];
    
    if ($health_count_after != 0) {
        throw new Exception("Health data should be deleted when user is deleted (CASCADE)");
    }
}

// Run the test if this file is executed directly
if (php_sapi_name() === 'cli') {
    $result = runDatabaseIntegrityTest();
    exit($result ? 0 : 1);
}
?>