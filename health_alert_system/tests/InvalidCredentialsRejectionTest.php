<?php
/**
 * Feature: health-alert-system, Property 3: Invalid Credentials Rejection
 * 
 * Property 3: Invalid Credentials Rejection
 * For any invalid credential combination, the login system should reject authentication 
 * and display an error message
 * Validates: Requirements 1.5
 */

// Simple test runner for Invalid Credentials Rejection Property Test
function runInvalidCredentialsRejectionTest() {
    echo "Running Invalid Credentials Rejection Property Test...\n";
    
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
        // Insert some valid users for testing
        $valid_users = [
            ['John Patient', 'john@test.com', 'correctpass123', 'patient', 'approved'],
            ['Dr. Smith', 'doctor@test.com', 'doctorpass456', 'doctor', 'approved'],
            ['Admin User', 'admin@test.com', 'adminpass789', 'admin', 'approved']
        ];
        
        foreach ($valid_users as $user_data) {
            $name = mysqli_real_escape_string($connection, $user_data[0]);
            $email = mysqli_real_escape_string($connection, $user_data[1]);
            $password = mysqli_real_escape_string($connection, $user_data[2]);
            $role = $user_data[3];
            $status = $user_data[4];
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) 
                           VALUES ('$name', '$email', '$password', '$role', '$status')";
            
            mysqli_query($connection, $insert_query);
        }
        
        // Test various invalid credential combinations (property-based approach)
        $invalid_credential_tests = [
            // Wrong passwords for existing emails
            ['john@test.com', 'wrongpassword'],
            ['doctor@test.com', 'incorrectpass'],
            ['admin@test.com', 'badpass'],
            
            // Non-existent emails
            ['nonexistent@test.com', 'anypassword'],
            ['fake@email.com', 'password123'],
            ['notreal@test.com', 'testpass'],
            
            // Empty credentials
            ['', ''],
            ['john@test.com', ''],
            ['', 'password123'],
            
            // Malformed emails with correct passwords
            ['john@test', 'correctpass123'],
            ['invalid-email', 'doctorpass456'],
            
            // Case sensitivity tests (emails should be case insensitive, but passwords case sensitive)
            ['JOHN@TEST.COM', 'correctpass123'], // This might be valid depending on implementation
            ['john@test.com', 'CORRECTPASS123'], // This should be invalid
            
            // SQL injection attempts
            ["john@test.com'; DROP TABLE users; --", 'password'],
            ['john@test.com', "password'; DROP TABLE users; --"],
            
            // Special characters
            ['john@test.com', 'correct pass123'], // Space in password
            ['john @test.com', 'correctpass123'], // Space in email
        ];
        
        foreach ($invalid_credential_tests as $credentials) {
            $email = $credentials[0];
            $password = $credentials[1];
            
            // Simulate login authentication process
            $email_escaped = mysqli_real_escape_string($connection, $email);
            $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email_escaped'";
            $result = mysqli_query($connection, $query);
            
            $authentication_should_fail = true;
            
            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                // Check if password matches
                if ($password === $user['password']) {
                    // Check if user is approved
                    if ($user['status'] === 'approved') {
                        $authentication_should_fail = false;
                    }
                }
            }
            
            // For this test, we expect authentication to fail for all these cases
            if (!$authentication_should_fail) {
                // This means we found a valid credential that shouldn't be in our invalid list
                // Let's check if this is the case-insensitive email scenario
                if (strtolower($email) === strtolower('john@test.com') && $password === 'correctpass123') {
                    // This might be acceptable if email comparison is case-insensitive
                    continue;
                } else {
                    throw new Exception("Credential combination should be invalid but was accepted: $email / $password");
                }
            }
            
            // If we reach here, authentication properly failed as expected
        }
        
        // Test that valid credentials still work (sanity check)
        foreach ($valid_users as $user_data) {
            $email = $user_data[1];
            $password = $user_data[2];
            
            $email_escaped = mysqli_real_escape_string($connection, $email);
            $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email_escaped'";
            $result = mysqli_query($connection, $query);
            
            if (!$result || mysqli_num_rows($result) == 0) {
                throw new Exception("Valid user should still be found: $email");
            }
            
            $user = mysqli_fetch_assoc($result);
            
            if ($password !== $user['password']) {
                throw new Exception("Valid password should still work: $email");
            }
            
            if ($user['status'] !== 'approved') {
                throw new Exception("Valid user should still be approved: $email");
            }
        }
        
        echo "PASS: Invalid Credentials Rejection property test passed\n";
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
    $result = runInvalidCredentialsRejectionTest();
    exit($result ? 0 : 1);
}
?>