<?php
/**
 * Feature: health-alert-system, Property 7: Alert Display Completeness
 * 
 * Property 7: Alert Display Completeness
 * For any patient with alerts, the alerts page should display all alerts sent to them 
 * with complete information (message, doctor, timestamp)
 * Validates: Requirements 3.1, 3.2
 */

// Simple test runner for Alert Display Completeness Property Test
function runAlertDisplayCompletenessTest() {
    echo "Running Alert Display Completeness Property Test...\n";
    
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
    mysqli_query($connection, $create_alerts);
    
    try {
        // Create test users (doctors and patients)
        $test_doctors = [
            ['Dr. John Smith', 'doctor1@test.com', 'password123', 'doctor'],
            ['Dr. Sarah Wilson', 'doctor2@test.com', 'password456', 'doctor'],
            ['Dr. Michael Brown', 'doctor3@test.com', 'password789', 'doctor']
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
        
        // Test alert display completeness for each patient
        foreach ($patient_ids as $patient_index => $patient_id) {
            // Create various alerts for this patient from different doctors
            $alert_scenarios = [
                [
                    'doctor_id' => $doctor_ids[0],
                    'message' => 'Please monitor your blood pressure more closely and reduce salt intake.',
                    'status' => 'sent',
                    'timestamp' => date('Y-m-d H:i:s', time() - 3600) // 1 hour ago
                ],
                [
                    'doctor_id' => $doctor_ids[1],
                    'message' => 'Your recent blood sugar levels are concerning. Please schedule a follow-up appointment.',
                    'status' => 'seen',
                    'timestamp' => date('Y-m-d H:i:s', time() - 7200) // 2 hours ago
                ],
                [
                    'doctor_id' => $doctor_ids[2],
                    'message' => 'Great job on maintaining healthy heart rate! Keep up the good work.',
                    'status' => 'sent',
                    'timestamp' => date('Y-m-d H:i:s', time() - 1800) // 30 minutes ago
                ],
                [
                    'doctor_id' => $doctor_ids[0],
                    'message' => 'Reminder: Take your medication as prescribed and record your daily readings.',
                    'status' => 'seen',
                    'timestamp' => date('Y-m-d H:i:s', time() - 86400) // 1 day ago
                ]
            ];
            
            $inserted_alert_ids = [];
            
            // Insert alerts for this patient
            foreach ($alert_scenarios as $alert) {
                $doctor_id = $alert['doctor_id'];
                $message = mysqli_real_escape_string($connection, $alert['message']);
                $status = $alert['status'];
                $timestamp = $alert['timestamp'];
                
                $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status, created_at) 
                               VALUES ($doctor_id, $patient_id, '$message', '$status', '$timestamp')";
                
                mysqli_query($connection, $insert_query);
                $inserted_alert_ids[] = mysqli_insert_id($connection);
            }
            
            // Test alert display query (simulate alerts.php)
            $display_query = "SELECT a.*, u.name as doctor_name 
                            FROM alerts a 
                            JOIN users u ON a.doctor_id = u.id 
                            WHERE a.patient_id = $patient_id
                            ORDER BY a.created_at DESC";
            
            $display_result = mysqli_query($connection, $display_query);
            
            if (!$display_result) {
                throw new Exception("Alert display query should succeed for patient ID: $patient_id");
            }
            
            $displayed_alerts = [];
            while ($row = mysqli_fetch_assoc($display_result)) {
                $displayed_alerts[] = $row;
            }
            
            // Test completeness: all alerts should be displayed
            if (count($displayed_alerts) !== count($alert_scenarios)) {
                throw new Exception("All alerts should be displayed. Expected: " . count($alert_scenarios) . ", Got: " . count($displayed_alerts));
            }
            
            // Test information completeness for each displayed alert
            foreach ($displayed_alerts as $alert) {
                // Test required fields are present
                $required_fields = ['id', 'doctor_id', 'patient_id', 'message', 'status', 'created_at', 'doctor_name'];
                
                foreach ($required_fields as $field) {
                    if (!isset($alert[$field]) || $alert[$field] === null) {
                        throw new Exception("Alert should contain '$field' field. Alert ID: " . $alert['id']);
                    }
                }
                
                // Test message content is not empty
                if (empty(trim($alert['message']))) {
                    throw new Exception("Alert message should not be empty. Alert ID: " . $alert['id']);
                }
                
                // Test doctor name is not empty
                if (empty(trim($alert['doctor_name']))) {
                    throw new Exception("Doctor name should not be empty. Alert ID: " . $alert['id']);
                }
                
                // Test timestamp is valid
                $timestamp = strtotime($alert['created_at']);
                if ($timestamp === false) {
                    throw new Exception("Alert timestamp should be valid. Alert ID: " . $alert['id']);
                }
                
                // Test status is valid
                if (!in_array($alert['status'], ['sent', 'seen'])) {
                    throw new Exception("Alert status should be 'sent' or 'seen'. Got: " . $alert['status']);
                }
                
                // Test patient_id matches
                if ($alert['patient_id'] != $patient_id) {
                    throw new Exception("Alert patient_id should match queried patient. Expected: $patient_id, Got: " . $alert['patient_id']);
                }
                
                // Test doctor_id is valid
                if (!in_array($alert['doctor_id'], $doctor_ids)) {
                    throw new Exception("Alert doctor_id should be valid. Got: " . $alert['doctor_id']);
                }
            }
            
            // Test chronological ordering (newest first)
            for ($i = 1; $i < count($displayed_alerts); $i++) {
                $current_time = strtotime($displayed_alerts[$i]['created_at']);
                $previous_time = strtotime($displayed_alerts[$i-1]['created_at']);
                
                if ($current_time > $previous_time) {
                    throw new Exception("Alerts should be ordered chronologically (newest first)");
                }
            }
        }
        
        // Test filtering functionality
        $test_patient_id = $patient_ids[0];
        
        // Test unread alerts filter
        $unread_query = "SELECT a.*, u.name as doctor_name 
                        FROM alerts a 
                        JOIN users u ON a.doctor_id = u.id 
                        WHERE a.patient_id = $test_patient_id AND a.status = 'sent'
                        ORDER BY a.created_at DESC";
        
        $unread_result = mysqli_query($connection, $unread_query);
        $unread_alerts = [];
        while ($row = mysqli_fetch_assoc($unread_result)) {
            $unread_alerts[] = $row;
        }
        
        // Verify all returned alerts have 'sent' status
        foreach ($unread_alerts as $alert) {
            if ($alert['status'] !== 'sent') {
                throw new Exception("Unread filter should only return alerts with 'sent' status");
            }
        }
        
        // Test read alerts filter
        $read_query = "SELECT a.*, u.name as doctor_name 
                      FROM alerts a 
                      JOIN users u ON a.doctor_id = u.id 
                      WHERE a.patient_id = $test_patient_id AND a.status = 'seen'
                      ORDER BY a.created_at DESC";
        
        $read_result = mysqli_query($connection, $read_query);
        $read_alerts = [];
        while ($row = mysqli_fetch_assoc($read_result)) {
            $read_alerts[] = $row;
        }
        
        // Verify all returned alerts have 'seen' status
        foreach ($read_alerts as $alert) {
            if ($alert['status'] !== 'seen') {
                throw new Exception("Read filter should only return alerts with 'seen' status");
            }
        }
        
        // Test pagination completeness
        $page_size = 2;
        $total_alerts_query = "SELECT COUNT(*) as total FROM alerts WHERE patient_id = $test_patient_id";
        $total_result = mysqli_query($connection, $total_alerts_query);
        $total_alerts = mysqli_fetch_assoc($total_result)['total'];
        
        $all_paginated_alerts = [];
        $pages_needed = ceil($total_alerts / $page_size);
        
        for ($page = 0; $page < $pages_needed; $page++) {
            $offset = $page * $page_size;
            
            $page_query = "SELECT a.*, u.name as doctor_name 
                          FROM alerts a 
                          JOIN users u ON a.doctor_id = u.id 
                          WHERE a.patient_id = $test_patient_id
                          ORDER BY a.created_at DESC 
                          LIMIT $page_size OFFSET $offset";
            
            $page_result = mysqli_query($connection, $page_query);
            
            while ($row = mysqli_fetch_assoc($page_result)) {
                $all_paginated_alerts[] = $row;
            }
        }
        
        // Verify pagination returns all alerts
        if (count($all_paginated_alerts) !== $total_alerts) {
            throw new Exception("Pagination should return all alerts. Expected: $total_alerts, Got: " . count($all_paginated_alerts));
        }
        
        // Test alert information with special characters and long messages
        $special_message = "Alert with special characters: <script>alert('test')</script> & symbols: @#$%^&*()";
        $long_message = str_repeat("This is a very long alert message that tests the system's ability to handle lengthy content. ", 10);
        
        $special_alerts = [
            ['message' => $special_message, 'description' => 'special characters'],
            ['message' => $long_message, 'description' => 'long message'],
            ['message' => '', 'description' => 'empty message'],
            ['message' => '   ', 'description' => 'whitespace only message']
        ];
        
        foreach ($special_alerts as $special_alert) {
            $message = mysqli_real_escape_string($connection, $special_alert['message']);
            $description = $special_alert['description'];
            
            $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status) 
                           VALUES ({$doctor_ids[0]}, $test_patient_id, '$message', 'sent')";
            
            mysqli_query($connection, $insert_query);
            $alert_id = mysqli_insert_id($connection);
            
            // Retrieve and verify the alert
            $verify_query = "SELECT a.*, u.name as doctor_name 
                           FROM alerts a 
                           JOIN users u ON a.doctor_id = u.id 
                           WHERE a.id = $alert_id";
            
            $verify_result = mysqli_query($connection, $verify_query);
            $retrieved_alert = mysqli_fetch_assoc($verify_result);
            
            if (!$retrieved_alert) {
                throw new Exception("Alert with $description should be retrievable");
            }
            
            // Verify message content is preserved (except for empty/whitespace)
            if (!empty(trim($special_alert['message']))) {
                if ($retrieved_alert['message'] !== $special_alert['message']) {
                    throw new Exception("Alert message should be preserved for $description");
                }
            }
            
            // Verify all required fields are still present
            if (empty($retrieved_alert['doctor_name']) || empty($retrieved_alert['created_at'])) {
                throw new Exception("Alert with $description should have complete information");
            }
        }
        
        echo "PASS: Alert Display Completeness property test passed\n";
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
    $result = runAlertDisplayCompletenessTest();
    exit($result ? 0 : 1);
}
?>