<?php
/**
 * Feature: health-alert-system, Property 12: Patient Health Data Display
 * 
 * Property 12: Patient Health Data Display
 * For any selected patient, the system should display their complete health history 
 * with all required fields (systolic BP, diastolic BP, sugar level, heart rate, date)
 * Validates: Requirements 5.2, 5.3
 */

// Simple test runner for Patient Health Data Display Property Test
function runPatientHealthDataDisplayTest() {
    echo "Running Patient Health Data Display Property Test...\n";
    
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
    mysqli_query($connection, $create_doctor_patients);
    mysqli_query($connection, $create_health_data);
    
    try {
        // Create test doctor
        $test_doctor = ['Dr. John Smith', 'doctor@test.com', 'password123', 'doctor'];
        $name = mysqli_real_escape_string($connection, $test_doctor[0]);
        $email = mysqli_real_escape_string($connection, $test_doctor[1]);
        $password = mysqli_real_escape_string($connection, $test_doctor[2]);
        $role = $test_doctor[3];
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', 'approved')";
        
        mysqli_query($connection, $insert_query);
        $doctor_id = mysqli_insert_id($connection);
        
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
            $patient_id = mysqli_insert_id($connection);
            $patient_ids[] = $patient_id;
            
            // Assign patient to doctor
            $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                           VALUES ($doctor_id, $patient_id)";
            
            mysqli_query($connection, $assign_query);
        }
        
        // Test health data display for each patient
        foreach ($patient_ids as $patient_index => $patient_id) {
            // Create various health data entries for this patient
            $health_data_entries = [
                // [systolic_bp, diastolic_bp, sugar_level, heart_rate, timestamp_offset_hours]
                [120, 80, 95.5, 72, -72], // 3 days ago
                [125, 82, 98.2, 75, -48], // 2 days ago
                [118, 78, 92.1, 70, -24], // 1 day ago
                [130, 85, 105.3, 78, -12], // 12 hours ago
                [122, 81, 96.7, 73, -6],  // 6 hours ago
                [127, 83, 99.8, 76, -2],  // 2 hours ago
                [124, 79, 94.5, 71, 0]    // Now
            ];
            
            $inserted_health_ids = [];
            
            // Insert health data entries
            foreach ($health_data_entries as $entry) {
                $systolic = $entry[0];
                $diastolic = $entry[1];
                $sugar = $entry[2];
                $heart_rate = $entry[3];
                $hours_offset = $entry[4];
                
                // Calculate timestamp
                $timestamp = date('Y-m-d H:i:s', time() + ($hours_offset * 3600));
                
                $insert_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) 
                               VALUES ($patient_id, $systolic, $diastolic, $sugar, $heart_rate, '$timestamp')";
                
                mysqli_query($connection, $insert_query);
                $inserted_health_ids[] = mysqli_insert_id($connection);
            }
            
            // Test health data display query (from patient_stats.php)
            $health_display_query = "SELECT * FROM health_data 
                                   WHERE patient_id = $patient_id 
                                   ORDER BY created_at DESC";
            
            $health_display_result = mysqli_query($connection, $health_display_query);
            
            if (!$health_display_result) {
                throw new Exception("Health data display query should succeed for patient ID: $patient_id");
            }
            
            $displayed_health_data = [];
            while ($row = mysqli_fetch_assoc($health_display_result)) {
                $displayed_health_data[] = $row;
            }
            
            // Test completeness: all health records should be displayed
            if (count($displayed_health_data) !== count($health_data_entries)) {
                throw new Exception("All health records should be displayed. Expected: " . count($health_data_entries) . ", Got: " . count($displayed_health_data));
            }
            
            // Test required fields are present and correct
            foreach ($displayed_health_data as $index => $health_record) {
                $required_fields = ['id', 'patient_id', 'systolic_bp', 'diastolic_bp', 'sugar_level', 'heart_rate', 'created_at'];
                
                // Test all required fields are present
                foreach ($required_fields as $field) {
                    if (!isset($health_record[$field]) || $health_record[$field] === null) {
                        throw new Exception("Health record should contain '$field' field. Record ID: " . $health_record['id']);
                    }
                }
                
                // Test patient_id matches
                if ($health_record['patient_id'] != $patient_id) {
                    throw new Exception("Health record patient_id should match queried patient. Expected: $patient_id, Got: " . $health_record['patient_id']);
                }
                
                // Test data types and ranges
                if (!is_numeric($health_record['systolic_bp']) || $health_record['systolic_bp'] < 0) {
                    throw new Exception("Systolic BP should be a positive number. Got: " . $health_record['systolic_bp']);
                }
                
                if (!is_numeric($health_record['diastolic_bp']) || $health_record['diastolic_bp'] < 0) {
                    throw new Exception("Diastolic BP should be a positive number. Got: " . $health_record['diastolic_bp']);
                }
                
                if (!is_numeric($health_record['sugar_level']) || $health_record['sugar_level'] < 0) {
                    throw new Exception("Sugar level should be a positive number. Got: " . $health_record['sugar_level']);
                }
                
                if (!is_numeric($health_record['heart_rate']) || $health_record['heart_rate'] < 0) {
                    throw new Exception("Heart rate should be a positive number. Got: " . $health_record['heart_rate']);
                }
                
                // Test timestamp is valid
                $timestamp = strtotime($health_record['created_at']);
                if ($timestamp === false) {
                    throw new Exception("Health record timestamp should be valid. Got: " . $health_record['created_at']);
                }
            }
            
            // Test chronological ordering (newest first)
            for ($i = 1; $i < count($displayed_health_data); $i++) {
                $current_time = strtotime($displayed_health_data[$i]['created_at']);
                $previous_time = strtotime($displayed_health_data[$i-1]['created_at']);
                
                if ($current_time > $previous_time) {
                    throw new Exception("Health records should be ordered chronologically (newest first)");
                }
            }
            
            // Test data integrity - values should match what was inserted
            $original_entry_index = 0;
            foreach ($displayed_health_data as $displayed_record) {
                // Find corresponding original entry (reverse order due to DESC sorting)
                $original_index = count($health_data_entries) - 1 - $original_entry_index;
                $original_entry = $health_data_entries[$original_index];
                
                if ($displayed_record['systolic_bp'] != $original_entry[0]) {
                    throw new Exception("Displayed systolic BP should match inserted value. Expected: " . $original_entry[0] . ", Got: " . $displayed_record['systolic_bp']);
                }
                
                if ($displayed_record['diastolic_bp'] != $original_entry[1]) {
                    throw new Exception("Displayed diastolic BP should match inserted value. Expected: " . $original_entry[1] . ", Got: " . $displayed_record['diastolic_bp']);
                }
                
                if (abs($displayed_record['sugar_level'] - $original_entry[2]) > 0.01) {
                    throw new Exception("Displayed sugar level should match inserted value. Expected: " . $original_entry[2] . ", Got: " . $displayed_record['sugar_level']);
                }
                
                if ($displayed_record['heart_rate'] != $original_entry[3]) {
                    throw new Exception("Displayed heart rate should match inserted value. Expected: " . $original_entry[3] . ", Got: " . $displayed_record['heart_rate']);
                }
                
                $original_entry_index++;
            }
        }
        
        // Test patient with no health data
        $no_data_patient = ['No Data Patient', 'nodata@test.com', 'password123', 'patient'];
        $name = mysqli_real_escape_string($connection, $no_data_patient[0]);
        $email = mysqli_real_escape_string($connection, $no_data_patient[1]);
        $password = mysqli_real_escape_string($connection, $no_data_patient[2]);
        $role = $no_data_patient[3];
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', 'approved')";
        
        mysqli_query($connection, $insert_query);
        $no_data_patient_id = mysqli_insert_id($connection);
        
        // Assign to doctor
        $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                       VALUES ($doctor_id, $no_data_patient_id)";
        
        mysqli_query($connection, $assign_query);
        
        // Test empty health data display
        $empty_query = "SELECT * FROM health_data WHERE patient_id = $no_data_patient_id";
        $empty_result = mysqli_query($connection, $empty_query);
        
        if (mysqli_num_rows($empty_result) !== 0) {
            throw new Exception("Patient with no health data should return empty result set");
        }
        
        // Test pagination functionality
        $pagination_patient_id = $patient_ids[0];
        $page_size = 3;
        $total_records = count($health_data_entries);
        $total_pages = ceil($total_records / $page_size);
        
        for ($page = 1; $page <= $total_pages; $page++) {
            $offset = ($page - 1) * $page_size;
            
            $page_query = "SELECT * FROM health_data 
                         WHERE patient_id = $pagination_patient_id 
                         ORDER BY created_at DESC 
                         LIMIT $page_size OFFSET $offset";
            
            $page_result = mysqli_query($connection, $page_query);
            $page_records = [];
            
            while ($row = mysqli_fetch_assoc($page_result)) {
                $page_records[] = $row;
            }
            
            // Test page contains correct number of records
            $expected_records = min($page_size, $total_records - $offset);
            if (count($page_records) !== $expected_records) {
                throw new Exception("Page $page should contain $expected_records records. Got: " . count($page_records));
            }
            
            // Test all records on page have required fields
            foreach ($page_records as $record) {
                $required_fields = ['systolic_bp', 'diastolic_bp', 'sugar_level', 'heart_rate', 'created_at'];
                
                foreach ($required_fields as $field) {
                    if (!isset($record[$field])) {
                        throw new Exception("Paginated record should contain '$field' field");
                    }
                }
            }
        }
        
        // Test summary statistics calculation
        $stats_query = "SELECT 
                          COUNT(*) as total_records,
                          AVG(systolic_bp) as avg_systolic,
                          AVG(diastolic_bp) as avg_diastolic,
                          AVG(sugar_level) as avg_sugar,
                          AVG(heart_rate) as avg_heart_rate,
                          MIN(created_at) as first_entry,
                          MAX(created_at) as last_entry
                        FROM health_data 
                        WHERE patient_id = " . $patient_ids[0];
        
        $stats_result = mysqli_query($connection, $stats_query);
        $stats = mysqli_fetch_assoc($stats_result);
        
        // Test statistics are calculated correctly
        if ($stats['total_records'] != count($health_data_entries)) {
            throw new Exception("Statistics should show correct total records. Expected: " . count($health_data_entries) . ", Got: " . $stats['total_records']);
        }
        
        if ($stats['avg_systolic'] <= 0 || $stats['avg_diastolic'] <= 0) {
            throw new Exception("Average blood pressure values should be positive");
        }
        
        if ($stats['avg_sugar'] <= 0 || $stats['avg_heart_rate'] <= 0) {
            throw new Exception("Average sugar and heart rate values should be positive");
        }
        
        // Test access control - doctor can only see assigned patients' data
        $unassigned_patient = ['Unassigned Patient', 'unassigned@test.com', 'password123', 'patient'];
        $name = mysqli_real_escape_string($connection, $unassigned_patient[0]);
        $email = mysqli_real_escape_string($connection, $unassigned_patient[1]);
        $password = mysqli_real_escape_string($connection, $unassigned_patient[2]);
        $role = $unassigned_patient[3];
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', 'approved')";
        
        mysqli_query($connection, $insert_query);
        $unassigned_patient_id = mysqli_insert_id($connection);
        
        // Add health data for unassigned patient
        $unassigned_health_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                                  VALUES ($unassigned_patient_id, 120, 80, 95.5, 72)";
        
        mysqli_query($connection, $unassigned_health_query);
        
        // Test that doctor cannot access unassigned patient's data through patient verification
        $verify_query = "SELECT u.name, u.email, u.created_at 
                        FROM users u 
                        JOIN doctor_patients dp ON u.id = dp.patient_id 
                        WHERE dp.doctor_id = $doctor_id AND dp.patient_id = $unassigned_patient_id AND u.role = 'patient'";
        
        $verify_result = mysqli_query($connection, $verify_query);
        
        if (mysqli_num_rows($verify_result) > 0) {
            throw new Exception("Doctor should not have access to unassigned patient's data");
        }
        
        // Test decimal precision for sugar levels
        $precision_patient_id = $patient_ids[1];
        $precision_values = [95.1, 95.12, 95.123, 100.99, 85.01];
        
        foreach ($precision_values as $sugar_value) {
            $precision_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                              VALUES ($precision_patient_id, 120, 80, $sugar_value, 72)";
            
            mysqli_query($connection, $precision_query);
        }
        
        // Verify decimal precision is preserved
        $precision_check_query = "SELECT sugar_level FROM health_data 
                                WHERE patient_id = $precision_patient_id 
                                ORDER BY id DESC 
                                LIMIT " . count($precision_values);
        
        $precision_check_result = mysqli_query($connection, $precision_check_query);
        $retrieved_values = [];
        
        while ($row = mysqli_fetch_assoc($precision_check_result)) {
            $retrieved_values[] = floatval($row['sugar_level']);
        }
        
        // Reverse to match insertion order
        $retrieved_values = array_reverse($retrieved_values);
        
        foreach ($precision_values as $index => $original_value) {
            $retrieved_value = $retrieved_values[$index];
            
            if (abs($retrieved_value - $original_value) > 0.01) {
                throw new Exception("Decimal precision should be preserved. Expected: $original_value, Got: $retrieved_value");
            }
        }
        
        echo "PASS: Patient Health Data Display property test passed\n";
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
    $result = runPatientHealthDataDisplayTest();
    exit($result ? 0 : 1);
}
?>