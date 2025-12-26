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
 * Primary Authentication Check
 * 
 * Verifies that the user has a valid session with both user_id and role set.
 * If either is missing, the session is considered invalid and the user
 * is redirected to the login page.
 */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Clean up any partial or corrupted session data
    session_destroy();
    
    // Redirect to login page with absolute path
    header("Location: /health_alert_system/index.php");
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
 * Role Authorization Check
 * 
 * If a specific role is required for the current page, verify that
 * the user's session role matches the requirement.
 */
if ($required_role && $_SESSION['role'] !== $required_role) {
    // Log the unauthorized access attempt for security monitoring
    error_log("Unauthorized access attempt: User ID " . $_SESSION['user_id'] . 
              " (role: " . $_SESSION['role'] . ") tried to access " . $required_role . " area");
    
    /**
     * Redirect to Appropriate Dashboard
     * 
     * Instead of showing an error, redirect users to their proper dashboard
     * based on their actual role. This provides a better user experience.
     */
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: /health_alert_system/admin/dashboard.php");
            break;
        case 'doctor':
            header("Location: /health_alert_system/doctor/dashboard.php");
            break;
        case 'patient':
            header("Location: /health_alert_system/patient/dashboard.php");
            break;
        default:
            // Invalid role in session - destroy and redirect to login
            session_destroy();
            header("Location: /health_alert_system/index.php");
    }
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