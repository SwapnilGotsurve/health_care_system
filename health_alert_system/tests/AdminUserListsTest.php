<?php
/**
 * Feature: health-alert-system, Property 17: Admin User Lists
 * 
 * Property 17: Admin User Lists
 * For any admin viewing user lists, the system should display all doctors with 
 * their status and all registered patients
 * Validates: Requirements 7.2, 7.5, 7.6
 */

// Simple test runner for Admin User Lists Property Test
function runAdminUserListsTest() {
    echo "Running Admin User Lists Property Test...\n";
    
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
    mysqli_query($connection, $create_alerts);
    mysqli_query($connection, $create_health_data);
    
    try {
        // Create test admin
        $admin_name = mysqli_real_escape_string($connection, 'Test Admin');
        $admin_email = mysqli_real_escape_string($connection, 'admin@test.com');
        $admin_password = mysqli_real_escape_string($connection, 'adminpass123');
        
        $admin_insert = "INSERT INTO users (name, email, password, role, status) 
                        VALUES ('$admin_name', '$admin_email', '$admin_password', 'admin', 'approved')";
        
        mysqli_query($connection, $admin_insert);
        $admin_id = mysqli_insert_id($connection);
        
        // Create test doctors with various statuses
        $test_doctors = [
            ['Dr. Alice Johnson', 'alice@test.com', 'password123', 'approved'],
            ['Dr. Bob Smith', 'bob@test.com', 'password456', 'approved'],
            ['Dr. Carol Wilson', 'carol@test.com', 'password789', 'pending'],
            ['Dr. David Brown', 'david@test.com', 'password101', 'pending'],
            ['Dr. Emma Davis', 'emma@test.com', 'password202', 'approved']
        ];
        
        $doctor_ids = [];
        
        // Insert doctors
        foreach ($test_doctors as $doctor_data) {
            $name = mysqli_real_escape_string($connection, $doctor_data[0]);
            $email = mysqli_real_escape_string($connection, $doctor_data[1]);
            $password = mysqli_real_escape_string($connection, $doctor_data[2]);
            $status = $doctor_data[3];
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', 'doctor', '$status')";
            
            mysqli_query($connection, $insert_query);
            $doctor_ids[] = mysqli_insert_id($connection);
        }
        
        // Create test patients
        $test_patients = [
            ['Patient Alpha', 'alpha@test.com', 'password123'],
            ['Patient Beta', 'beta@test.com', 'password456'],
            ['Patient Gamma', 'gamma@test.com', 'password789'],
            ['Patient Delta', 'delta@test.com', 'password101'],
            ['Patient Echo', 'echo@test.com', 'password202'],
            ['Patient Foxtrot', 'foxtrot@test.com', 'password303']
        ];
        
        $patient_ids = [];
        
        // Insert patients
        foreach ($test_patients as $patient_data) {
            $name = mysqli_real_escape_string($connection, $patient_data[0]);
            $email = mysqli_real_escape_string($connection, $patient_data[1]);
            $password = mysqli_real_escape_string($connection, $patient_data[2]);
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', 'patient', 'approved')";
            
            mysqli_query($connection, $insert_query);
            $patient_ids[] = mysqli_insert_id($connection);
        }
        
        // Create doctor-patient assignments
        $assignments = [
            [$doctor_ids[0], $patient_ids[0]], // Dr. Alice -> Patient Alpha
            [$doctor_ids[0], $patient_ids[1]], // Dr. Alice -> Patient Beta
            [$doctor_ids[1], $patient_ids[2]], // Dr. Bob -> Patient Gamma
            [$doctor_ids[1], $patient_ids[3]], // Dr. Bob -> Patient Delta
            [$doctor_ids[4], $patient_ids[4]], // Dr. Emma -> Patient Echo
        ];
        
        foreach ($assignments as $assignment) {
            $doctor_id = $assignment[0];
            $patient_id = $assignment[1];
            
            $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                           VALUES ($doctor_id, $patient_id)";
            
            mysqli_query($connection, $assign_query);
        }
        
        // Create some alerts
        $alert_data = [
            [$doctor_ids[0], $patient_ids[0], 'Test alert 1'],
            [$doctor_ids[0], $patient_ids[1], 'Test alert 2'],
            [$doctor_ids[1], $patient_ids[2], 'Test alert 3'],
            [$doctor_ids[4], $patient_ids[4], 'Test alert 4']
        ];
        
        foreach ($alert_data as $alert) {
            $doctor_id = $alert[0];
            $patient_id = $alert[1];
            $message = mysqli_real_escape_string($connection, $alert[2]);
            
            $alert_insert = "INSERT INTO alerts (doctor_id, patient_id, message) 
                           VALUES ($doctor_id, $patient_id, '$message')";
            
            mysqli_query($connection, $alert_insert);
        }
        
        // Create some health data
        $health_data = [
            [$patient_ids[0], 120, 80, 95.5, 72],
            [$patient_ids[1], 130, 85, 110.2, 78],
            [$patient_ids[2], 125, 82, 88.7, 75],
            [$patient_ids[4], 135, 90, 105.3, 80]
        ];
        
        foreach ($health_data as $health) {
            $patient_id = $health[0];
            $systolic = $health[1];
            $diastolic = $health[2];
            $sugar = $health[3];
            $heart_rate = $health[4];
            
            $health_insert = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                            VALUES ($patient_id, $systolic, $diastolic, $sugar, $heart_rate)";
            
            mysqli_query($connection, $health_insert);
        }
        
        // Test: Doctor list display (from doctor_list.php)
        $doctor_list_query = "SELECT u.id, u.name, u.email, u.status, u.created_at,
                                     COUNT(dp.patient_id) as patient_count,
                                     COUNT(a.id) as alert_count
                              FROM users u
                              LEFT JOIN doctor_patients dp ON u.id = dp.doctor_id
                              LEFT JOIN alerts a ON u.id = a.doctor_id
                              WHERE u.role = 'doctor'
                              GROUP BY u.id, u.name, u.email, u.status, u.created_at
                              ORDER BY u.created_at DESC";
        
        $doctor_list_result = mysqli_query($connection, $doctor_list_query);
        
        if (!$doctor_list_result) {
            throw new Exception("Doctor list query should execute successfully");
        }
        
        $displayed_doctors = [];
        while ($doctor = mysqli_fetch_assoc($doctor_list_result)) {
            $displayed_doctors[] = $doctor;
        }
        
        // Test: All doctors should be displayed
        if (count($displayed_doctors) != count($test_doctors)) {
            throw new Exception("All doctors should be displayed. Expected: " . count($test_doctors) . ", Got: " . count($displayed_doctors));
        }
        
        // Test: Each doctor should have required information
        foreach ($displayed_doctors as $doctor) {
            // Test doctor ID presence
            if (empty($doctor['id']) || !is_numeric($doctor['id'])) {
                throw new Exception("Each doctor should have a valid ID");
            }
            
            // Test name presence
            if (empty($doctor['name'])) {
                throw new Exception("Each doctor should have a name");
            }
            
            // Test email presence
            if (empty($doctor['email'])) {
                throw new Exception("Each doctor should have an email");
            }
            
            // Test status presence and validity
            if (!in_array($doctor['status'], ['pending', 'approved'])) {
                throw new Exception("Each doctor should have valid status (pending or approved). Got: " . $doctor['status']);
            }
            
            // Test created_at presence
            if (empty($doctor['created_at']) || strtotime($doctor['created_at']) === false) {
                throw new Exception("Each doctor should have valid created_at timestamp");
            }
            
            // Test patient_count is numeric
            if (!is_numeric($doctor['patient_count'])) {
                throw new Exception("Patient count should be numeric. Got: " . $doctor['patient_count']);
            }
            
            // Test alert_count is numeric
            if (!is_numeric($doctor['alert_count'])) {
                throw new Exception("Alert count should be numeric. Got: " . $doctor['alert_count']);
            }
        }
        
        // Test: Doctor status filtering
        $approved_doctors_query = "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'approved'";
        $approved_doctors_result = mysqli_query($connection, $approved_doctors_query);
        $approved_count = mysqli_fetch_assoc($approved_doctors_result)['count'];
        
        $pending_doctors_query = "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'pending'";
        $pending_doctors_result = mysqli_query($connection, $pending_doctors_query);
        $pending_count = mysqli_fetch_assoc($pending_doctors_result)['count'];
        
        // Verify counts match expected
        $expected_approved = count(array_filter($test_doctors, function($d) { return $d[3] === 'approved'; }));
        $expected_pending = count(array_filter($test_doctors, function($d) { return $d[3] === 'pending'; }));
        
        if ($approved_count != $expected_approved) {
            throw new Exception("Approved doctors count should match. Expected: $expected_approved, Got: $approved_count");
        }
        
        if ($pending_count != $expected_pending) {
            throw new Exception("Pending doctors count should match. Expected: $expected_pending, Got: $pending_count");
        }
        
        // Test: Patient list display (from patient_list.php)
        $patient_list_query = "SELECT u.id, u.name, u.email, u.created_at,
                                      COUNT(DISTINCT hd.id) as health_records_count,
                                      COUNT(DISTINCT dp.doctor_id) as assigned_doctors_count,
                                      COUNT(DISTINCT a.id) as alerts_received_count,
                                      MAX(hd.created_at) as last_health_record
                               FROM users u
                               LEFT JOIN health_data hd ON u.id = hd.patient_id
                               LEFT JOIN doctor_patients dp ON u.id = dp.patient_id
                               LEFT JOIN alerts a ON u.id = a.patient_id
                               WHERE u.role = 'patient'
                               GROUP BY u.id, u.name, u.email, u.created_at
                               ORDER BY u.created_at DESC";
        
        $patient_list_result = mysqli_query($connection, $patient_list_query);
        
        if (!$patient_list_result) {
            throw new Exception("Patient list query should execute successfully");
        }
        
        $displayed_patients = [];
        while ($patient = mysqli_fetch_assoc($patient_list_result)) {
            $displayed_patients[] = $patient;
        }
        
        // Test: All patients should be displayed
        if (count($displayed_patients) != count($test_patients)) {
            throw new Exception("All patients should be displayed. Expected: " . count($test_patients) . ", Got: " . count($displayed_patients));
        }
        
        // Test: Each patient should have required information
        foreach ($displayed_patients as $patient) {
            // Test patient ID presence
            if (empty($patient['id']) || !is_numeric($patient['id'])) {
                throw new Exception("Each patient should have a valid ID");
            }
            
            // Test name presence
            if (empty($patient['name'])) {
                throw new Exception("Each patient should have a name");
            }
            
            // Test email presence
            if (empty($patient['email'])) {
                throw new Exception("Each patient should have an email");
            }
            
            // Test created_at presence
            if (empty($patient['created_at']) || strtotime($patient['created_at']) === false) {
                throw new Exception("Each patient should have valid created_at timestamp");
            }
            
            // Test health_records_count is numeric
            if (!is_numeric($patient['health_records_count'])) {
                throw new Exception("Health records count should be numeric. Got: " . $patient['health_records_count']);
            }
            
            // Test assigned_doctors_count is numeric
            if (!is_numeric($patient['assigned_doctors_count'])) {
                throw new Exception("Assigned doctors count should be numeric. Got: " . $patient['assigned_doctors_count']);
            }
            
            // Test alerts_received_count is numeric
            if (!is_numeric($patient['alerts_received_count'])) {
                throw new Exception("Alerts received count should be numeric. Got: " . $patient['alerts_received_count']);
            }
        }
        
        // Test: Search functionality for doctors
        $search_term = 'Alice';
        $search_escaped = mysqli_real_escape_string($connection, $search_term);
        $doctor_search_query = "SELECT u.id, u.name, u.email, u.status, u.created_at,
                                       COUNT(dp.patient_id) as patient_count,
                                       COUNT(a.id) as alert_count
                                FROM users u
                                LEFT JOIN doctor_patients dp ON u.id = dp.doctor_id
                                LEFT JOIN alerts a ON u.id = a.doctor_id
                                WHERE u.role = 'doctor' AND (u.name LIKE '%$search_escaped%' OR u.email LIKE '%$search_escaped%')
                                GROUP BY u.id, u.name, u.email, u.status, u.created_at
                                ORDER BY u.created_at DESC";
        
        $doctor_search_result = mysqli_query($connection, $doctor_search_query);
        $search_results = [];
        
        while ($doctor = mysqli_fetch_assoc($doctor_search_result)) {
            $search_results[] = $doctor;
        }
        
        // Should find Dr. Alice Johnson
        if (count($search_results) == 0) {
            throw new Exception("Search should find matching doctors");
        }
        
        $found_alice = false;
        foreach ($search_results as $result) {
            if (strpos($result['name'], 'Alice') !== false) {
                $found_alice = true;
                break;
            }
        }
        
        if (!$found_alice) {
            throw new Exception("Search should find Dr. Alice Johnson");
        }
        
        // Test: Search functionality for patients
        $patient_search_term = 'Alpha';
        $patient_search_escaped = mysqli_real_escape_string($connection, $patient_search_term);
        $patient_search_query = "SELECT u.id, u.name, u.email, u.created_at,
                                        COUNT(DISTINCT hd.id) as health_records_count,
                                        COUNT(DISTINCT dp.doctor_id) as assigned_doctors_count,
                                        COUNT(DISTINCT a.id) as alerts_received_count,
                                        MAX(hd.created_at) as last_health_record
                                 FROM users u
                                 LEFT JOIN health_data hd ON u.id = hd.patient_id
                                 LEFT JOIN doctor_patients dp ON u.id = dp.patient_id
                                 LEFT JOIN alerts a ON u.id = a.patient_id
                                 WHERE u.role = 'patient' AND (u.name LIKE '%$patient_search_escaped%' OR u.email LIKE '%$patient_search_escaped%')
                                 GROUP BY u.id, u.name, u.email, u.created_at
                                 ORDER BY u.created_at DESC";
        
        $patient_search_result = mysqli_query($connection, $patient_search_query);
        $patient_search_results = [];
        
        while ($patient = mysqli_fetch_assoc($patient_search_result)) {
            $patient_search_results[] = $patient;
        }
        
        // Should find Patient Alpha
        if (count($patient_search_results) == 0) {
            throw new Exception("Patient search should find matching patients");
        }
        
        $found_alpha = false;
        foreach ($patient_search_results as $result) {
            if (strpos($result['name'], 'Alpha') !== false) {
                $found_alpha = true;
                break;
            }
        }
        
        if (!$found_alpha) {
            throw new Exception("Patient search should find Patient Alpha");
        }
        
        // Test: Statistics accuracy for doctors
        $doctor_stats_query = "SELECT 
                                  COUNT(*) as total_doctors,
                                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                                  SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                                  COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
                               FROM users 
                               WHERE role = 'doctor'";
        
        $doctor_stats_result = mysqli_query($connection, $doctor_stats_query);
        $doctor_stats = mysqli_fetch_assoc($doctor_stats_result);
        
        // Verify statistics consistency
        if ($doctor_stats['total_doctors'] != ($doctor_stats['pending_count'] + $doctor_stats['approved_count'])) {
            throw new Exception("Doctor statistics should be consistent. Total: " . $doctor_stats['total_doctors'] . 
                              ", Pending: " . $doctor_stats['pending_count'] . ", Approved: " . $doctor_stats['approved_count']);
        }
        
        if ($doctor_stats['total_doctors'] != count($test_doctors)) {
            throw new Exception("Total doctors should match test data. Expected: " . count($test_doctors) . ", Got: " . $doctor_stats['total_doctors']);
        }
        
        // Test: Statistics accuracy for patients
        $patient_stats_query = "SELECT 
                                   COUNT(*) as total_patients,
                                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_this_month,
                                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_this_week
                               FROM users 
                               WHERE role = 'patient'";
        
        $patient_stats_result = mysqli_query($connection, $patient_stats_query);
        $patient_stats = mysqli_fetch_assoc($patient_stats_result);
        
        if ($patient_stats['total_patients'] != count($test_patients)) {
            throw new Exception("Total patients should match test data. Expected: " . count($test_patients) . ", Got: " . $patient_stats['total_patients']);
        }
        
        // Test: Pagination functionality
        $page_size = 3;
        $page_1_query = "SELECT u.id, u.name, u.email, u.status, u.created_at,
                               COUNT(dp.patient_id) as patient_count,
                               COUNT(a.id) as alert_count
                        FROM users u
                        LEFT JOIN doctor_patients dp ON u.id = dp.doctor_id
                        LEFT JOIN alerts a ON u.id = a.doctor_id
                        WHERE u.role = 'doctor'
                        GROUP BY u.id, u.name, u.email, u.status, u.created_at
                        ORDER BY u.created_at DESC
                        LIMIT $page_size OFFSET 0";
        
        $page_1_result = mysqli_query($connection, $page_1_query);
        $page_1_doctors = [];
        
        while ($doctor = mysqli_fetch_assoc($page_1_result)) {
            $page_1_doctors[] = $doctor;
        }
        
        $page_2_query = "SELECT u.id, u.name, u.email, u.status, u.created_at,
                               COUNT(dp.patient_id) as patient_count,
                               COUNT(a.id) as alert_count
                        FROM users u
                        LEFT JOIN doctor_patients dp ON u.id = dp.doctor_id
                        LEFT JOIN alerts a ON u.id = a.doctor_id
                        WHERE u.role = 'doctor'
                        GROUP BY u.id, u.name, u.email, u.status, u.created_at
                        ORDER BY u.created_at DESC
                        LIMIT $page_size OFFSET $page_size";
        
        $page_2_result = mysqli_query($connection, $page_2_query);
        $page_2_doctors = [];
        
        while ($doctor = mysqli_fetch_assoc($page_2_result)) {
            $page_2_doctors[] = $doctor;
        }
        
        // Test: No overlap between pages
        foreach ($page_1_doctors as $doctor1) {
            foreach ($page_2_doctors as $doctor2) {
                if ($doctor1['id'] == $doctor2['id']) {
                    throw new Exception("Pagination should not have overlapping doctors");
                }
            }
        }
        
        // Test: Page size limits
        if (count($page_1_doctors) > $page_size) {
            throw new Exception("Page 1 should not exceed page size. Got: " . count($page_1_doctors));
        }
        
        if (count($page_2_doctors) > $page_size) {
            throw new Exception("Page 2 should not exceed page size. Got: " . count($page_2_doctors));
        }
        
        // Test: Role isolation (doctors and patients should not appear in each other's lists)
        $role_isolation_query = "SELECT DISTINCT role FROM users WHERE role IN ('doctor', 'patient')";
        $role_isolation_result = mysqli_query($connection, $role_isolation_query);
        
        $roles_found = [];
        while ($role = mysqli_fetch_assoc($role_isolation_result)) {
            $roles_found[] = $role['role'];
        }
        
        if (!in_array('doctor', $roles_found) || !in_array('patient', $roles_found)) {
            throw new Exception("Both doctor and patient roles should exist in test data");
        }
        
        // Verify doctors don't appear in patient list
        $doctor_in_patient_list_query = "SELECT COUNT(*) as count FROM users WHERE role = 'patient' AND id IN (" . implode(',', $doctor_ids) . ")";
        $doctor_in_patient_result = mysqli_query($connection, $doctor_in_patient_list_query);
        $doctors_in_patient_list = mysqli_fetch_assoc($doctor_in_patient_result)['count'];
        
        if ($doctors_in_patient_list > 0) {
            throw new Exception("Doctors should not appear in patient list");
        }
        
        // Verify patients don't appear in doctor list
        $patient_in_doctor_list_query = "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND id IN (" . implode(',', $patient_ids) . ")";
        $patient_in_doctor_result = mysqli_query($connection, $patient_in_doctor_list_query);
        $patients_in_doctor_list = mysqli_fetch_assoc($patient_in_doctor_result)['count'];
        
        if ($patients_in_doctor_list > 0) {
            throw new Exception("Patients should not appear in doctor list");
        }
        
        // Test: Data integrity with relationships
        foreach ($displayed_doctors as $doctor) {
            // Verify patient count accuracy
            $actual_patient_count_query = "SELECT COUNT(*) as count FROM doctor_patients WHERE doctor_id = " . $doctor['id'];
            $actual_patient_count_result = mysqli_query($connection, $actual_patient_count_query);
            $actual_patient_count = mysqli_fetch_assoc($actual_patient_count_result)['count'];
            
            if ($doctor['patient_count'] != $actual_patient_count) {
                throw new Exception("Doctor patient count should be accurate. Expected: $actual_patient_count, Got: " . $doctor['patient_count']);
            }
            
            // Verify alert count accuracy
            $actual_alert_count_query = "SELECT COUNT(*) as count FROM alerts WHERE doctor_id = " . $doctor['id'];
            $actual_alert_count_result = mysqli_query($connection, $actual_alert_count_query);
            $actual_alert_count = mysqli_fetch_assoc($actual_alert_count_result)['count'];
            
            if ($doctor['alert_count'] != $actual_alert_count) {
                throw new Exception("Doctor alert count should be accurate. Expected: $actual_alert_count, Got: " . $doctor['alert_count']);
            }
        }
        
        foreach ($displayed_patients as $patient) {
            // Verify health records count accuracy
            $actual_health_count_query = "SELECT COUNT(*) as count FROM health_data WHERE patient_id = " . $patient['id'];
            $actual_health_count_result = mysqli_query($connection, $actual_health_count_query);
            $actual_health_count = mysqli_fetch_assoc($actual_health_count_result)['count'];
            
            if ($patient['health_records_count'] != $actual_health_count) {
                throw new Exception("Patient health records count should be accurate. Expected: $actual_health_count, Got: " . $patient['health_records_count']);
            }
            
            // Verify assigned doctors count accuracy
            $actual_doctors_count_query = "SELECT COUNT(*) as count FROM doctor_patients WHERE patient_id = " . $patient['id'];
            $actual_doctors_count_result = mysqli_query($connection, $actual_doctors_count_query);
            $actual_doctors_count = mysqli_fetch_assoc($actual_doctors_count_result)['count'];
            
            if ($patient['assigned_doctors_count'] != $actual_doctors_count) {
                throw new Exception("Patient assigned doctors count should be accurate. Expected: $actual_doctors_count, Got: " . $patient['assigned_doctors_count']);
            }
            
            // Verify alerts received count accuracy
            $actual_alerts_count_query = "SELECT COUNT(*) as count FROM alerts WHERE patient_id = " . $patient['id'];
            $actual_alerts_count_result = mysqli_query($connection, $actual_alerts_count_query);
            $actual_alerts_count = mysqli_fetch_assoc($actual_alerts_count_result)['count'];
            
            if ($patient['alerts_received_count'] != $actual_alerts_count) {
                throw new Exception("Patient alerts received count should be accurate. Expected: $actual_alerts_count, Got: " . $patient['alerts_received_count']);
            }
        }
        
        // Test: Empty result handling
        $empty_search_query = "SELECT u.id, u.name, u.email, u.status, u.created_at,
                                      COUNT(dp.patient_id) as patient_count,
                                      COUNT(a.id) as alert_count
                               FROM users u
                               LEFT JOIN doctor_patients dp ON u.id = dp.doctor_id
                               LEFT JOIN alerts a ON u.id = a.doctor_id
                               WHERE u.role = 'doctor' AND u.name LIKE '%NonExistentName%'
                               GROUP BY u.id, u.name, u.email, u.status, u.created_at
                               ORDER BY u.created_at DESC";
        
        $empty_search_result = mysqli_query($connection, $empty_search_query);
        
        if (mysqli_num_rows($empty_search_result) != 0) {
            throw new Exception("Empty search should return no results");
        }
        
        // Test: Large dataset performance
        $large_doctors = [];
        for ($i = 0; $i < 50; $i++) {
            $large_name = mysqli_real_escape_string($connection, "Dr. Large Test $i");
            $large_email = mysqli_real_escape_string($connection, "large$i@test.com");
            $large_password = mysqli_real_escape_string($connection, "password$i");
            $large_status = ($i % 2 == 0) ? 'approved' : 'pending';
            
            $large_insert = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$large_name', '$large_email', '$large_password', 'doctor', '$large_status')";
            
            mysqli_query($connection, $large_insert);
            $large_doctors[] = mysqli_insert_id($connection);
        }
        
        // Test query performance with larger dataset
        $start_time = microtime(true);
        
        $large_query = "SELECT u.id, u.name, u.email, u.status, u.created_at,
                               COUNT(dp.patient_id) as patient_count,
                               COUNT(a.id) as alert_count
                        FROM users u
                        LEFT JOIN doctor_patients dp ON u.id = dp.doctor_id
                        LEFT JOIN alerts a ON u.id = a.doctor_id
                        WHERE u.role = 'doctor'
                        GROUP BY u.id, u.name, u.email, u.status, u.created_at
                        ORDER BY u.created_at DESC";
        
        $large_result = mysqli_query($connection, $large_query);
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        // Should complete within reasonable time (2 seconds)
        if ($execution_time > 2.0) {
            throw new Exception("Large dataset query should complete within reasonable time. Took: {$execution_time}s");
        }
        
        // Verify all doctors are returned
        $large_count = mysqli_num_rows($large_result);
        $expected_large_count = count($test_doctors) + count($large_doctors);
        
        if ($large_count != $expected_large_count) {
            throw new Exception("Large dataset should return all doctors. Expected: $expected_large_count, Got: $large_count");
        }
        
        echo "PASS: Admin User Lists property test passed\n";
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
    $result = runAdminUserListsTest();
    exit($result ? 0 : 1);
}
?>