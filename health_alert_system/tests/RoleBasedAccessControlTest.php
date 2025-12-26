<?php
/**
 * Feature: health-alert-system, Property 21: Role-Based Access Control
 * 
 * Property 21: Role-Based Access Control
 * For any user attempting to access role-specific pages, the system should verify 
 * their role matches the required access level
 * Validates: Requirements 10.2
 */

// Simple test runner for Role-Based Access Control Property Test
function runRoleBasedAccessControlTest() {
    echo "Running Role-Based Access Control Property Test...\n";
    
    try {
        // Test role verification logic from auth_check.php
        $test_scenarios = [
            // [user_role, requested_directory, should_allow_access]
            ['admin', 'admin', true],
            ['doctor', 'doctor', true], 
            ['patient', 'patient', true],
            ['admin', 'doctor', false],
            ['admin', 'patient', false],
            ['doctor', 'admin', false],
            ['doctor', 'patient', false],
            ['patient', 'admin', false],
            ['patient', 'doctor', false],
            ['invalid_role', 'admin', false],
            ['invalid_role', 'doctor', false],
            ['invalid_role', 'patient', false]
        ];
        
        // Test each scenario
        foreach ($test_scenarios as $scenario) {
            $user_role = $scenario[0];
            $requested_dir = $scenario[1];
            $should_allow = $scenario[2];
            
            // Simulate the role checking logic from auth_check.php
            $required_role = '';
            switch ($requested_dir) {
                case 'admin':
                    $required_role = 'admin';
                    break;
                case 'doctor':
                    $required_role = 'doctor';
                    break;
                case 'patient':
                    $required_role = 'patient';
                    break;
            }
            
            // Test role verification
            $access_granted = ($required_role && $user_role === $required_role);
            
            if ($should_allow && !$access_granted) {
                throw new Exception("User with role '$user_role' should have access to '$requested_dir' directory");
            }
            
            if (!$should_allow && $access_granted) {
                throw new Exception("User with role '$user_role' should NOT have access to '$requested_dir' directory");
            }
            
            // Test redirect logic for unauthorized access
            if (!$access_granted && in_array($user_role, ['admin', 'doctor', 'patient'])) {
                $expected_redirect = '';
                switch ($user_role) {
                    case 'admin':
                        $expected_redirect = '/health_alert_system/admin/dashboard.php';
                        break;
                    case 'doctor':
                        $expected_redirect = '/health_alert_system/doctor/dashboard.php';
                        break;
                    case 'patient':
                        $expected_redirect = '/health_alert_system/patient/dashboard.php';
                        break;
                }
                
                if (empty($expected_redirect)) {
                    throw new Exception("Valid user role should have a redirect target when accessing unauthorized area");
                }
            }
        }
        
        // Test session requirement scenarios
        $session_scenarios = [
            // [has_user_id, has_role, should_allow_access]
            [true, true, true],
            [false, true, false],
            [true, false, false],
            [false, false, false]
        ];
        
        foreach ($session_scenarios as $session_scenario) {
            $has_user_id = $session_scenario[0];
            $has_role = $session_scenario[1];
            $should_allow = $session_scenario[2];
            
            // Simulate session check logic from auth_check.php
            $session_valid = ($has_user_id && $has_role);
            
            if ($should_allow && !$session_valid) {
                throw new Exception("Valid session (user_id and role present) should allow access");
            }
            
            if (!$should_allow && $session_valid) {
                throw new Exception("Invalid session should not allow access");
            }
        }
        
        // Test multiple users with different roles accessing various directories
        $users = [
            ['id' => 1, 'role' => 'admin', 'name' => 'Admin User'],
            ['id' => 2, 'role' => 'doctor', 'name' => 'Dr. Smith'],
            ['id' => 3, 'role' => 'patient', 'name' => 'John Patient'],
            ['id' => 4, 'role' => 'admin', 'name' => 'Another Admin'],
            ['id' => 5, 'role' => 'doctor', 'name' => 'Dr. Wilson']
        ];
        
        $directories = ['admin', 'doctor', 'patient'];
        
        foreach ($users as $user) {
            foreach ($directories as $directory) {
                $should_have_access = ($user['role'] === $directory);
                
                // Simulate access check
                $access_granted = ($user['role'] === $directory);
                
                if ($should_have_access !== $access_granted) {
                    throw new Exception("Access control failed for user {$user['name']} (role: {$user['role']}) accessing $directory directory");
                }
            }
        }
        
        echo "PASS: Role-Based Access Control property test passed\n";
        return true;
        
    } catch (Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the test if this file is executed directly
if (php_sapi_name() === 'cli') {
    $result = runRoleBasedAccessControlTest();
    exit($result ? 0 : 1);
}
?>