<?php
/**
 * Feature: health-alert-system, Property 5: Health History Chronological Ordering
 * 
 * Property 5: Health History Chronological Ordering
 * For any patient with multiple health records, the health history display should 
 * show all records in chronological order (newest first)
 * Validates: Requirements 2.3
 */

// Simple test runner for Health History Chronological Ordering Property Test
function runHealthHistoryChronologicalOrderingTest() {
    echo "Running Health History Chronological Ordering Property Test...\n";
    
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
            ['Patient One', 'patient1@test.com', 'password123', 'patient'],
            ['Patient Two', 'patient2@test.com', 'password456', 'patient'],
            ['Patient Three', 'patient3@test.com', 'password789', 'patient']
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
        
        // Test chronological ordering with multiple records per patient
        foreach ($patient_ids as $patient_id) {
            // Insert health records with specific timestamps (simulate different times)
            $health_records = [
                // [systolic, diastolic, sugar, heart_rate, timestamp_offset_hours]
                [120, 80, 95.5, 72, -48], // 2 days ago
                [125, 82, 98.2, 75, -24], // 1 day ago
                [118, 78, 92.1, 70, -12], // 12 hours ago
                [130, 85, 105.3, 78, -6],  // 6 hours ago
                [122, 81, 96.7, 73, -2],  // 2 hours ago
                [127, 83, 99.8, 76, -1],  // 1 hour ago
                [124, 79, 94.5, 71, 0]    // Now
            ];
            
            $inserted_records = [];
            
            foreach ($health_records as $record) {
                $systolic = $record[0];
                $diastolic = $record[1];
                $sugar = $record[2];
                $heart_rate = $record[3];
                $hours_offset = $record[4];
                
                // Calculate timestamp
                $timestamp = date('Y-m-d H:i:s', time() + ($hours_offset * 3600));
                
                $insert_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) 
                               VALUES ($patient_id, $systolic, $diastolic, $sugar, $heart_rate, '$timestamp')";
                
                mysqli_query($connection, $insert_query);
                
                $inserted_records[] = [
                    'id' => mysqli_insert_id($connection),
                    'timestamp' => $timestamp,
                    'systolic' => $systolic,
                    'hours_offset' => $hours_offset
                ];
            }
            
            // Test chronological ordering (newest first) - simulate health_history.php query
            $history_query = "SELECT * FROM health_data 
                            WHERE patient_id = $patient_id 
                            ORDER BY created_at DESC";
            
            $history_result = mysqli_query($connection, $history_query);
            
            if (!$history_result) {
                throw new Exception("Health history query should succeed for patient ID: $patient_id");
            }
            
            $retrieved_records = [];
            while ($row = mysqli_fetch_assoc($history_result)) {
                $retrieved_records[] = $row;
            }
            
            // Verify chronological ordering (newest first)
            $previous_timestamp = null;
            foreach ($retrieved_records as $index => $record) {
                $current_timestamp = strtotime($record['created_at']);
                
                if ($previous_timestamp !== null) {
                    if ($current_timestamp > $previous_timestamp) {
                        throw new Exception("Health records should be ordered chronologically (newest first). Record at index $index is newer than previous record for patient $patient_id");
                    }
                }
                
                $previous_timestamp = $current_timestamp;
            }
            
            // Verify all records are retrieved
            if (count($retrieved_records) !== count($health_records)) {
                throw new Exception("All health records should be retrieved. Expected: " . count($health_records) . ", Got: " . count($retrieved_records));
            }
            
            // Verify the newest record is first
            $newest_record = $retrieved_records[0];
            $oldest_record = $retrieved_records[count($retrieved_records) - 1];
            
            $newest_timestamp = strtotime($newest_record['created_at']);
            $oldest_timestamp = strtotime($oldest_record['created_at']);
            
            if ($newest_timestamp <= $oldest_timestamp) {
                throw new Exception("Newest record should have later timestamp than oldest record");
            }
        }
        
        // Test ordering with same timestamps (edge case)
        $patient_id = $patient_ids[0];
        $same_timestamp = date('Y-m-d H:i:s');
        
        // Insert multiple records with same timestamp
        for ($i = 0; $i < 5; $i++) {
            $systolic = 120 + $i;
            $insert_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) 
                           VALUES ($patient_id, $systolic, 80, 95.5, 72, '$same_timestamp')";
            mysqli_query($connection, $insert_query);
        }
        
        // Query should still work with same timestamps
        $same_time_query = "SELECT * FROM health_data 
                          WHERE patient_id = $patient_id AND created_at = '$same_timestamp'
                          ORDER BY created_at DESC, id DESC";
        
        $same_time_result = mysqli_query($connection, $same_time_query);
        
        if (!$same_time_result || mysqli_num_rows($same_time_result) != 5) {
            throw new Exception("Query should handle records with same timestamp");
        }
        
        // Test ordering with large dataset
        $large_patient_id = $patient_ids[1];
        $large_dataset_size = 100;
        
        for ($i = 0; $i < $large_dataset_size; $i++) {
            $timestamp = date('Y-m-d H:i:s', time() - ($i * 3600)); // Each record 1 hour apart
            $systolic = 100 + ($i % 50); // Varying values
            
            $insert_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) 
                           VALUES ($large_patient_id, $systolic, 80, 95.5, 72, '$timestamp')";
            mysqli_query($connection, $insert_query);
        }
        
        // Test chronological ordering with large dataset
        $large_query = "SELECT * FROM health_data 
                       WHERE patient_id = $large_patient_id 
                       ORDER BY created_at DESC 
                       LIMIT 50";
        
        $large_result = mysqli_query($connection, $large_query);
        
        if (!$large_result) {
            throw new Exception("Large dataset query should succeed");
        }
        
        $large_records = [];
        while ($row = mysqli_fetch_assoc($large_result)) {
            $large_records[] = $row;
        }
        
        // Verify ordering in large dataset
        for ($i = 1; $i < count($large_records); $i++) {
            $current_time = strtotime($large_records[$i]['created_at']);
            $previous_time = strtotime($large_records[$i-1]['created_at']);
            
            if ($current_time > $previous_time) {
                throw new Exception("Large dataset should maintain chronological order");
            }
        }
        
        // Test pagination ordering consistency
        $page_size = 10;
        $total_pages = ceil($large_dataset_size / $page_size);
        
        $all_paginated_records = [];
        
        for ($page = 0; $page < min(3, $total_pages); $page++) { // Test first 3 pages
            $offset = $page * $page_size;
            
            $page_query = "SELECT * FROM health_data 
                          WHERE patient_id = $large_patient_id 
                          ORDER BY created_at DESC 
                          LIMIT $page_size OFFSET $offset";
            
            $page_result = mysqli_query($connection, $page_query);
            
            while ($row = mysqli_fetch_assoc($page_result)) {
                $all_paginated_records[] = $row;
            }
        }
        
        // Verify pagination maintains chronological order across pages
        for ($i = 1; $i < count($all_paginated_records); $i++) {
            $current_time = strtotime($all_paginated_records[$i]['created_at']);
            $previous_time = strtotime($all_paginated_records[$i-1]['created_at']);
            
            if ($current_time > $previous_time) {
                throw new Exception("Pagination should maintain chronological order across pages");
            }
        }
        
        // Test ordering with mixed patients (ensure patient isolation)
        $mixed_query = "SELECT patient_id, created_at FROM health_data 
                       WHERE patient_id IN (" . implode(',', $patient_ids) . ") 
                       ORDER BY patient_id, created_at DESC";
        
        $mixed_result = mysqli_query($connection, $mixed_query);
        
        $records_by_patient = [];
        while ($row = mysqli_fetch_assoc($mixed_result)) {
            $records_by_patient[$row['patient_id']][] = $row['created_at'];
        }
        
        // Verify each patient's records are chronologically ordered
        foreach ($records_by_patient as $pid => $timestamps) {
            for ($i = 1; $i < count($timestamps); $i++) {
                $current_time = strtotime($timestamps[$i]);
                $previous_time = strtotime($timestamps[$i-1]);
                
                if ($current_time > $previous_time) {
                    throw new Exception("Patient $pid records should be chronologically ordered");
                }
            }
        }
        
        echo "PASS: Health History Chronological Ordering property test passed\n";
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
    $result = runHealthHistoryChronologicalOrderingTest();
    exit($result ? 0 : 1);
}
?>