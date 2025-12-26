<?php
/**
 * Feature: health-alert-system, Property 16: Admin Doctor Rejection
 * 
 * Property 16: Admin Doctor Rejection
 * For any pending doctor, when an admin rejects them, the system should delete 
 * their account from the system
 * Validates: Requirements 7.4
 */

// Simple test runner for Admin Doctor Rejection Property Test
function runAdminDoctorRejectionTest() {
    echo "Running Admin Doctor Rejection Property Test...\n";
    
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
        // Create test admin
        $admin_name = mysqli_real_escape_string($connection, 'Test Admin');
        $admin_email = mysqli_real_escape_string($connection, 'admin@test.com');
        $admin_password = mysqli_real_escape_string($connection, 'adminpass123');
        
        $admin_insert = "INSERT INTO users (name, email, password, role, status) 
                        VALUES ('$admin_name', '$admin_email', '$admin_password', 'admin', 'approved')";
        
        mysqli_query($connection, $admin_insert);
        $admin_id = mysqli_insert_id($connection);
        
        // Create test pending doctors for rejection
        $pending_doctors = [
            ['Dr. Reject Alpha', 'reject.alpha@test.com', 'password123'],
            ['Dr. Reject Beta', 'reject.beta@test.com', 'password456'],
            ['Dr. Reject Gamma', 'reject.gamma@test.com', 'password789'],
            ['Dr. Reject Delta', 'reject.delta@test.com', 'password101'],
            ['Dr. Reject Echo', 'reject.echo@test.com', 'password202']
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
        
        // Test: Reject each pending doctor
        foreach ($pending_doctor_ids as $index => $doctor_id) {
            $doctor_name = $pending_doctors[$index][0];
            
            // Verify doctor exists and is pending
            $check_exists_query = "SELECT id, status FROM users WHERE id = $doctor_id AND role = 'doctor'";
            $check_exists_result = mysqli_query($connection, $check_exists_query);
            
            if (mysqli_num_rows($check_exists_result) == 0) {
                throw new Exception("Doctor should exist before rejection for $doctor_name");
            }
            
            $doctor_data = mysqli_fetch_assoc($check_exists_result);
            
            if ($doctor_data['status'] !== 'pending') {
                throw new Exception("Doctor should have pending status before rejection for $doctor_name. Got: " . $doctor_data['status']);
            }
            
            // Simulate admin rejection (from doctor_approvals.php)
            $reject_query = "DELETE FROM users WHERE id = $doctor_id AND role = 'doctor' AND status = 'pending'";
            
            $reject_result = mysqli_query($connection, $reject_query);
            
            if (!$reject_result) {
                throw new Exception("Rejection query should execute successfully for $doctor_name");
            }
            
            $affected_rows = mysqli_affected_rows($connection);
            
            if ($affected_rows != 1) {
                throw new Exception("Rejection should affect exactly 1 row for $doctor_name. Affected: $affected_rows");
            }
            
            // Verify doctor no longer exists in database
            $check_deleted_query = "SELECT COUNT(*) as count FROM users WHERE id = $doctor_id";
            $check_deleted_result = mysqli_query($connection, $check_deleted_query);
            $remaining_count = mysqli_fetch_assoc($check_deleted_result)['count'];
            
            if ($remaining_count != 0) {
                throw new Exception("Doctor should be completely deleted after rejection for $doctor_name. Remaining: $remaining_count");
            }
            
            // Test: Verify doctor cannot be rejected again (idempotency)
            $double_reject_query = "DELETE FROM users WHERE id = $doctor_id AND role = 'doctor' AND status = 'pending'";
            
            mysqli_query($connection, $double_reject_query);
            $double_affected = mysqli_affected_rows($connection);
            
            if ($double_affected != 0) {
                throw new Exception("Double rejection should not affect any rows for $doctor_name. Affected: $double_affected");
            }
        }
        
        // Test: Rejection of non-existent doctor
        $non_existent_id = 99999;
        $non_existent_query = "DELETE FROM users WHERE id = $non_existent_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $non_existent_query);
        $non_existent_affected = mysqli_affected_rows($connection);
        
        if ($non_existent_affected != 0) {
            throw new Exception("Rejection of non-existent doctor should not affect any rows. Affected: $non_existent_affected");
        }
        
        // Test: Rejection with wrong role constraint
        $patient_name = mysqli_real_escape_string($connection, 'Test Patient');
        $patient_email = mysqli_real_escape_string($connection, 'patient@test.com');
        $patient_password = mysqli_real_escape_string($connection, 'patientpass');
        
        $patient_insert = "INSERT INTO users (name, email, password, role, status) 
                         VALUES ('$patient_name', '$patient_email', '$patient_password', 'patient', 'approved')";
        
        mysqli_query($connection, $patient_insert);
        $patient_id = mysqli_insert_id($connection);
        
        $wrong_role_query = "DELETE FROM users WHERE id = $patient_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $wrong_role_query);
        $wrong_role_affected = mysqli_affected_rows($connection);
        
        if ($wrong_role_affected != 0) {
            throw new Exception("Rejection with wrong role constraint should not affect any rows. Affected: $wrong_role_affected");
        }
        
        // Verify patient still exists
        $patient_check_query = "SELECT COUNT(*) as count FROM users WHERE id = $patient_id";
        $patient_check_result = mysqli_query($connection, $patient_check_query);
        $patient_exists = mysqli_fetch_assoc($patient_check_result)['count'];
        
        if ($patient_exists != 1) {
            throw new Exception("Patient should not be affected by doctor rejection query");
        }
        
        // Test: Rejection with wrong status constraint
        $approved_doctor_name = mysqli_real_escape_string($connection, 'Dr. Already Approved');
        $approved_doctor_email = mysqli_real_escape_string($connection, 'approved@test.com');
        $approved_doctor_password = mysqli_real_escape_string($connection, 'password');
        
        $approved_doctor_insert = "INSERT INTO users (name, email, password, role, status) 
                                 VALUES ('$approved_doctor_name', '$approved_doctor_email', '$approved_doctor_password', 'doctor', 'approved')";
        
        mysqli_query($connection, $approved_doctor_insert);
        $approved_doctor_id = mysqli_insert_id($connection);
        
        $wrong_status_query = "DELETE FROM users WHERE id = $approved_doctor_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $wrong_status_query);
        $wrong_status_affected = mysqli_affected_rows($connection);
        
        if ($wrong_status_affected != 0) {
            throw new Exception("Rejection with wrong status constraint should not affect any rows. Affected: $wrong_status_affected");
        }
        
        // Verify approved doctor still exists
        $approved_check_query = "SELECT COUNT(*) as count FROM users WHERE id = $approved_doctor_id";
        $approved_check_result = mysqli_query($connection, $approved_check_query);
        $approved_exists = mysqli_fetch_assoc($approved_check_result)['count'];
        
        if ($approved_exists != 1) {
            throw new Exception("Approved doctor should not be affected by pending rejection query");
        }
        
        // Test: Cascade deletion with related data
        $cascade_doctor_name = mysqli_real_escape_string($connection, 'Dr. Cascade Test');
        $cascade_doctor_email = mysqli_real_escape_string($connection, 'cascade@test.com');
        $cascade_doctor_password = mysqli_real_escape_string($connection, 'cascadepass');
        
        $cascade_insert = "INSERT INTO users (name, email, password, role, status) 
                         VALUES ('$cascade_doctor_name', '$cascade_doctor_email', '$cascade_doctor_password', 'doctor', 'pending')";
        
        mysqli_query($connection, $cascade_insert);
        $cascade_doctor_id = mysqli_insert_id($connection);
        
        // Create a patient for assignment
        $cascade_patient_name = mysqli_real_escape_string($connection, 'Cascade Patient');
        $cascade_patient_email = mysqli_real_escape_string($connection, 'cascadepatient@test.com');
        $cascade_patient_password = mysqli_real_escape_string($connection, 'patientpass');
        
        $cascade_patient_insert = "INSERT INTO users (name, email, password, role, status) 
                                 VALUES ('$cascade_patient_name', '$cascade_patient_email', '$cascade_patient_password', 'patient', 'approved')";
        
        mysqli_query($connection, $cascade_patient_insert);
        $cascade_patient_id = mysqli_insert_id($connection);
        
        // First approve the doctor to create relationships
        $temp_approve_query = "UPDATE users SET status = 'approved' WHERE id = $cascade_doctor_id";
        mysqli_query($connection, $temp_approve_query);
        
        // Create doctor-patient assignment
        $assignment_insert = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                            VALUES ($cascade_doctor_id, $cascade_patient_id)";
        
        mysqli_query($connection, $assignment_insert);
        
        // Create alert from doctor to patient
        $alert_message = mysqli_real_escape_string($connection, 'Test alert for cascade deletion');
        $alert_insert = "INSERT INTO alerts (doctor_id, patient_id, message) 
                        VALUES ($cascade_doctor_id, $cascade_patient_id, '$alert_message')";
        
        mysqli_query($connection, $alert_insert);
        
        // Now set doctor back to pending for rejection test
        $temp_pending_query = "UPDATE users SET status = 'pending' WHERE id = $cascade_doctor_id";
        mysqli_query($connection, $temp_pending_query);
        
        // Reject the doctor (should cascade delete related data)
        $cascade_reject_query = "DELETE FROM users WHERE id = $cascade_doctor_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $cascade_reject_query);
        $cascade_affected = mysqli_affected_rows($connection);
        
        if ($cascade_affected != 1) {
            throw new Exception("Cascade rejection should affect exactly 1 row. Affected: $cascade_affected");
        }
        
        // Verify doctor is deleted
        $cascade_doctor_check = "SELECT COUNT(*) as count FROM users WHERE id = $cascade_doctor_id";
        $cascade_doctor_result = mysqli_query($connection, $cascade_doctor_check);
        $cascade_doctor_exists = mysqli_fetch_assoc($cascade_doctor_result)['count'];
        
        if ($cascade_doctor_exists != 0) {
            throw new Exception("Doctor should be deleted in cascade test");
        }
        
        // Verify related assignments are deleted (CASCADE)
        $assignment_check = "SELECT COUNT(*) as count FROM doctor_patients WHERE doctor_id = $cascade_doctor_id";
        $assignment_result = mysqli_query($connection, $assignment_check);
        $assignment_exists = mysqli_fetch_assoc($assignment_result)['count'];
        
        if ($assignment_exists != 0) {
            throw new Exception("Doctor-patient assignments should be cascade deleted");
        }
        
        // Verify related alerts are deleted (CASCADE)
        $alert_check = "SELECT COUNT(*) as count FROM alerts WHERE doctor_id = $cascade_doctor_id";
        $alert_result = mysqli_query($connection, $alert_check);
        $alert_exists = mysqli_fetch_assoc($alert_result)['count'];
        
        if ($alert_exists != 0) {
            throw new Exception("Doctor alerts should be cascade deleted");
        }
        
        // Verify patient is not affected
        $cascade_patient_check = "SELECT COUNT(*) as count FROM users WHERE id = $cascade_patient_id";
        $cascade_patient_result = mysqli_query($connection, $cascade_patient_check);
        $cascade_patient_exists = mysqli_fetch_assoc($cascade_patient_result)['count'];
        
        if ($cascade_patient_exists != 1) {
            throw new Exception("Patient should not be affected by doctor rejection");
        }
        
        // Test: Bulk rejection scenario
        $bulk_doctors = [];
        for ($i = 0; $i < 10; $i++) {
            $bulk_name = mysqli_real_escape_string($connection, "Dr. Bulk Reject $i");
            $bulk_email = mysqli_real_escape_string($connection, "bulkreject$i@test.com");
            $bulk_password = mysqli_real_escape_string($connection, "password$i");
            
            $bulk_insert = "INSERT INTO users (name, email, password, role, status) 
                          VALUES ('$bulk_name', '$bulk_email', '$bulk_password', 'doctor', 'pending')";
            
            mysqli_query($connection, $bulk_insert);
            $bulk_doctors[] = mysqli_insert_id($connection);
        }
        
        // Reject all bulk doctors
        foreach ($bulk_doctors as $bulk_id) {
            $bulk_reject_query = "DELETE FROM users WHERE id = $bulk_id AND role = 'doctor' AND status = 'pending'";
            
            mysqli_query($connection, $bulk_reject_query);
            $bulk_affected = mysqli_affected_rows($connection);
            
            if ($bulk_affected != 1) {
                throw new Exception("Bulk rejection should affect exactly 1 row per doctor. Affected: $bulk_affected");
            }
        }
        
        // Verify all bulk doctors are deleted
        $bulk_ids_str = implode(',', $bulk_doctors);
        $bulk_verify_query = "SELECT COUNT(*) as remaining_count 
                            FROM users 
                            WHERE id IN ($bulk_ids_str)";
        
        $bulk_verify_result = mysqli_query($connection, $bulk_verify_query);
        $remaining_count = mysqli_fetch_assoc($bulk_verify_result)['remaining_count'];
        
        if ($remaining_count != 0) {
            throw new Exception("All bulk doctors should be deleted. Remaining: $remaining_count");
        }
        
        // Test: Concurrent rejection scenario (race condition simulation)
        $concurrent_name = mysqli_real_escape_string($connection, 'Dr. Concurrent Reject');
        $concurrent_email = mysqli_real_escape_string($connection, 'concurrentreject@test.com');
        $concurrent_password = mysqli_real_escape_string($connection, 'concurrentpass');
        
        $concurrent_insert = "INSERT INTO users (name, email, password, role, status) 
                            VALUES ('$concurrent_name', '$concurrent_email', '$concurrent_password', 'doctor', 'pending')";
        
        mysqli_query($connection, $concurrent_insert);
        $concurrent_id = mysqli_insert_id($connection);
        
        // Simulate two concurrent rejection attempts
        $concurrent_query1 = "DELETE FROM users WHERE id = $concurrent_id AND role = 'doctor' AND status = 'pending'";
        $concurrent_query2 = "DELETE FROM users WHERE id = $concurrent_id AND role = 'doctor' AND status = 'pending'";
        
        mysqli_query($connection, $concurrent_query1);
        $concurrent_affected1 = mysqli_affected_rows($connection);
        
        mysqli_query($connection, $concurrent_query2);
        $concurrent_affected2 = mysqli_affected_rows($connection);
        
        // Only one should succeed
        $total_concurrent_affected = $concurrent_affected1 + $concurrent_affected2;
        
        if ($total_concurrent_affected != 1) {
            throw new Exception("Concurrent rejections should result in exactly 1 total affected row. Got: $total_concurrent_affected");
        }
        
        // Verify doctor is deleted
        $concurrent_check = "SELECT COUNT(*) as count FROM users WHERE id = $concurrent_id";
        $concurrent_result = mysqli_query($connection, $concurrent_check);
        $concurrent_exists = mysqli_fetch_assoc($concurrent_result)['count'];
        
        if ($concurrent_exists != 0) {
            throw new Exception("Concurrent rejection should result in doctor deletion");
        }
        
        // Test: Rejection statistics accuracy
        $stats_before_query = "SELECT COUNT(*) as pending_before FROM users WHERE role = 'doctor' AND status = 'pending'";
        $stats_before_result = mysqli_query($connection, $stats_before_query);
        $pending_before = mysqli_fetch_assoc($stats_before_result)['pending_before'];
        
        // Create and reject a doctor for stats test
        $stats_name = mysqli_real_escape_string($connection, 'Dr. Stats Test');
        $stats_email = mysqli_real_escape_string($connection, 'stats@test.com');
        $stats_password = mysqli_real_escape_string($connection, 'statspass');
        
        $stats_insert = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$stats_name', '$stats_email', '$stats_password', 'doctor', 'pending')";
        
        mysqli_query($connection, $stats_insert);
        $stats_id = mysqli_insert_id($connection);
        
        $stats_after_insert_query = "SELECT COUNT(*) as pending_after_insert FROM users WHERE role = 'doctor' AND status = 'pending'";
        $stats_after_insert_result = mysqli_query($connection, $stats_after_insert_query);
        $pending_after_insert = mysqli_fetch_assoc($stats_after_insert_result)['pending_after_insert'];
        
        if ($pending_after_insert != ($pending_before + 1)) {
            throw new Exception("Pending count should increase by 1 after insertion. Before: $pending_before, After: $pending_after_insert");
        }
        
        // Reject the doctor
        $stats_reject_query = "DELETE FROM users WHERE id = $stats_id AND role = 'doctor' AND status = 'pending'";
        mysqli_query($connection, $stats_reject_query);
        
        $stats_after_reject_query = "SELECT COUNT(*) as pending_after_reject FROM users WHERE role = 'doctor' AND status = 'pending'";
        $stats_after_reject_result = mysqli_query($connection, $stats_after_reject_query);
        $pending_after_reject = mysqli_fetch_assoc($stats_after_reject_result)['pending_after_reject'];
        
        if ($pending_after_reject != $pending_before) {
            throw new Exception("Pending count should return to original after rejection. Original: $pending_before, After: $pending_after_reject");
        }
        
        // Test: Rejection query performance with large dataset
        $performance_doctors = [];
        for ($i = 0; $i < 100; $i++) {
            $perf_name = mysqli_real_escape_string($connection, "Dr. Performance Reject $i");
            $perf_email = mysqli_real_escape_string($connection, "perfreject$i@test.com");
            $perf_password = mysqli_real_escape_string($connection, "perfpass$i");
            
            $perf_insert = "INSERT INTO users (name, email, password, role, status) 
                          VALUES ('$perf_name', '$perf_email', '$perf_password', 'doctor', 'pending')";
            
            mysqli_query($connection, $perf_insert);
            $performance_doctors[] = mysqli_insert_id($connection);
        }
        
        // Time the rejection operations
        $start_time = microtime(true);
        
        foreach ($performance_doctors as $perf_id) {
            $perf_reject_query = "DELETE FROM users WHERE id = $perf_id AND role = 'doctor' AND status = 'pending'";
            mysqli_query($connection, $perf_reject_query);
        }
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        // Verify reasonable performance (should complete within 5 seconds)
        if ($execution_time > 5.0) {
            throw new Exception("Rejection operations should complete within reasonable time. Took: {$execution_time}s");
        }
        
        // Verify all performance test doctors are deleted
        $perf_ids_str = implode(',', $performance_doctors);
        $perf_verify_query = "SELECT COUNT(*) as perf_remaining 
                            FROM users 
                            WHERE id IN ($perf_ids_str)";
        
        $perf_verify_result = mysqli_query($connection, $perf_verify_query);
        $perf_remaining = mysqli_fetch_assoc($perf_verify_result)['perf_remaining'];
        
        if ($perf_remaining != 0) {
            throw new Exception("All performance test doctors should be deleted. Remaining: $perf_remaining");
        }
        
        // Test: Email uniqueness after rejection and re-registration
        $unique_name = mysqli_real_escape_string($connection, 'Dr. Unique Test');
        $unique_email = mysqli_real_escape_string($connection, 'unique@test.com');
        $unique_password = mysqli_real_escape_string($connection, 'uniquepass');
        
        // Create and reject doctor
        $unique_insert = "INSERT INTO users (name, email, password, role, status) 
                        VALUES ('$unique_name', '$unique_email', '$unique_password', 'doctor', 'pending')";
        
        mysqli_query($connection, $unique_insert);
        $unique_id = mysqli_insert_id($connection);
        
        $unique_reject_query = "DELETE FROM users WHERE id = $unique_id AND role = 'doctor' AND status = 'pending'";
        mysqli_query($connection, $unique_reject_query);
        
        // Try to register again with same email
        $unique_reregister = "INSERT INTO users (name, email, password, role, status) 
                            VALUES ('$unique_name', '$unique_email', '$unique_password', 'doctor', 'pending')";
        
        $reregister_result = mysqli_query($connection, $unique_reregister);
        
        if (!$reregister_result) {
            throw new Exception("Should be able to re-register with same email after rejection");
        }
        
        $reregister_id = mysqli_insert_id($connection);
        
        if ($reregister_id <= 0) {
            throw new Exception("Re-registration should create new user ID");
        }
        
        echo "PASS: Admin Doctor Rejection property test passed\n";
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
    $result = runAdminDoctorRejectionTest();
    exit($result ? 0 : 1);
}
?>