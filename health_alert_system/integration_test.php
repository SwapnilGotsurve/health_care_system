<?php
/**
 * Health Alert System - Integration Test
 * 
 * This script validates that all modules are properly wired together
 * and tests complete user workflows end-to-end.
 */

require_once 'config/db.php';

// Test results storage
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

function run_test($test_name, $test_function) {
    global $test_results, $total_tests, $passed_tests;
    
    $total_tests++;
    echo "Running: $test_name... ";
    
    try {
        $result = $test_function();
        if ($result) {
            echo "✅ PASSED\n";
            $passed_tests++;
            $test_results[$test_name] = 'PASSED';
        } else {
            echo "❌ FAILED\n";
            $test_results[$test_name] = 'FAILED';
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $test_results[$test_name] = 'ERROR: ' . $e->getMessage();
    }
}

// Test 1: Database Connection
run_test("Database Connection", function() {
    global $connection;
    return mysqli_ping($connection);
});

// Test 2: All Required Tables Exist
run_test("Database Schema", function() {
    global $connection;
    
    $required_tables = ['users', 'health_data', 'alerts', 'doctor_patients'];
    
    foreach ($required_tables as $table) {
        $result = mysqli_query($connection, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) == 0) {
            return false;
        }
    }
    return true;
});

// Test 3: Sample Data Exists
run_test("Sample Data Integrity", function() {
    global $connection;
    
    // Check for admin user
    $admin_check = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $admin_count = mysqli_fetch_assoc($admin_check)['count'];
    
    // Check for sample patients
    $patient_check = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'patient'");
    $patient_count = mysqli_fetch_assoc($patient_check)['count'];
    
    // Check for sample doctors
    $doctor_check = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'doctor'");
    $doctor_count = mysqli_fetch_assoc($doctor_check)['count'];
    
    return ($admin_count >= 1 && $patient_count >= 1 && $doctor_count >= 1);
});

// Test 4: User Authentication Flow
run_test("Authentication System", function() {
    global $connection;
    
    // Test valid login credentials
    $email = 'admin@healthalert.com';
    $password = 'admin123';
    
    $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email'";
    $result = mysqli_query($connection, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        return ($user['password'] === $password && $user['role'] === 'admin');
    }
    
    return false;
});

// Test 5: Doctor-Patient Assignments
run_test("Doctor-Patient Assignments", function() {
    global $connection;
    
    $assignment_check = mysqli_query($connection, "SELECT COUNT(*) as count FROM doctor_patients");
    $assignment_count = mysqli_fetch_assoc($assignment_check)['count'];
    
    // Check if assignments have valid foreign keys
    $integrity_check = mysqli_query($connection, "
        SELECT COUNT(*) as count 
        FROM doctor_patients dp
        JOIN users d ON dp.doctor_id = d.id AND d.role = 'doctor'
        JOIN users p ON dp.patient_id = p.id AND p.role = 'patient'
    ");
    $integrity_count = mysqli_fetch_assoc($integrity_check)['count'];
    
    return ($assignment_count > 0 && $assignment_count === $integrity_count);
});

// Test 6: Health Data Integration
run_test("Health Data System", function() {
    global $connection;
    
    // Check if health data exists and is properly linked to patients
    $health_check = mysqli_query($connection, "
        SELECT COUNT(*) as count 
        FROM health_data hd
        JOIN users u ON hd.patient_id = u.id AND u.role = 'patient'
    ");
    $health_count = mysqli_fetch_assoc($health_check)['count'];
    
    return ($health_count > 0);
});

// Test 7: Alert System Integration
run_test("Alert System", function() {
    global $connection;
    
    // Check if alerts exist and are properly linked
    $alert_check = mysqli_query($connection, "
        SELECT COUNT(*) as count 
        FROM alerts a
        JOIN users d ON a.doctor_id = d.id AND d.role = 'doctor'
        JOIN users p ON a.patient_id = p.id AND p.role = 'patient'
    ");
    $alert_count = mysqli_fetch_assoc($alert_check)['count'];
    
    return ($alert_count > 0);
});

// Test 8: File Structure Integrity
run_test("File Structure", function() {
    $required_files = [
        'index.php',
        'register.php',
        'logout.php',
        'config/db.php',
        'includes/header.php',
        'includes/footer.php',
        'includes/auth_check.php',
        'includes/components.php',
        'admin/dashboard.php',
        'admin/doctor_approvals.php',
        'admin/assign_patients.php',
        'doctor/dashboard.php',
        'doctor/patient_list.php',
        'doctor/send_alert.php',
        'patient/dashboard.php',
        'patient/add_health_data.php',
        'patient/alerts.php',
        'assets/css/animations.css'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("Missing file: $file");
        }
    }
    
    return true;
});

// Test 9: Role-Based Access Control
run_test("Role-Based Access Control", function() {
    global $connection;
    
    // Check if all roles exist and have proper status
    $role_check = mysqli_query($connection, "
        SELECT role, status, COUNT(*) as count 
        FROM users 
        GROUP BY role, status
    ");
    
    $roles_found = [];
    while ($row = mysqli_fetch_assoc($role_check)) {
        $roles_found[] = $row['role'];
    }
    
    $required_roles = ['admin', 'doctor', 'patient'];
    foreach ($required_roles as $role) {
        if (!in_array($role, $roles_found)) {
            return false;
        }
    }
    
    return true;
});

// Test 10: Navigation Integration
run_test("Navigation System", function() {
    // Check if header includes proper navigation for all roles
    $header_content = file_get_contents('includes/header.php');
    
    $required_nav_elements = [
        'dashboard.php',
        'patient_list.php',
        'doctor_approvals.php',
        'add_health_data.php',
        'alerts.php'
    ];
    
    foreach ($required_nav_elements as $element) {
        if (strpos($header_content, $element) === false) {
            throw new Exception("Missing navigation element: $element");
        }
    }
    
    return true;
});

// Test 11: Database Referential Integrity
run_test("Database Referential Integrity", function() {
    global $connection;
    
    // Test foreign key constraints
    $integrity_tests = [
        "SELECT COUNT(*) as count FROM health_data hd LEFT JOIN users u ON hd.patient_id = u.id WHERE u.id IS NULL",
        "SELECT COUNT(*) as count FROM alerts a LEFT JOIN users d ON a.doctor_id = d.id WHERE d.id IS NULL",
        "SELECT COUNT(*) as count FROM alerts a LEFT JOIN users p ON a.patient_id = p.id WHERE p.id IS NULL",
        "SELECT COUNT(*) as count FROM doctor_patients dp LEFT JOIN users d ON dp.doctor_id = d.id WHERE d.id IS NULL",
        "SELECT COUNT(*) as count FROM doctor_patients dp LEFT JOIN users p ON dp.patient_id = p.id WHERE p.id IS NULL"
    ];
    
    foreach ($integrity_tests as $test_query) {
        $result = mysqli_query($connection, $test_query);
        $orphaned_records = mysqli_fetch_assoc($result)['count'];
        
        if ($orphaned_records > 0) {
            return false;
        }
    }
    
    return true;
});

// Test 12: Component System
run_test("UI Component System", function() {
    $components_content = file_get_contents('includes/components.php');
    
    $required_functions = [
        'render_alert',
        'render_button',
        'render_form_input',
        'render_status_badge',
        'render_data_card'
    ];
    
    foreach ($required_functions as $function) {
        if (strpos($components_content, "function $function") === false) {
            throw new Exception("Missing component function: $function");
        }
    }
    
    return true;
});

// Run all tests
echo "=== Health Alert System Integration Test ===\n\n";

echo "Testing system integration and workflows...\n\n";

// Execute all tests (they're already defined above with run_test calls)

// Display summary
echo "\n=== Test Summary ===\n";
echo "Total Tests: $total_tests\n";
echo "Passed: $passed_tests\n";
echo "Failed: " . ($total_tests - $passed_tests) . "\n";
echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n\n";

if ($passed_tests === $total_tests) {
    echo "🎉 ALL TESTS PASSED! System is fully integrated and ready for use.\n";
} else {
    echo "⚠️  Some tests failed. Please review the results above.\n";
    echo "\nFailed Tests:\n";
    foreach ($test_results as $test => $result) {
        if ($result !== 'PASSED') {
            echo "- $test: $result\n";
        }
    }
}

echo "\n=== Integration Validation Complete ===\n";

// Additional system information
echo "\nSystem Information:\n";
echo "- PHP Version: " . PHP_VERSION . "\n";
echo "- MySQL Version: " . mysqli_get_server_info($connection) . "\n";
echo "- Database: health_alert_system\n";
echo "- Total Users: ";

$user_count_query = mysqli_query($connection, "SELECT COUNT(*) as count FROM users");
echo mysqli_fetch_assoc($user_count_query)['count'] . "\n";

echo "- Total Health Records: ";
$health_count_query = mysqli_query($connection, "SELECT COUNT(*) as count FROM health_data");
echo mysqli_fetch_assoc($health_count_query)['count'] . "\n";

echo "- Total Alerts: ";
$alert_count_query = mysqli_query($connection, "SELECT COUNT(*) as count FROM alerts");
echo mysqli_fetch_assoc($alert_count_query)['count'] . "\n";

echo "- Total Assignments: ";
$assignment_count_query = mysqli_query($connection, "SELECT COUNT(*) as count FROM doctor_patients");
echo mysqli_fetch_assoc($assignment_count_query)['count'] . "\n";

mysqli_close($connection);
?>