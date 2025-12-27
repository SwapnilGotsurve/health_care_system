<?php
session_start();
require_once 'config/db.php';

$page_title = 'Login';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: {$role}/dashboard.php");
    exit();
}

$error = '';
$email = '';

if ($_POST) {
    // Validate input
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $email_clean = mysqli_real_escape_string($connection, $email);
        
        $query = "SELECT id, name, email, password, role, status FROM users WHERE email = '$email_clean'";
        $result = mysqli_query($connection, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Simple password check (no hashing for college project)
            if ($password === $user['password']) {
                // Check if doctor is approved
                if ($user['role'] === 'doctor' && $user['status'] === 'pending') {
                    $error = 'Your account is awaiting approval from an administrator.';
                } else {
                    // Create session with user data
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Redirect to appropriate dashboard based on role
                    switch ($user['role']) {
                        case 'admin':
                            header("Location: admin/dashboard.php");
                            break;
                        case 'doctor':
                            header("Location: doctor/dashboard.php");
                            break;
                        case 'patient':
                            header("Location: patient/dashboard.php");
                            break;
                        default:
                            header("Location: index.php");
                    }
                    exit();
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center animate-fade-in-up">
            <div class="mx-auto h-20 w-20 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg hover-lift">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <h2 class="text-4xl font-bold text-gray-900 mb-3">Welcome Back</h2>
            <p class="text-gray-600 text-lg">Sign in to your Health Alert System account</p>
        </div>

        <!-- Login Form -->
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
            
            <form method="POST" class="space-y-6" id="loginForm">
                <!-- Email Field -->
                <div class="form-group">
                    <div class="relative">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value=""
                               placeholder=" "
                               required
                               class="form-input peer w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all duration-300 text-gray-900 placeholder-transparent">
                        <label for="email" 
                               class="form-label absolute left-4 -top-2.5 bg-white px-2 text-sm text-gray-600 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3 peer-placeholder-shown:left-4 peer-focus:-top-2.5 peer-focus:left-4 peer-focus:text-sm peer-focus:text-primary-600 peer-focus:font-medium">
                            Email Address
                        </label>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                          
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
                               class="form-input peer w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all duration-300 text-gray-900 placeholder-transparent">
                        <label for="password" 
                               class="form-label absolute left-4 -top-2.5 bg-white px-2 text-sm text-gray-600 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3 peer-placeholder-shown:left-4 peer-focus:-top-2.5 peer-focus:left-4 peer-focus:text-sm peer-focus:text-primary-600 peer-focus:font-medium">
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
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" 
                               name="remember-me" 
                               type="checkbox" 
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded transition-colors">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-700 hover:text-gray-900 cursor-pointer">
                            Remember me
                        </label>
                    </div>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-primary-600 hover:text-primary-500 transition-colors hover-underline">
                            Forgot password?
                        </a>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                            id="submitBtn"
                            class="btn-animate btn-ripple w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl text-sm font-medium text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-200 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <span id="submitText">Sign In</span>
                        <svg id="loadingSpinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
            
            <!-- Register Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="font-medium text-primary-600 hover:text-primary-500 transition-colors hover-underline">
                        Create one now
                    </a>
                </p>
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
    
    // Demo account auto-fill functionality
    const demoAccounts = document.querySelectorAll('.demo-account');
    demoAccounts.forEach(account => {
        account.addEventListener('click', function() {
            const email = this.getAttribute('data-email');
            const password = this.getAttribute('data-password');
            
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Add visual feedback
            this.classList.add('animate-pulse');
            setTimeout(() => this.classList.remove('animate-pulse'), 1000);
            
            // Focus on submit button
            document.getElementById('submitBtn').focus();
        });
    });
    
    // Form submission with loading state
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitText.textContent = 'Signing In...';
        loadingSpinner.classList.remove('hidden');
        submitBtn.classList.add('opacity-75');
    });
    
    // Input validation feedback
    const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
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
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + 1, 2, 3 for demo accounts
        if (e.altKey && e.key >= '1' && e.key <= '3') {
            e.preventDefault();
            const accountIndex = parseInt(e.key) - 1;
            if (demoAccounts[accountIndex]) {
                demoAccounts[accountIndex].click();
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>