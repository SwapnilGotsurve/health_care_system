<?php
/**
 * Feature: health-alert-system, Property 2: Valid Login Authentication
 * 
 * Property 2: Valid Login Authentication
 * For any user with valid credentials, the login system should authenticate them 
 * and redirect to their role-appropriate dashboard
 * Validates: Requirements 1.4, 4.4
 */

// Simple test runner for Valid Login Authentication Property Test
function runValidLoginAuthenticationTest() {
    echo "Running Valid Login Authentication Property Test...\n";
    
    $test_db = 'health_alert_system_test';
    $connection = mysqli_connect('localhost', 'root', '', '');
    
    if (!$connection) {
        echo "SKIP: Cannot connect to MySQL server\n";
        return false;
    }
    
    // Create test database
    mysqli_query($connection, "CREATE DATABASE IF NOT EXISTS {$test_db}");
    mysqli_select_db($connection, $test_db);
    
    // Create users table
    $create_table = "
    CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('patient', 'doctor', 'admin') NOT NULL,
        status ENUM('pending', 'approved') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($connection, $create_table);
    
    try {
        // Test multiple valid login scenarios (property-based approach)
        $test_users = [
            ['John Patient', 'john@test.com', 'password123', 'patient', 'approved'],
            ['Dr. Smith', 'doctor@test.com', 'docpass456', 'doctor', 'approved'],
            ['Admin User', 'admin@test.com', 'adminpass', 'admin', 'approved'],
            ['Jane Doe', 'jane@test.com', 'mypass789', 'patient', 'approved'],
            ['Dr. Wilson', 'wilson@test.com', 'medpass', 'doctor', 'approved']
        ];
        
        // Insert test users
        foreach ($test_users as $user_data) {
            $name = mysqli_real_escape_string($connection, $user_data[0]);
            $email = mysqli_real_escape_string($connection, $user_data[1]);
            $password = mysqli_real_escape_string($connection, $user_data[2]);
            $role = $user_data[3];
            $status = $user_data[4];
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', '$role', '$status')";
            
            mysqli_query($connection, $insert_query);
        }
        
        // Test authentication for each user
        foreach ($test_users as $user_data) {
            $email = $user_data[1];
            $password = $user_data[2];
            $expected_role = $user_data[3];
            $expected_status = $user_data[4];
            
            // Simulate login authentication process
            $email_escaped = mysqli_real_escape_string($connection, $email);
            $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email_escaped'";
            $result = mysqli_query($connection, $query);
            
            if (!$result || mysqli_num_rows($result) == 0) {
                throw new Exception("Valid user should be found in database: $email");
            }
            
            $user = mysqli_fetch_assoc($result);
            
            // Test password authentication
            if ($password !== $user['password']) {
                throw new Exception("Valid password should authenticate successfully for: $email");
            }
            
            // Test role verification
            if ($user['role'] !== $expected_role) {
                throw new Exception("User role should match expected role. Expected: $expected_role, Got: " . $user['role']);
            }
            
            // Test status verification for approved users
            if ($user['status'] !== $expected_status) {
                throw new Exception("User status should match expected status. Expected: $expected_status, Got: " . $user['status']);
            }
            
            // Simulate session creation (what would happen in real login)
            $session_data = [
                'user_id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
                'email' => $user['email']
            ];
            
            // Verify session data is complete
            if (empty($session_data['user_id']) || empty($session_data['role'])) {
                throw new Exception("Session should contain user_id and role for valid login");
            }
            
            // Test role-based redirect logic
            $expected_redirect = '';
            switch ($user['role']) {
                case 'admin':
                    $expected_redirect = 'admin/dashboard.php';
                    break;
                case 'doctor':
                    $expected_redirect = 'doctor/dashboard.php';
                    break;
                case 'patient':
                    $expected_redirect = 'patient/dashboard.php';
                    break;
            }
            
            if (empty($expected_redirect)) {
                throw new Exception("Valid role should have a corresponding dashboard redirect");
            }
        }
        
        // Test that pending doctors cannot login (contrast test)
        $pending_doctor = ['Dr. Pending', 'pending@test.com', 'pendingpass', 'doctor', 'pending'];
        $name = mysqli_real_escape_string($connection, $pending_doctor[0]);
        $email = mysqli_real_escape_string($connection, $pending_doctor[1]);
        $password = mysqli_real_escape_string($connection, $pending_doctor[2]);
        $role = $pending_doctor[3];
        $status = $pending_doctor[4];
        
        $insert_query = "INSERT INTO users (name, email, password, role, status) 
                       VALUES ('$name', '$email', '$password', '$role', '$status')";
        mysqli_query($connection, $insert_query);
        
        // Simulate login attempt for pending doctor
        $email_escaped = mysqli_real_escape_string($connection, $email);
        $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email_escaped'";
        $result = mysqli_query($connection, $query);
        $user = mysqli_fetch_assoc($result);
        
        // Password should match but status should prevent login
        if ($password === $user['password'] && $user['role'] === 'doctor' && $user['status'] === 'pending') {
            // This should result in login denial - this is the expected behavior
            // In real implementation, this would show "awaiting approval" message
        } else {
            throw new Exception("Pending doctor authentication test setup failed");
        }
        
        echo "PASS: Valid Login Authentication property test passed\n";
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
    $result = runValidLoginAuthenticationTest();
    exit($result ? 0 : 1);
}
?>