<?php
/**
 * DATABASE DIAGNOSTIC SCRIPT
 * This script will check the database structure and identify any issues
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Diagnostic - Health Alert System</title>";
echo "<style>
    body{font-family:Arial,sans-serif;max-width:1000px;margin:30px auto;padding:20px;background:#f8f9fa;}
    .success{color:#155724;background:#d4edda;border:1px solid #c3e6cb;padding:12px;border-radius:5px;margin:10px 0;}
    .error{color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;padding:12px;border-radius:5px;margin:10px 0;}
    .warning{color:#856404;background:#fff3cd;border:1px solid #ffeaa7;padding:12px;border-radius:5px;margin:10px 0;}
    .info{color:#0c5460;background:#d1ecf1;border:1px solid #bee5eb;padding:12px;border-radius:5px;margin:10px 0;}
    table{width:100%;border-collapse:collapse;margin:15px 0;background:white;}
    th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px;}
    th{background:#f8f9fa;font-weight:bold;}
    .btn{background:#007bff;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;}
    .btn-success{background:#28a745;} .btn-danger{background:#dc3545;}
    h1,h2,h3{color:#333;}
</style>";
echo "</head><body>";

echo "<h1>🔍 Database Diagnostic Report</h1>";
echo "<p>Comprehensive analysis of the Health Alert System database</p>";

// Step 1: Check database connection
if (!$connection) {
    echo "<div class='error'>❌ Database connection failed: " . mysqli_connect_error() . "</div>";
    echo "</body></html>";
    exit;
}
echo "<div class='success'>✅ Database connected successfully to: health_alert_system</div>";

// Step 2: Check if all required tables exist
echo "<h2>📋 Table Structure Analysis</h2>";

$required_tables = ['users', 'doctor_patients', 'health_data', 'alerts'];
$existing_tables = [];

$tables_query = "SHOW TABLES";
$tables_result = mysqli_query($connection, $tables_query);

echo "<h3>Existing Tables:</h3>";
echo "<table>";
echo "<tr><th>Table Name</th><th>Status</th></tr>";

while ($table = mysqli_fetch_array($tables_result)) {
    $table_name = $table[0];
    $existing_tables[] = $table_name;
    $status = in_array($table_name, $required_tables) ? '✅ Required' : '⚠️ Extra';
    echo "<tr><td>$table_name</td><td>$status</td></tr>";
}
echo "</table>";

// Check for missing tables
$missing_tables = array_diff($required_tables, $existing_tables);
if (!empty($missing_tables)) {
    echo "<div class='error'>❌ Missing tables: " . implode(', ', $missing_tables) . "</div>";
} else {
    echo "<div class='success'>✅ All required tables exist</div>";
}

// Step 3: Analyze table structures
echo "<h2>🏗️ Table Structure Details</h2>";

foreach ($required_tables as $table) {
    if (in_array($table, $existing_tables)) {
        echo "<h3>Table: $table</h3>";
        
        $structure_query = "DESCRIBE $table";
        $structure_result = mysqli_query($connection, $structure_query);
        
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($field = mysqli_fetch_assoc($structure_result)) {
            echo "<tr>";
            echo "<td>{$field['Field']}</td>";
            echo "<td>{$field['Type']}</td>";
            echo "<td>{$field['Null']}</td>";
            echo "<td>{$field['Key']}</td>";
            echo "<td>{$field['Default']}</td>";
            echo "<td>{$field['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Get row count
        $count_query = "SELECT COUNT(*) as count FROM $table";
        $count_result = mysqli_query($connection, $count_query);
        $count = mysqli_fetch_assoc($count_result)['count'];
        echo "<div class='info'>📊 Total records: $count</div>";
    }
}

// Step 4: Check users table data
echo "<h2>👥 Users Analysis</h2>";

$users_by_role_query = "SELECT role, status, COUNT(*) as count FROM users GROUP BY role, status";
$users_by_role_result = mysqli_query($connection, $users_by_role_query);

echo "<h3>Users by Role and Status:</h3>";
echo "<table>";
echo "<tr><th>Role</th><th>Status</th><th>Count</th></tr>";

while ($user_stat = mysqli_fetch_assoc($users_by_role_result)) {
    echo "<tr>";
    echo "<td>" . ucfirst($user_stat['role']) . "</td>";
    echo "<td>" . ucfirst($user_stat['status']) . "</td>";
    echo "<td>{$user_stat['count']}</td>";
    echo "</tr>";
}
echo "</table>";

// Show sample users
echo "<h3>Sample Users:</h3>";
$sample_users_query = "SELECT id, name, email, role, status FROM users ORDER BY role, id LIMIT 10";
$sample_users_result = mysqli_query($connection, $sample_users_query);

echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";

while ($user = mysqli_fetch_assoc($sample_users_result)) {
    $status_color = $user['status'] == 'approved' ? 'color: green;' : 'color: orange;';
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>" . htmlspecialchars($user['name']) . "</td>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . ucfirst($user['role']) . "</td>";
    echo "<td style='$status_color'>" . ucfirst($user['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 5: Check doctor_patients assignments
echo "<h2>🔗 Assignment Analysis</h2>";

$assignments_count_query = "SELECT COUNT(*) as count FROM doctor_patients";
$assignments_count_result = mysqli_query($connection, $assignments_count_query);
$assignments_count = mysqli_fetch_assoc($assignments_count_result)['count'];

echo "<div class='info'>📊 Total assignments: $assignments_count</div>";

if ($assignments_count > 0) {
    // Show assignment details
    $assignments_query = "SELECT dp.id, d.name as doctor_name, p.name as patient_name, dp.created_at
                         FROM doctor_patients dp
                         JOIN users d ON dp.doctor_id = d.id
                         JOIN users p ON dp.patient_id = p.id
                         ORDER BY dp.created_at DESC
                         LIMIT 10";
    $assignments_result = mysqli_query($connection, $assignments_query);
    
    echo "<h3>Current Assignments:</h3>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Doctor</th><th>Patient</th><th>Created</th></tr>";
    
    while ($assignment = mysqli_fetch_assoc($assignments_result)) {
        echo "<tr>";
        echo "<td>{$assignment['id']}</td>";
        echo "<td>" . htmlspecialchars($assignment['doctor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($assignment['patient_name']) . "</td>";
        echo "<td>" . ($assignment['created_at'] ? date('M j, Y g:i A', strtotime($assignment['created_at'])) : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Assignment statistics
    $doctor_assignment_stats = "SELECT d.name, COUNT(dp.patient_id) as patient_count
                               FROM users d
                               LEFT JOIN doctor_patients dp ON d.id = dp.doctor_id
                               WHERE d.role = 'doctor' AND d.status = 'approved'
                               GROUP BY d.id, d.name
                               ORDER BY patient_count DESC";
    $doctor_stats_result = mysqli_query($connection, $doctor_assignment_stats);
    
    echo "<h3>Doctor Assignment Statistics:</h3>";
    echo "<table>";
    echo "<tr><th>Doctor</th><th>Assigned Patients</th></tr>";
    
    while ($stat = mysqli_fetch_assoc($doctor_stats_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($stat['name']) . "</td>";
        echo "<td>{$stat['patient_count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<div class='warning'>⚠️ No assignments found in database</div>";
}

// Step 6: Check health_data
echo "<h2>📊 Health Data Analysis</h2>";

$health_data_count_query = "SELECT COUNT(*) as count FROM health_data";
$health_data_count_result = mysqli_query($connection, $health_data_count_query);
$health_data_count = mysqli_fetch_assoc($health_data_count_result)['count'];

echo "<div class='info'>📊 Total health records: $health_data_count</div>";

if ($health_data_count > 0) {
    $recent_health_query = "SELECT hd.*, u.name as patient_name 
                           FROM health_data hd
                           JOIN users u ON hd.patient_id = u.id
                           ORDER BY hd.created_at DESC
                           LIMIT 5";
    $recent_health_result = mysqli_query($connection, $recent_health_query);
    
    echo "<h3>Recent Health Records:</h3>";
    echo "<table>";
    echo "<tr><th>Patient</th><th>BP</th><th>Sugar</th><th>Heart Rate</th><th>Date</th></tr>";
    
    while ($health = mysqli_fetch_assoc($recent_health_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($health['patient_name']) . "</td>";
        echo "<td>{$health['systolic_bp']}/{$health['diastolic_bp']}</td>";
        echo "<td>{$health['sugar_level']}</td>";
        echo "<td>{$health['heart_rate']}</td>";
        echo "<td>" . date('M j, Y g:i A', strtotime($health['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 7: Check alerts
echo "<h2>📢 Alerts Analysis</h2>";

$alerts_count_query = "SELECT COUNT(*) as count FROM alerts";
$alerts_count_result = mysqli_query($connection, $alerts_count_query);
$alerts_count = mysqli_fetch_assoc($alerts_count_result)['count'];

echo "<div class='info'>📊 Total alerts: $alerts_count</div>";

if ($alerts_count > 0) {
    $recent_alerts_query = "SELECT a.*, d.name as doctor_name, p.name as patient_name
                           FROM alerts a
                           JOIN users d ON a.doctor_id = d.id
                           JOIN users p ON a.patient_id = p.id
                           ORDER BY a.created_at DESC
                           LIMIT 5";
    $recent_alerts_result = mysqli_query($connection, $recent_alerts_query);
    
    echo "<h3>Recent Alerts:</h3>";
    echo "<table>";
    echo "<tr><th>Doctor</th><th>Patient</th><th>Message</th><th>Status</th><th>Date</th></tr>";
    
    while ($alert = mysqli_fetch_assoc($recent_alerts_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($alert['doctor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($alert['patient_name']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($alert['message'], 0, 50)) . "...</td>";
        echo "<td>" . ucfirst($alert['status']) . "</td>";
        echo "<td>" . date('M j, Y g:i A', strtotime($alert['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 8: System Health Summary
echo "<h2>🏥 System Health Summary</h2>";

$approved_doctors = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'approved'"))['count'];
$total_patients = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'patient'"))['count'];
$total_assignments = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM doctor_patients"))['count'];

echo "<table>";
echo "<tr><th>Metric</th><th>Value</th><th>Status</th></tr>";
echo "<tr><td>Approved Doctors</td><td>$approved_doctors</td><td>" . ($approved_doctors > 0 ? '✅ Good' : '❌ Need doctors') . "</td></tr>";
echo "<tr><td>Total Patients</td><td>$total_patients</td><td>" . ($total_patients > 0 ? '✅ Good' : '❌ Need patients') . "</td></tr>";
echo "<tr><td>Active Assignments</td><td>$total_assignments</td><td>" . ($total_assignments > 0 ? '✅ Good' : '❌ Need assignments') . "</td></tr>";
echo "<tr><td>Health Records</td><td>$health_data_count</td><td>" . ($health_data_count > 0 ? '✅ Good' : '⚠️ No data yet') . "</td></tr>";
echo "<tr><td>Alerts Sent</td><td>$alerts_count</td><td>" . ($alerts_count > 0 ? '✅ Good' : '⚠️ No alerts yet') . "</td></tr>";
echo "</table>";

// Recommendations
echo "<h2>💡 Recommendations</h2>";

if ($approved_doctors == 0) {
    echo "<div class='error'>❌ No approved doctors found. Create doctor accounts and approve them.</div>";
}

if ($total_patients == 0) {
    echo "<div class='error'>❌ No patients found. Create patient accounts.</div>";
}

if ($total_assignments == 0) {
    echo "<div class='error'>❌ No assignments found. This is the main issue preventing the system from working.</div>";
    echo "<div class='warning'>🔧 Run the assignment creation scripts to fix this issue.</div>";
}

if ($approved_doctors > 0 && $total_patients > 0 && $total_assignments == 0) {
    echo "<div class='warning'>⚠️ System has users but no assignments. This suggests an issue with the assignment creation process.</div>";
}

echo "<h2>🔧 Quick Actions</h2>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='test_assignment_creation.php' class='btn btn-success'>🔧 Create Test Assignments</a>";
echo "<a href='test_form_submission.php' class='btn'>🧪 Test Form Logic</a>";
echo "<a href='admin/assign_patients.php' class='btn'>👥 Assignment Page</a>";
echo "<a href='emergency_fix.php' class='btn btn-danger'>🚨 Emergency Fix</a>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔍 Diagnostic Complete</h3>";
echo "<p>This report shows the current state of your Health Alert System database. Use the recommendations above to fix any issues.</p>";
echo "</div>";

echo "</body></html>";

mysqli_close($connection);
?>