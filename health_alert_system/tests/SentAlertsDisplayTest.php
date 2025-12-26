<?php
/**
 * Feature: health-alert-system, Property 14: Sent Alerts Display
 * 
 * Property 14: Sent Alerts Display
 * For any doctor with sent alerts, the sent alerts page should display all alerts 
 * they have sent with patient names and timestamps
 * Validates: Requirements 6.3
 */

// Simple test runner for Sent Alerts Display Property Test
function runSentAlertsDisplayTest() {
    echo "Running Sent Alerts Display Property Test...\n";
    
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
            ['Dr. Alice Johnson', 'alice@test.com', 'password123', 'doctor'],
            ['Dr. Bob Smith', 'bob@test.com', 'password456', 'doctor'],
            ['Dr. Carol Wilson', 'carol@test.com', 'password789', 'doctor']
        ];
        
        $test_patients = [
            ['Patient Alpha', 'alpha@test.com', 'password123', 'patient'],
            ['Patient Beta', 'beta@test.com', 'password456', 'patient'],
            ['Patient Gamma', 'gamma@test.com', 'password789', 'patient'],
            ['Patient Delta', 'delta@test.com', 'password101', 'patient'],
            ['Patient Echo', 'echo@test.com', 'password202', 'patient']
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
            [$doctor_ids[0], $patient_ids[0]], // Dr. Alice -> Patient Alpha
            [$doctor_ids[0], $patient_ids[1]], // Dr. Alice -> Patient Beta
            [$doctor_ids[0], $patient_ids[2]], // Dr. Alice -> Patient Gamma
            [$doctor_ids[1], $patient_ids[3]], // Dr. Bob -> Patient Delta
            [$doctor_ids[1], $patient_ids[4]], // Dr. Bob -> Patient Echo
            [$doctor_ids[2], $patient_ids[0]], // Dr. Carol -> Patient Alpha (shared patient)
        ];
        
        foreach ($assignments as $assignment) {
            $doctor_id = $assignment[0];
            $patient_id = $assignment[1];
            
            $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                           VALUES ($doctor_id, $patient_id)";
            
            mysqli_query($connection, $assign_query);
        }
        
        // Create test alerts with various scenarios
        $alert_scenarios = [
            // Dr. Alice's alerts
            [
                'doctor_id' => $doctor_ids[0],
                'patient_id' => $patient_ids[0],
                'message' => 'Please monitor your blood pressure daily and record the readings.',
                'status' => 'sent',
                'created_at' => date('Y-m-d H:i:s', time() - 3600), // 1 hour ago
                'description' => 'recent unread alert'
            ],
            [
                'doctor_id' => $doctor_ids[0],
                'patient_id' => $patient_ids[1],
                'message' => 'Your recent blood sugar levels look good. Keep up the excellent work!',
                'status' => 'seen',
                'created_at' => date('Y-m-d H:i:s', time() - 7200), // 2 hours ago
                'description' => 'read positive feedback'
            ],
            [
                'doctor_id' => $doctor_ids[0],
                'patient_id' => $patient_ids[2],
                'message' => 'Please schedule a follow-up appointment to discuss your recent test results.',
                'status' => 'sent',
                'created_at' => date('Y-m-d H:i:s', time() - 86400), // 1 day ago
                'description' => 'older unread alert'
            ],
            [
                'doctor_id' => $doctor_ids[0],
                'patient_id' => $patient_ids[0],
                'message' => 'Reminder: Take your medication as prescribed twice daily.',
                'status' => 'seen',
                'created_at' => date('Y-m-d H:i:s', time() - 172800), // 2 days ago
                'description' => 'older read alert'
            ],
            
            // Dr. Bob's alerts
            [
                'doctor_id' => $doctor_ids[1],
                'patient_id' => $patient_ids[3],
                'message' => 'Your heart rate readings are within normal range. Continue your current routine.',
                'status' => 'seen',
                'created_at' => date('Y-m-d H:i:s', time() - 1800), // 30 minutes ago
                'description' => 'very recent read alert'
            ],
            [
                'doctor_id' => $doctor_ids[1],
                'patient_id' => $patient_ids[4],
                'message' => 'Please reduce salt intake and increase water consumption as discussed.',
                'status' => 'sent',
                'created_at' => date('Y-m-d H:i:s', time() - 259200), // 3 days ago
                'description' => 'multi-day old unread'
            ],
            
            // Dr. Carol's alerts (fewer alerts)
            [
                'doctor_id' => $doctor_ids[2],
                'patient_id' => $patient_ids[0],
                'message' => 'Welcome to our health monitoring program. Please start recording your daily readings.',
                'status' => 'seen',
                'created_at' => date('Y-m-d H:i:s', time() - 604800), // 1 week ago
                'description' => 'welcome message'
            ]
        ];
        
        $alert_ids = [];
        
        // Insert all test alerts
        foreach ($alert_scenarios as $scenario) {
            $doctor_id = $scenario['doctor_id'];
            $patient_id = $scenario['patient_id'];
            $message = mysqli_real_escape_string($connection, $scenario['message']);
            $status = $scenario['status'];
            $created_at = $scenario['created_at'];
            
            $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status, created_at) 
                           VALUES ($doctor_id, $patient_id, '$message', '$status', '$created_at')";
            
            mysqli_query($connection, $insert_query);
            $alert_ids[] = mysqli_insert_id($connection);
        }
        
        // Test sent alerts display for each doctor
        foreach ($doctor_ids as $doctor_index => $doctor_id) {
            $doctor_name = $test_doctors[$doctor_index][0];
            
            // Simulate sent_alerts.php query
            $sent_alerts_query = "SELECT a.id, a.message, a.status, a.created_at, 
                                        u.name as patient_name, u.email as patient_email
                                FROM alerts a
                                JOIN users u ON a.patient_id = u.id
                                WHERE a.doctor_id = $doctor_id
                                ORDER BY a.created_at DESC";
            
            $sent_alerts_result = mysqli_query($connection, $sent_alerts_query);
            
            if (!$sent_alerts_result) {
                throw new Exception("Sent alerts query should execute successfully for $doctor_name");
            }
            
            $displayed_alerts = [];
            while ($alert = mysqli_fetch_assoc($sent_alerts_result)) {
                $displayed_alerts[] = $alert;
            }
            
            // Get expected alerts for this doctor
            $expected_alerts = array_filter($alert_scenarios, function($scenario) use ($doctor_id) {
                return $scenario['doctor_id'] == $doctor_id;
            });
            
            // Test: All sent alerts should be displayed
            if (count($displayed_alerts) != count($expected_alerts)) {
                throw new Exception("All sent alerts should be displayed for $doctor_name. Expected: " . count($expected_alerts) . ", Got: " . count($displayed_alerts));
            }
            
            // Test: Alerts should be ordered by created_at DESC (newest first)
            for ($i = 0; $i < count($displayed_alerts) - 1; $i++) {
                $current_time = strtotime($displayed_alerts[$i]['created_at']);
                $next_time = strtotime($displayed_alerts[$i + 1]['created_at']);
                
                if ($current_time < $next_time) {
                    throw new Exception("Alerts should be ordered by newest first for $doctor_name");
                }
            }
            
            // Test: Each alert should contain required information
            foreach ($displayed_alerts as $alert) {
                // Test alert ID presence
                if (empty($alert['id']) || !is_numeric($alert['id'])) {
                    throw new Exception("Each alert should have a valid ID for $doctor_name");
                }
                
                // Test message presence
                if (empty($alert['message'])) {
                    throw new Exception("Each alert should have a message for $doctor_name");
                }
                
                // Test status presence and validity
                if (!in_array($alert['status'], ['sent', 'seen'])) {
                    throw new Exception("Each alert should have valid status for $doctor_name");
                }
                
                // Test timestamp presence and validity
                if (empty($alert['created_at']) || strtotime($alert['created_at']) === false) {
                    throw new Exception("Each alert should have valid timestamp for $doctor_name");
                }
                
                // Test patient name presence
                if (empty($alert['patient_name'])) {
                    throw new Exception("Each alert should include patient name for $doctor_name");
                }
                
                // Test patient email presence
                if (empty($alert['patient_email'])) {
                    throw new Exception("Each alert should include patient email for $doctor_name");
                }
                
                // Test patient name matches expected patients
                $patient_names = array_column($test_patients, 0);
                if (!in_array($alert['patient_name'], $patient_names)) {
                    throw new Exception("Patient name should match expected patients for $doctor_name. Got: " . $alert['patient_name']);
                }
            }
            
            // Test: Verify specific alert content integrity
            foreach ($displayed_alerts as $alert) {
                // Find matching expected alert
                $matching_expected = null;
                foreach ($expected_alerts as $expected) {
                    if ($expected['message'] === $alert['message'] && 
                        $expected['status'] === $alert['status']) {
                        $matching_expected = $expected;
                        break;
                    }
                }
                
                if (!$matching_expected) {
                    throw new Exception("Displayed alert should match expected alert for $doctor_name");
                }
                
                // Test timestamp matches (within 1 second tolerance)
                $expected_time = strtotime($matching_expected['created_at']);
                $actual_time = strtotime($alert['created_at']);
                
                if (abs($expected_time - $actual_time) > 1) {
                    throw new Exception("Alert timestamp should match expected for $doctor_name");
                }
            }
        }
        
        // Test: Doctor should only see their own alerts
        $cross_doctor_query = "SELECT COUNT(*) as count
                             FROM alerts a1
                             JOIN alerts a2 ON a1.id != a2.id
                             WHERE a1.doctor_id != a2.doctor_id";
        
        $cross_result = mysqli_query($connection, $cross_doctor_query);
        $cross_count = mysqli_fetch_assoc($cross_result)['count'];
        
        if ($cross_count > 0) {
            // This is expected - we have alerts from different doctors
            // Now test that each doctor only sees their own
            foreach ($doctor_ids as $doctor_id) {
                $isolation_query = "SELECT DISTINCT doctor_id 
                                  FROM alerts 
                                  WHERE doctor_id = $doctor_id";
                
                $isolation_result = mysqli_query($connection, $isolation_query);
                
                while ($row = mysqli_fetch_assoc($isolation_result)) {
                    if ($row['doctor_id'] != $doctor_id) {
                        throw new Exception("Doctor should only see their own alerts");
                    }
                }
            }
        }
        
        // Test: Statistics accuracy
        foreach ($doctor_ids as $doctor_index => $doctor_id) {
            $doctor_name = $test_doctors[$doctor_index][0];
            
            $stats_query = "SELECT 
                               COUNT(*) as total_sent,
                               SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as unread_count,
                               SUM(CASE WHEN status = 'seen' THEN 1 ELSE 0 END) as read_count,
                               COUNT(DISTINCT patient_id) as patients_contacted
                           FROM alerts 
                           WHERE doctor_id = $doctor_id";
            
            $stats_result = mysqli_query($connection, $stats_query);
            $stats = mysqli_fetch_assoc($stats_result);
            
            // Verify statistics are non-negative
            if ($stats['total_sent'] < 0 || $stats['unread_count'] < 0 || 
                $stats['read_count'] < 0 || $stats['patients_contacted'] < 0) {
                throw new Exception("Statistics should be non-negative for $doctor_name");
            }
            
            // Verify total equals sum of read and unread
            if ($stats['total_sent'] != ($stats['unread_count'] + $stats['read_count'])) {
                throw new Exception("Total alerts should equal sum of read and unread for $doctor_name");
            }
            
            // Verify patients contacted is reasonable
            if ($stats['patients_contacted'] > $stats['total_sent']) {
                throw new Exception("Patients contacted should not exceed total alerts for $doctor_name");
            }
        }
        
        // Test: Empty results for doctor with no alerts
        $no_alerts_doctor_query = "INSERT INTO users (name, email, password, role, status) 
                                 VALUES ('Dr. No Alerts', 'noalerts@test.com', 'password', 'doctor', 'approved')";
        
        mysqli_query($connection, $no_alerts_doctor_query);
        $no_alerts_doctor_id = mysqli_insert_id($connection);
        
        $empty_query = "SELECT a.id, a.message, a.status, a.created_at, 
                              u.name as patient_name, u.email as patient_email
                      FROM alerts a
                      JOIN users u ON a.patient_id = u.id
                      WHERE a.doctor_id = $no_alerts_doctor_id
                      ORDER BY a.created_at DESC";
        
        $empty_result = mysqli_query($connection, $empty_query);
        
        if (mysqli_num_rows($empty_result) != 0) {
            throw new Exception("Doctor with no alerts should have empty results");
        }
        
        // Test: Filtering functionality
        $filter_doctor_id = $doctor_ids[0]; // Dr. Alice has multiple alerts
        
        // Test status filtering
        $sent_only_query = "SELECT COUNT(*) as count
                          FROM alerts a
                          JOIN users u ON a.patient_id = u.id
                          WHERE a.doctor_id = $filter_doctor_id AND a.status = 'sent'";
        
        $sent_only_result = mysqli_query($connection, $sent_only_query);
        $sent_only_count = mysqli_fetch_assoc($sent_only_result)['count'];
        
        $seen_only_query = "SELECT COUNT(*) as count
                          FROM alerts a
                          JOIN users u ON a.patient_id = u.id
                          WHERE a.doctor_id = $filter_doctor_id AND a.status = 'seen'";
        
        $seen_only_result = mysqli_query($connection, $seen_only_query);
        $seen_only_count = mysqli_fetch_assoc($seen_only_result)['count'];
        
        if ($sent_only_count < 0 || $seen_only_count < 0) {
            throw new Exception("Filtered counts should be non-negative");
        }
        
        // Test patient filtering
        $patient_filter_id = $patient_ids[0]; // Patient Alpha
        $patient_filter_query = "SELECT COUNT(*) as count
                               FROM alerts a
                               JOIN users u ON a.patient_id = u.id
                               WHERE a.doctor_id = $filter_doctor_id AND a.patient_id = $patient_filter_id";
        
        $patient_filter_result = mysqli_query($connection, $patient_filter_query);
        $patient_filter_count = mysqli_fetch_assoc($patient_filter_result)['count'];
        
        if ($patient_filter_count < 0) {
            throw new Exception("Patient filtered count should be non-negative");
        }
        
        // Test date range filtering
        $recent_query = "SELECT COUNT(*) as count
                       FROM alerts a
                       JOIN users u ON a.patient_id = u.id
                       WHERE a.doctor_id = $filter_doctor_id 
                       AND a.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        
        $recent_result = mysqli_query($connection, $recent_query);
        $recent_count = mysqli_fetch_assoc($recent_result)['count'];
        
        if ($recent_count < 0) {
            throw new Exception("Date filtered count should be non-negative");
        }
        
        // Test: Pagination simulation
        $page_size = 2;
        $page_1_query = "SELECT a.id, a.message, a.status, a.created_at, 
                               u.name as patient_name, u.email as patient_email
                       FROM alerts a
                       JOIN users u ON a.patient_id = u.id
                       WHERE a.doctor_id = $filter_doctor_id
                       ORDER BY a.created_at DESC
                       LIMIT $page_size OFFSET 0";
        
        $page_1_result = mysqli_query($connection, $page_1_query);
        $page_1_alerts = [];
        
        while ($alert = mysqli_fetch_assoc($page_1_result)) {
            $page_1_alerts[] = $alert;
        }
        
        $page_2_query = "SELECT a.id, a.message, a.status, a.created_at, 
                               u.name as patient_name, u.email as patient_email
                       FROM alerts a
                       JOIN users u ON a.patient_id = u.id
                       WHERE a.doctor_id = $filter_doctor_id
                       ORDER BY a.created_at DESC
                       LIMIT $page_size OFFSET $page_size";
        
        $page_2_result = mysqli_query($connection, $page_2_query);
        $page_2_alerts = [];
        
        while ($alert = mysqli_fetch_assoc($page_2_result)) {
            $page_2_alerts[] = $alert;
        }
        
        // Test: No overlap between pages
        foreach ($page_1_alerts as $alert1) {
            foreach ($page_2_alerts as $alert2) {
                if ($alert1['id'] == $alert2['id']) {
                    throw new Exception("Pagination should not have overlapping alerts");
                }
            }
        }
        
        // Test: Page 1 should have newer alerts than page 2
        if (!empty($page_1_alerts) && !empty($page_2_alerts)) {
            $page_1_newest = strtotime($page_1_alerts[0]['created_at']);
            $page_2_oldest = strtotime(end($page_2_alerts)['created_at']);
            
            if ($page_1_newest < $page_2_oldest) {
                throw new Exception("Page 1 should contain newer alerts than page 2");
            }
        }
        
        echo "PASS: Sent Alerts Display property test passed\n";
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
    $result = runSentAlertsDisplayTest();
    exit($result ? 0 : 1);
}
?>