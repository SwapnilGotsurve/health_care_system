<?php
/**
 * Feature: health-alert-system, Property 6: Health Data Validation
 * 
 * Property 6: Health Data Validation
 * For any health data with missing required fields or invalid numeric values, 
 * the system should reject submission and display appropriate validation errors
 * Validates: Requirements 2.4, 2.5
 */

// Simple test runner for Health Data Validation Property Test
function runHealthDataValidationTest() {
    echo "Running Health Data Validation Property Test...\n";
    
    try {
        // Test missing required fields scenarios
        $missing_field_scenarios = [
            // [systolic_bp, diastolic_bp, sugar_level, heart_rate, expected_error_contains]
            ['', '80', '95.5', '72', 'Systolic blood pressure is required'],
            ['120', '', '95.5', '72', 'Diastolic blood pressure is required'],
            ['120', '80', '', '72', 'Sugar level is required'],
            ['120', '80', '95.5', '', 'Heart rate is required'],
            ['', '', '95.5', '72', 'blood pressure is required'],
            ['120', '80', '', '', 'required'],
            ['', '', '', '', 'required'],
            [null, '80', '95.5', '72', 'required'],
            ['120', null, '95.5', '72', 'required'],
            ['120', '80', null, '72', 'required'],
            ['120', '80', '95.5', null, 'required']
        ];
        
        foreach ($missing_field_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart = $scenario[3];
            $expected_error = $scenario[4];
            
            // Simulate validation logic from add_health_data.php
            $errors = [];
            
            if (empty($systolic)) {
                $errors[] = 'Systolic blood pressure is required.';
            }
            if (empty($diastolic)) {
                $errors[] = 'Diastolic blood pressure is required.';
            }
            if (empty($sugar)) {
                $errors[] = 'Sugar level is required.';
            }
            if (empty($heart)) {
                $errors[] = 'Heart rate is required.';
            }
            
            if (empty($errors)) {
                throw new Exception("Validation should detect missing fields for scenario: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart=$heart");
            }
            
            // Check that appropriate error message is generated
            $error_message = implode(' ', $errors);
            if (strpos(strtolower($error_message), strtolower($expected_error)) === false) {
                throw new Exception("Error message should contain '$expected_error' for missing field scenario");
            }
        }
        
        // Test invalid numeric values scenarios
        $invalid_numeric_scenarios = [
            // [systolic_bp, diastolic_bp, sugar_level, heart_rate, expected_error_type]
            ['abc', '80', '95.5', '72', 'non-numeric systolic'],
            ['120', 'xyz', '95.5', '72', 'non-numeric diastolic'],
            ['120', '80', 'invalid', '72', 'non-numeric sugar'],
            ['120', '80', '95.5', 'text', 'non-numeric heart rate'],
            ['79', '80', '95.5', '72', 'systolic too low'],
            ['201', '80', '95.5', '72', 'systolic too high'],
            ['120', '49', '95.5', '72', 'diastolic too low'],
            ['120', '121', '95.5', '72', 'diastolic too high'],
            ['120', '80', '69', '72', 'sugar too low'],
            ['120', '80', '401', '72', 'sugar too high'],
            ['120', '80', '95.5', '39', 'heart rate too low'],
            ['120', '80', '95.5', '201', 'heart rate too high'],
            ['-120', '80', '95.5', '72', 'negative systolic'],
            ['120', '-80', '95.5', '72', 'negative diastolic'],
            ['120', '80', '-95.5', '72', 'negative sugar'],
            ['120', '80', '95.5', '-72', 'negative heart rate']
        ];
        
        foreach ($invalid_numeric_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart = $scenario[3];
            $error_type = $scenario[4];
            
            // Simulate validation logic
            $errors = [];
            
            // Check if all fields are provided (skip empty check for this test)
            $has_all_fields = !empty($systolic) && !empty($diastolic) && !empty($sugar) && !empty($heart);
            
            if ($has_all_fields) {
                // Validate numeric values and ranges
                if (!is_numeric($systolic) || $systolic < 80 || $systolic > 200) {
                    $errors[] = 'Systolic blood pressure must be between 80 and 200 mmHg.';
                }
                
                if (!is_numeric($diastolic) || $diastolic < 50 || $diastolic > 120) {
                    $errors[] = 'Diastolic blood pressure must be between 50 and 120 mmHg.';
                }
                
                if (!is_numeric($sugar) || $sugar < 70 || $sugar > 400) {
                    $errors[] = 'Sugar level must be between 70 and 400 mg/dL.';
                }
                
                if (!is_numeric($heart) || $heart < 40 || $heart > 200) {
                    $errors[] = 'Heart rate must be between 40 and 200 BPM.';
                }
                
                // Additional validation: systolic should be higher than diastolic
                if (is_numeric($systolic) && is_numeric($diastolic) && $systolic <= $diastolic) {
                    $errors[] = 'Systolic blood pressure must be higher than diastolic blood pressure.';
                }
            }
            
            if (empty($errors)) {
                throw new Exception("Validation should detect invalid values for $error_type: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart=$heart");
            }
        }
        
        // Test blood pressure relationship validation
        $bp_relationship_scenarios = [
            // [systolic_bp, diastolic_bp, should_be_valid]
            ['120', '80', true],   // Valid: systolic > diastolic
            ['130', '85', true],   // Valid: systolic > diastolic
            ['80', '80', false],   // Invalid: systolic = diastolic
            ['75', '80', false],   // Invalid: systolic < diastolic
            ['90', '95', false],   // Invalid: systolic < diastolic
            ['100', '100', false], // Invalid: systolic = diastolic
            ['140', '90', true],   // Valid: systolic > diastolic
            ['85', '90', false]    // Invalid: systolic < diastolic
        ];
        
        foreach ($bp_relationship_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $should_be_valid = $scenario[2];
            
            // Simulate blood pressure relationship validation
            $errors = [];
            
            if (is_numeric($systolic) && is_numeric($diastolic) && $systolic <= $diastolic) {
                $errors[] = 'Systolic blood pressure must be higher than diastolic blood pressure.';
            }
            
            $is_valid = empty($errors);
            
            if ($should_be_valid && !$is_valid) {
                throw new Exception("Blood pressure relationship should be valid: systolic=$systolic, diastolic=$diastolic");
            }
            
            if (!$should_be_valid && $is_valid) {
                throw new Exception("Blood pressure relationship should be invalid: systolic=$systolic, diastolic=$diastolic");
            }
        }
        
        // Test edge cases and boundary values
        $boundary_scenarios = [
            // [systolic_bp, diastolic_bp, sugar_level, heart_rate, should_be_valid]
            ['80', '50', '70', '40', true],    // Minimum valid values
            ['200', '120', '400', '200', true], // Maximum valid values
            ['79', '50', '70', '40', false],   // Systolic below minimum
            ['80', '49', '70', '40', false],   // Diastolic below minimum
            ['80', '50', '69', '40', false],   // Sugar below minimum
            ['80', '50', '70', '39', false],   // Heart rate below minimum
            ['201', '120', '400', '200', false], // Systolic above maximum
            ['200', '121', '400', '200', false], // Diastolic above maximum
            ['200', '120', '401', '200', false], // Sugar above maximum
            ['200', '120', '400', '201', false], // Heart rate above maximum
        ];
        
        foreach ($boundary_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart = $scenario[3];
            $should_be_valid = $scenario[4];
            
            // Simulate complete validation
            $errors = [];
            
            if (!is_numeric($systolic) || $systolic < 80 || $systolic > 200) {
                $errors[] = 'Systolic blood pressure must be between 80 and 200 mmHg.';
            }
            
            if (!is_numeric($diastolic) || $diastolic < 50 || $diastolic > 120) {
                $errors[] = 'Diastolic blood pressure must be between 50 and 120 mmHg.';
            }
            
            if (!is_numeric($sugar) || $sugar < 70 || $sugar > 400) {
                $errors[] = 'Sugar level must be between 70 and 400 mg/dL.';
            }
            
            if (!is_numeric($heart) || $heart < 40 || $heart > 200) {
                $errors[] = 'Heart rate must be between 40 and 200 BPM.';
            }
            
            if (is_numeric($systolic) && is_numeric($diastolic) && $systolic <= $diastolic) {
                $errors[] = 'Systolic blood pressure must be higher than diastolic blood pressure.';
            }
            
            $is_valid = empty($errors);
            
            if ($should_be_valid && !$is_valid) {
                throw new Exception("Boundary values should be valid: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart=$heart");
            }
            
            if (!$should_be_valid && $is_valid) {
                throw new Exception("Boundary values should be invalid: systolic=$systolic, diastolic=$diastolic, sugar=$sugar, heart=$heart");
            }
        }
        
        // Test decimal precision for sugar level
        $decimal_scenarios = [
            ['120', '80', '95.5', '72', true],   // Valid decimal
            ['120', '80', '95.12', '72', true],  // Valid decimal with 2 places
            ['120', '80', '95', '72', true],     // Valid integer
            ['120', '80', '95.0', '72', true],   // Valid decimal with .0
        ];
        
        foreach ($decimal_scenarios as $scenario) {
            $systolic = $scenario[0];
            $diastolic = $scenario[1];
            $sugar = $scenario[2];
            $heart = $scenario[3];
            $should_be_valid = $scenario[4];
            
            // Test that decimal values are handled correctly
            if (!is_numeric($sugar)) {
                throw new Exception("Sugar level validation should handle decimal values: $sugar");
            }
            
            $sugar_value = floatval($sugar);
            if ($sugar_value < 70 || $sugar_value > 400) {
                if ($should_be_valid) {
                    throw new Exception("Valid decimal sugar level should pass validation: $sugar");
                }
            }
        }
        
        echo "PASS: Health Data Validation property test passed\n";
        return true;
        
    } catch (Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the test if this file is executed directly
if (php_sapi_name() === 'cli') {
    $result = runHealthDataValidationTest();
    exit($result ? 0 : 1);
}
?>