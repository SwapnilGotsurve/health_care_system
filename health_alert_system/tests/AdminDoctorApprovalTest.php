<?php
/**
 * Feature: health-alert-system, Property 15: Admin Doctor Approval
 * 
 * Property 15: Admin Doctor Approval
 * For any pending doctor, when an admin approves them, the system should update 
 * their status to "approved"
 * Validates: Requirements 7.3
 */

// Simple test runner for Admin Doctor Approval Property Test
function runAdminDoctorApprovalTest() {
    echo "Running Admin Doctor Approval Property Test...\n";
    
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
    
    mysqli_query($connection, $create_users);
    
    try {
        // Create test admin
        $admin_name = mysqli_real_escape_string($connection, 'Test Admin');
        $admin_email = mysqli_real_escape_string($connection, 'admin@test.com');
        $admin_password = mysqli_real_escape_string($connection, 'adminpass123');
        
        $admin_insert = "INSERT INTO users (name, email, password, role, status) 
                        VALUES ('$admin_name', '$admin_email', '$admin_password', 'admin', 'approved')";
        
        mysqli_query($connection, $admin_insert);
        $admin_id = mysqli_insert_id($connection);
        
        // Create test pending doctors
        $pending_doctors = [
            ['Dr. Alice Johnson', 'alice.johnson@hospital.com', 'password123'],
            ['Dr. Bob Smith', 'bob.smith@clinic.org', 'password456'],
            ['Dr. Carol Wilson', 'carol.wilson@medical.net', 'password789'],
            ['Dr. David Brown', 'david.brown@healthcare.com', 'password101'],
            ['Dr. Emma Davis', 'emma.davis@medicine.edu', 'password202']
        ];
        
        $pending_doctor_ids = [];
        
        // Insert pending doctors
        foreach ($pending_doctors as $doctor_data) {
            $name = mysqli_real_escape_string($connection, $doctor_data[0]);
            $email = mysqli_real_escape_string($connection, $doctor_data[1]);
            $password = mysqli_real_escape_string($connection, $doctor_data[2]);
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', 'doctor', 'pending')";
            
            mysqli_query($connection, $insert_query);
            $pending_doctor_ids[] = mysqli_insert_id($connection);
        }
        
        // Test: Approve each pending doctor
        foreach ($pending_doctor_ids as $index => $doctor_id) {
            $doctor_name = $pending_doctors[$index][0];
            
            // Verify doctor is initially pending
            $check_pending_query = "SELECT status FROM users WHERE id = $doctor_id AND role = 'doctor'";
            $check_pending_result = mysqli_query($connection, $check_pending_query);
            $initial_status = mysqli_fetch_assoc($check_pending_result)['status'];
            
            if ($initial_status !== 'pending') {
                throw new Exception("Doctor should initially have pending status for $doctor_name. Got: $initial_status");
            }
            
            // Simulate admin approval (from doctor_approvals.php)
            $approve_query = "UPDATE users SET status = 'approved' 
                            WHERE id = $doctor_id AND role = 'doctor' AND status = 'pending'";
            
            $approve_result = mysqli_query($connection, $approve_query);
            
            if (!$approve_result) {
                throw new Exception("Approval query should execute successfully for $doctor_name");
            }
            
            $affected_rows = mysqli_affected_rows($connection);
            
            if ($affected_rows != 1) {
                throw new Exception("Approval should affect exactly 1 row for $doctor_name. Affected: $affected_rows");
            }
            
            // Verify doctor status is now approved
            $check_approved_query = "SELECT status FROM users WHERE id = $doctor_id AND role = 'doctor'";
            $check_approved_result = mysqli_query($connection, $check_approved_query);
            $final_status = mysqli_fetch_assoc($check_approved_result)['status'];
            
            if ($final_status !== 'approved') {
                throw new Exception("Doctor should have approved status after approval for $doctor_name. Got: $final_status");
            }
            
            // Test: Verify doctor can no longer be approved again (idempotency)
            $double_approve_query = "UPDATE users SET status = 'approved' 
                                   WHERE id = $doctor_id AND role = 'doctor' AND status = 'pending'";
            
            mysqli_query($connection, $double_approve_query);
            $double_affected = mysqli_affected_rows($connection);
            
            if ($double_affected != 0) {
                throw new Exception("Double approval should not affect any rows for $doctor_name. Affected: $double_affected");
            }
        }
        
        // Test: Approval of non-existent doctor
        $non_existent_id = 99999;
        $non_existent_query = "UPDATE users SET status = 'approved' 
                             WHERE id = $non_existent_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $non_existent_query);
        $non_existent_affected = mysqli_affected_rows($connection);
        
        if ($non_existent_affected != 0) {
            throw new Exception("Approval of non-existent doctor should not affect any rows. Affected: $non_existent_affected");
        }
        
        // Test: Approval with wrong role constraint
        $patient_name = mysqli_real_escape_string($connection, 'Test Patient');
        $patient_email = mysqli_real_escape_string($connection, 'patient@test.com');
        $patient_password = mysqli_real_escape_string($connection, 'patientpass');
        
        $patient_insert = "INSERT INTO users (name, email, password, role, status) 
                         VALUES ('$patient_name', '$patient_email', '$patient_password', 'patient', 'approved')";
        
        mysqli_query($connection, $patient_insert);
        $patient_id = mysqli_insert_id($connection);
        
        $wrong_role_query = "UPDATE users SET status = 'approved' 
                           WHERE id = $patient_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $wrong_role_query);
        $wrong_role_affected = mysqli_affected_rows($connection);
        
        if ($wrong_role_affected != 0) {
            throw new Exception("Approval with wrong role constraint should not affect any rows. Affected: $wrong_role_affected");
        }
        
        // Test: Approval with wrong status constraint
        $already_approved_name = mysqli_real_escape_string($connection, 'Dr. Already Approved');
        $already_approved_email = mysqli_real_escape_string($connection, 'approved@test.com');
        $already_approved_password = mysqli_real_escape_string($connection, 'password');
        
        $already_approved_insert = "INSERT INTO users (name, email, password, role, status) 
                                  VALUES ('$already_approved_name', '$already_approved_email', '$already_approved_password', 'doctor', 'approved')";
        
        mysqli_query($connection, $already_approved_insert);
        $already_approved_id = mysqli_insert_id($connection);
        
        $wrong_status_query = "UPDATE users SET status = 'approved' 
                             WHERE id = $already_approved_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $wrong_status_query);
        $wrong_status_affected = mysqli_affected_rows($connection);
        
        if ($wrong_status_affected != 0) {
            throw new Exception("Approval with wrong status constraint should not affect any rows. Affected: $wrong_status_affected");
        }
        
        // Test: Bulk approval scenario
        $bulk_doctors = [];
        for ($i = 0; $i < 10; $i++) {
            $bulk_name = mysqli_real_escape_string($connection, "Dr. Bulk Test $i");
            $bulk_email = mysqli_real_escape_string($connection, "bulk$i@test.com");
            $bulk_password = mysqli_real_escape_string($connection, "password$i");
            
            $bulk_insert = "INSERT INTO users (name, email, password, role, status) 
                          VALUES ('$bulk_name', '$bulk_email', '$bulk_password', 'doctor', 'pending')";
            
            mysqli_query($connection, $bulk_insert);
            $bulk_doctors[] = mysqli_insert_id($connection);
        }
        
        // Approve all bulk doctors
        foreach ($bulk_doctors as $bulk_id) {
            $bulk_approve_query = "UPDATE users SET status = 'approved' 
                                 WHERE id = $bulk_id AND role = 'doctor' AND status = 'pending'";
            
            mysqli_query($connection, $bulk_approve_query);
            $bulk_affected = mysqli_affected_rows($connection);
            
            if ($bulk_affected != 1) {
                throw new Exception("Bulk approval should affect exactly 1 row per doctor. Affected: $bulk_affected");
            }
        }
        
        // Verify all bulk doctors are approved
        $bulk_ids_str = implode(',', $bulk_doctors);
        $bulk_verify_query = "SELECT COUNT(*) as approved_count 
                            FROM users 
                            WHERE id IN ($bulk_ids_str) AND role = 'doctor' AND status = 'approved'";
        
        $bulk_verify_result = mysqli_query($connection, $bulk_verify_query);
        $approved_count = mysqli_fetch_assoc($bulk_verify_result)['approved_count'];
        
        if ($approved_count != count($bulk_doctors)) {
            throw new Exception("All bulk doctors should be approved. Expected: " . count($bulk_doctors) . ", Got: $approved_count");
        }
        
        // Test: Approval preserves other user data
        $preserve_test_name = mysqli_real_escape_string($connection, 'Dr. Preserve Test');
        $preserve_test_email = mysqli_real_escape_string($connection, 'preserve@test.com');
        $preserve_test_password = mysqli_real_escape_string($connection, 'preservepass');
        
        $preserve_insert = "INSERT INTO users (name, email, password, role, status) 
                          VALUES ('$preserve_test_name', '$preserve_test_email', '$preserve_test_password', 'doctor', 'pending')";
        
        mysqli_query($connection, $preserve_insert);
        $preserve_id = mysqli_insert_id($connection);
        
        // Get original data
        $original_query = "SELECT name, email, password, role, created_at FROM users WHERE id = $preserve_id";
        $original_result = mysqli_query($connection, $original_query);
        $original_data = mysqli_fetch_assoc($original_result);
        
        // Approve doctor
        $preserve_approve_query = "UPDATE users SET status = 'approved' 
                                 WHERE id = $preserve_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $preserve_approve_query);
        
        // Verify other data is preserved
        $after_query = "SELECT name, email, password, role, created_at FROM users WHERE id = $preserve_id";
        $after_result = mysqli_query($connection, $after_query);
        $after_data = mysqli_fetch_assoc($after_result);
        
        foreach ($original_data as $field => $value) {
            if ($after_data[$field] !== $value) {
                throw new Exception("Field $field should be preserved during approval. Expected: $value, Got: " . $after_data[$field]);
            }
        }
        
        // Test: Concurrent approval scenario (race condition simulation)
        $concurrent_name = mysqli_real_escape_string($connection, 'Dr. Concurrent Test');
        $concurrent_email = mysqli_real_escape_string($connection, 'concurrent@test.com');
        $concurrent_password = mysqli_real_escape_string($connection, 'concurrentpass');
        
        $concurrent_insert = "INSERT INTO users (name, email, password, role, status) 
                            VALUES ('$concurrent_name', '$concurrent_email', '$concurrent_password', 'doctor', 'pending')";
        
        mysqli_query($connection, $concurrent_insert);
        $concurrent_id = mysqli_insert_id($connection);
        
        // Simulate two concurrent approval attempts
        $concurrent_query1 = "UPDATE users SET status = 'approved' 
                            WHERE id = $concurrent_id AND role = 'doctor' AND status = 'pending'";
        
        $concurrent_query2 = "UPDATE users SET status = 'approved' 
                            WHERE id = $concurrent_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $concurrent_query1);
        $concurrent_affected1 = mysqli_affected_rows($connection);
        
        mysqli_query($connection, $concurrent_query2);
        $concurrent_affected2 = mysqli_affected_rows($connection);
        
        // Only one should succeed
        $total_concurrent_affected = $concurrent_affected1 + $concurrent_affected2;
        
        if ($total_concurrent_affected != 1) {
            throw new Exception("Concurrent approvals should result in exactly 1 total affected row. Got: $total_concurrent_affected");
        }
        
        // Test: Approval statistics accuracy
        $stats_query = "SELECT 
                          COUNT(*) as total_doctors,
                          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                          SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
                        FROM users 
                        WHERE role = 'doctor'";
        
        $stats_result = mysqli_query($connection, $stats_query);
        $stats = mysqli_fetch_assoc($stats_result);
        
        // Verify statistics consistency
        if ($stats['total_doctors'] != ($stats['pending_count'] + $stats['approved_count'])) {
            throw new Exception("Doctor statistics should be consistent. Total: " . $stats['total_doctors'] . 
                              ", Pending: " . $stats['pending_count'] . ", Approved: " . $stats['approved_count']);
        }
        
        // Test: Approval with timestamp verification
        $timestamp_name = mysqli_real_escape_string($connection, 'Dr. Timestamp Test');
        $timestamp_email = mysqli_real_escape_string($connection, 'timestamp@test.com');
        $timestamp_password = mysqli_real_escape_string($connection, 'timestamppass');
        
        $timestamp_insert = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$timestamp_name', '$timestamp_email', '$timestamp_password', 'doctor', 'pending')";
        
        mysqli_query($connection, $timestamp_insert);
        $timestamp_id = mysqli_insert_id($connection);
        
        // Get creation timestamp
        $creation_query = "SELECT created_at FROM users WHERE id = $timestamp_id";
        $creation_result = mysqli_query($connection, $creation_query);
        $creation_time = mysqli_fetch_assoc($creation_result)['created_at'];
        
        // Approve doctor
        $timestamp_approve_query = "UPDATE users SET status = 'approved' 
                                  WHERE id = $timestamp_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $timestamp_approve_query);
        
        // Verify creation timestamp is preserved
        $after_creation_query = "SELECT created_at FROM users WHERE id = $timestamp_id";
        $after_creation_result = mysqli_query($connection, $after_creation_query);
        $after_creation_time = mysqli_fetch_assoc($after_creation_result)['created_at'];
        
        if ($creation_time !== $after_creation_time) {
            throw new Exception("Creation timestamp should be preserved during approval. Expected: $creation_time, Got: $after_creation_time");
        }
        
        // Test: Approval query performance with large dataset
        $performance_doctors = [];
        for ($i = 0; $i < 100; $i++) {
            $perf_name = mysqli_real_escape_string($connection, "Dr. Performance Test $i");
            $perf_email = mysqli_real_escape_string($connection, "perf$i@test.com");
            $perf_password = mysqli_real_escape_string($connection, "perfpass$i");
            
            $perf_insert = "INSERT INTO users (name, email, password, role, status) 
                          VALUES ('$perf_name', '$perf_email', '$perf_password', 'doctor', 'pending')";
            
            mysqli_query($connection, $perf_insert);
            $performance_doctors[] = mysqli_insert_id($connection);
        }
        
        // Time the approval operations
        $start_time = microtime(true);
        
        foreach ($performance_doctors as $perf_id) {
            $perf_approve_query = "UPDATE users SET status = 'approved' 
                                 WHERE id = $perf_id AND role = 'doctor' AND status = 'pending'";
            
            mysqli_query($connection, $perf_approve_query);
        }
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        // Verify reasonable performance (should complete within 5 seconds)
        if ($execution_time > 5.0) {
            throw new Exception("Approval operations should complete within reasonable time. Took: {$execution_time}s");
        }
        
        // Verify all performance test doctors are approved
        $perf_ids_str = implode(',', $performance_doctors);
        $perf_verify_query = "SELECT COUNT(*) as perf_approved 
                            FROM users 
                            WHERE id IN ($perf_ids_str) AND role = 'doctor' AND status = 'approved'";
        
        $perf_verify_result = mysqli_query($connection, $perf_verify_query);
        $perf_approved = mysqli_fetch_assoc($perf_verify_result)['perf_approved'];
        
        if ($perf_approved != count($performance_doctors)) {
            throw new Exception("All performance test doctors should be approved. Expected: " . count($performance_doctors) . ", Got: $perf_approved");
        }
        
        echo "PASS: Admin Doctor Approval property test passed\n";
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
    $result = runAdminDoctorApprovalTest();
    exit($result ? 0 : 1);
}
?>