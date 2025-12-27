<?php
/**
 * Dashboard Query Diagnostic Script
 * 
 * This script tests all the queries used in the doctor dashboard
 * to identify which specific query is causing the error.
 * 
 * Access this file through your web browser to run the diagnostics.
 */

require_once 'config/db.php';

// Test user ID (replace with actual doctor ID for testing)
$test_user_id = 2; // Dr. Sarah Johnson from sample data

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Diagnostics - Health Alert System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard Query Diagnostics</h1>
            <p class="text-gray-600 mb-8">Testing all dashboard queries for user ID: <strong><?php echo $test_user_id; ?></strong></p>

            <?php
            // Test 1: Database connection
            echo "<div class='mb-8'>";
            echo "<h2 class='text-xl font-bold text-gray-800 mb-4'>1. Database Connection Test</h2>";
            if ($connection) {
                echo "<p class='text-green-600 font-medium'>✓ Database connection successful</p>";
            } else {
                echo "<p class='text-red-600 font-medium'>✗ Database connection failed: " . mysqli_connect_error() . "</p>";
                echo "</div></div></body></html>";
                exit();
            }
            echo "</div>";

            // Test 2: Check if tables exist
            echo "<div class='mb-8'>";
            echo "<h2 class='text-xl font-bold text-gray-800 mb-4'>2. Table Existence Check</h2>";
            $tables = ['users', 'doctor_patients', 'alerts', 'health_data'];
            foreach ($tables as $table) {
                $result = mysqli_query($connection, "SHOW TABLES LIKE '$table'");
                if (mysqli_num_rows($result) > 0) {
                    echo "<p class='text-green-600'>✓ Table '$table' exists</p>";
                } else {
                    echo "<p class='text-red-600'>✗ Table '$table' does not exist</p>";
                }
            }
            echo "</div>";

            // Test 3: Check if user exists and is a doctor
            echo "<div class='mb-8'>";
            echo "<h2 class='text-xl font-bold text-gray-800 mb-4'>3. User Validation</h2>";
            $user_check = mysqli_query($connection, "SELECT id, name, role, status FROM users WHERE id = $test_user_id");
            if ($user_check && mysqli_num_rows($user_check) > 0) {
                $user = mysqli_fetch_assoc($user_check);
                echo "<p class='text-green-600'>✓ User exists: " . htmlspecialchars($user['name']) . " (Role: " . $user['role'] . ", Status: " . $user['status'] . ")</p>";
                if ($user['role'] !== 'doctor') {
                    echo "<p class='text-orange-500'>⚠ Warning: User is not a doctor. Dashboard queries may return empty results.</p>";
                }
                if ($user['status'] !== 'active') {
                    echo "<p class='text-orange-500'>⚠ Warning: User status is '" . $user['status'] . "'. Should be 'active'.</p>";
                }
            } else {
                echo "<p class='text-red-600'>✗ User with ID $test_user_id does not exist</p>";
                echo "<p class='text-gray-600'>Available doctors:</p>";
                $doctors = mysqli_query($connection, "SELECT id, name, email, status FROM users WHERE role = 'doctor' LIMIT 5");
                if ($doctors && mysqli_num_rows($doctors) > 0) {
                    echo "<ul class='ml-4'>";
                    while ($doc = mysqli_fetch_assoc($doctors)) {
                        echo "<li class='text-gray-600'>ID: " . $doc['id'] . " - " . htmlspecialchars($doc['name']) . " (" . $doc['email'] . ") - Status: " . $doc['status'] . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='text-red-600'>No doctors found in database!</p>";
                }
            }
            echo "</div>";

            // Test 4: Individual query tests
            echo "<div class='mb-8'>";
            echo "<h2 class='text-xl font-bold text-gray-800 mb-4'>4. Individual Query Tests</h2>";

            // Query 1: Patient count
            echo "<div class='mb-4'>";
            echo "<h3 class='text-lg font-semibold text-gray-700 mb-2'>4.1 Patient Count Query</h3>";
            $patients_query = "SELECT COUNT(*) as total FROM doctor_patients WHERE doctor_id = ?";
            $patients_stmt = mysqli_prepare($connection, $patients_query);
            if ($patients_stmt) {
                mysqli_stmt_bind_param($patients_stmt, "i", $test_user_id);
                if (mysqli_stmt_execute($patients_stmt)) {
                    $patients_result = mysqli_stmt_get_result($patients_stmt);
                    $total_patients = mysqli_fetch_assoc($patients_result)['total'];
                    echo "<p class='text-green-600'>✓ Patient count query successful: $total_patients patients</p>";
                } else {
                    echo "<p class='text-red-600'>✗ Patient count query execution failed: " . mysqli_stmt_error($patients_stmt) . "</p>";
                }
                mysqli_stmt_close($patients_stmt);
            } else {
                echo "<p class='text-red-600'>✗ Patient count query preparation failed: " . mysqli_error($connection) . "</p>";
            }
            echo "</div>";

            // Query 2: Alerts count
            echo "<div class='mb-4'>";
            echo "<h3 class='text-lg font-semibold text-gray-700 mb-2'>4.2 Alerts Count Query</h3>";
            $sent_alerts_query = "SELECT COUNT(*) as total FROM alerts WHERE doctor_id = ?";
            $sent_alerts_stmt = mysqli_prepare($connection, $sent_alerts_query);
            if ($sent_alerts_stmt) {
                mysqli_stmt_bind_param($sent_alerts_stmt, "i", $test_user_id);
                if (mysqli_stmt_execute($sent_alerts_stmt)) {
                    $sent_alerts_result = mysqli_stmt_get_result($sent_alerts_stmt);
                    $total_alerts_sent = mysqli_fetch_assoc($sent_alerts_result)['total'];
                    echo "<p class='text-green-600'>✓ Alerts count query successful: $total_alerts_sent alerts</p>";
                } else {
                    echo "<p class='text-red-600'>✗ Alerts count query execution failed: " . mysqli_stmt_error($sent_alerts_stmt) . "</p>";
                }
                mysqli_stmt_close($sent_alerts_stmt);
            } else {
                echo "<p class='text-red-600'>✗ Alerts count query preparation failed: " . mysqli_error($connection) . "</p>";
            }
            echo "</div>";

            // Query 3: Unread alerts count
            echo "<div class='mb-4'>";
            echo "<h3 class='text-lg font-semibold text-gray-700 mb-2'>4.3 Unread Alerts Count Query</h3>";
            $unread_alerts_query = "SELECT COUNT(*) as total FROM alerts WHERE doctor_id = ? AND status = 'sent'";
            $unread_alerts_stmt = mysqli_prepare($connection, $unread_alerts_query);
            if ($unread_alerts_stmt) {
                mysqli_stmt_bind_param($unread_alerts_stmt, "i", $test_user_id);
                if (mysqli_stmt_execute($unread_alerts_stmt)) {
                    $unread_alerts_result = mysqli_stmt_get_result($unread_alerts_stmt);
                    $unread_alerts_count = mysqli_fetch_assoc($unread_alerts_result)['total'];
                    echo "<p class='text-green-600'>✓ Unread alerts count query successful: $unread_alerts_count unread alerts</p>";
                } else {
                    echo "<p class='text-red-600'>✗ Unread alerts count query execution failed: " . mysqli_stmt_error($unread_alerts_stmt) . "</p>";
                }
                mysqli_stmt_close($unread_alerts_stmt);
            } else {
                echo "<p class='text-red-600'>✗ Unread alerts count query preparation failed: " . mysqli_error($connection) . "</p>";
            }
            echo "</div>";

            // Query 4: Recent health data count
            echo "<div class='mb-4'>";
            echo "<h3 class='text-lg font-semibold text-gray-700 mb-2'>4.4 Recent Health Data Count Query</h3>";
            $recent_data_query = "SELECT COUNT(DISTINCT hd.patient_id) as count 
                                 FROM health_data hd 
                                 JOIN doctor_patients dp ON hd.patient_id = dp.patient_id 
                                 WHERE dp.doctor_id = ? 
                                 AND hd.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $recent_data_stmt = mysqli_prepare($connection, $recent_data_query);
            if ($recent_data_stmt) {
                mysqli_stmt_bind_param($recent_data_stmt, "i", $test_user_id);
                if (mysqli_stmt_execute($recent_data_stmt)) {
                    $recent_data_result = mysqli_stmt_get_result($recent_data_stmt);
                    $recent_data_count = mysqli_fetch_assoc($recent_data_result)['count'];
                    echo "<p class='text-green-600'>✓ Recent health data count query successful: $recent_data_count active patients today</p>";
                } else {
                    echo "<p class='text-red-600'>✗ Recent health data count query execution failed: " . mysqli_stmt_error($recent_data_stmt) . "</p>";
                }
                mysqli_stmt_close($recent_data_stmt);
            } else {
                echo "<p class='text-red-600'>✗ Recent health data count query preparation failed: " . mysqli_error($connection) . "</p>";
            }
            echo "</div>";

            // Query 5: Recent alerts
            echo "<div class='mb-4'>";
            echo "<h3 class='text-lg font-semibold text-gray-700 mb-2'>4.5 Recent Alerts Query</h3>";
            $recent_alerts_query = "SELECT a.message, a.created_at, u.name as patient_name, a.status
                                   FROM alerts a
                                   JOIN users u ON a.patient_id = u.id
                                   WHERE a.doctor_id = ?
                                   ORDER BY a.created_at DESC
                                   LIMIT 5";
            $recent_alerts_stmt = mysqli_prepare($connection, $recent_alerts_query);
            if ($recent_alerts_stmt) {
                mysqli_stmt_bind_param($recent_alerts_stmt, "i", $test_user_id);
                if (mysqli_stmt_execute($recent_alerts_stmt)) {
                    $recent_alerts_result = mysqli_stmt_get_result($recent_alerts_stmt);
                    $alert_count = mysqli_num_rows($recent_alerts_result);
                    echo "<p class='text-green-600'>✓ Recent alerts query successful: $alert_count recent alerts found</p>";
                    
                    if ($alert_count > 0) {
                        echo "<div class='mt-2 p-3 bg-gray-50 rounded'>";
                        echo "<p class='text-sm font-medium text-gray-700 mb-2'>Sample alerts:</p>";
                        while ($alert = mysqli_fetch_assoc($recent_alerts_result)) {
                            echo "<p class='text-xs text-gray-600'>• " . htmlspecialchars(substr($alert['message'], 0, 50)) . "... (Status: " . $alert['status'] . ")</p>";
                        }
                        echo "</div>";
                    }
                } else {
                    echo "<p class='text-red-600'>✗ Recent alerts query execution failed: " . mysqli_stmt_error($recent_alerts_stmt) . "</p>";
                }
                mysqli_stmt_close($recent_alerts_stmt);
            } else {
                echo "<p class='text-red-600'>✗ Recent alerts query preparation failed: " . mysqli_error($connection) . "</p>";
            }
            echo "</div>";
            echo "</div>";

            // Test 6: Check data relationships
            echo "<div class='mb-8'>";
            echo "<h2 class='text-xl font-bold text-gray-800 mb-4'>5. Data Relationship Check</h2>";
            
            // Check doctor-patient assignments for this doctor
            $assignments_query = "SELECT dp.*, u.name as patient_name, u.email 
                                 FROM doctor_patients dp 
                                 JOIN users u ON dp.patient_id = u.id 
                                 WHERE dp.doctor_id = $test_user_id";
            $assignments_result = mysqli_query($connection, $assignments_query);
            
            if ($assignments_result) {
                $assignment_count = mysqli_num_rows($assignments_result);
                echo "<p class='text-green-600'>✓ Found $assignment_count patient assignments for this doctor</p>";
                
                if ($assignment_count > 0) {
                    echo "<div class='mt-2 p-3 bg-gray-50 rounded'>";
                    echo "<p class='text-sm font-medium text-gray-700 mb-2'>Assigned patients:</p>";
                    while ($assignment = mysqli_fetch_assoc($assignments_result)) {
                        echo "<p class='text-xs text-gray-600'>• " . htmlspecialchars($assignment['patient_name']) . " (" . $assignment['email'] . ")</p>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<p class='text-red-600'>✗ Failed to check assignments: " . mysqli_error($connection) . "</p>";
            }
            echo "</div>";

            echo "<div class='bg-blue-50 border border-blue-200 rounded-lg p-6'>";
            echo "<h2 class='text-xl font-bold text-blue-800 mb-4'>Summary</h2>";
            echo "<p class='text-blue-700 mb-2'>If all tests show green checkmarks, the dashboard should work properly.</p>";
            echo "<p class='text-blue-700 mb-4'>If any tests show red X marks, those are the issues that need to be fixed.</p>";
            echo "<div class='bg-white rounded p-4'>";
            echo "<p class='font-medium text-gray-800 mb-2'>Next Steps:</p>";
            echo "<ol class='list-decimal list-inside text-sm text-gray-600 space-y-1'>";
            echo "<li>If you see database connection errors, check your XAMPP MySQL service</li>";
            echo "<li>If tables are missing, run the database_setup.sql script</li>";
            echo "<li>If the test user doesn't exist, run the fix_dashboard_database.php script</li>";
            echo "<li>If queries fail, check the error messages for specific issues</li>";
            echo "</ol>";
            echo "</div>";
            echo "</div>";

            mysqli_close($connection);
            ?>
        </div>
    </div>
</body>
</html>