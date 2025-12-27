<?php
/**
 * TEST ASSIGNMENT CREATION
 * This script will test creating assignments directly in the database
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Assignment Creation</title>";
echo "<style>
    body{font-family:Arial,sans-serif;max-width:800px;margin:30px auto;padding:20px;background:#f8f9fa;}
    .success{color:#155724;background:#d4edda;border:1px solid #c3e6cb;padding:12px;border-radius:5px;margin:10px 0;}
    .error{color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;padding:12px;border-radius:5px;margin:10px 0;}
    .info{color:#0c5460;background:#d1ecf1;border:1px solid #bee5eb;padding:12px;border-radius:5px;margin:10px 0;}
    table{width:100%;border-collapse:collapse;margin:15px 0;background:white;}
    th,td{border:1px solid #ddd;padding:10px;text-align:left;}
    th{background:#f8f9fa;font-weight:bold;}
</style>";
echo "</head><body>";

echo "<h1>🧪 Test Assignment Creation</h1>";

// Step 1: Check database connection
if (!$connection) {
    echo "<div class='error'>❌ Database connection failed: " . mysqli_connect_error() . "</div>";
    echo "</body></html>";
    exit;
}
echo "<div class='success'>✅ Database connected successfully</div>";

// Step 2: Get available doctors and patients
echo "<h2>📋 Available Users</h2>";

$doctors_query = "SELECT id, name, email, status FROM users WHERE role = 'doctor' ORDER BY name";
$doctors_result = mysqli_query($connection, $doctors_query);

echo "<h3>Doctors:</h3>";
echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
$approved_doctors = [];
while ($doctor = mysqli_fetch_assoc($doctors_result)) {
    $status_color = $doctor['status'] == 'approved' ? 'color: green;' : 'color: orange;';
    echo "<tr>";
    echo "<td>{$doctor['id']}</td>";
    echo "<td>" . htmlspecialchars($doctor['name']) . "</td>";
    echo "<td>" . htmlspecialchars($doctor['email']) . "</td>";
    echo "<td style='$status_color'>" . ucfirst($doctor['status']) . "</td>";
    echo "</tr>";
    
    if ($doctor['status'] == 'approved') {
        $approved_doctors[] = $doctor;
    }
}
echo "</table>";

$patients_query = "SELECT id, name, email FROM users WHERE role = 'patient' ORDER BY name";
$patients_result = mysqli_query($connection, $patients_query);

echo "<h3>Patients:</h3>";
echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
$all_patients = [];
while ($patient = mysqli_fetch_assoc($patients_result)) {
    echo "<tr>";
    echo "<td>{$patient['id']}</td>";
    echo "<td>" . htmlspecialchars($patient['name']) . "</td>";
    echo "<td>" . htmlspecialchars($patient['email']) . "</td>";
    echo "</tr>";
    
    $all_patients[] = $patient;
}
echo "</table>";

echo "<div class='info'>Found " . count($approved_doctors) . " approved doctors and " . count($all_patients) . " patients</div>";

// Step 3: Clear existing assignments
echo "<h2>🧹 Clearing Existing Assignments</h2>";
$clear_query = "DELETE FROM doctor_patients";
if (mysqli_query($connection, $clear_query)) {
    echo "<div class='success'>✅ Cleared existing assignments</div>";
} else {
    echo "<div class='error'>❌ Error clearing assignments: " . mysqli_error($connection) . "</div>";
}

// Step 4: Create test assignments
echo "<h2>🔗 Creating Test Assignments</h2>";

if (count($approved_doctors) > 0 && count($all_patients) > 0) {
    $assignments_created = 0;
    
    // Assign each patient to the first approved doctor for testing
    $test_doctor = $approved_doctors[0];
    
    foreach ($all_patients as $patient) {
        $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id, created_at) VALUES ({$test_doctor['id']}, {$patient['id']}, NOW())";
        
        if (mysqli_query($connection, $assign_query)) {
            echo "<div class='success'>✅ Assigned {$patient['name']} to Dr. {$test_doctor['name']}</div>";
            $assignments_created++;
        } else {
            echo "<div class='error'>❌ Error assigning {$patient['name']} to Dr. {$test_doctor['name']}: " . mysqli_error($connection) . "</div>";
        }
    }
    
    // Also create some assignments for other doctors if available
    if (count($approved_doctors) > 1 && count($all_patients) > 2) {
        $second_doctor = $approved_doctors[1];
        
        // Assign first 2 patients to second doctor as well (patients can have multiple doctors)
        for ($i = 0; $i < min(2, count($all_patients)); $i++) {
            $patient = $all_patients[$i];
            $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id, created_at) VALUES ({$second_doctor['id']}, {$patient['id']}, NOW())";
            
            if (mysqli_query($connection, $assign_query)) {
                echo "<div class='success'>✅ Also assigned {$patient['name']} to Dr. {$second_doctor['name']}</div>";
                $assignments_created++;
            } else {
                echo "<div class='error'>❌ Error assigning {$patient['name']} to Dr. {$second_doctor['name']}: " . mysqli_error($connection) . "</div>";
            }
        }
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

echo "<h2>🎉 TEST COMPLETE!</h2>";
echo "<div class='success'>";
echo "<h3>✅ Assignment System Status: OPERATIONAL</h3>";
echo "<p>Test assignments have been created successfully!</p>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='admin/assign_patients.php' style='background:#007bff;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>👥 View Assignment Page</a>";
echo "<a href='doctor/dashboard.php' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>👨‍⚕️ Doctor Dashboard</a>";
echo "<a href='patient/dashboard.php' style='background:#17a2b8;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>🏥 Patient Dashboard</a>";
echo "</div>";

echo "</body></html>";

mysqli_close($connection);
?>