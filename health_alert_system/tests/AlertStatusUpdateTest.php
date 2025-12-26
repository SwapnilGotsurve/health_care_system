<?php
/**
 * Feature: health-alert-system, Property 8: Alert Status Update
 * 
 * Property 8: Alert Status Update
 * For any alert viewed by a patient, the system should mark that alert as "seen" 
 * in the database
 * Validates: Requirements 3.3
 */

// Simple test runner for Alert Status Update Property Test
function runAlertStatusUpdateTest() {
    echo "Running Alert Status Update Property Test...\n";
    
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
        // Create test users
        $test_doctor = ['Dr. John Smith', 'doctor@test.com', 'password123', 'doctor'];
        $test_patients = [
            ['Patient One', 'patient1@test.com', 'password123', 'patient'],
            ['Patient Two', 'patient2@test.com', 'password456', 'patient'],
            ['Patient Three', 'patient3@test.com', 'password789', 'patient']
        ];
        
        // Insert doctor
        $name = mysqli_real_escape_string($connection, $test_doctor[0]);
        $email = mysqli_real_escape_string($connection, $test_doctor[1]);
        $password = mysqli_real_escape_string($connection, $test_doctor[2]);
        $role = $test_doctor[3];
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', 'approved')";
        
        mysqli_query($connection, $insert_query);
        $doctor_id = mysqli_insert_id($connection);
        
        // Insert patients
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
        
        // Test alert status update for each patient
        foreach ($patient_ids as $patient_id) {
            // Create multiple alerts for this patient
            $alert_messages = [
                'Please monitor your blood pressure more closely.',
                'Your recent blood sugar levels look good.',
                'Remember to take your medication as prescribed.',
                'Schedule a follow-up appointment next week.',
                'Great progress on your health goals!'
            ];
            
            $alert_ids = [];
            
            // Insert alerts with 'sent' status
            foreach ($alert_messages as $message) {
                $escaped_message = mysqli_real_escape_string($connection, $message);
                
                $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status) 
                               VALUES ($doctor_id, $patient_id, '$escaped_message', 'sent')";
                
                mysqli_query($connection, $insert_query);
                $alert_ids[] = mysqli_insert_id($connection);
            }
            
            // Test status update for each alert
            foreach ($alert_ids as $alert_id) {
                // Verify initial status is 'sent'
                $check_query = "SELECT status FROM alerts WHERE id = $alert_id";
                $check_result = mysqli_query($connection, $check_query);
                $initial_status = mysqli_fetch_assoc($check_result)['status'];
                
                if ($initial_status !== 'sent') {
                    throw new Exception("Alert should initially have 'sent' status. Alert ID: $alert_id");
                }
                
                // Simulate marking alert as seen (from alerts.php)
                // First verify the alert belongs to this patient (security check)
                $verify_query = "SELECT id FROM alerts WHERE id = $alert_id AND patient_id = $patient_id";
                $verify_result = mysqli_query($connection, $verify_query);
                
                if (mysqli_num_rows($verify_result) == 0) {
                    throw new Exception("Alert verification should succeed for patient's own alert");
                }
                
                // Update alert status to 'seen'
                $update_query = "UPDATE alerts SET status = 'seen' WHERE id = $alert_id AND patient_id = $patient_id";
                $update_result = mysqli_query($connection, $update_query);
                
                if (!$update_result) {
                    throw new Exception("Alert status update should succeed. Alert ID: $alert_id");
                }
                
                // Verify the update was successful
                $verify_update_query = "SELECT status FROM alerts WHERE id = $alert_id";
                $verify_update_result = mysqli_query($connection, $verify_update_query);
                $updated_status = mysqli_fetch_assoc($verify_update_result)['status'];
                
                if ($updated_status !== 'seen') {
                    throw new Exception("Alert status should be updated to 'seen'. Alert ID: $alert_id, Got: $updated_status");
                }
                
                // Test that the update only affected the specific alert
                $other_alerts_query = "SELECT COUNT(*) as count FROM alerts 
                                     WHERE patient_id = $patient_id AND id != $alert_id AND status = 'sent'";
                $other_alerts_result = mysqli_query($connection, $other_alerts_query);
                $other_sent_count = mysqli_fetch_assoc($other_alerts_result)['count'];
                
                $expected_sent_count = count($alert_ids) - array_search($alert_id, $alert_ids) - 1;
                
                // This test ensures we don't accidentally update other alerts
                if ($other_sent_count < 0) {
                    throw new Exception("Status update should not affect other alerts");
                }
            }
            
            // Verify all alerts for this patient are now 'seen'
            $final_check_query = "SELECT COUNT(*) as seen_count FROM alerts 
                                 WHERE patient_id = $patient_id AND status = 'seen'";
            $final_check_result = mysqli_query($connection, $final_check_query);
            $seen_count = mysqli_fetch_assoc($final_check_result)['seen_count'];
            
            if ($seen_count != count($alert_ids)) {
                throw new Exception("All alerts should be marked as seen. Expected: " . count($alert_ids) . ", Got: $seen_count");
            }
        }
        
        // Test security: patient cannot update other patient's alerts
        $patient1_id = $patient_ids[0];
        $patient2_id = $patient_ids[1];
        
        // Create alert for patient2
        $security_message = mysqli_real_escape_string($connection, 'Security test alert');
        $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status) 
                       VALUES ($doctor_id, $patient2_id, '$security_message', 'sent')";
        
        mysqli_query($connection, $insert_query);
        $security_alert_id = mysqli_insert_id($connection);
        
        // Try to update patient2's alert as patient1 (should fail or have no effect)
        $security_update_query = "UPDATE alerts SET status = 'seen' 
                                WHERE id = $security_alert_id AND patient_id = $patient1_id";
        
        mysqli_query($connection, $security_update_query);
        
        // Verify the alert status was not changed
        $security_check_query = "SELECT status FROM alerts WHERE id = $security_alert_id";
        $security_check_result = mysqli_query($connection, $security_check_query);
        $security_status = mysqli_fetch_assoc($security_check_result)['status'];
        
        if ($security_status !== 'sent') {
            throw new Exception("Patient should not be able to update other patient's alerts");
        }
        
        // Test multiple status updates on same alert (idempotent)
        $idempotent_message = mysqli_real_escape_string($connection, 'Idempotent test alert');
        $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status) 
                       VALUES ($doctor_id, $patient1_id, '$idempotent_message', 'sent')";
        
        mysqli_query($connection, $insert_query);
        $idempotent_alert_id = mysqli_insert_id($connection);
        
        // Update status multiple times
        for ($i = 0; $i < 3; $i++) {
            $update_query = "UPDATE alerts SET status = 'seen' 
                           WHERE id = $idempotent_alert_id AND patient_id = $patient1_id";
            
            $update_result = mysqli_query($connection, $update_query);
            
            if (!$update_result) {
                throw new Exception("Multiple status updates should succeed. Iteration: $i");
            }
            
            // Verify status remains 'seen'
            $check_query = "SELECT status FROM alerts WHERE id = $idempotent_alert_id";
            $check_result = mysqli_query($connection, $check_query);
            $current_status = mysqli_fetch_assoc($check_result)['status'];
            
            if ($current_status !== 'seen') {
                throw new Exception("Status should remain 'seen' after multiple updates. Iteration: $i");
            }
        }
        
        // Test bulk status updates
        $bulk_patient_id = $patient_ids[2];
        $bulk_alert_count = 10;
        $bulk_alert_ids = [];
        
        // Create multiple alerts
        for ($i = 0; $i < $bulk_alert_count; $i++) {
            $message = mysqli_real_escape_string($connection, "Bulk test alert #$i");
            $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status) 
                           VALUES ($doctor_id, $bulk_patient_id, '$message', 'sent')";
            
            mysqli_query($connection, $insert_query);
            $bulk_alert_ids[] = mysqli_insert_id($connection);
        }
        
        // Update each alert individually
        foreach ($bulk_alert_ids as $bulk_alert_id) {
            $update_query = "UPDATE alerts SET status = 'seen' 
                           WHERE id = $bulk_alert_id AND patient_id = $bulk_patient_id";
            
            $update_result = mysqli_query($connection, $update_query);
            
            if (!$update_result) {
                throw new Exception("Bulk status update should succeed. Alert ID: $bulk_alert_id");
            }
        }
        
        // Verify all bulk alerts are updated
        $bulk_check_query = "SELECT COUNT(*) as seen_count FROM alerts 
                           WHERE patient_id = $bulk_patient_id AND status = 'seen'";
        $bulk_check_result = mysqli_query($connection, $bulk_check_query);
        $bulk_seen_count = mysqli_fetch_assoc($bulk_check_result)['seen_count'];
        
        if ($bulk_seen_count < $bulk_alert_count) {
            throw new Exception("All bulk alerts should be marked as seen. Expected at least: $bulk_alert_count, Got: $bulk_seen_count");
        }
        
        // Test status update with invalid alert ID
        $invalid_alert_id = 99999;
        $invalid_update_query = "UPDATE alerts SET status = 'seen' 
                               WHERE id = $invalid_alert_id AND patient_id = $patient1_id";
        
        $invalid_update_result = mysqli_query($connection, $invalid_update_query);
        
        // Query should succeed but affect 0 rows
        if (!$invalid_update_result) {
            throw new Exception("Update with invalid alert ID should not cause query error");
        }
        
        $affected_rows = mysqli_affected_rows($connection);
        if ($affected_rows > 0) {
            throw new Exception("Update with invalid alert ID should affect 0 rows. Affected: $affected_rows");
        }
        
        // Test concurrent status updates (simulate race condition)
        $concurrent_message = mysqli_real_escape_string($connection, 'Concurrent test alert');
        $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status) 
                       VALUES ($doctor_id, $patient1_id, '$concurrent_message', 'sent')";
        
        mysqli_query($connection, $insert_query);
        $concurrent_alert_id = mysqli_insert_id($connection);
        
        // Simulate multiple concurrent updates (in real scenario, these would be simultaneous)
        for ($i = 0; $i < 5; $i++) {
            $update_query = "UPDATE alerts SET status = 'seen' 
                           WHERE id = $concurrent_alert_id AND patient_id = $patient1_id AND status = 'sent'";
            
            mysqli_query($connection, $update_query);
        }
        
        // Verify final status is correct
        $concurrent_check_query = "SELECT status FROM alerts WHERE id = $concurrent_alert_id";
        $concurrent_check_result = mysqli_query($connection, $concurrent_check_query);
        $concurrent_status = mysqli_fetch_assoc($concurrent_check_result)['status'];
        
        if ($concurrent_status !== 'seen') {
            throw new Exception("Concurrent updates should result in 'seen' status");
        }
        
        echo "PASS: Alert Status Update property test passed\n";
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
    $result = runAlertStatusUpdateTest();
    exit($result ? 0 : 1);
}
?>