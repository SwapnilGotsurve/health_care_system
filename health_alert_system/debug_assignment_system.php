<?php
/**
 * Debug Assignment System
 * Quick diagnostic script to check assignment system status
 */

require_once 'config/db.php';

echo "<h1>Assignment System Debug</h1>";

// Check database connection
if ($connection) {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

// Check tables exist
$tables = ['users', 'doctor_patients', 'health_data', 'alerts'];
foreach ($tables as $table) {
    $result = mysqli_query($connection, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' missing</p>";
    }
}

// Check user counts
$user_counts = mysqli_query($connection, "SELECT role, status, COUNT(*) as count FROM users GROUP BY role, status");
echo "<h2>User Counts:</h2>";
while ($row = mysqli_fetch_assoc($user_counts)) {
    echo "<p>{$row['role']} ({$row['status']}): {$row['count']}</p>";
}

// Check assignments
$assignment_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM doctor_patients"))['count'];
echo "<h2>Assignments: $assignment_count</h2>";

// Test dropdown queries
echo "<h2>Dropdown Test:</h2>";

$doctors = mysqli_query($connection, "SELECT id, name, email FROM users WHERE role = 'doctor' AND status = 'approved'");
echo "<p>Approved doctors: " . mysqli_num_rows($doctors) . "</p>";

$patients = mysqli_query($connection, "SELECT id, name, email FROM users WHERE role = 'patient'");
echo "<p>Patients: " . mysqli_num_rows($patients) . "</p>";

echo "<p><a href='fix_database.php'>Run Database Fix</a></p>";
echo "<p><a href='admin/assign_patients.php'>Go to Assignment Page</a></p>";
?>