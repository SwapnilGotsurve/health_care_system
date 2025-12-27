<?php
/**
 * Database Configuration File
 * 
 * This file establishes the database connection for the Health Alert System.
 * It uses MySQLi procedural functions for database operations.
 * 
 * @author Health Alert System Team
 * @version 1.0
 */

// Database connection parameters
// These settings are configured for default XAMPP installation
$host = 'localhost';        // Database server hostname
$username = 'root';         // MySQL username (default for XAMPP)
$password = '';             // MySQL password (empty by default in XAMPP)
$database = 'health_alert_system';  // Target database name

/**
 * Establish database connection using MySQLi procedural interface
 * 
 * MySQLi is used instead of PDO for educational purposes and 
 * to maintain consistency with procedural PHP approach throughout the system.
 */
$connection = mysqli_connect($host, $username, $password, $database);

/**
 * Connection error handling with user-friendly messages
 * 
 * If the connection fails, show a user-friendly error page instead of
 * technical error messages that might confuse users.
 */
if (!$connection) {
    // Log the technical error for debugging
    error_log("Database Connection Failed: " . mysqli_connect_error());
    
    // Show user-friendly error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Service Temporarily Unavailable - Health Alert System</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-4">Service Temporarily Unavailable</h1>
            <p class="text-gray-600 mb-6">We're experiencing technical difficulties. Please try again in a few moments.</p>
            <div class="space-y-3">
                <button onclick="location.reload()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    Try Again
                </button>
                <a href="index.php" class="block w-full bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                    Go to Home
                </a>
            </div>
            <p class="text-xs text-gray-500 mt-6">If this problem persists, please contact support.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

/**
 * Set character encoding to UTF-8
 * 
 * This ensures proper handling of international characters
 * and prevents character encoding issues in the database.
 */
mysqli_set_charset($connection, "utf8");

/**
 * Database Schema Overview:
 * 
 * - users: Stores user accounts (patients, doctors, admins)
 * - health_data: Patient health records (BP, sugar, heart rate)
 * - alerts: Doctor-to-patient alert messages
 * - doctor_patients: Many-to-many relationship between doctors and patients
 * 
 * All tables use AUTO_INCREMENT primary keys and include created_at timestamps
 * for audit trails and data tracking.
 */
?>