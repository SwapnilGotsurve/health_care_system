<?php
/**
 * Feature: health-alert-system, Property 13: Alert Storage Integrity
 * 
 * Property 13: Alert Storage Integrity
 * For any alert sent by a doctor, the system should store the alert with correct 
 * doctor ID, patient ID, message, and timestamp
 * Validates: Requirements 6.2
 */

// Simple test runner for Alert Storage Integrity Property Test
function runAlertStorageIntegrityTest() {
    echo "Running Alert Storage Integrity Property Test...\n";
    
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
    
    $create_doctor_patients = "
    CREATE TABLE doctor_patients (
        id INT PRIMARY KEY AUTO_INCREMENT,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (doctor_id, patient_id)
    )";
    
    $create_alerts = "
    CREATE TABLE alerts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        message TEXT NOT NULL,
        status ENUM('sent', 'seen') DEFAULT 'sent',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    mysqli_query($connection, $create_users);
    mysqli_query($connection, $create_doctor_patients);
    mysqli_query($connection, $create_alerts);
    
    try {
        // Create test doctors and patients
        $test_doctors = [
            ['Dr. John Smith', 'doctor1@test.com', 'password123', 'doctor'],
            ['Dr. Sarah Wilson', 'doctor2@test.com', 'password456', 'doctor']
        ];
        
        $test_patients = [
            ['Patient One', 'patient1@test.com', 'password123', 'patient'],
            ['Patient Two', 'patient2@test.com', 'password456', 'patient'],
            ['Patient Three', 'patient3@test.com', 'password789', 'patient']
        ];
        
        $doctor_ids = [];
        $patient_ids = [];
        
        // Insert doctors
        foreach ($test_doctors as $doctor_data) {
            $name = mysqli_real_escape_string($connection, $doctor_data[0]);
            $email = mysqli_real_escape_string($connection, $doctor_data[1]);
            $password = mysqli_real_escape_string($connection, $doctor_data[2]);
            $role = $doctor_data[3];
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', '$role', 'approved')";
            
            mysqli_query($connection, $insert_query);
            $doctor_ids[] = mysqli_insert_id($connection);
        }
        
        // Insert patients
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
        
        // Create doctor-patient assignments
        $assignments = [
            [$doctor_ids[0], $patient_ids[0]],
            [$doctor_ids[0], $patient_ids[1]],
            [$doctor_ids[1], $patient_ids[2]]
        ];
        
        foreach ($assignments as $assignment) {
            $doctor_id = $assignment[0];
            $patient_id = $assignment[1];
            
            $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                           VALUES ($doctor_id, $patient_id)";
            
            mysqli_query($connection, $assign_query);
        }
        
        // Test alert storage scenarios
        $alert_scenarios = [
            [
                'doctor_id' => $doctor_ids[0],
                'patient_id' => $patient_ids[0],
                'message' => 'Please monitor your blood pressure more closely and reduce salt intake.',
                'description' => 'standard health advice'
            ],
            [
                'doctor_id' => $doctor_ids[0],
                'patient_id' => $patient_ids[1],
                'message' => 'Your recent blood sugar levels are concerning. Please schedule a follow-up appointment.',
                'description' => 'urgent follow-up'
            ],
            [
                'doctor_id' => $doctor_ids[1],
                'patient_id' => $patient_ids[2],
                'message' => 'Great job on maintaining healthy readings! Keep up the excellent work.',
                'description' => 'positive feedback'
            ],
            [
                'doctor_id' => $doctor_ids[0],
                'patient_id' => $patient_ids[0],
                'message' => 'Reminder: Take your medication as prescribed.',
                'description' => 'medication reminder'
            ]
        ];
        
        $test_start_time = time();
        
        foreach ($alert_scenarios as $scenario) {
            $doctor_id = $scenario['doctor_id'];
            $patient_id = $scenario['patient_id'];
            $message = $scenario['message'];
            $description = $scenario['description'];
            
            // Simulate alert sending (from send_alert.php)
            $message_escaped = mysqli_real_escape_string($connection, $message);
            
            $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status, created_at) 
                           VALUES ($doctor_id, $patient_id, '$message_escaped', 'sent', NOW())";
            
            $insert_result = mysqli_query($connection, $insert_query);
            
            if (!$insert_result) {
                throw new Exception("Alert insertion should succeed for $description");
            }
            
            $alert_id = mysqli_insert_id($connection);
            
            // Verify stored alert integrity
            $verify_query = "SELECT * FROM alerts WHERE id = $alert_id";
            $verify_result = mysqli_query($connection, $verify_query);
            
            if (!$verify_result || mysqli_num_rows($verify_result) == 0) {
                throw new Exception("Inserted alert should be retrievable for $description");
            }
            
            $stored_alert = mysqli_fetch_assoc($verify_result);
            
            // Test doctor ID integrity
            if ($stored_alert['doctor_id'] != $doctor_id) {
                throw new Exception("Stored doctor_id should match. Expected: $doctor_id, Got: " . $stored_alert['doctor_id']);
            }
            
            // Test patient ID integrity
            if ($stored_alert['patient_id'] != $patient_id) {
                throw new Exception("Stored patient_id should match. Expected: $patient_id, Got: " . $stored_alert['patient_id']);
            }
            
            // Test message integrity
            if ($stored_alert['message'] !== $message) {
                throw new Exception("Stored message should match original. Expected: '$message', Got: '" . $stored_alert['message'] . "'");
            }
            
            // Test default status
            if ($stored_alert['status'] !== 'sent') {
                throw new Exception("Default alert status should be 'sent'. Got: " . $stored_alert['status']);
            }
            
            // Test timestamp integrity
            $stored_timestamp = strtotime($stored_alert['created_at']);
            $time_difference = abs($stored_timestamp - time());
            
            if ($time_difference > 60) { // Allow 1 minute tolerance
                throw new Exception("Stored timestamp should be current time for $description. Time difference: $time_difference seconds");
            }
            
            // Test timestamp is not in future
            if ($stored_timestamp > time() + 10) { // Allow 10 second tolerance
                throw new Exception("Stored timestamp should not be in future for $description");
            }
            
            // Test timestamp is after test start
            if ($stored_timestamp < $test_start_time - 10) { // Allow 10 second tolerance
                throw new Exception("Stored timestamp should be after test start for $description");
            }
        }
        
        // Test foreign key constraints
        $invalid_doctor_id = 99999;
        $invalid_patient_id = 99999;
        
        // Test invalid doctor ID
        $invalid_doctor_query = "INSERT INTO alerts (doctor_id, patient_id, message) 
                               VALUES ($invalid_doctor_id, {$patient_ids[0]}, 'Test message')";
        
        $invalid_doctor_result = mysqli_query($connection, $invalid_doctor_query);
        
        if ($invalid_doctor_result) {
            throw new Exception("Alert insertion should fail for invalid doctor ID");
        }
        
        // Test invalid patient ID
        $invalid_patient_query = "INSERT INTO alerts (doctor_id, patient_id, message) 
                                VALUES ({$doctor_ids[0]}, $invalid_patient_id, 'Test message')";
        
        $invalid_patient_result = mysqli_query($connection, $invalid_patient_query);
        
        if ($invalid_patient_result) {
            throw new Exception("Alert insertion should fail for invalid patient ID");
        }
        
        // Test message content integrity with special characters
        $special_messages = [
            "Alert with quotes: 'single' and \"double\" quotes",
            "Alert with symbols: @#$%^&*()_+-={}[]|\\:;\"'<>?,./",
            "Alert with newlines:\nLine 1\nLine 2\nLine 3",
            "Alert with unicode: 🏥 Health Alert 📊 Check your readings! 💊",
            "Alert with HTML: <script>alert('test')</script> <b>Bold</b> text"
        ];
        
        foreach ($special_messages as $special_message) {
            $escaped_message = mysqli_real_escape_string($connection, $special_message);
            
            $special_insert = "INSERT INTO alerts (doctor_id, patient_id, message) 
                             VALUES ({$doctor_ids[0]}, {$patient_ids[0]}, '$escaped_message')";
            
            mysqli_query($connection, $special_insert);
            $special_alert_id = mysqli_insert_id($connection);
            
            // Verify message integrity
            $special_verify = "SELECT message FROM alerts WHERE id = $special_alert_id";
            $special_result = mysqli_query($connection, $special_verify);
            $retrieved_message = mysqli_fetch_assoc($special_result)['message'];
            
            if ($retrieved_message !== $special_message) {
                throw new Exception("Special character message should be preserved. Expected: '$special_message', Got: '$retrieved_message'");
            }
        }
        
        // Test long message handling
        $long_message = str_repeat("This is a long alert message. ", 50); // ~1500 characters
        $long_escaped = mysqli_real_escape_string($connection, $long_message);
        
        $long_insert = "INSERT INTO alerts (doctor_id, patient_id, message) 
                      VALUES ({$doctor_ids[0]}, {$patient_ids[0]}, '$long_escaped')";
        
        mysqli_query($connection, $long_insert);
        $long_alert_id = mysqli_insert_id($connection);
        
        // Verify long message integrity
        $long_verify = "SELECT message FROM alerts WHERE id = $long_alert_id";
        $long_result = mysqli_query($connection, $long_verify);
        $retrieved_long = mysqli_fetch_assoc($long_result)['message'];
        
        if ($retrieved_long !== $long_message) {
            throw new Exception("Long message should be preserved completely");
        }
        
        // Test bulk alert storage
        $bulk_doctor_id = $doctor_ids[1];
        $bulk_patient_id = $patient_ids[2];
        $bulk_count = 25;
        
        for ($i = 0; $i < $bulk_count; $i++) {
            $bulk_message = mysqli_real_escape_string($connection, "Bulk alert message #$i");
            
            $bulk_insert = "INSERT INTO alerts (doctor_id, patient_id, message) 
                          VALUES ($bulk_doctor_id, $bulk_patient_id, '$bulk_message')";
            
            if (!mysqli_query($connection, $bulk_insert)) {
                throw new Exception("Bulk alert insertion should succeed for iteration $i");
            }
        }
        
        // Verify bulk storage
        $bulk_count_query = "SELECT COUNT(*) as count FROM alerts 
                           WHERE doctor_id = $bulk_doctor_id AND patient_id = $bulk_patient_id";
        $bulk_count_result = mysqli_query($connection, $bulk_count_query);
        $stored_bulk_count = mysqli_fetch_assoc($bulk_count_result)['count'];
        
        if ($stored_bulk_count < $bulk_count) {
            throw new Exception("All bulk alerts should be stored. Expected at least: $bulk_count, Got: $stored_bulk_count");
        }
        
        // Test concurrent alert storage (simulate race conditions)
        $concurrent_alerts = [];
        for ($i = 0; $i < 5; $i++) {
            $concurrent_message = mysqli_real_escape_string($connection, "Concurrent alert #$i");
            
            $concurrent_insert = "INSERT INTO alerts (doctor_id, patient_id, message) 
                                VALUES ({$doctor_ids[0]}, {$patient_ids[0]}, '$concurrent_message')";
            
            mysqli_query($connection, $concurrent_insert);
            $concurrent_alerts[] = mysqli_insert_id($connection);
        }
        
        // Verify all concurrent alerts were stored
        foreach ($concurrent_alerts as $alert_id) {
            $concurrent_verify = "SELECT id FROM alerts WHERE id = $alert_id";
            $concurrent_result = mysqli_query($connection, $concurrent_verify);
            
            if (mysqli_num_rows($concurrent_result) == 0) {
                throw new Exception("Concurrent alert should be stored. Alert ID: $alert_id");
            }
        }
        
        // Test alert storage with explicit timestamp
        $explicit_timestamp = date('Y-m-d H:i:s', time() - 3600); // 1 hour ago
        $explicit_message = mysqli_real_escape_string($connection, 'Alert with explicit timestamp');
        
        $explicit_insert = "INSERT INTO alerts (doctor_id, patient_id, message, created_at) 
                          VALUES ({$doctor_ids[0]}, {$patient_ids[0]}, '$explicit_message', '$explicit_timestamp')";
        
        mysqli_query($connection, $explicit_insert);
        $explicit_alert_id = mysqli_insert_id($connection);
        
        // Verify explicit timestamp
        $explicit_verify = "SELECT created_at FROM alerts WHERE id = $explicit_alert_id";
        $explicit_result = mysqli_query($connection, $explicit_verify);
        $stored_explicit_time = mysqli_fetch_assoc($explicit_result)['created_at'];
        
        if ($stored_explicit_time !== $explicit_timestamp) {
            throw new Exception("Explicit timestamp should be preserved. Expected: $explicit_timestamp, Got: $stored_explicit_time");
        }
        
        echo "PASS: Alert Storage Integrity property test passed\n";
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
    $result = runAlertStorageIntegrityTest();
    exit($result ? 0 : 1);
}
?>