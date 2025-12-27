<?php
/**
 * Authentication Middleware
 * 
 * This file provides role-based access control for the Health Alert System.
 * It must be included at the top of every protected page to ensure proper
 * authentication and authorization.
 * 
 * Features:
 * - Session validation
 * - Role-based access control
 * - Automatic redirects for unauthorized access
 * - Session timeout management
 * 
 * @author Health Alert System Team
 * @version 1.0
 */

// Start or resume the current session
session_start();

/**
 * Primary Authentication Check with User-Friendly Handling
 * 
 * Verifies that the user has a valid session with both user_id and role set.
 * If either is missing, show a user-friendly login prompt instead of just redirecting.
 */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Clean up any partial or corrupted session data
    session_destroy();
    
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Return JSON response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Session expired', 'redirect' => '/health_alert_system/index.php']);
        exit();
    }
    
    // For regular requests, show user-friendly login page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Please Log In - Health Alert System</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-4">Please Log In</h1>
            <p class="text-gray-600 mb-6">You need to log in to access this page. Your session may have expired for security reasons.</p>
            <div class="space-y-3">
                <a href="/health_alert_system/index.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    Go to Login Page
                </a>
                <button onclick="history.back()" class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                    Go Back
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-6">For your security, sessions expire after a period of inactivity.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

/**
 * Role-Based Access Control (RBAC)
 * 
 * Determines the required role based on the current directory structure.
 * The system uses a simple directory-based approach where:
 * - /admin/ pages require 'admin' role
 * - /doctor/ pages require 'doctor' role  
 * - /patient/ pages require 'patient' role
 */

// Extract the current directory from the request URI
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$required_role = '';

// Map directory names to required roles
switch ($current_dir) {
    case 'admin':
        $required_role = 'admin';
        break;
    case 'doctor':
        $required_role = 'doctor';
        break;
    case 'patient':
        $required_role = 'patient';
        break;
    // Root directory pages (like index.php) don't require specific roles
    default:
        $required_role = '';
}

/**
 * Role Authorization Check with User-Friendly Error Pages
 * 
 * If a specific role is required for the current page, verify that
 * the user's session role matches the requirement.
 */
if ($required_role && $_SESSION['role'] !== $required_role) {
    // Log the unauthorized access attempt for security monitoring
    error_log("Unauthorized access attempt: User ID " . $_SESSION['user_id'] . 
              " (role: " . $_SESSION['role'] . ") tried to access " . $required_role . " area");
    
    // Show user-friendly access denied page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - Health Alert System</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-yellow-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-4">Access Denied</h1>
            <p class="text-gray-600 mb-6">You don't have permission to access this page. You're currently logged in as a <?php echo ucfirst($_SESSION['role']); ?>.</p>
            <div class="space-y-3">
                <?php
                // Provide appropriate dashboard link based on user's role
                switch ($_SESSION['role']) {
                    case 'admin':
                        echo '<a href="/health_alert_system/admin/dashboard.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">Go to Admin Dashboard</a>';
                        break;
                    case 'doctor':
                        echo '<a href="/health_alert_system/doctor/dashboard.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">Go to Doctor Dashboard</a>';
                        break;
                    case 'patient':
                        echo '<a href="/health_alert_system/patient/dashboard.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">Go to Patient Dashboard</a>';
                        break;
                    default:
                        echo '<a href="/health_alert_system/index.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">Go to Login</a>';
                }
                ?>
                <button onclick="history.back()" class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                    Go Back
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-6">If you believe this is an error, please contact your administrator.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

/**
 * Session Activity Tracking
 * 
 * Update the last activity timestamp for session timeout management.
 * This can be used to implement automatic logout after inactivity.
 * 
 * Note: Session timeout logic would be implemented in a separate
 * session management function if required.
 */
$_SESSION['last_activity'] = time();

/**
 * Additional Security Considerations:
 * 
 * 1. Session Fixation Protection: Consider regenerating session ID on login
 * 2. CSRF Protection: Implement CSRF tokens for state-changing operations
 * 3. Session Timeout: Implement automatic logout after inactivity period
 * 4. Secure Session Configuration: Use secure session settings in production
 */
?>