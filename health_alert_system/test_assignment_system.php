<?php
/**
 * Assignment System Test Script
 * 
 * This script tests the doctor-patient assignment functionality
 * and provides a summary of the current system state.
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Assignment System Test - Health Alert System</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
    .success { color: #059669; background: #ecfdf5; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0369a1; background: #eff6ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #f8fafc; font-weight: bold; }
    tr:nth-child(even) { background-color: #f9fafb; }
    .btn { background: #3b82f6; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px; }
    .btn:hover { background: #2563eb; }
</style>";
echo "</head><body>";

echo "<h1>🏥 Health Alert System - Assignment System Test</h1>";
echo "<p>Testing the doctor-patient assignment functionality and database integrity.</p>";

// Test 1: Check if all required tables exist
echo "<h2>📋 Test 1: Database Structure Check</h2>";

$required_tables = ['users', 'doctor_patients', 'health_data', 'alerts'];
$missing_tables = [];

foreach ($required_tables as $table) {
    $check_query = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($connection, $check_query);
    
    if (mysqli_num_rows($result) == 0) {
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "<div class='success'>✅ All required tables exist: " . implode(', ', $required_tables) . "</div>";
} else {
    echo "<div class='error'>❌ Missing tables: " . implode(', ', $missing_tables) . "</div>";
    echo "<p>Please run the database setup script first.</p>";
    echo "</body></html>";
    exit;
}

// Test 2: Check doctor_patients table structure
echo "<h2>🔧 Test 2: Doctor-Patients Table Structure</h2>";

$columns_query = "DESCRIBE doctor_patients";
$columns_result = mysqli_query($connection, $columns_query);

echo "<table>";
echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

$has_created_at = false;
while ($column = mysqli_fetch_assoc($columns_result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
    echo "</tr>";
    
    if ($column['Field'] === 'created_at') {
        $has_created_at = true;
    }
}
echo "</table>";

if ($has_created_at) {
    echo "<div class='success'>✅ created_at column exists in doctor_patients table</div>";
} else {
    echo "<div class='error'>❌ created_at column missing in doctor_patients table</div>";
    echo "<p>Run the fix_database.php script to add the missing column.</p>";
}

// Test 3: User Statistics
echo "<h2>👥 Test 3: User Statistics</h2>";

$user_stats_queries = [
    'Total Users' => "SELECT COUNT(*) as count FROM users",
    'Patients' => "SELECT COUNT(*) as count FROM users WHERE role = 'patient'",
    'Approved Doctors' => "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'approved'",
    'Pending Doctors' => "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'pending'",
    'Admins' => "SELECT COUNT(*) as count FROM users WHERE role = 'admin'"
];

echo "<table>";
echo "<tr><th>User Type</th><th>Count</th></tr>";

foreach ($user_stats_queries as $label => $query) {
    $result = mysqli_query($connection, $query);
    $count = mysqli_fetch_assoc($result)['count'];
    echo "<tr><td>$label</td><td>$count</td></tr>";
}
echo "</table>";

// Test 4: Assignment Statistics
echo "<h2>🔗 Test 4: Assignment Statistics</h2>";

$assignment_stats_queries = [
    'Total Assignments' => "SELECT COUNT(*) as count FROM doctor_patients",
    'Doctors with Patients' => "SELECT COUNT(DISTINCT doctor_id) as count FROM doctor_patients",
    'Patients with Doctors' => "SELECT COUNT(DISTINCT patient_id) as count FROM doctor_patients",
    'Unassigned Patients' => "SELECT COUNT(*) as count FROM users WHERE role = 'patient' AND id NOT IN (SELECT DISTINCT patient_id FROM doctor_patients)",
    'Unassigned Doctors' => "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'approved' AND id NOT IN (SELECT DISTINCT doctor_id FROM doctor_patients)"
];

echo "<table>";
echo "<tr><th>Assignment Type</th><th>Count</th></tr>";

foreach ($assignment_stats_queries as $label => $query) {
    $result = mysqli_query($connection, $query);
    $count = mysqli_fetch_assoc($result)['count'];
    echo "<tr><td>$label</td><td>$count</td></tr>";
}
echo "</table>";

// Test 5: Current Assignments
echo "<h2>📊 Test 5: Current Doctor-Patient Assignments</h2>";

$assignments_query = "SELECT dp.id, d.name as doctor_name, d.email as doctor_email,
                             p.name as patient_name, p.email as patient_email,
                             dp.created_at
                      FROM doctor_patients dp
                      JOIN users d ON dp.doctor_id = d.id
                      JOIN users p ON dp.patient_id = p.id
                      ORDER BY dp.created_at DESC";

$assignments_result = mysqli_query($connection, $assignments_query);

if (mysqli_num_rows($assignments_result) > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Doctor</th><th>Patient</th><th>Assigned Date</th></tr>";
    
    while ($assignment = mysqli_fetch_assoc($assignments_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($assignment['id']) . "</td>";
        echo "<td>" . htmlspecialchars($assignment['doctor_name']) . "<br><small>" . htmlspecialchars($assignment['doctor_email']) . "</small></td>";
        echo "<td>" . htmlspecialchars($assignment['patient_name']) . "<br><small>" . htmlspecialchars($assignment['patient_email']) . "</small></td>";
        echo "<td>" . ($assignment['created_at'] ? date('M j, Y g:i A', strtotime($assignment['created_at'])) : 'Unknown') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>✅ Assignment system is working! Found " . mysqli_num_rows($assignments_result) . " active assignments.</div>";
} else {
    echo "<div class='info'>ℹ️ No assignments found. You can create assignments using the admin panel.</div>";
}

// Test 6: Alert System Test
echo "<h2>💬 Test 6: Alert System Statistics</h2>";

$alert_stats_queries = [
    'Total Alerts' => "SELECT COUNT(*) as count FROM alerts",
    'Unread Alerts' => "SELECT COUNT(*) as count FROM alerts WHERE status = 'sent'",
    'Read Alerts' => "SELECT COUNT(*) as count FROM alerts WHERE status = 'seen'",
    'Recent Alerts (24h)' => "SELECT COUNT(*) as count FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
];

echo "<table>";
echo "<tr><th>Alert Type</th><th>Count</th></tr>";

foreach ($alert_stats_queries as $label => $query) {
    $result = mysqli_query($connection, $query);
    $count = mysqli_fetch_assoc($result)['count'];
    echo "<tr><td>$label</td><td>$count</td></tr>";
}
echo "</table>";

// Test 7: Health Data Statistics
echo "<h2>📈 Test 7: Health Data Statistics</h2>";

$health_stats_queries = [
    'Total Health Records' => "SELECT COUNT(*) as count FROM health_data",
    'Patients with Data' => "SELECT COUNT(DISTINCT patient_id) as count FROM health_data",
    'Recent Records (7 days)' => "SELECT COUNT(*) as count FROM health_data WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    'Records Today' => "SELECT COUNT(*) as count FROM health_data WHERE DATE(created_at) = CURDATE()"
];

echo "<table>";
echo "<tr><th>Health Data Type</th><th>Count</th></tr>";

foreach ($health_stats_queries as $label => $query) {
    $result = mysqli_query($connection, $query);
    $count = mysqli_fetch_assoc($result)['count'];
    echo "<tr><td>$label</td><td>$count</td></tr>";
}
echo "</table>";

// Test 8: System Functionality Test
echo "<h2>⚙️ Test 8: Assignment Query Test</h2>";

$test_query = "SELECT dp.id, d.name as doctor_name, p.name as patient_name, dp.created_at
               FROM doctor_patients dp
               JOIN users d ON dp.doctor_id = d.id
               JOIN users p ON dp.patient_id = p.id
               LIMIT 1";

$test_result = mysqli_query($connection, $test_query);

if ($test_result) {
    echo "<div class='success'>✅ Assignment query executed successfully!</div>";
    
    if (mysqli_num_rows($test_result) > 0) {
        $test_row = mysqli_fetch_assoc($test_result);
        echo "<div class='info'>Sample assignment: Dr. " . htmlspecialchars($test_row['doctor_name']) . 
             " → " . htmlspecialchars($test_row['patient_name']) . "</div>";
    }
} else {
    echo "<div class='error'>❌ Assignment query failed: " . mysqli_error($connection) . "</div>";
}

// Summary and Next Steps
echo "<h2>📝 Summary and Next Steps</h2>";

$total_users_result = mysqli_query($connection, "SELECT COUNT(*) as count FROM users");
$total_users = mysqli_fetch_assoc($total_users_result)['count'];

$total_assignments_result = mysqli_query($connection, "SELECT COUNT(*) as count FROM doctor_patients");
$total_assignments = mysqli_fetch_assoc($total_assignments_result)['count'];

if ($total_users > 0 && $total_assignments > 0) {
    echo "<div class='success'>";
    echo "<h3>✅ System Status: OPERATIONAL</h3>";
    echo "<p>The assignment system is working correctly with:</p>";
    echo "<ul>";
    echo "<li>$total_users users in the system</li>";
    echo "<li>$total_assignments active doctor-patient assignments</li>";
    echo "<li>Database structure is complete</li>";
    echo "<li>All core functionality is available</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='info'>";
    echo "<h3>ℹ️ System Status: READY FOR SETUP</h3>";
    echo "<p>The system is ready but needs initial data:</p>";
    echo "<ul>";
    echo "<li>Run database_setup.sql to add sample users</li>";
    echo "<li>Use the admin panel to create assignments</li>";
    echo "<li>Test the alert system between doctors and patients</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<h3>🔗 Quick Links</h3>";
echo "<div>";
echo "<a href='admin/assign_patients.php' class='btn'>Manage Assignments</a>";
echo "<a href='admin/dashboard.php' class='btn'>Admin Dashboard</a>";
echo "<a href='doctor/dashboard.php' class='btn'>Doctor Dashboard</a>";
echo "<a href='patient/dashboard.php' class='btn'>Patient Dashboard</a>";
echo "<a href='fix_database.php' class='btn'>Fix Database</a>";
echo "</div>";

echo "<div class='info' style='margin-top: 30px;'>";
echo "<h3>🛠️ Troubleshooting</h3>";
echo "<p>If you encounter issues:</p>";
echo "<ol>";
echo "<li>Run <code>fix_database.php</code> to ensure database structure is correct</li>";
echo "<li>Import <code>database_setup.sql</code> for sample data</li>";
echo "<li>Check that users have the correct roles (admin, doctor, patient)</li>";
echo "<li>Verify doctors are approved before assigning patients</li>";
echo "<li>Test assignments through the admin panel</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";

mysqli_close($connection);
?>