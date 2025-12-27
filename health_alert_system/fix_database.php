<?php
/**
 * Database Fix Script
 * This script checks and fixes common database issues and ensures sample data exists
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Fix - Health Alert System</title>";
echo "<style>
    body{font-family:Arial,sans-serif;max-width:900px;margin:30px auto;padding:20px;background:#f8f9fa;}
    .success{color:#155724;background:#d4edda;border:1px solid #c3e6cb;padding:12px;border-radius:5px;margin:10px 0;}
    .error{color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;padding:12px;border-radius:5px;margin:10px 0;}
    .warning{color:#856404;background:#fff3cd;border:1px solid #ffeaa7;padding:12px;border-radius:5px;margin:10px 0;}
    .info{color:#0c5460;background:#d1ecf1;border:1px solid #bee5eb;padding:12px;border-radius:5px;margin:10px 0;}
    table{width:100%;border-collapse:collapse;margin:15px 0;background:white;}
    th,td{border:1px solid #ddd;padding:10px;text-align:left;}
    th{background:#f8f9fa;font-weight:bold;}
    .btn{background:#007bff;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;}
    .btn:hover{background:#0056b3;}
    .btn-success{background:#28a745;} .btn-success:hover{background:#1e7e34;}
    .btn-warning{background:#ffc107;color:#212529;} .btn-warning:hover{background:#e0a800;}
</style>";
echo "</head><body>";

echo "<h1>🏥 Health Alert System - Database Fix & Setup</h1>";
echo "<p>Checking and fixing database structure, ensuring sample data exists...</p>";

// Step 1: Check if all required tables exist
echo "<h2>📋 Step 1: Database Structure Check</h2>";

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
    echo "<div class='warning'>";
    echo "<h3>⚠️ Database Setup Required</h3>";
    echo "<p>Some required tables are missing. Please:</p>";
    echo "<ol>";
    echo "<li>Go to phpMyAdmin or your MySQL interface</li>";
    echo "<li>Select the 'health_alert_system' database</li>";
    echo "<li>Import the <strong>database_setup.sql</strong> file</li>";
    echo "<li>Or run the SQL commands from that file manually</li>";
    echo "</ol>";
    echo "<p><a href='database_setup.sql' class='btn btn-warning'>📄 View database_setup.sql</a></p>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// Step 2: Check doctor_patients table structure
echo "<h2>🔧 Step 2: Doctor-Patients Table Structure</h2>";

// Check if created_at column exists in doctor_patients table
$check_column_query = "SHOW COLUMNS FROM doctor_patients LIKE 'created_at'";
$result = mysqli_query($connection, $check_column_query);

if (mysqli_num_rows($result) == 0) {
    echo "<div class='warning'>⚠️ Missing 'created_at' column in doctor_patients table. Adding it...</div>";
    
    // Add the missing column
    $add_column_query = "ALTER TABLE doctor_patients ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    
    if (mysqli_query($connection, $add_column_query)) {
        echo "<div class='success'>✅ Successfully added 'created_at' column to doctor_patients table.</div>";
    } else {
        echo "<div class='error'>❌ Error adding column: " . mysqli_error($connection) . "</div>";
    }
} else {
    echo "<div class='success'>✅ 'created_at' column exists in doctor_patients table.</div>";
}

// Check if there are any doctor_patients records without created_at values
$check_null_query = "SELECT COUNT(*) as count FROM doctor_patients WHERE created_at IS NULL";
$null_result = mysqli_query($connection, $check_null_query);

if ($null_result) {
    $null_count = mysqli_fetch_assoc($null_result)['count'];

    if ($null_count > 0) {
        echo "<div class='warning'>⚠️ Found {$null_count} records with NULL created_at values. Updating...</div>";
        
        // Update NULL values with current timestamp
        $update_null_query = "UPDATE doctor_patients SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL";
        
        if (mysqli_query($connection, $update_null_query)) {
            echo "<div class='success'>✅ Successfully updated {$null_count} records with current timestamp.</div>";
        } else {
            echo "<div class='error'>❌ Error updating records: " . mysqli_error($connection) . "</div>";
        }
    } else {
        echo "<div class='success'>✅ All doctor_patients records have valid created_at values.</div>";
    }
}

// Step 3: Check and create sample users if needed
echo "<h2>👥 Step 3: User Data Check & Creation</h2>";

$user_count_query = "SELECT COUNT(*) as count FROM users";
$user_result = mysqli_query($connection, $user_count_query);
$user_count = mysqli_fetch_assoc($user_result)['count'];

echo "<div class='info'>Current users in system: <strong>{$user_count}</strong></div>";

// Get detailed user counts by role
$role_counts_query = "SELECT role, status, COUNT(*) as count FROM users GROUP BY role, status ORDER BY role, status";
$role_counts_result = mysqli_query($connection, $role_counts_query);

echo "<table>";
echo "<tr><th>Role</th><th>Status</th><th>Count</th></tr>";
while ($role_count = mysqli_fetch_assoc($role_counts_result)) {
    echo "<tr>";
    echo "<td>" . ucfirst($role_count['role']) . "</td>";
    echo "<td>" . ucfirst($role_count['status']) . "</td>";
    echo "<td>" . $role_count['count'] . "</td>";
    echo "</tr>";
}
echo "</table>";

if ($user_count < 8) {
    echo "<div class='warning'>⚠️ Insufficient sample data. Creating essential users...</div>";
    
    // Create admin if doesn't exist
    $admin_check = mysqli_query($connection, "SELECT id FROM users WHERE email = 'admin@healthalert.com'");
    if (mysqli_num_rows($admin_check) == 0) {
        $admin_query = "INSERT INTO users (name, email, password, role, status) VALUES 
                       ('System Administrator', 'admin@healthalert.com', 'admin123', 'admin', 'approved')";
        if (mysqli_query($connection, $admin_query)) {
            echo "<div class='success'>✅ Created admin user: <strong>admin@healthalert.com</strong> / admin123</div>";
        } else {
            echo "<div class='error'>❌ Error creating admin: " . mysqli_error($connection) . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Admin user already exists: admin@healthalert.com</div>";
    }
    
    // Create doctors if needed
    $doctor_check = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'approved'");
    $doctor_count = mysqli_fetch_assoc($doctor_check)['count'];
    
    if ($doctor_count < 3) {
        echo "<div class='info'>Creating sample doctors...</div>";
        $doctors = [
            ['Dr. Sarah Johnson', 'sarah.johnson@hospital.com', 'doctor123'],
            ['Dr. Michael Chen', 'michael.chen@clinic.com', 'doctor123'],
            ['Dr. Emily Rodriguez', 'emily.rodriguez@medical.com', 'doctor123']
        ];
        
        foreach ($doctors as $doctor) {
            $check_existing = mysqli_query($connection, "SELECT id FROM users WHERE email = '{$doctor[1]}'");
            if (mysqli_num_rows($check_existing) == 0) {
                $doctor_query = "INSERT INTO users (name, email, password, role, status) VALUES 
                               ('{$doctor[0]}', '{$doctor[1]}', '{$doctor[2]}', 'doctor', 'approved')";
                if (mysqli_query($connection, $doctor_query)) {
                    echo "<div class='success'>✅ Created doctor: <strong>{$doctor[1]}</strong> / {$doctor[2]}</div>";
                } else {
                    echo "<div class='error'>❌ Error creating doctor {$doctor[0]}: " . mysqli_error($connection) . "</div>";
                }
            } else {
                echo "<div class='info'>ℹ️ Doctor already exists: {$doctor[1]}</div>";
            }
        }
    } else {
        echo "<div class='info'>ℹ️ Sufficient approved doctors exist: {$doctor_count}</div>";
    }
    
    // Create patients if needed
    $patient_check = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'patient'");
    $patient_count = mysqli_fetch_assoc($patient_check)['count'];
    
    if ($patient_count < 5) {
        echo "<div class='info'>Creating sample patients...</div>";
        $patients = [
            ['John Smith', 'john.smith@email.com', 'patient123'],
            ['Mary Johnson', 'mary.johnson@email.com', 'patient123'],
            ['Robert Brown', 'robert.brown@email.com', 'patient123'],
            ['Jennifer Davis', 'jennifer.davis@email.com', 'patient123'],
            ['William Miller', 'william.miller@email.com', 'patient123']
        ];
        
        foreach ($patients as $patient) {
            $check_existing = mysqli_query($connection, "SELECT id FROM users WHERE email = '{$patient[1]}'");
            if (mysqli_num_rows($check_existing) == 0) {
                $patient_query = "INSERT INTO users (name, email, password, role, status) VALUES 
                                ('{$patient[0]}', '{$patient[1]}', '{$patient[2]}', 'patient', 'approved')";
                if (mysqli_query($connection, $patient_query)) {
                    echo "<div class='success'>✅ Created patient: <strong>{$patient[1]}</strong> / {$patient[2]}</div>";
                } else {
                    echo "<div class='error'>❌ Error creating patient {$patient[0]}: " . mysqli_error($connection) . "</div>";
                }
            } else {
                echo "<div class='info'>ℹ️ Patient already exists: {$patient[1]}</div>";
            }
        }
    } else {
        echo "<div class='info'>ℹ️ Sufficient patients exist: {$patient_count}</div>";
    }
    
    // Recount users after creation
    $user_result = mysqli_query($connection, $user_count_query);
    $user_count = mysqli_fetch_assoc($user_result)['count'];
    echo "<div class='success'>Updated total user count: <strong>{$user_count}</strong></div>";
} else {
    echo "<div class='success'>✅ Sufficient user data exists ({$user_count} users)</div>";
}

// Step 4: Test assignment functionality
echo "<h2>⚙️ Step 4: Assignment System Test</h2>";

// Test the assignment query that was failing
$test_query = "SELECT dp.id,
                      d.name as doctor_name, d.email as doctor_email,
                      p.name as patient_name, p.email as patient_email,
                      COUNT(hd.id) as health_records,
                      COUNT(a.id) as alerts_sent,
                      dp.created_at
               FROM doctor_patients dp
               JOIN users d ON dp.doctor_id = d.id
               JOIN users p ON dp.patient_id = p.id
               LEFT JOIN health_data hd ON p.id = hd.patient_id
               LEFT JOIN alerts a ON dp.doctor_id = a.doctor_id AND dp.patient_id = a.patient_id
               GROUP BY dp.id, dp.created_at, d.name, d.email, p.name, p.email
               ORDER BY dp.id DESC
               LIMIT 5";

$test_result = mysqli_query($connection, $test_query);

if ($test_result) {
    $row_count = mysqli_num_rows($test_result);
    echo "<div class='success'>✅ Assignment query executed successfully! Found {$row_count} assignments.</div>";
    
    if ($row_count > 0) {
        echo "<h4>Current assignments:</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Doctor</th><th>Patient</th><th>Health Records</th><th>Alerts</th><th>Created</th></tr>";
        
        while ($row = mysqli_fetch_assoc($test_result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['doctor_name']) . "<br><small>" . htmlspecialchars($row['doctor_email']) . "</small></td>";
            echo "<td>" . htmlspecialchars($row['patient_name']) . "<br><small>" . htmlspecialchars($row['patient_email']) . "</small></td>";
            echo "<td>" . $row['health_records'] . "</td>";
            echo "<td>" . $row['alerts_sent'] . "</td>";
            echo "<td>" . ($row['created_at'] ? date('M j, Y g:i A', strtotime($row['created_at'])) : 'Unknown') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ No assignments found yet. You can create assignments using the admin panel.</div>";
    }
} else {
    echo "<div class='error'>❌ Assignment query failed: " . mysqli_error($connection) . "</div>";
}

// Step 5: Test dropdown data availability
echo "<h2>📋 Step 5: Dropdown Data Availability Test</h2>";

// Test doctors dropdown
$doctors_query = "SELECT id, name, email FROM users WHERE role = 'doctor' AND status = 'approved' ORDER BY name";
$doctors_result = mysqli_query($connection, $doctors_query);
$doctors_count = mysqli_num_rows($doctors_result);

echo "<div class='info'><strong>Available Approved Doctors:</strong> {$doctors_count}</div>";
if ($doctors_count > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    while ($doctor = mysqli_fetch_assoc($doctors_result)) {
        echo "<tr>";
        echo "<td>" . $doctor['id'] . "</td>";
        echo "<td>" . htmlspecialchars($doctor['name']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['email']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No approved doctors found! This will cause empty dropdowns.</div>";
}

// Test patients dropdown
$patients_query = "SELECT id, name, email FROM users WHERE role = 'patient' ORDER BY name";
$patients_result = mysqli_query($connection, $patients_query);
$patients_count = mysqli_num_rows($patients_result);

echo "<div class='info'><strong>Available Patients:</strong> {$patients_count}</div>";
if ($patients_count > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    $count = 0;
    while ($patient = mysqli_fetch_assoc($patients_result) && $count < 5) {
        echo "<tr>";
        echo "<td>" . $patient['id'] . "</td>";
        echo "<td>" . htmlspecialchars($patient['name']) . "</td>";
        echo "<td>" . htmlspecialchars($patient['email']) . "</td>";
        echo "</tr>";
        $count++;
    }
    if ($patients_count > 5) {
        echo "<tr><td colspan='3'><em>... and " . ($patients_count - 5) . " more patients</em></td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No patients found! This will cause empty dropdowns.</div>";
}

// Step 6: Create sample assignments if none exist
echo "<h2>🔗 Step 6: Sample Assignment Creation</h2>";

$assignment_count_query = "SELECT COUNT(*) as count FROM doctor_patients";
$assignment_count_result = mysqli_query($connection, $assignment_count_query);
$assignment_count = mysqli_fetch_assoc($assignment_count_result)['count'];

if ($assignment_count == 0 && $doctors_count > 0 && $patients_count > 0) {
    echo "<div class='warning'>⚠️ No assignments exist. Creating sample assignments...</div>";
    
    // Get first doctor and first few patients
    mysqli_data_seek($doctors_result, 0);
    $first_doctor = mysqli_fetch_assoc($doctors_result);
    
    mysqli_data_seek($patients_result, 0);
    $sample_assignments = [];
    $count = 0;
    while ($patient = mysqli_fetch_assoc($patients_result) && $count < 3) {
        $sample_assignments[] = $patient;
        $count++;
    }
    
    foreach ($sample_assignments as $patient) {
        $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) VALUES ({$first_doctor['id']}, {$patient['id']})";
        if (mysqli_query($connection, $assign_query)) {
            echo "<div class='success'>✅ Assigned {$patient['name']} to Dr. {$first_doctor['name']}</div>";
        } else {
            echo "<div class='error'>❌ Error creating assignment: " . mysqli_error($connection) . "</div>";
        }
    }
    
    // Recount assignments
    $assignment_count_result = mysqli_query($connection, $assignment_count_query);
    $assignment_count = mysqli_fetch_assoc($assignment_count_result)['count'];
    echo "<div class='success'>Total assignments now: <strong>{$assignment_count}</strong></div>";
} else if ($assignment_count > 0) {
    echo "<div class='success'>✅ Assignments already exist: <strong>{$assignment_count}</strong></div>";
} else {
    echo "<div class='warning'>⚠️ Cannot create sample assignments - insufficient doctors or patients</div>";
}

// Step 7: Final Summary and Instructions
echo "<h2>📝 Step 7: System Status Summary</h2>";

// Get final counts
$final_user_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM users"))['count'];
$final_doctor_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'approved'"))['count'];
$final_patient_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'patient'"))['count'];
$final_assignment_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM doctor_patients"))['count'];

echo "<table>";
echo "<tr><th>Component</th><th>Status</th><th>Count</th></tr>";
echo "<tr><td>Total Users</td><td>" . ($final_user_count >= 5 ? "✅ Good" : "⚠️ Low") . "</td><td>{$final_user_count}</td></tr>";
echo "<tr><td>Approved Doctors</td><td>" . ($final_doctor_count >= 1 ? "✅ Good" : "❌ None") . "</td><td>{$final_doctor_count}</td></tr>";
echo "<tr><td>Patients</td><td>" . ($final_patient_count >= 1 ? "✅ Good" : "❌ None") . "</td><td>{$final_patient_count}</td></tr>";
echo "<tr><td>Assignments</td><td>" . ($final_assignment_count >= 1 ? "✅ Good" : "ℹ️ None") . "</td><td>{$final_assignment_count}</td></tr>";
echo "</table>";

if ($final_doctor_count > 0 && $final_patient_count > 0) {
    echo "<div class='success'>";
    echo "<h3>✅ System Status: READY FOR USE</h3>";
    echo "<p>The assignment system is now properly configured and ready for use!</p>";
    echo "<ul>";
    echo "<li>Database structure is complete</li>";
    echo "<li>Sample users are available</li>";
    echo "<li>Assignment dropdowns will be populated</li>";
    echo "<li>All core functionality is operational</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ System Status: NEEDS ATTENTION</h3>";
    echo "<p>The system needs more users to function properly:</p>";
    echo "<ul>";
    if ($final_doctor_count == 0) echo "<li>No approved doctors available for assignments</li>";
    if ($final_patient_count == 0) echo "<li>No patients available for assignments</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<div class='info'>";
echo "<h3>🔑 Sample Login Credentials</h3>";
echo "<table>";
echo "<tr><th>Role</th><th>Email</th><th>Password</th></tr>";
echo "<tr><td>Admin</td><td>admin@healthalert.com</td><td>admin123</td></tr>";
echo "<tr><td>Doctor</td><td>sarah.johnson@hospital.com</td><td>doctor123</td></tr>";
echo "<tr><td>Patient</td><td>john.smith@email.com</td><td>patient123</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🚀 Next Steps</h3>";
echo "<ol>";
echo "<li><strong>Test Login:</strong> Try logging in with the sample accounts above</li>";
echo "<li><strong>Create Assignments:</strong> Use the admin account to assign patients to doctors</li>";
echo "<li><strong>Test Alerts:</strong> Have doctors send alerts to assigned patients</li>";
echo "<li><strong>Verify Dashboards:</strong> Check that assignments show up in doctor and patient dashboards</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔗 Quick Navigation</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='admin/assign_patients.php' class='btn'>👥 Manage Assignments</a>";
echo "<a href='admin/dashboard.php' class='btn'>🏠 Admin Dashboard</a>";
echo "<a href='doctor/dashboard.php' class='btn'>👨‍⚕️ Doctor Dashboard</a>";
echo "<a href='patient/dashboard.php' class='btn'>🏥 Patient Dashboard</a>";
echo "<a href='index.php' class='btn btn-success'>🔐 Login Page</a>";
echo "</div>";

echo "<div class='warning' style='margin-top: 30px;'>";
echo "<h3>🛠️ Troubleshooting Guide</h3>";
echo "<p>If you still encounter issues:</p>";
echo "<ol>";
echo "<li><strong>Empty Dropdowns:</strong> Refresh this page to ensure users were created</li>";
echo "<li><strong>Assignment Errors:</strong> Check that doctors have 'approved' status</li>";
echo "<li><strong>Login Issues:</strong> Verify the credentials above are correct</li>";
echo "<li><strong>Database Errors:</strong> Import the complete database_setup.sql file</li>";
echo "<li><strong>Permission Issues:</strong> Ensure MySQL user has proper permissions</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";

mysqli_close($connection);
?>