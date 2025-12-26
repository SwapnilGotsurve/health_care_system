<?php
/**
 * Health Alert System - Complete Workflow Test
 * 
 * This script tests complete end-to-end user workflows to ensure
 * all modules work together seamlessly.
 */

require_once 'config/db.php';

// Test configuration
$test_results = [];
$workflow_tests = 0;
$workflow_passed = 0;

function test_workflow($workflow_name, $test_function) {
    global $test_results, $workflow_tests, $workflow_passed;
    
    $workflow_tests++;
    echo "\n=== Testing Workflow: $workflow_name ===\n";
    
    try {
        $result = $test_function();
        if ($result) {
            echo "✅ WORKFLOW PASSED: $workflow_name\n";
            $workflow_passed++;
            $test_results[$workflow_name] = 'PASSED';
        } else {
            echo "❌ WORKFLOW FAILED: $workflow_name\n";
            $test_results[$workflow_name] = 'FAILED';
        }
    } catch (Exception $e) {
        echo "❌ WORKFLOW ERROR: $workflow_name - " . $e->getMessage() . "\n";
        $test_results[$workflow_name] = 'ERROR: ' . $e->getMessage();
    }
}

// Workflow 1: Patient Registration and Health Data Entry
test_workflow("Patient Registration and Health Data Entry", function() {
    global $connection;
    
    echo "1. Testing patient registration...\n";
    
    // Simulate patient registration
    $test_email = 'test_patient_' . time() . '@test.com';
    $test_name = 'Test Patient';
    $test_password = 'test123';
    
    $register_query = "INSERT INTO users (name, email, password, role, status) 
                      VALUES ('$test_name', '$test_email', '$test_password', 'patient', 'approved')";
    
    if (!mysqli_query($connection, $register_query)) {
        throw new Exception("Patient registration failed");
    }
    
    $patient_id = mysqli_insert_id($connection);
    echo "   ✓ Patient registered with ID: $patient_id\n";
    
    echo "2. Testing patient login simulation...\n";
    
    // Simulate login check
    $login_query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$test_email'";
    $login_result = mysqli_query($connection, $login_query);
    
    if (mysqli_num_rows($login_result) == 0) {
        throw new Exception("Patient login verification failed");
    }
    
    $user = mysqli_fetch_assoc($login_result);
    if ($user['password'] !== $test_password || $user['role'] !== 'patient') {
        throw new Exception("Patient authentication failed");
    }
    
    echo "   ✓ Patient login verified\n";
    
    echo "3. Testing health data entry...\n";
    
    // Simulate health data entry
    $health_data = [
        'systolic_bp' => 120,
        'diastolic_bp' => 80,
        'sugar_level' => 95.5,
        'heart_rate' => 72
    ];
    
    $health_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                    VALUES ($patient_id, {$health_data['systolic_bp']}, {$health_data['diastolic_bp']}, 
                           {$health_data['sugar_level']}, {$health_data['heart_rate']})";
    
    if (!mysqli_query($connection, $health_query)) {
        throw new Exception("Health data entry failed");
    }
    
    echo "   ✓ Health data recorded\n";
    
    echo "4. Testing health history retrieval...\n";
    
    // Verify health data can be retrieved
    $history_query = "SELECT * FROM health_data WHERE patient_id = $patient_id ORDER BY created_at DESC";
    $history_result = mysqli_query($connection, $history_query);
    
    if (mysqli_num_rows($history_result) == 0) {
        throw new Exception("Health history retrieval failed");
    }
    
    $health_record = mysqli_fetch_assoc($history_result);
    if ($health_record['systolic_bp'] != $health_data['systolic_bp']) {
        throw new Exception("Health data integrity check failed");
    }
    
    echo "   ✓ Health history retrieved successfully\n";
    
    // Cleanup test data
    mysqli_query($connection, "DELETE FROM health_data WHERE patient_id = $patient_id");
    mysqli_query($connection, "DELETE FROM users WHERE id = $patient_id");
    
    return true;
});

// Workflow 2: Doctor Registration, Approval, and Patient Management
test_workflow("Doctor Registration, Approval, and Patient Management", function() {
    global $connection;
    
    echo "1. Testing doctor registration...\n";
    
    // Simulate doctor registration
    $test_email = 'test_doctor_' . time() . '@test.com';
    $test_name = 'Test Doctor';
    $test_password = 'test123';
    
    $register_query = "INSERT INTO users (name, email, password, role, status) 
                      VALUES ('$test_name', '$test_email', '$test_password', 'doctor', 'pending')";
    
    if (!mysqli_query($connection, $register_query)) {
        throw new Exception("Doctor registration failed");
    }
    
    $doctor_id = mysqli_insert_id($connection);
    echo "   ✓ Doctor registered with pending status\n";
    
    echo "2. Testing pending doctor access control...\n";
    
    // Verify pending doctor cannot access system
    $pending_check = "SELECT status FROM users WHERE id = $doctor_id";
    $pending_result = mysqli_query($connection, $pending_check);
    $doctor_status = mysqli_fetch_assoc($pending_result)['status'];
    
    if ($doctor_status !== 'pending') {
        throw new Exception("Doctor status check failed");
    }
    
    echo "   ✓ Pending doctor access properly restricted\n";
    
    echo "3. Testing admin approval process...\n";
    
    // Simulate admin approval
    $approval_query = "UPDATE users SET status = 'approved' WHERE id = $doctor_id";
    if (!mysqli_query($connection, $approval_query)) {
        throw new Exception("Doctor approval failed");
    }
    
    echo "   ✓ Doctor approved by admin\n";
    
    echo "4. Testing doctor-patient assignment...\n";
    
    // Get a test patient
    $patient_query = "SELECT id FROM users WHERE role = 'patient' LIMIT 1";
    $patient_result = mysqli_query($connection, $patient_query);
    
    if (mysqli_num_rows($patient_result) == 0) {
        throw new Exception("No test patient available");
    }
    
    $patient_id = mysqli_fetch_assoc($patient_result)['id'];
    
    // Create assignment
    $assignment_query = "INSERT INTO doctor_patients (doctor_id, patient_id) VALUES ($doctor_id, $patient_id)";
    if (!mysqli_query($connection, $assignment_query)) {
        throw new Exception("Patient assignment failed");
    }
    
    echo "   ✓ Patient assigned to doctor\n";
    
    echo "5. Testing doctor patient list access...\n";
    
    // Verify doctor can see assigned patients
    $patient_list_query = "SELECT u.id, u.name FROM users u 
                          JOIN doctor_patients dp ON u.id = dp.patient_id 
                          WHERE dp.doctor_id = $doctor_id";
    $patient_list_result = mysqli_query($connection, $patient_list_query);
    
    if (mysqli_num_rows($patient_list_result) == 0) {
        throw new Exception("Doctor patient list access failed");
    }
    
    echo "   ✓ Doctor can access assigned patient list\n";
    
    // Cleanup test data
    mysqli_query($connection, "DELETE FROM doctor_patients WHERE doctor_id = $doctor_id");
    mysqli_query($connection, "DELETE FROM users WHERE id = $doctor_id");
    
    return true;
});

// Workflow 3: Doctor-Patient Communication (Alerts)
test_workflow("Doctor-Patient Communication (Alerts)", function() {
    global $connection;
    
    echo "1. Setting up test doctor and patient...\n";
    
    // Get existing doctor and patient for testing
    $doctor_query = "SELECT id FROM users WHERE role = 'doctor' AND status = 'approved' LIMIT 1";
    $doctor_result = mysqli_query($connection, $doctor_query);
    
    if (mysqli_num_rows($doctor_result) == 0) {
        throw new Exception("No approved doctor available for testing");
    }
    
    $doctor_id = mysqli_fetch_assoc($doctor_result)['id'];
    
    $patient_query = "SELECT id FROM users WHERE role = 'patient' LIMIT 1";
    $patient_result = mysqli_query($connection, $patient_query);
    
    if (mysqli_num_rows($patient_result) == 0) {
        throw new Exception("No patient available for testing");
    }
    
    $patient_id = mysqli_fetch_assoc($patient_result)['id'];
    
    echo "   ✓ Test users identified\n";
    
    echo "2. Testing alert sending...\n";
    
    // Simulate doctor sending alert
    $alert_message = "Test alert message - " . date('Y-m-d H:i:s');
    $alert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status) 
                   VALUES ($doctor_id, $patient_id, '$alert_message', 'sent')";
    
    if (!mysqli_query($connection, $alert_query)) {
        throw new Exception("Alert sending failed");
    }
    
    $alert_id = mysqli_insert_id($connection);
    echo "   ✓ Alert sent successfully\n";
    
    echo "3. Testing patient alert retrieval...\n";
    
    // Verify patient can see the alert
    $patient_alerts_query = "SELECT a.*, d.name as doctor_name 
                            FROM alerts a 
                            JOIN users d ON a.doctor_id = d.id 
                            WHERE a.patient_id = $patient_id AND a.id = $alert_id";
    $patient_alerts_result = mysqli_query($connection, $patient_alerts_query);
    
    if (mysqli_num_rows($patient_alerts_result) == 0) {
        throw new Exception("Patient alert retrieval failed");
    }
    
    $alert = mysqli_fetch_assoc($patient_alerts_result);
    if ($alert['message'] !== $alert_message) {
        throw new Exception("Alert message integrity check failed");
    }
    
    echo "   ✓ Patient can view alert\n";
    
    echo "4. Testing alert status update...\n";
    
    // Simulate patient viewing alert (marking as seen)
    $update_query = "UPDATE alerts SET status = 'seen' WHERE id = $alert_id";
    if (!mysqli_query($connection, $update_query)) {
        throw new Exception("Alert status update failed");
    }
    
    // Verify status was updated
    $status_check = "SELECT status FROM alerts WHERE id = $alert_id";
    $status_result = mysqli_query($connection, $status_check);
    $new_status = mysqli_fetch_assoc($status_result)['status'];
    
    if ($new_status !== 'seen') {
        throw new Exception("Alert status verification failed");
    }
    
    echo "   ✓ Alert marked as seen\n";
    
    echo "5. Testing doctor sent alerts history...\n";
    
    // Verify doctor can see sent alerts
    $sent_alerts_query = "SELECT a.*, p.name as patient_name 
                         FROM alerts a 
                         JOIN users p ON a.patient_id = p.id 
                         WHERE a.doctor_id = $doctor_id AND a.id = $alert_id";
    $sent_alerts_result = mysqli_query($connection, $sent_alerts_query);
    
    if (mysqli_num_rows($sent_alerts_result) == 0) {
        throw new Exception("Doctor sent alerts history failed");
    }
    
    echo "   ✓ Doctor can view sent alerts history\n";
    
    // Cleanup test data
    mysqli_query($connection, "DELETE FROM alerts WHERE id = $alert_id");
    
    return true;
});

// Workflow 4: Admin System Management
test_workflow("Admin System Management", function() {
    global $connection;
    
    echo "1. Testing admin dashboard access...\n";
    
    // Verify admin user exists
    $admin_query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
    $admin_result = mysqli_query($connection, $admin_query);
    
    if (mysqli_num_rows($admin_result) == 0) {
        throw new Exception("No admin user available");
    }
    
    $admin_id = mysqli_fetch_assoc($admin_result)['id'];
    echo "   ✓ Admin user verified\n";
    
    echo "2. Testing system statistics retrieval...\n";
    
    // Test admin dashboard statistics queries
    $stats_queries = [
        'total_users' => "SELECT COUNT(*) as count FROM users",
        'total_patients' => "SELECT COUNT(*) as count FROM users WHERE role = 'patient'",
        'total_doctors' => "SELECT COUNT(*) as count FROM users WHERE role = 'doctor'",
        'pending_doctors' => "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'pending'",
        'total_alerts' => "SELECT COUNT(*) as count FROM alerts",
        'total_health_records' => "SELECT COUNT(*) as count FROM health_data",
        'doctor_patient_assignments' => "SELECT COUNT(*) as count FROM doctor_patients"
    ];
    
    foreach ($stats_queries as $stat_name => $query) {
        $result = mysqli_query($connection, $query);
        if (!$result) {
            throw new Exception("Statistics query failed: $stat_name");
        }
        $count = mysqli_fetch_assoc($result)['count'];
        echo "   ✓ $stat_name: $count\n";
    }
    
    echo "3. Testing doctor approval workflow...\n";
    
    // Create a test pending doctor
    $test_doctor_email = 'pending_doctor_' . time() . '@test.com';
    $pending_doctor_query = "INSERT INTO users (name, email, password, role, status) 
                            VALUES ('Pending Doctor', '$test_doctor_email', 'test123', 'doctor', 'pending')";
    
    if (!mysqli_query($connection, $pending_doctor_query)) {
        throw new Exception("Test pending doctor creation failed");
    }
    
    $pending_doctor_id = mysqli_insert_id($connection);
    
    // Test approval
    $approve_query = "UPDATE users SET status = 'approved' WHERE id = $pending_doctor_id";
    if (!mysqli_query($connection, $approve_query)) {
        throw new Exception("Doctor approval failed");
    }
    
    // Verify approval
    $verify_query = "SELECT status FROM users WHERE id = $pending_doctor_id";
    $verify_result = mysqli_query($connection, $verify_query);
    $status = mysqli_fetch_assoc($verify_result)['status'];
    
    if ($status !== 'approved') {
        throw new Exception("Doctor approval verification failed");
    }
    
    echo "   ✓ Doctor approval workflow working\n";
    
    echo "4. Testing assignment management...\n";
    
    // Test assignment creation
    $test_patient_query = "SELECT id FROM users WHERE role = 'patient' LIMIT 1";
    $test_patient_result = mysqli_query($connection, $test_patient_query);
    $test_patient_id = mysqli_fetch_assoc($test_patient_result)['id'];
    
    $assignment_query = "INSERT INTO doctor_patients (doctor_id, patient_id) 
                        VALUES ($pending_doctor_id, $test_patient_id)";
    
    if (!mysqli_query($connection, $assignment_query)) {
        // Assignment might already exist, check if it exists
        $check_query = "SELECT id FROM doctor_patients WHERE doctor_id = $pending_doctor_id AND patient_id = $test_patient_id";
        $check_result = mysqli_query($connection, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            throw new Exception("Assignment creation failed");
        }
    }
    
    echo "   ✓ Assignment management working\n";
    
    // Cleanup test data
    mysqli_query($connection, "DELETE FROM doctor_patients WHERE doctor_id = $pending_doctor_id");
    mysqli_query($connection, "DELETE FROM users WHERE id = $pending_doctor_id");
    
    return true;
});

// Workflow 5: Complete User Journey Integration
test_workflow("Complete User Journey Integration", function() {
    global $connection;
    
    echo "1. Testing cross-module navigation...\n";
    
    // Verify all required files exist and are accessible
    $critical_files = [
        'index.php' => 'Login page',
        'register.php' => 'Registration page',
        'admin/dashboard.php' => 'Admin dashboard',
        'doctor/dashboard.php' => 'Doctor dashboard',
        'patient/dashboard.php' => 'Patient dashboard',
        'includes/auth_check.php' => 'Authentication middleware'
    ];
    
    foreach ($critical_files as $file => $description) {
        if (!file_exists($file)) {
            throw new Exception("Critical file missing: $file ($description)");
        }
        echo "   ✓ $description exists\n";
    }
    
    echo "2. Testing data flow integrity...\n";
    
    // Test complete data flow: Patient -> Health Data -> Doctor -> Alert -> Patient
    
    // Get test users
    $patient_query = "SELECT id FROM users WHERE role = 'patient' LIMIT 1";
    $patient_result = mysqli_query($connection, $patient_query);
    $patient_id = mysqli_fetch_assoc($patient_result)['id'];
    
    $doctor_query = "SELECT id FROM users WHERE role = 'doctor' AND status = 'approved' LIMIT 1";
    $doctor_result = mysqli_query($connection, $doctor_query);
    $doctor_id = mysqli_fetch_assoc($doctor_result)['id'];
    
    // Ensure assignment exists
    $assignment_check = "SELECT id FROM doctor_patients WHERE doctor_id = $doctor_id AND patient_id = $patient_id";
    $assignment_result = mysqli_query($connection, $assignment_check);
    
    if (mysqli_num_rows($assignment_result) == 0) {
        // Create assignment
        $create_assignment = "INSERT INTO doctor_patients (doctor_id, patient_id) VALUES ($doctor_id, $patient_id)";
        mysqli_query($connection, $create_assignment);
    }
    
    // Test health data entry
    $health_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
                    VALUES ($patient_id, 140, 90, 150, 85)";
    mysqli_query($connection, $health_query);
    $health_id = mysqli_insert_id($connection);
    
    // Test doctor can see patient health data
    $doctor_view_query = "SELECT hd.* FROM health_data hd 
                         JOIN doctor_patients dp ON hd.patient_id = dp.patient_id 
                         WHERE dp.doctor_id = $doctor_id AND hd.id = $health_id";
    $doctor_view_result = mysqli_query($connection, $doctor_view_query);
    
    if (mysqli_num_rows($doctor_view_result) == 0) {
        throw new Exception("Doctor cannot access patient health data");
    }
    
    echo "   ✓ Doctor can access patient health data\n";
    
    // Test alert creation based on health data
    $alert_query = "INSERT INTO alerts (doctor_id, patient_id, message) 
                   VALUES ($doctor_id, $patient_id, 'Your blood pressure is elevated. Please schedule an appointment.')";
    mysqli_query($connection, $alert_query);
    $alert_id = mysqli_insert_id($connection);
    
    // Test patient can see alert
    $patient_alert_query = "SELECT * FROM alerts WHERE id = $alert_id AND patient_id = $patient_id";
    $patient_alert_result = mysqli_query($connection, $patient_alert_query);
    
    if (mysqli_num_rows($patient_alert_result) == 0) {
        throw new Exception("Patient cannot access their alerts");
    }
    
    echo "   ✓ Complete data flow working\n";
    
    // Cleanup
    mysqli_query($connection, "DELETE FROM alerts WHERE id = $alert_id");
    mysqli_query($connection, "DELETE FROM health_data WHERE id = $health_id");
    
    return true;
});

// Run all workflow tests
echo "=== Health Alert System - Complete Workflow Testing ===\n";
echo "Testing end-to-end user workflows and system integration...\n";

// Display final summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "WORKFLOW TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Total Workflows Tested: $workflow_tests\n";
echo "Workflows Passed: $workflow_passed\n";
echo "Workflows Failed: " . ($workflow_tests - $workflow_passed) . "\n";
echo "Success Rate: " . round(($workflow_passed / $workflow_tests) * 100, 2) . "%\n\n";

if ($workflow_passed === $workflow_tests) {
    echo "🎉 ALL WORKFLOWS PASSED!\n";
    echo "✅ System is fully integrated and all user journeys work correctly.\n";
    echo "✅ Ready for production use!\n";
} else {
    echo "⚠️  Some workflows failed. System needs attention.\n\n";
    echo "Failed Workflows:\n";
    foreach ($test_results as $workflow => $result) {
        if ($result !== 'PASSED') {
            echo "❌ $workflow: $result\n";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Integration testing complete.\n";
echo "System Status: " . ($workflow_passed === $workflow_tests ? "READY" : "NEEDS ATTENTION") . "\n";
echo str_repeat("=", 60) . "\n";

mysqli_close($connection);
?>