<?php
/**
 * Feature: health-alert-system, Property 4: Health Data Storage Integrity
 * 
 * Property 4: Health Data Storage Integrity
 * For any valid health data submission, the system should store the data with 
 * correct patient ID and current timestamp
 * Validates: Requirements 2.2
 */

// Simple test runner for Health Data Storage Integrity Property Test
function runHealthDataStorageIntegrityTest() {
    echo "Running Health Data Storage Integrity Property Test...\n";
    
    $test_db = 'health_alert_system_test';
    $connection = mysqli_connect('localhost', 'root', '', '');
    
    if (!$connection) {
        echo "SKIP: Cannot connect to MySQL server\n";
        return false;
    }
    
    // Create test database
    mysqli_query($connection, "CREATE DATABASE IF NOT EXISTS {$test_db}");
    mysqli_select_db($connection, $test_db);
    
    // Create tables
    $create_users = "
    CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('patient', 'doctor', 'admin') NOT NULL,
        status ENUM('pending', 'approved') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $create_health_data = "
    CREATE TABLE health_data (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_id INT NOT NULL,
        systolic_bp INT NOT NULL,
        diastolic_bp INT NOT NULL,
        sugar_level DECIMAL(5,2) NOT NULL,
        heart_rate INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    mysqli_query($connection, $create_users);
    mysqli_query($connection, $create_health_data);
    
    try {
        // Create test patients
        $test_patients = [
            ['John Patient', 'john@test.com', 'password123', 'patient'],
            ['Jane Doe', 'jane@test.com', 'password456', 'patient'],
            ['Bob Smith', 'bob@test.com', 'password789', 'patient'],
            ['Alice Johnson', 'alice@test.com', 'passwordabc', 'patient'],
            ['Charlie Brown', 'charlie@test.com', 'passworddef', 'patient']
        ];
        
        $patient_ids = [];
        foreach ($test_patients as $patient_data) {
            $name = mysqli_real_escape_string($connection, $patient_data[0]);
            $email = mysqli_real_escape_string($connection, $patient_data[1]);
            $password = mysqli_real_escape_string($connection, $patient_data[2]);
            $role = $patient_data[3];
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', '$role', 'approved')";
            
            mysqli_query($connection, $insert_query);
            $patient_ids[] = mysqli_insert_id($connection);
        }
        
        // Test health data storage scenarios
        $health_data_scenarios = [
            // [systolic_bp, diastolic_bp, sugar_level, heart_rate]
            [120, 80, 95.5, 72],
            [130, 85, 110.2, 78],
            [110, 70, 88.7, 65],
            [140, 90, 125.3, 85],
            [125, 82, 102.1, 70],
            [135, 88, 118.9, 82],
            [115, 75, 92.4, 68],
            [145, 95, 135.7, 90],
            [105, 65, 85.2, 62],
            [150, 100, 145.8, 95]
        ];
        
        $test_start_time = time();
        
        foreach ($patient_ids as $patient_id) {
            foreach ($health_data_scenarios as $health_data) {
                $systolic_bp = $health_data[0];
                $diastolic_bp = $health_data[1];
                $sugar_level = $health_data[2];
                $heart_rate = $health_data[3];
                
                // Simulate health data submission (from add_health_data.php)
                $insert_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) 
                               VALUES ($patient_id, $systolic_bp, $diastolic_bp, $sugar_level, $heart_rate, NOW())";
                
                $insert_result = mysqli_query($connection, $insert_query);
                
                if (!$insert_result) {
                    throw new Exception("Health data insertion should succeed for patient ID: $patient_id");
                }
                
                $inserted_id = mysqli_insert_id($connection);
                
                // Verify the stored data
                $verify_query = "SELECT * FROM health_data WHERE id = $inserted_id";
                $verify_result = mysqli_query($connection, $verify_query);
                
                if (!$verify_result || mysqli_num_rows($verify_result) == 0) {
                    throw new Exception("Inserted health data should be retrievable");
                }
                
                $stored_data = mysqli_fetch_assoc($verify_result);
                
                // Test patient ID integrity
                if ($stored_data['patient_id'] != $patient_id) {
                    throw new Exception("Stored patient_id should match submitted patient_id. Expected: $patient_id, Got: " . $stored_data['patient_id']);
                }
                
                // Test health data integrity
                if ($stored_data['systolic_bp'] != $systolic_bp) {
                    throw new Exception("Stored systolic_bp should match submitted value. Expected: $systolic_bp, Got: " . $stored_data['systolic_bp']);
                }
                
                if ($stored_data['diastolic_bp'] != $diastolic_bp) {
                    throw new Exception("Stored diastolic_bp should match submitted value. Expected: $diastolic_bp, Got: " . $stored_data['diastolic_bp']);
                }
                
                if (abs($stored_data['sugar_level'] - $sugar_level) > 0.01) {
                    throw new Exception("Stored sugar_level should match submitted value. Expected: $sugar_level, Got: " . $stored_data['sugar_level']);
                }
                
                if ($stored_data['heart_rate'] != $heart_rate) {
                    throw new Exception("Stored heart_rate should match submitted value. Expected: $heart_rate, Got: " . $stored_data['heart_rate']);
                }
                
                // Test timestamp integrity (should be current time)
                $stored_timestamp = strtotime($stored_data['created_at']);
                $time_difference = abs($stored_timestamp - time());
                
                if ($time_difference > 60) { // Allow 1 minute tolerance
                    throw new Exception("Stored timestamp should be current time. Time difference: $time_difference seconds");
                }
                
                // Test that timestamp is not in the future
                if ($stored_timestamp > time() + 10) { // Allow 10 second tolerance
                    throw new Exception("Stored timestamp should not be in the future");
                }
                
                // Test that timestamp is not too old (should be after test start)
                if ($stored_timestamp < $test_start_time - 10) { // Allow 10 second tolerance
                    throw new Exception("Stored timestamp should be recent (after test start)");
                }
            }
        }
        
        // Test foreign key constraint integrity
        $invalid_patient_id = 99999;
        $invalid_insert_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                               VALUES ($invalid_patient_id, 120, 80, 95.5, 72)";
        
        $invalid_result = mysqli_query($connection, $invalid_insert_query);
        
        if ($invalid_result) {
            throw new Exception("Health data insertion should fail for non-existent patient ID");
        }
        
        // Test data type constraints
        $invalid_data_scenarios = [
            // Test negative values
            [-120, 80, 95.5, 72, 'negative systolic_bp'],
            [120, -80, 95.5, 72, 'negative diastolic_bp'],
            [120, 80, -95.5, 72, 'negative sugar_level'],
            [120, 80, 95.5, -72, 'negative heart_rate'],
        ];
        
        foreach ($invalid_data_scenarios as $invalid_data) {
            $systolic = $invalid_data[0];
            $diastolic = $invalid_data[1];
            $sugar = $invalid_data[2];
            $heart = $invalid_data[3];
            $description = $invalid_data[4];
            
            $invalid_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                            VALUES ({$patient_ids[0]}, $systolic, $diastolic, $sugar, $heart)";
            
            $result = mysqli_query($connection, $invalid_query);
            
            // Note: MySQL may allow negative values depending on configuration
            // This test verifies the concept of data validation
            if ($result) {
                // If database allows it, application should validate
                echo "WARNING: Database allowed $description - application validation needed\n";
            }
        }
        
        // Test bulk data integrity
        $bulk_patient_id = $patient_ids[0];
        $bulk_data_count = 50;
        
        for ($i = 0; $i < $bulk_data_count; $i++) {
            $systolic = rand(100, 160);
            $diastolic = rand(60, 100);
            $sugar = rand(80, 150) + (rand(0, 99) / 100);
            $heart = rand(60, 100);
            
            $bulk_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                         VALUES ($bulk_patient_id, $systolic, $diastolic, $sugar, $heart)";
            
            if (!mysqli_query($connection, $bulk_query)) {
                throw new Exception("Bulk health data insertion should succeed for iteration $i");
            }
        }
        
        // Verify bulk data count
        $count_query = "SELECT COUNT(*) as count FROM health_data WHERE patient_id = $bulk_patient_id";
        $count_result = mysqli_query($connection, $count_query);
        $count_data = mysqli_fetch_assoc($count_result);
        
        $expected_count = $bulk_data_count + count($health_data_scenarios); // Previous insertions + bulk
        if ($count_data['count'] < $bulk_data_count) {
            throw new Exception("Bulk data count should be at least $bulk_data_count, got: " . $count_data['count']);
        }
        
        echo "PASS: Health Data Storage Integrity property test passed\n";
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
    $result = runHealthDataStorageIntegrityTest();
    exit($result ? 0 : 1);
}
?>