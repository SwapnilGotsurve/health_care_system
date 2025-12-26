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

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center animate-fade-in-up">
            <div class="mx-auto h-16 w-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
            <p class="text-gray-600">Sign in to your Health Alert System account</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-xl shadow-lg p-8 animate-fade-in-up stagger-1">
            <?php if ($error): ?>
                <?php echo render_alert($error, 'danger'); ?>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <?php 
                echo render_form_input(
                    'email', 
                    'Email Address', 
                    'email', 
                    $email, 
                    true, 
                    '', 
                    ['placeholder' => 'Enter your email']
                );
                
                echo render_form_input(
                    'password', 
                    'Password', 
                    'password', 
                    '', 
                    true, 
                    '', 
                    ['placeholder' => 'Enter your password']
                );
                ?>
                
                <div class="pt-4">
                    <?php echo render_button('Sign In', 'submit', 'primary', 'lg', false, ['class' => 'w-full']); ?>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="font-medium text-primary-600 hover:text-primary-500 transition-colors hover-underline">
                        Register here
                    </a>
                </p>
            </div>
        </div>

        <!-- Demo Accounts -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 animate-fade-in-up stagger-2">
            <h3 class="text-sm font-medium text-gray-900 mb-4 flex items-center">
                <div class="w-5 h-5 bg-blue-200 rounded-full flex items-center justify-center mr-2">
                    <span class="text-blue-800 text-xs">ℹ️</span>
                </div>
                Demo Accounts
            </h3>
            <div class="grid grid-cols-1 gap-3 text-xs">
                <div class="bg-white rounded-lg p-3 shadow-sm">
                    <div class="font-medium text-gray-900">Admin</div>
                    <div class="text-gray-600">admin@healthalert.com / admin123</div>
                </div>
                <div class="bg-white rounded-lg p-3 shadow-sm">
                    <div class="font-medium text-gray-900">Doctor</div>
                    <div class="text-gray-600">sarah.johnson@hospital.com / doctor123</div>
                </div>
                <div class="bg-white rounded-lg p-3 shadow-sm">
                    <div class="font-medium text-gray-900">Patient</div>
                    <div class="text-gray-600">john.smith@email.com / patient123</div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="text-center animate-fade-in-up stagger-3">
            <div class="grid grid-cols-3 gap-4 text-xs text-gray-500">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <span>Secure</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span>Fast</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <span>Reliable</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add focus animations to form inputs
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('animate-scale-in');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('animate-scale-in');
        });
    });
    
    // Demo account quick fill
    const demoAccounts = document.querySelectorAll('.bg-white.rounded-lg.p-3');
    demoAccounts.forEach(account => {
        account.addEventListener('click', function() {
            const text = this.querySelector('.text-gray-600').textContent;
            const [email, password] = text.split(' / ');
            
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Add visual feedback
            this.classList.add('animate-pulse');
            setTimeout(() => this.classList.remove('animate-pulse'), 1000);
        });
        
        // Add hover effect
        account.classList.add('cursor-pointer', 'hover:shadow-md', 'transition-shadow');
    });
});
</script>

<?php include 'includes/footer.php'; ?>