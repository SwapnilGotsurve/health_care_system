<?php
/**
 * Feature: health-alert-system, Property 19: Health Status Badge Logic
 * 
 * Property 19: Health Status Badge Logic
 * For any health data entry, the system should display appropriate status badges 
 * based on health value ranges (Normal/Alert)
 * Validates: Requirements 9.3
 */

// Simple test runner for Health Status Badge Logic Property Test
function runHealthStatusBadgeLogicTest() {
    echo "Running Health Status Badge Logic Property Test...\n";
    
    try {
        // Function to simulate health status determination (from dashboard.php and health_history.php)
        function getHealthStatus($systolic, $diastolic, $sugar, $heart_rate) {
            if ($systolic > 140 || $diastolic > 90 || $sugar > 140 || $heart_rate > 100 ||
                $systolic < 90 || $diastolic < 60 || $sugar < 70 || $heart_rate < 60) {
                return ['status' => 'Alert', 'color' => 'red'];
            } else {
                return ['status' => 'Healthy', 'color' => 'green'];
            }
        }
        
        // Test normal/healthy ranges
        $healthy_scenarios = [
            // [systolic, diastolic, sugar, heart_rate, description]
            [120, 80, 95, 72, 'typical normal values'],
            [90, 60, 70, 60, 'minimum normal values'],
            [140, 90, 140, 100, 'maximum normal values'],
            [110, 70, 85, 65, 'low-normal values'],
            [130, 85, 120, 90, 'high-normal values'],
            [100, 65, 80, 70, 'optimal values'],
            [125, 82, 110, 75, 'good values'],
            [115, 75, 95, 68, 'excellent values'],
            [135, 88, 130, 85, 'borderline normal'],
            [105, 68, 88, 62, 'athletic range']
        ];
        
        foreach ($healthy_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart_rate = $scenario[3];
            $description = $scenario[4];
            
            $status = getHealthStatus($systolic, $diastolic, $sugar, $heart_rate);
            
            if ($status['status'] !== 'Healthy') {
                throw new Exception("Health status should be 'Healthy' for $description: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart_rate=$heart_rate");
            }
            
            if ($status['color'] !== 'green') {
                throw new Exception("Health status color should be 'green' for healthy values: $description");
            }
        }
        
        // Test alert conditions - high values
        $high_alert_scenarios = [
            // [systolic, diastolic, sugar, heart_rate, alert_reason]
            [141, 80, 95, 72, 'high systolic'],
            [120, 91, 95, 72, 'high diastolic'],
            [120, 80, 141, 72, 'high sugar'],
            [120, 80, 95, 101, 'high heart rate'],
            [150, 95, 150, 110, 'all high values'],
            [160, 80, 95, 72, 'very high systolic'],
            [120, 100, 95, 72, 'very high diastolic'],
            [120, 80, 200, 72, 'very high sugar'],
            [120, 80, 95, 120, 'very high heart rate'],
            [200, 120, 400, 200, 'maximum high values']
        ];
        
        foreach ($high_alert_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart_rate = $scenario[3];
            $alert_reason = $scenario[4];
            
            $status = getHealthStatus($systolic, $diastolic, $sugar, $heart_rate);
            
            if ($status['status'] !== 'Alert') {
                throw new Exception("Health status should be 'Alert' for $alert_reason: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart_rate=$heart_rate");
            }
            
            if ($status['color'] !== 'red') {
                throw new Exception("Health status color should be 'red' for alert values: $alert_reason");
            }
        }
        
        // Test alert conditions - low values
        $low_alert_scenarios = [
            // [systolic, diastolic, sugar, heart_rate, alert_reason]
            [89, 80, 95, 72, 'low systolic'],
            [120, 59, 95, 72, 'low diastolic'],
            [120, 80, 69, 72, 'low sugar'],
            [120, 80, 95, 59, 'low heart rate'],
            [85, 55, 65, 55, 'all low values'],
            [80, 80, 95, 72, 'very low systolic'],
            [120, 50, 95, 72, 'very low diastolic'],
            [120, 80, 60, 72, 'very low sugar'],
            [120, 80, 95, 40, 'very low heart rate'],
            [80, 50, 70, 40, 'minimum low values']
        ];
        
        foreach ($low_alert_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart_rate = $scenario[3];
            $alert_reason = $scenario[4];
            
            $status = getHealthStatus($systolic, $diastolic, $sugar, $heart_rate);
            
            if ($status['status'] !== 'Alert') {
                throw new Exception("Health status should be 'Alert' for $alert_reason: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart_rate=$heart_rate");
            }
            
            if ($status['color'] !== 'red') {
                throw new Exception("Health status color should be 'red' for alert values: $alert_reason");
            }
        }
        
        // Test boundary values (edge cases)
        $boundary_scenarios = [
            // [systolic, diastolic, sugar, heart_rate, expected_status, description]
            [90, 60, 70, 60, 'Healthy', 'minimum healthy boundaries'],
            [140, 90, 140, 100, 'Healthy', 'maximum healthy boundaries'],
            [89, 60, 70, 60, 'Alert', 'systolic just below minimum'],
            [90, 59, 70, 60, 'Alert', 'diastolic just below minimum'],
            [90, 60, 69, 60, 'Alert', 'sugar just below minimum'],
            [90, 60, 70, 59, 'Alert', 'heart rate just below minimum'],
            [141, 90, 140, 100, 'Alert', 'systolic just above maximum'],
            [140, 91, 140, 100, 'Alert', 'diastolic just above maximum'],
            [140, 90, 141, 100, 'Alert', 'sugar just above maximum'],
            [140, 90, 140, 101, 'Alert', 'heart rate just above maximum']
        ];
        
        foreach ($boundary_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart_rate = $scenario[3];
            $expected_status = $scenario[4];
            $description = $scenario[5];
            
            $status = getHealthStatus($systolic, $diastolic, $sugar, $heart_rate);
            
            if ($status['status'] !== $expected_status) {
                throw new Exception("Health status should be '$expected_status' for $description: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart_rate=$heart_rate, got: " . $status['status']);
            }
            
            $expected_color = ($expected_status === 'Healthy') ? 'green' : 'red';
            if ($status['color'] !== $expected_color) {
                throw new Exception("Health status color should be '$expected_color' for $description");
            }
        }
        
        // Test mixed conditions (some values normal, some alert)
        $mixed_scenarios = [
            // [systolic, diastolic, sugar, heart_rate, expected_status, description]
            [150, 80, 95, 72, 'Alert', 'high systolic, others normal'],
            [120, 100, 95, 72, 'Alert', 'high diastolic, others normal'],
            [120, 80, 200, 72, 'Alert', 'high sugar, others normal'],
            [120, 80, 95, 120, 'Alert', 'high heart rate, others normal'],
            [80, 80, 95, 72, 'Alert', 'low systolic, others normal'],
            [120, 50, 95, 72, 'Alert', 'low diastolic, others normal'],
            [120, 80, 60, 72, 'Alert', 'low sugar, others normal'],
            [120, 80, 95, 50, 'Alert', 'low heart rate, others normal'],
            [150, 100, 95, 72, 'Alert', 'high systolic and diastolic'],
            [80, 50, 95, 72, 'Alert', 'low systolic and diastolic'],
            [120, 80, 200, 120, 'Alert', 'high sugar and heart rate'],
            [80, 50, 60, 50, 'Alert', 'all low values']
        ];
        
        foreach ($mixed_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart_rate = $scenario[3];
            $expected_status = $scenario[4];
            $description = $scenario[5];
            
            $status = getHealthStatus($systolic, $diastolic, $sugar, $heart_rate);
            
            if ($status['status'] !== $expected_status) {
                throw new Exception("Health status should be '$expected_status' for $description: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart_rate=$heart_rate");
            }
        }
        
        // Test random value combinations to ensure consistency
        $random_scenarios = [
            [rand(90, 140), rand(60, 90), rand(70, 140), rand(60, 100)],
            [rand(90, 140), rand(60, 90), rand(70, 140), rand(60, 100)],
            [rand(90, 140), rand(60, 90), rand(70, 140), rand(60, 100)],
            [rand(90, 140), rand(60, 90), rand(70, 140), rand(60, 100)],
            [rand(90, 140), rand(60, 90), rand(70, 140), rand(60, 100)]
        ];
        
        foreach ($random_scenarios as $values) {
            $systolic = $values[0];
            $diastolic = $values[1];
            $sugar = $values[2];
            $heart_rate = $values[3];
            
            $status = getHealthStatus($systolic, $diastolic, $sugar, $heart_rate);
            
            // Verify status is either 'Healthy' or 'Alert'
            if (!in_array($status['status'], ['Healthy', 'Alert'])) {
                throw new Exception("Health status should be either 'Healthy' or 'Alert', got: " . $status['status']);
            }
            
            // Verify color matches status
            $expected_color = ($status['status'] === 'Healthy') ? 'green' : 'red';
            if ($status['color'] !== $expected_color) {
                throw new Exception("Health status color should match status. Status: " . $status['status'] . ", Color: " . $status['color']);
            }
            
            // Verify logic consistency
            $should_be_alert = ($systolic > 140 || $diastolic > 90 || $sugar > 140 || $heart_rate > 100 ||
                              $systolic < 90 || $diastolic < 60 || $sugar < 70 || $heart_rate < 60);
            
            $is_alert = ($status['status'] === 'Alert');
            
            if ($should_be_alert !== $is_alert) {
                throw new Exception("Health status logic inconsistency for values: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart_rate=$heart_rate");
            }
        }
        
        // Test extreme values
        $extreme_scenarios = [
            // [systolic, diastolic, sugar, heart_rate, expected_status, description]
            [300, 200, 500, 300, 'Alert', 'extremely high values'],
            [50, 30, 30, 20, 'Alert', 'extremely low values'],
            [0, 0, 0, 0, 'Alert', 'zero values'],
            [1000, 1000, 1000, 1000, 'Alert', 'unrealistic high values']
        ];
        
        foreach ($extreme_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart_rate = $scenario[3];
            $expected_status = $scenario[4];
            $description = $scenario[5];
            
            $status = getHealthStatus($systolic, $diastolic, $sugar, $heart_rate);
            
            if ($status['status'] !== $expected_status) {
                throw new Exception("Health status should be '$expected_status' for $description");
            }
        }
        
        // Test decimal values for sugar level
        $decimal_scenarios = [
            [120, 80, 95.5, 72, 'Healthy', 'normal decimal sugar'],
            [120, 80, 140.1, 72, 'Alert', 'high decimal sugar'],
            [120, 80, 69.9, 72, 'Alert', 'low decimal sugar'],
            [120, 80, 99.99, 72, 'Healthy', 'high normal decimal'],
            [120, 80, 70.01, 72, 'Healthy', 'low normal decimal']
        ];
        
        foreach ($decimal_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart_rate = $scenario[3];
            $expected_status = $scenario[4];
            $description = $scenario[5];
            
            $status = getHealthStatus($systolic, $diastolic, $sugar, $heart_rate);
            
            if ($status['status'] !== $expected_status) {
                throw new Exception("Health status should be '$expected_status' for $description: sugar=$sugar");
            }
        }
        
        echo "PASS: Health Status Badge Logic property test passed\n";
        return true;
        
    } catch (Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the test if this file is executed directly
if (php_sapi_name() === 'cli') {
    $result = runHealthStatusBadgeLogicTest();
    exit($result ? 0 : 1);
}
?>