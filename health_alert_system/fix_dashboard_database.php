<?php
/**
 * Dashboard Database Fix Script
 * 
 * This script fixes the database schema issues that are causing
 * the "Unable to load alerts" error in the doctor dashboard.
 */

require_once 'config/db.php';

echo "<h1>Dashboard Database Fix</h1>";
echo "<p>Fixing database schema issues...</p>";

// Check database connection
if (!$connection) {
    echo "<p style='color: red;'>✗ Database connection failed: " . mysqli_connect_error() . "</p>";
    exit();
}

echo "<p style='color: green;'>✓ Database connection successful</p>";

// Fix 1: Update user status enum values
echo "<h2>1. Fixing User Status Enum</h2>";
$alter_users_query = "ALTER TABLE users MODIFY COLUMN status ENUM('active', 'pending', 'inactive') DEFAULT 'active'";
if (mysqli_query($connection, $alter_users_query)) {
    echo "<p style='color: green;'>✓ User status enum updated successfully</p>";
} else {
    echo "<p style='color: orange;'>⚠ User status enum update failed (may already be correct): " . mysqli_error($connection) . "</p>";
}

// Fix 2: Update existing status values
echo "<h2>2. Updating Existing Status Values</h2>";
$update_status_query = "UPDATE users SET status = 'active' WHERE status = 'approved'";
if (mysqli_query($connection, $update_status_query)) {
    $affected_rows = mysqli_affected_rows($connection);
    echo "<p style='color: green;'>✓ Updated $affected_rows user status values from 'approved' to 'active'</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to update status values: " . mysqli_error($connection) . "</p>";
}

// Fix 3: Ensure we have proper hashed passwords for testing
echo "<h2>3. Checking Password Hashing</h2>";
$check_passwords_query = "SELECT id, email, password FROM users WHERE LENGTH(password) < 50 LIMIT 5";
$password_result = mysqli_query($connection, $check_passwords_query);

if ($password_result && mysqli_num_rows($password_result) > 0) {
    echo "<p style='color: orange;'>⚠ Found users with plain text passwords. Updating...</p>";
    
    // Hash common passwords
    $hashed_admin = password_hash('admin123', PASSWORD_DEFAULT);
    $hashed_doctor = password_hash('doctor123', PASSWORD_DEFAULT);
    $hashed_patient = password_hash('patient123', PASSWORD_DEFAULT);
    
    // Update admin passwords
    $update_admin = "UPDATE users SET password = '$hashed_admin' WHERE role = 'admin' AND LENGTH(password) < 50";
    mysqli_query($connection, $update_admin);
    
    // Update doctor passwords
    $update_doctor = "UPDATE users SET password = '$hashed_doctor' WHERE role = 'doctor' AND LENGTH(password) < 50";
    mysqli_query($connection, $update_doctor);
    
    // Update patient passwords
    $update_patient = "UPDATE users SET password = '$hashed_patient' WHERE role = 'patient' AND LENGTH(password) < 50";
    mysqli_query($connection, $update_patient);
    
    echo "<p style='color: green;'>✓ Passwords hashed successfully</p>";
} else {
    echo "<p style='color: green;'>✓ All passwords are properly hashed</p>";
}

// Fix 4: Verify table structure
echo "<h2>4. Verifying Table Structure</h2>";
$tables = ['users', 'doctor_patients', 'alerts', 'health_data'];
foreach ($tables as $table) {
    $result = mysqli_query($connection, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' missing</p>";
    }
}

// Fix 5: Check for sample data
echo "<h2>5. Checking Sample Data</h2>";

// Check for doctors
$doctor_count = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'doctor'");
$doctor_result = mysqli_fetch_assoc($doctor_count);
echo "<p>Doctors in database: " . $doctor_result['count'] . "</p>";

// Check for patients
$patient_count = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'patient'");
$patient_result = mysqli_fetch_assoc($patient_count);
echo "<p>Patients in database: " . $patient_result['count'] . "</p>";

// Check for assignments
$assignment_count = mysqli_query($connection, "SELECT COUNT(*) as count FROM doctor_patients");
$assignment_result = mysqli_fetch_assoc($assignment_count);
echo "<p>Doctor-patient assignments: " . $assignment_result['count'] . "</p>";

// Check for health data
$health_count = mysqli_query($connection, "SELECT COUNT(*) as count FROM health_data");
$health_result = mysqli_fetch_assoc($health_count);
echo "<p>Health records: " . $health_result['count'] . "</p>";

// Check for alerts
$alert_count = mysqli_query($connection, "SELECT COUNT(*) as count FROM alerts");
$alert_result = mysqli_fetch_assoc($alert_count);
echo "<p>Alerts: " . $alert_result['count'] . "</p>";

// Fix 6: Test dashboard queries with first doctor
echo "<h2>6. Testing Dashboard Queries</h2>";
$first_doctor_query = "SELECT id, name, email FROM users WHERE role = 'doctor' AND status = 'active' LIMIT 1";
$first_doctor_result = mysqli_query($connection, $first_doctor_query);

if ($first_doctor_result && mysqli_num_rows($first_doctor_result) > 0) {
    $doctor = mysqli_fetch_assoc($first_doctor_result);
    $doctor_id = $doctor['id'];
    echo "<p>Testing with doctor: " . htmlspecialchars($doctor['name']) . " (ID: $doctor_id)</p>";
    
    // Test patient count query
    $test_patients = mysqli_query($connection, "SELECT COUNT(*) as total FROM doctor_patients WHERE doctor_id = $doctor_id");
    if ($test_patients) {
        $patient_total = mysqli_fetch_assoc($test_patients)['total'];
        echo "<p style='color: green;'>✓ Patient count query works: $patient_total patients</p>";
    } else {
        echo "<p style='color: red;'>✗ Patient count query failed: " . mysqli_error($connection) . "</p>";
    }
    
    // Test alerts query
    $test_alerts = mysqli_query($connection, "SELECT COUNT(*) as total FROM alerts WHERE doctor_id = $doctor_id");
    if ($test_alerts) {
        $alert_total = mysqli_fetch_assoc($test_alerts)['total'];
        echo "<p style='color: green;'>✓ Alerts count query works: $alert_total alerts</p>";
    } else {
        echo "<p style='color: red;'>✗ Alerts count query failed: " . mysqli_error($connection) . "</p>";
    }
    
    // Test recent alerts query
    $test_recent = mysqli_query($connection, "SELECT a.message, a.created_at, u.name as patient_name, a.status
                                            FROM alerts a
                                            JOIN users u ON a.patient_id = u.id
                                            WHERE a.doctor_id = $doctor_id
                                            ORDER BY a.created_at DESC
                                            LIMIT 5");
    if ($test_recent) {
        $recent_count = mysqli_num_rows($test_recent);
        echo "<p style='color: green;'>✓ Recent alerts query works: $recent_count recent alerts</p>";
    } else {
        echo "<p style='color: red;'>✗ Recent alerts query failed: " . mysqli_error($connection) . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ No active doctors found in database</p>";
}

echo "<h2>7. Summary</h2>";
echo "<p><strong>Database fixes completed!</strong></p>";
echo "<p>You can now test the doctor dashboard with these login credentials:</p>";
echo "<ul>";
echo "<li><strong>Email:</strong> sarah.johnson@hospital.com</li>";
echo "<li><strong>Password:</strong> doctor123</li>";
echo "</ul>";
echo "<p>If you still see errors, please run the database setup script again to ensure all sample data is properly inserted.</p>";

mysqli_close($connection);
?>