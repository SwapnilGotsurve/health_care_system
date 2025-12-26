<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$page_title = 'Add Health Data';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Form data variables
$systolic_bp = '';
$diastolic_bp = '';
$sugar_level = '';
$heart_rate = '';

if ($_POST) {
    // Get and validate input data
    $systolic_bp = trim($_POST['systolic_bp']);
    $diastolic_bp = trim($_POST['diastolic_bp']);
    $sugar_level = trim($_POST['sugar_level']);
    $heart_rate = trim($_POST['heart_rate']);
    
    // Validation
    $errors = [];
    
    // Check if all fields are provided
    if (empty($systolic_bp)) {
        $errors[] = 'Systolic blood pressure is required.';
    }
    if (empty($diastolic_bp)) {
        $errors[] = 'Diastolic blood pressure is required.';
    }
    if (empty($sugar_level)) {
        $errors[] = 'Sugar level is required.';
    }
    if (empty($heart_rate)) {
        $errors[] = 'Heart rate is required.';
    }
    
    // Validate numeric values and ranges
    if (!empty($systolic_bp)) {
        if (!is_numeric($systolic_bp) || $systolic_bp < 80 || $systolic_bp > 200) {
            $errors[] = 'Systolic blood pressure must be between 80 and 200 mmHg.';
        }
    }
    
    if (!empty($diastolic_bp)) {
        if (!is_numeric($diastolic_bp) || $diastolic_bp < 50 || $diastolic_bp > 120) {
            $errors[] = 'Diastolic blood pressure must be between 50 and 120 mmHg.';
        }
    }
    
    if (!empty($sugar_level)) {
        if (!is_numeric($sugar_level) || $sugar_level < 70 || $sugar_level > 400) {
            $errors[] = 'Sugar level must be between 70 and 400 mg/dL.';
        }
    }
    
    if (!empty($heart_rate)) {
        if (!is_numeric($heart_rate) || $heart_rate < 40 || $heart_rate > 200) {
            $errors[] = 'Heart rate must be between 40 and 200 BPM.';
        }
    }
    
    // Additional validation: systolic should be higher than diastolic
    if (!empty($systolic_bp) && !empty($diastolic_bp) && is_numeric($systolic_bp) && is_numeric($diastolic_bp)) {
        if ($systolic_bp <= $diastolic_bp) {
            $errors[] = 'Systolic blood pressure must be higher than diastolic blood pressure.';
        }
    }
    
    if (empty($errors)) {
        // Escape values for database
        $systolic_bp_clean = mysqli_real_escape_string($connection, $systolic_bp);
        $diastolic_bp_clean = mysqli_real_escape_string($connection, $diastolic_bp);
        $sugar_level_clean = mysqli_real_escape_string($connection, $sugar_level);
        $heart_rate_clean = mysqli_real_escape_string($connection, $heart_rate);
        
        // Insert health data with current timestamp and patient ID
        $insert_query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, created_at) 
                        VALUES ($user_id, $systolic_bp_clean, $diastolic_bp_clean, $sugar_level_clean, $heart_rate_clean, NOW())";
        
        if (mysqli_query($connection, $insert_query)) {
            $success = 'Health data added successfully!';
            // Clear form data on success
            $systolic_bp = $diastolic_bp = $sugar_level = $heart_rate = '';
        } else {
            $error = 'Failed to save health data. Please try again. Error: ' . mysqli_error($connection);
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

include '../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 animate-fade-in-up">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Add Health Data</h1>
        <p class="text-gray-600">Record your daily health metrics to track your wellness over time.</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($error): ?>
        <?php echo render_alert($error, 'danger'); ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <?php echo render_alert($success, 'success'); ?>
    <?php endif; ?>

    <!-- Health Data Form -->
    <div class="bg-white rounded-lg shadow-md p-6 animate-fade-in-up stagger-1">
        <form method="POST" class="space-y-8">
            <!-- Blood Pressure Section -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    Blood Pressure
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php 
                    echo render_form_input(
                        'systolic_bp', 
                        'Systolic Pressure (mmHg)', 
                        'number', 
                        $systolic_bp, 
                        true, 
                        '', 
                        ['min' => '80', 'max' => '200', 'step' => '1', 'placeholder' => 'e.g., 120']
                    );
                    ?>
                    <p class="text-xs text-gray-500 -mt-4">Normal range: 90-140 mmHg</p>
                    
                    <?php 
                    echo render_form_input(
                        'diastolic_bp', 
                        'Diastolic Pressure (mmHg)', 
                        'number', 
                        $diastolic_bp, 
                        true, 
                        '', 
                        ['min' => '50', 'max' => '120', 'step' => '1', 'placeholder' => 'e.g., 80']
                    );
                    ?>
                    <p class="text-xs text-gray-500 -mt-4">Normal range: 60-90 mmHg</p>
                </div>
            </div>

            <!-- Sugar Level Section -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    Blood Sugar
                </h3>
                <div class="max-w-md">
                    <?php 
                    echo render_form_input(
                        'sugar_level', 
                        'Sugar Level (mg/dL)', 
                        'number', 
                        $sugar_level, 
                        true, 
                        '', 
                        ['min' => '70', 'max' => '400', 'step' => '0.1', 'placeholder' => 'e.g., 95.5']
                    );
                    ?>
                    <p class="text-xs text-gray-500 -mt-4">Normal range: 80-140 mg/dL (fasting)</p>
                </div>
            </div>

            <!-- Heart Rate Section -->
            <div class="pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    Heart Rate
                </h3>
                <div class="max-w-md">
                    <?php 
                    echo render_form_input(
                        'heart_rate', 
                        'Heart Rate (BPM)', 
                        'number', 
                        $heart_rate, 
                        true, 
                        '', 
                        ['min' => '40', 'max' => '200', 'step' => '1', 'placeholder' => 'e.g., 72']
                    );
                    ?>
                    <p class="text-xs text-gray-500 -mt-4">Normal range: 60-100 BPM (resting)</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row gap-4 pt-6">
                <?php echo render_button('Save Health Data', 'submit', 'primary', 'lg', false, ['class' => 'flex-1']); ?>
                <?php echo render_button('Cancel', 'button', 'secondary', 'lg', false, ['class' => 'flex-1', 'onclick' => "window.location.href='dashboard.php'"]); ?>
            </div>
        </form>
    </div>

    <!-- Health Tips -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mt-6 animate-fade-in-up stagger-2">
        <h3 class="text-lg font-medium text-blue-900 mb-4 flex items-center">
            <div class="w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-2">
                <span class="text-blue-800 text-sm">💡</span>
            </div>
            Health Tips
        </h3>
        <ul class="text-sm text-blue-800 space-y-2">
            <li class="flex items-start">
                <span class="text-blue-600 mr-2">•</span>
                Take measurements at the same time each day for consistency
            </li>
            <li class="flex items-start">
                <span class="text-blue-600 mr-2">•</span>
                Rest for 5 minutes before taking blood pressure readings
            </li>
            <li class="flex items-start">
                <span class="text-blue-600 mr-2">•</span>
                Check blood sugar levels as recommended by your doctor
            </li>
            <li class="flex items-start">
                <span class="text-blue-600 mr-2">•</span>
                Record your resting heart rate, preferably in the morning
            </li>
        </ul>
    </div>

    <!-- Navigation -->
    <div class="mt-6 text-center animate-fade-in-up stagger-3">
        <a href="health_history.php" class="inline-flex items-center text-primary-600 hover:text-primary-800 font-medium transition-colors hover-underline">
            View Health History
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>
</div>

<script>
// Enhanced client-side validation with animations
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[type="number"]');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseFloat(this.value);
            const min = parseFloat(this.min);
            const max = parseFloat(this.max);
            
            // Remove previous validation classes
            this.classList.remove('border-red-500', 'border-green-500', 'error', 'success');
            
            if (this.value && (value < min || value > max)) {
                this.classList.add('error');
                this.classList.add('animate-shake');
                setTimeout(() => this.classList.remove('animate-shake'), 500);
            } else if (this.value) {
                this.classList.add('success');
            }
        });
        
        // Add focus animations
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('animate-scale-in');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('animate-scale-in');
        });
    });
    
    // Blood pressure validation with enhanced feedback
    const systolic = document.getElementById('systolic_bp');
    const diastolic = document.getElementById('diastolic_bp');
    
    function validateBloodPressure() {
        const systolicValue = parseFloat(systolic.value);
        const diastolicValue = parseFloat(diastolic.value);
        
        if (systolic.value && diastolic.value && systolicValue <= diastolicValue) {
            systolic.classList.add('error');
            diastolic.classList.add('error');
            
            // Show temporary error message
            showValidationMessage('Systolic pressure must be higher than diastolic pressure', 'error');
        }
    }
    
    function showValidationMessage(message, type) {
        // Remove existing messages
        const existingMessage = document.querySelector('.validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `validation-message p-3 rounded-md text-sm animate-fade-in-up ${type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`;
        messageDiv.textContent = message;
        
        // Insert after form
        form.parentNode.insertBefore(messageDiv, form.nextSibling);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.classList.add('animate-fade-out');
                setTimeout(() => messageDiv.remove(), 300);
            }
        }, 3000);
    }
    
    systolic.addEventListener('input', validateBloodPressure);
    diastolic.addEventListener('input', validateBloodPressure);
    
    // Form submission with loading state
    form.addEventListener('submit', function() {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
            `;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>