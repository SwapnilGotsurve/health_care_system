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
 * Connection error handling
 * 
 * If the connection fails, the script terminates with an error message.
 * In production, this should log errors instead of displaying them.
 */
if (!$connection) {
    die("Database Connection Error: " . mysqli_connect_error());
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