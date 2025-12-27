<?php
session_start();
require_once 'config/db.php';

$page_title = 'Register';

$error = '';
$success = '';

if ($_POST) {
    // Validate input fields
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, ['patient', 'doctor'])) {
        $error = 'Invalid role selected.';
    } else {
        // Escape strings for database
        $name = mysqli_real_escape_string($connection, $name);
        $email = mysqli_real_escape_string($connection, $email);
        $password = mysqli_real_escape_string($connection, $password);
        $role = mysqli_real_escape_string($connection, $role);
        
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($connection, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Email already exists. Please use a different email.';
        } else {
            // Set status based on role - patients are approved, doctors are pending
            $status = ($role === 'doctor') ? 'pending' : 'approved';
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) VALUES ('$name', '$email', '$password', '$role', '$status')";
            
            if (mysqli_query($connection, $insert_query)) {
                if ($role === 'doctor') {
                    $success = 'Registration successful! Your account is pending approval from an administrator.';
                } else {
                    $success = 'Registration successful! You can now login.';
                }
                // Clear form data on success
                $name = $email = $role = '';
            } else {
                $error = 'Registration failed. Please try again. Error: ' . mysqli_error($connection);
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-green-50 via-white to-blue-50">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center animate-fade-in-up">
            <div class="mx-auto h-20 w-20 bg-gradient-to-br from-green-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg hover-lift">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h2 class="text-4xl font-bold text-gray-900 mb-3">Join Us Today</h2>
            <p class="text-gray-600 text-lg">Create your Health Alert System account</p>
        </div>

        <!-- Registration Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8 animate-fade-in-up stagger-1 border border-gray-100">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg animate-shake">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-medium"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg animate-bounce-in">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700 font-medium"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6" id="registerForm">
                <!-- Name Field -->
                <div class="form-group">
                    <div class="relative">
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                               placeholder=" "
                               required
                               maxlength="100"
                               class="form-input peer w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-100 transition-all duration-300 text-gray-900 placeholder-transparent">
                        <label for="name" 
                               class="form-label absolute left-4 -top-2.5 bg-white px-2 text-sm text-gray-600 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3 peer-placeholder-shown:left-4 peer-focus:-top-2.5 peer-focus:left-4 peer-focus:text-sm peer-focus:text-green-600 peer-focus:font-medium">
                            Full Name
                        </label>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <div class="relative">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                               placeholder=" "
                               required
                               maxlength="100"
                               class="form-input peer w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-100 transition-all duration-300 text-gray-900 placeholder-transparent">
                        <label for="email" 
                               class="form-label absolute left-4 -top-2.5 bg-white px-2 text-sm text-gray-600 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3 peer-placeholder-shown:left-4 peer-focus:-top-2.5 peer-focus:left-4 peer-focus:text-sm peer-focus:text-green-600 peer-focus:font-medium">
                            Email Address
                        </label>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder=" "
                               required
                               minlength="6"
                               class="form-input peer w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-100 transition-all duration-300 text-gray-900 placeholder-transparent">
                        <label for="password" 
                               class="form-label absolute left-4 -top-2.5 bg-white px-2 text-sm text-gray-600 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3 peer-placeholder-shown:left-4 peer-focus:-top-2.5 peer-focus:left-4 peer-focus:text-sm peer-focus:text-green-600 peer-focus:font-medium">
                            Password
                        </label>
                        <button type="button" 
                                id="togglePassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Minimum 6 characters required</p>
                </div>

                <!-- Role Field -->
                <div class="form-group">
                    <div class="relative">
                        <select id="role" 
                                name="role" 
                                required 
                                class="form-input peer w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-100 transition-all duration-300 text-gray-900 appearance-none">
                            <option value="">Select Your Role</option>
                            <option value="patient" <?php echo (isset($role) && $role === 'patient') ? 'selected' : ''; ?>>Patient</option>
                            <option value="doctor" <?php echo (isset($role) && $role === 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                        </select>
                        <label for="role" 
                               class="form-label absolute left-4 -top-2.5 bg-white px-2 text-sm text-green-600 font-medium">
                            Role
                        </label>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                            id="submitBtn"
                            class="btn-animate btn-ripple w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl text-sm font-medium text-white bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 focus:outline-none focus:ring-4 focus:ring-green-200 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <span id="submitText">Create Account</span>
                        <svg id="loadingSpinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
            
            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="index.php" class="font-medium text-green-600 hover:text-green-500 transition-colors hover-underline">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>

        <!-- Role Information -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-2xl p-6 animate-fade-in-up stagger-2 border border-green-100">
            <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center">
                <div class="w-6 h-6 bg-green-200 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-3 h-3 text-green-800" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                Account Types
            </h3>
            <div class="grid grid-cols-1 gap-3 text-sm">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">Patient</div>
                            <div class="text-gray-600 text-xs">Track health data, receive alerts, view history</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">Doctor</div>
                            <div class="text-gray-600 text-xs">Monitor patients, send alerts (requires approval)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Features -->
        <div class="text-center animate-fade-in-up stagger-3">
            <div class="grid grid-cols-3 gap-6 text-sm text-gray-500">
                <div class="flex flex-col items-center hover-scale">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 rounded-xl flex items-center justify-center mb-3 shadow-sm">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <span class="font-medium">Secure</span>
                </div>
                <div class="flex flex-col items-center hover-scale">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center mb-3 shadow-sm">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="font-medium">Verified</span>
                </div>
                <div class="flex flex-col items-center hover-scale">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center mb-3 shadow-sm">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="font-medium">Fast</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle eye icon
        if (type === 'text') {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            `;
        }
    });
    
    // Form submission with loading state
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitText.textContent = 'Creating Account...';
        loadingSpinner.classList.remove('hidden');
        submitBtn.classList.add('opacity-75');
    });
    
    // Input validation feedback
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !this.checkValidity()) {
                this.classList.add('border-red-300', 'animate-shake');
                setTimeout(() => this.classList.remove('animate-shake'), 500);
            } else if (this.value && this.checkValidity()) {
                this.classList.add('border-green-300');
                this.classList.remove('border-red-300');
            }
        });
        
        input.addEventListener('input', function() {
            this.classList.remove('border-red-300', 'border-green-300');
        });
    });
    
    // Password strength indicator
    const passwordField = document.getElementById('password');
    passwordField.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        updatePasswordStrengthIndicator(strength);
    });
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        return strength;
    }
    
    function updatePasswordStrengthIndicator(strength) {
        // You can add a visual password strength indicator here
        const colors = ['red', 'orange', 'yellow', 'lightgreen', 'green'];
        const passwordField = document.getElementById('password');
        
        if (strength > 0) {
            passwordField.style.borderLeftColor = colors[strength - 1];
            passwordField.style.borderLeftWidth = '4px';
        } else {
            passwordField.style.borderLeftColor = '';
            passwordField.style.borderLeftWidth = '';
        }
    }
    
    // Role selection enhancement
    const roleSelect = document.getElementById('role');
    roleSelect.addEventListener('change', function() {
        if (this.value === 'doctor') {
            // Show doctor approval notice
            if (!document.getElementById('doctorNotice')) {
                const notice = document.createElement('div');
                notice.id = 'doctorNotice';
                notice.className = 'mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg animate-fade-in-up';
                notice.innerHTML = `
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-yellow-700">Doctor accounts require administrator approval before access is granted.</p>
                    </div>
                `;
                roleSelect.parentElement.appendChild(notice);
            }
        } else {
            const notice = document.getElementById('doctorNotice');
            if (notice) {
                notice.remove();
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>