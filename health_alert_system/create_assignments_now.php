<?php
/**
 * CREATE ASSIGNMENTS NOW - Immediate Fix
 * This script will create assignments immediately to fix the system
 */

require_once 'config/db.php';

// Simple HTML output
echo "<h1>Creating Assignments Now...</h1>";

if (!$connection) {
    echo "<p style='color: red;'>Database connection failed!</p>";
    exit;
}

echo "<p style='color: green;'>Database connected successfully</p>";

// Get approved doctors
$doctors = [];
$doctor_query = "SELECT id, name FROM users WHERE role = 'doctor' AND status = 'approved'";
$doctor_result = mysqli_query($connection, $doctor_query);
while ($row = mysqli_fetch_assoc($doctor_result)) {
    $doctors[] = $row;
}

// Get patients
$patients = [];
$patient_query = "SELECT id, name FROM users WHERE role = 'patient'";
$patient_result = mysqli_query($connection, $patient_query);
while ($row = mysqli_fetch_assoc($patient_result)) {
    $patients[] = $row;
}

echo "<p>Found " . count($doctors) . " doctors and " . count($patients) . " patients</p>";

// Clear existing assignments
mysqli_query($connection, "DELETE FROM doctor_patients");
echo "<p>Cleared existing assignments</p>";

// Create assignments
$created = 0;
if (count($doctors) > 0 && count($patients) > 0) {
    foreach ($patients as $patient) {
        foreach ($doctors as $doctor) {
            $query = "INSERT INTO doctor_patients (doctor_id, patient_id, created_at) VALUES ({$doctor['id']}, {$patient['id']}, NOW())";
            if (mysqli_query($connection, $query)) {
                echo "<p style='color: green;'>✓ Assigned {$patient['name']} to Dr. {$doctor['name']}</p>";
                $created++;
            } else {
                echo "<p style='color: red;'>✗ Failed to assign {$patient['name']} to Dr. {$doctor['name']}: " . mysqli_error($connection) . "</p>";
            }
        }
    }
}

echo "<h2>Created $created assignments!</h2>";

// Verify
$verify = mysqli_query($connection, "SELECT COUNT(*) as count FROM doctor_patients");
$count = mysqli_fetch_assoc($verify)['count'];
echo "<p><strong>Total assignments in database: $count</strong></p>";

if ($count > 0) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ SUCCESS! Assignment system is now working!</strong></p>";
    echo "<p><a href='admin/assign_patients.php' style='background: blue; color: white; padding: 10px; text-decoration: none;'>Go to Assignment Page</a></p>";
    echo "<p><a href='doctor/dashboard.php' style='background: green; color: white; padding: 10px; text-decoration: none;'>Go to Doctor Dashboard</a></p>";
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ Still no assignments created</strong></p>";
}

mysqli_close($connection);
?>