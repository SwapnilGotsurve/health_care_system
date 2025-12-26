<?php
/**
 * Feature: health-alert-system, Property 11: Doctor Patient Assignment Display
 * 
 * Property 11: Doctor Patient Assignment Display
 * For any doctor with assigned patients, the patient list should display all and only 
 * the patients assigned to that doctor
 * Validates: Requirements 5.1
 */

// Simple test runner for Doctor Patient Assignment Display Property Test
function runDoctorPatientAssignmentDisplayTest() {
    echo "Running Doctor Patient Assignment Display Property Test...\n";
    
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
        // Create test doctors
        $test_doctors = [
            ['Dr. John Smith', 'doctor1@test.com', 'password123', 'doctor'],
            ['Dr. Sarah Wilson', 'doctor2@test.com', 'password456', 'doctor'],
            ['Dr. Michael Brown', 'doctor3@test.com', 'password789', 'doctor']
        ];
        
        // Create test patients
        $test_patients = [
            ['Patient One', 'patient1@test.com', 'password123', 'patient'],
            ['Patient Two', 'patient2@test.com', 'password456', 'patient'],
            ['Patient Three', 'patient3@test.com', 'password789', 'patient'],
            ['Patient Four', 'patient4@test.com', 'passwordabc', 'patient'],
            ['Patient Five', 'patient5@test.com', 'passworddef', 'patient'],
            ['Patient Six', 'patient6@test.com', 'passwordghi', 'patient']
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
        
        // Create various assignment scenarios
        $assignment_scenarios = [
            // [doctor_index, patient_indices]
            [0, [0, 1, 2]], // Doctor 1 has patients 1, 2, 3
            [1, [3, 4]],    // Doctor 2 has patients 4, 5
            [2, [5]]        // Doctor 3 has patient 6
        ];
        
        // Create assignments
        foreach ($assignment_scenarios as $scenario) {
            $doctor_index = $scenario[0];
            $patient_indices = $scenario[1];
            $doctor_id = $doctor_ids[$doctor_index];
            
            foreach ($patient_indices as $patient_index) {
                $patient_id = $patient_ids[$patient_index];
                
                $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                               VALUES ($doctor_id, $patient_id)";
                
                mysqli_query($connection, $assign_query);
            }
        }
        
        // Test patient assignment display for each doctor
        foreach ($assignment_scenarios as $scenario) {
            $doctor_index = $scenario[0];
            $expected_patient_indices = $scenario[1];
            $doctor_id = $doctor_ids[$doctor_index];
            
            // Simulate patient list query (from patient_list.php)
            $patient_list_query = "SELECT u.id, u.name, u.email, u.created_at as registered_date,
                                         COUNT(hd.id) as total_records,
                                         MAX(hd.created_at) as last_entry,
                                         COUNT(CASE WHEN hd.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_records
                                  FROM users u
                                  JOIN doctor_patients dp ON u.id = dp.patient_id
                                  LEFT JOIN health_data hd ON u.id = hd.patient_id
                                  WHERE dp.doctor_id = $doctor_id AND u.role = 'patient'
                                  GROUP BY u.id, u.name, u.email, u.created_at
                                  ORDER BY u.name ASC";
            
            $patient_list_result = mysqli_query($connection, $patient_list_query);
            
            if (!$patient_list_result) {
                throw new Exception("Patient list query should succeed for doctor ID: $doctor_id");
            }
            
            $displayed_patients = [];
            while ($row = mysqli_fetch_assoc($patient_list_result)) {
                $displayed_patients[] = $row;
            }
            
            // Test that correct number of patients are displayed
            if (count($displayed_patients) !== count($expected_patient_indices)) {
                throw new Exception("Doctor should see exactly " . count($expected_patient_indices) . " patients. Got: " . count($displayed_patients));
            }
            
            // Test that only assigned patients are displayed
            $displayed_patient_ids = array_column($displayed_patients, 'id');
            $expected_patient_ids = [];
            
            foreach ($expected_patient_indices as $patient_index) {
                $expected_patient_ids[] = $patient_ids[$patient_index];
            }
            
            sort($displayed_patient_ids);
            sort($expected_patient_ids);
            
            if ($displayed_patient_ids !== $expected_patient_ids) {
                throw new Exception("Doctor should see only assigned patients. Expected: " . implode(',', $expected_patient_ids) . ", Got: " . implode(',', $displayed_patient_ids));
            }
            
            // Test that all displayed patients have correct role
            foreach ($displayed_patients as $patient) {
                // Verify patient role
                $role_check_query = "SELECT role FROM users WHERE id = " . $patient['id'];
                $role_check_result = mysqli_query($connection, $role_check_query);
                $patient_role = mysqli_fetch_assoc($role_check_result)['role'];
                
                if ($patient_role !== 'patient') {
                    throw new Exception("All displayed users should have 'patient' role. Got: $patient_role");
                }
            }
            
            // Test that no unassigned patients are displayed
            $all_patient_ids = $patient_ids;
            $unassigned_patient_ids = array_diff($all_patient_ids, $expected_patient_ids);
            
            foreach ($unassigned_patient_ids as $unassigned_id) {
                if (in_array($unassigned_id, $displayed_patient_ids)) {
                    throw new Exception("Unassigned patient should not be displayed. Patient ID: $unassigned_id");
                }
            }
        }
        
        // Test doctor with no assigned patients
        $no_patients_doctor = ['Dr. No Patients', 'nopatients@test.com', 'password123', 'doctor'];
        $name = mysqli_real_escape_string($connection, $no_patients_doctor[0]);
        $email = mysqli_real_escape_string($connection, $no_patients_doctor[1]);
        $password = mysqli_real_escape_string($connection, $no_patients_doctor[2]);
        $role = $no_patients_doctor[3];
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', 'approved')";
        
        mysqli_query($connection, $insert_query);
        $no_patients_doctor_id = mysqli_insert_id($connection);
        
        // Test empty patient list
        $empty_list_query = "SELECT u.id, u.name, u.email 
                           FROM users u
                           JOIN doctor_patients dp ON u.id = dp.patient_id
                           WHERE dp.doctor_id = $no_patients_doctor_id AND u.role = 'patient'";
        
        $empty_list_result = mysqli_query($connection, $empty_list_query);
        
        if (mysqli_num_rows($empty_list_result) !== 0) {
            throw new Exception("Doctor with no assignments should see empty patient list");
        }
        
        // Test assignment uniqueness (no duplicate patients)
        $duplicate_test_doctor_id = $doctor_ids[0];
        $duplicate_test_patient_id = $patient_ids[0];
        
        // Try to create duplicate assignment (should fail due to unique constraint)
        $duplicate_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                          VALUES ($duplicate_test_doctor_id, $duplicate_test_patient_id)";
        
        $duplicate_result = mysqli_query($connection, $duplicate_query);
        
        if ($duplicate_result) {
            throw new Exception("Duplicate patient assignment should be prevented by unique constraint");
        }
        
        // Test cross-doctor patient isolation
        foreach ($doctor_ids as $doctor_id) {
            $isolation_query = "SELECT u.id 
                              FROM users u
                              JOIN doctor_patients dp ON u.id = dp.patient_id
                              WHERE dp.doctor_id = $doctor_id AND u.role = 'patient'";
            
            $isolation_result = mysqli_query($connection, $isolation_query);
            $doctor_patients = [];
            
            while ($row = mysqli_fetch_assoc($isolation_result)) {
                $doctor_patients[] = $row['id'];
            }
            
            // Check that this doctor doesn't see other doctors' patients
            foreach ($doctor_ids as $other_doctor_id) {
                if ($doctor_id === $other_doctor_id) continue;
                
                $other_query = "SELECT u.id 
                              FROM users u
                              JOIN doctor_patients dp ON u.id = dp.patient_id
                              WHERE dp.doctor_id = $other_doctor_id AND u.role = 'patient'";
                
                $other_result = mysqli_query($connection, $other_query);
                $other_patients = [];
                
                while ($row = mysqli_fetch_assoc($other_result)) {
                    $other_patients[] = $row['id'];
                }
                
                // Check for overlap (should be none unless explicitly assigned)
                $overlap = array_intersect($doctor_patients, $other_patients);
                
                // In this test, we don't have shared patients, so overlap should be empty
                if (!empty($overlap)) {
                    throw new Exception("Patients should not be shared between doctors in this test scenario");
                }
            }
        }
        
        // Test with health data to ensure JOIN works correctly
        $health_data_doctor_id = $doctor_ids[0];
        $health_data_patient_id = $patient_ids[0]; // This patient is assigned to doctor 0
        
        // Add some health data
        for ($i = 0; $i < 5; $i++) {
            $systolic = 120 + $i;
            $diastolic = 80 + $i;
            $sugar = 95 + $i;
            $heart_rate = 72 + $i;
            
            $health_insert = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                            VALUES ($health_data_patient_id, $systolic, $diastolic, $sugar, $heart_rate)";
            
            mysqli_query($connection, $health_insert);
        }
        
        // Test patient list with health data
        $health_data_query = "SELECT u.id, u.name, COUNT(hd.id) as total_records
                            FROM users u
                            JOIN doctor_patients dp ON u.id = dp.patient_id
                            LEFT JOIN health_data hd ON u.id = hd.patient_id
                            WHERE dp.doctor_id = $health_data_doctor_id AND u.role = 'patient'
                            GROUP BY u.id, u.name";
        
        $health_data_result = mysqli_query($connection, $health_data_query);
        
        $found_patient_with_data = false;
        while ($row = mysqli_fetch_assoc($health_data_result)) {
            if ($row['id'] == $health_data_patient_id) {
                $found_patient_with_data = true;
                
                if ($row['total_records'] != 5) {
                    throw new Exception("Patient should have 5 health records. Got: " . $row['total_records']);
                }
            }
        }
        
        if (!$found_patient_with_data) {
            throw new Exception("Patient with health data should be found in doctor's patient list");
        }
        
        // Test large dataset performance
        $large_doctor = ['Dr. Large Practice', 'large@test.com', 'password123', 'doctor'];
        $name = mysqli_real_escape_string($connection, $large_doctor[0]);
        $email = mysqli_real_escape_string($connection, $large_doctor[1]);
        $password = mysqli_real_escape_string($connection, $large_doctor[2]);
        $role = $large_doctor[3];
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', 'approved')";
        
        mysqli_query($connection, $insert_query);
        $large_doctor_id = mysqli_insert_id($connection);
        
        // Create many patients for this doctor
        $large_patient_count = 50;
        $large_patient_ids = [];
        
        for ($i = 0; $i < $large_patient_count; $i++) {
            $patient_name = mysqli_real_escape_string($connection, "Large Patient $i");
            $patient_email = mysqli_real_escape_string($connection, "large$i@test.com");
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$patient_name', '$patient_email', 'password123', 'patient', 'approved')";
            
            mysqli_query($connection, $insert_query);
            $large_patient_ids[] = mysqli_insert_id($connection);
            
            // Assign to doctor
            $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                           VALUES ($large_doctor_id, " . mysqli_insert_id($connection) . ")";
            
            mysqli_query($connection, $assign_query);
        }
        
        // Test large patient list query
        $large_list_query = "SELECT COUNT(*) as patient_count
                           FROM users u
                           JOIN doctor_patients dp ON u.id = dp.patient_id
                           WHERE dp.doctor_id = $large_doctor_id AND u.role = 'patient'";
        
        $large_list_result = mysqli_query($connection, $large_list_query);
        $large_count = mysqli_fetch_assoc($large_list_result)['patient_count'];
        
        if ($large_count != $large_patient_count) {
            throw new Exception("Large doctor should have $large_patient_count patients. Got: $large_count");
        }
        
        echo "PASS: Doctor Patient Assignment Display property test passed\n";
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
    $result = runDoctorPatientAssignmentDisplayTest();
    exit($result ? 0 : 1);
}
?>