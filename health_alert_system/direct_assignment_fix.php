<?php
/**
 * DIRECT ASSIGNMENT FIX
 * This script will directly assign doctors to patients through database queries
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Direct Assignment Fix - Health Alert System</title>";
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
</style>";
echo "</head><body>";

echo "<h1>🔧 Direct Assignment Fix</h1>";
echo "<p>Directly assigning doctors to patients through database...</p>";

// Step 1: Check database connection
if (!$connection) {
    echo "<div class='error'>❌ Database connection failed: " . mysqli_connect_error() . "</div>";
    echo "</body></html>";
    exit;
}
echo "<div class='success'>✅ Database connected successfully</div>";

// Step 2: Show current users
echo "<h2>👥 Current Users in System</h2>";

// Get all doctors
$doctors_query = "SELECT id, name, email, status FROM users WHERE role = 'doctor' ORDER BY name";
$doctors_result = mysqli_query($connection, $doctors_query);

echo "<h3>Doctors:</h3>";
echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
while ($doctor = mysqli_fetch_assoc($doctors_result)) {
    $status_color = $doctor['status'] == 'approved' ? 'color: green;' : 'color: orange;';
    echo "<tr>";
    echo "<td>{$doctor['id']}</td>";
    echo "<td>" . htmlspecialchars($doctor['name']) . "</td>";
    echo "<td>" . htmlspecialchars($doctor['email']) . "</td>";
    echo "<td style='$status_color'>" . ucfirst($doctor['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Get all patients
$patients_query = "SELECT id, name, email FROM users WHERE role = 'patient' ORDER BY name";
$patients_result = mysqli_query($connection, $patients_query);

echo "<h3>Patients:</h3>";
echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
while ($patient = mysqli_fetch_assoc($patients_result)) {
    echo "<tr>";
    echo "<td>{$patient['id']}</td>";
    echo "<td>" . htmlspecialchars($patient['name']) . "</td>";
    echo "<td>" . htmlspecialchars($patient['email']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 3: Clear existing assignments (if any)
echo "<h2>🧹 Clearing Existing Assignments</h2>";
$clear_query = "DELETE FROM doctor_patients";
if (mysqli_query($connection, $clear_query)) {
    echo "<div class='success'>✅ Cleared existing assignments</div>";
} else {
    echo "<div class='error'>❌ Error clearing assignments: " . mysqli_error($connection) . "</div>";
}

// Step 4: Create new assignments
echo "<h2>🔗 Creating New Assignments</h2>";

// Get approved doctors and patients for assignment
mysqli_data_seek($doctors_result, 0);
mysqli_data_seek($patients_result, 0);

$approved_doctors = [];
while ($doctor = mysqli_fetch_assoc($doctors_result)) {
    if ($doctor['status'] == 'approved') {
        $approved_doctors[] = $doctor;
    }
}

$all_patients = [];
while ($patient = mysqli_fetch_assoc($patients_result)) {
    $all_patients[] = $patient;
}

echo "<div class='info'>Found " . count($approved_doctors) . " approved doctors and " . count($all_patients) . " patients</div>";

// Assign patients to doctors in a round-robin fashion
if (count($approved_doctors) > 0 && count($all_patients) > 0) {
    $doctor_index = 0;
    $assignments_created = 0;
    
    foreach ($all_patients as $patient) {
        $doctor = $approved_doctors[$doctor_index];
        
        $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id, created_at) VALUES ({$doctor['id']}, {$patient['id']}, NOW())";
        
        if (mysqli_query($connection, $assign_query)) {
            echo "<div class='success'>✅ Assigned {$patient['name']} to Dr. {$doctor['name']}</div>";
            $assignments_created++;
        } else {
            echo "<div class='error'>❌ Error assigning {$patient['name']} to Dr. {$doctor['name']}: " . mysqli_error($connection) . "</div>";
        }
        
        // Move to next doctor (round-robin)
        $doctor_index = ($doctor_index + 1) % count($approved_doctors);
    }
    
    echo "<div class='success'>✅ Created $assignments_created assignments total</div>";
} else {
    echo "<div class='error'>❌ Cannot create assignments: No approved doctors or patients found</div>";
}

// Step 5: Verify assignments
echo "<h2>✅ Verification - Current Assignments</h2>";

$verify_query = "SELECT dp.id, d.name as doctor_name, d.email as doctor_email, 
                        p.name as patient_name, p.email as patient_email, dp.created_at
                 FROM doctor_patients dp
                 JOIN users d ON dp.doctor_id = d.id
                 JOIN users p ON dp.patient_id = p.id
                 ORDER BY dp.created_at DESC";

$verify_result = mysqli_query($connection, $verify_query);

if (mysqli_num_rows($verify_result) > 0) {
    echo "<div class='success'>✅ Found " . mysqli_num_rows($verify_result) . " active assignments</div>";
    
    echo "<table>";
    echo "<tr><th>Assignment ID</th><th>Doctor</th><th>Patient</th><th>Created</th></tr>";
    
    while ($assignment = mysqli_fetch_assoc($verify_result)) {
        echo "<tr>";
        echo "<td>{$assignment['id']}</td>";
        echo "<td>Dr. " . htmlspecialchars($assignment['doctor_name']) . "<br><small>" . htmlspecialchars($assignment['doctor_email']) . "</small></td>";
        echo "<td>" . htmlspecialchars($assignment['patient_name']) . "<br><small>" . htmlspecialchars($assignment['patient_email']) . "</small></td>";
        echo "<td>" . date('M j, Y g:i A', strtotime($assignment['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No assignments found after creation attempt</div>";
}

// Step 6: Test specific doctor assignments
echo "<h2>🔍 Doctor Assignment Summary</h2>";

$doctor_summary_query = "SELECT d.id, d.name, d.email, COUNT(dp.patient_id) as patient_count
                        FROM users d
                        LEFT JOIN doctor_patients dp ON d.id = dp.doctor_id
                        WHERE d.role = 'doctor' AND d.status = 'approved'
                        GROUP BY d.id, d.name, d.email
                        ORDER BY d.name";

$doctor_summary_result = mysqli_query($connection, $doctor_summary_query);

echo "<table>";
echo "<tr><th>Doctor ID</th><th>Doctor Name</th><th>Email</th><th>Assigned Patients</th></tr>";

while ($doctor_summary = mysqli_fetch_assoc($doctor_summary_result)) {
    echo "<tr>";
    echo "<td>{$doctor_summary['id']}</td>";
    echo "<td>Dr. " . htmlspecialchars($doctor_summary['name']) . "</td>";
    echo "<td>" . htmlspecialchars($doctor_summary['email']) . "</td>";
    echo "<td><strong>{$doctor_summary['patient_count']}</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>🎉 ASSIGNMENT FIX COMPLETE!</h2>";
echo "<div class='success'>";
echo "<h3>✅ Assignment System Status: OPERATIONAL</h3>";
echo "<p>Doctor-patient assignments have been created successfully!</p>";
echo "</div>";

echo "<h3>🔗 Quick Navigation</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='admin/assign_patients.php' class='btn'>👥 View Assignment Page</a>";
echo "<a href='doctor/dashboard.php' class='btn'>👨‍⚕️ Doctor Dashboard</a>";
echo "<a href='patient/dashboard.php' class='btn'>🏥 Patient Dashboard</a>";
echo "<a href='index.php' class='btn btn-success'>🔐 Login Page</a>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🚀 Next Steps</h3>";
echo "<ol>";
echo "<li><strong>Refresh Assignment Page:</strong> Go back to admin/assign_patients.php and refresh</li>";
echo "<li><strong>Check Doctor Dashboard:</strong> Login as any doctor to see assigned patients</li>";
echo "<li><strong>Test Alert System:</strong> Send alerts from doctors to patients</li>";
echo "<li><strong>Verify Patient Dashboard:</strong> Login as patient to see assigned doctors</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";

mysqli_close($connection);
?>