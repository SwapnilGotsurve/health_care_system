<?php
/**
 * TEST FORM SUBMISSION
 * This script simulates the assignment form submission to test the logic
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Form Submission</title>";
echo "<style>
    body{font-family:Arial,sans-serif;max-width:800px;margin:30px auto;padding:20px;background:#f8f9fa;}
    .success{color:#155724;background:#d4edda;border:1px solid #c3e6cb;padding:12px;border-radius:5px;margin:10px 0;}
    .error{color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;padding:12px;border-radius:5px;margin:10px 0;}
    .info{color:#0c5460;background:#d1ecf1;border:1px solid #bee5eb;padding:12px;border-radius:5px;margin:10px 0;}
    table{width:100%;border-collapse:collapse;margin:15px 0;background:white;}
    th,td{border:1px solid #ddd;padding:10px;text-align:left;}
    th{background:#f8f9fa;font-weight:bold;}
    .btn{background:#007bff;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;}
</style>";
echo "</head><body>";

echo "<h1>🧪 Test Form Submission Logic</h1>";

// Check database connection
if (!$connection) {
    echo "<div class='error'>❌ Database connection failed: " . mysqli_connect_error() . "</div>";
    echo "</body></html>";
    exit;
}
echo "<div class='success'>✅ Database connected successfully</div>";

// Get first available doctor and patient for testing
$doctor_query = "SELECT id, name FROM users WHERE role = 'doctor' AND status = 'approved' LIMIT 1";
$doctor_result = mysqli_query($connection, $doctor_query);
$doctor = mysqli_fetch_assoc($doctor_result);

$patient_query = "SELECT id, name FROM users WHERE role = 'patient' LIMIT 1";
$patient_result = mysqli_query($connection, $patient_query);
$patient = mysqli_fetch_assoc($patient_result);

if (!$doctor || !$patient) {
    echo "<div class='error'>❌ No doctor or patient found for testing</div>";
    echo "</body></html>";
    exit;
}

echo "<div class='info'>Testing with Doctor: {$doctor['name']} (ID: {$doctor['id']}) and Patient: {$patient['name']} (ID: {$patient['id']})</div>";

// Simulate the form submission logic from assign_patients.php
echo "<h2>🔄 Simulating Form Submission</h2>";

// Simulate POST data
$_POST['assign_patient'] = true;
$_POST['doctor_id'] = $doctor['id'];
$_POST['patient_id'] = $patient['id'];

$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['assign_patient'])) {
        $doctor_id = (int)$_POST['doctor_id'];
        $patient_id = (int)$_POST['patient_id'];
        
        echo "<div class='info'>Processing assignment: Doctor ID $doctor_id → Patient ID $patient_id</div>";
        
        // Check if assignment already exists
        $check_query = "SELECT id FROM doctor_patients WHERE doctor_id = $doctor_id AND patient_id = $patient_id";
        echo "<div class='info'>Check query: $check_query</div>";
        
        $check_result = mysqli_query($connection, $check_query);
        
        if (!$check_result) {
            echo "<div class='error'>❌ Check query failed: " . mysqli_error($connection) . "</div>";
        } else {
            echo "<div class='success'>✅ Check query executed successfully</div>";
            echo "<div class='info'>Existing assignments found: " . mysqli_num_rows($check_result) . "</div>";
        }
        
        if (mysqli_num_rows($check_result) == 0) {
            $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) VALUES ($doctor_id, $patient_id)";
            echo "<div class='info'>Assignment query: $assign_query</div>";
            
            if (mysqli_query($connection, $assign_query)) {
                $message = "Patient assigned to doctor successfully!";
                $message_type = "success";
                echo "<div class='success'>✅ $message</div>";
            } else {
                $message = "Error assigning patient: " . mysqli_error($connection);
                $message_type = "error";
                echo "<div class='error'>❌ $message</div>";
            }
        } else {
            $message = "This patient is already assigned to this doctor.";
            $message_type = "error";
            echo "<div class='error'>⚠️ $message</div>";
        }
    }
}

// Verify the assignment was created
echo "<h2>✅ Verification</h2>";

$verify_query = "SELECT dp.id, d.name as doctor_name, p.name as patient_name, dp.created_at
                 FROM doctor_patients dp
                 JOIN users d ON dp.doctor_id = d.id
                 JOIN users p ON dp.patient_id = p.id
                 WHERE dp.doctor_id = {$doctor['id']} AND dp.patient_id = {$patient['id']}";

$verify_result = mysqli_query($connection, $verify_query);

if (mysqli_num_rows($verify_result) > 0) {
    $assignment = mysqli_fetch_assoc($verify_result);
    echo "<div class='success'>✅ Assignment verified in database!</div>";
    echo "<table>";
    echo "<tr><th>Assignment ID</th><th>Doctor</th><th>Patient</th><th>Created</th></tr>";
    echo "<tr>";
    echo "<td>{$assignment['id']}</td>";
    echo "<td>" . htmlspecialchars($assignment['doctor_name']) . "</td>";
    echo "<td>" . htmlspecialchars($assignment['patient_name']) . "</td>";
    echo "<td>" . ($assignment['created_at'] ? date('M j, Y g:i A', strtotime($assignment['created_at'])) : 'N/A') . "</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "<div class='error'>❌ Assignment not found in database</div>";
}

// Show all current assignments
echo "<h2>📋 All Current Assignments</h2>";

$all_assignments_query = "SELECT dp.id, d.name as doctor_name, p.name as patient_name, dp.created_at
                         FROM doctor_patients dp
                         JOIN users d ON dp.doctor_id = d.id
                         JOIN users p ON dp.patient_id = p.id
                         ORDER BY dp.created_at DESC";

$all_assignments_result = mysqli_query($connection, $all_assignments_query);

if (mysqli_num_rows($all_assignments_result) > 0) {
    echo "<div class='success'>✅ Found " . mysqli_num_rows($all_assignments_result) . " total assignments</div>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Doctor</th><th>Patient</th><th>Created</th></tr>";
    
    while ($assignment = mysqli_fetch_assoc($all_assignments_result)) {
        echo "<tr>";
        echo "<td>{$assignment['id']}</td>";
        echo "<td>" . htmlspecialchars($assignment['doctor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($assignment['patient_name']) . "</td>";
        echo "<td>" . ($assignment['created_at'] ? date('M j, Y g:i A', strtotime($assignment['created_at'])) : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No assignments found in database</div>";
}

echo "<h2>🎉 TEST COMPLETE!</h2>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='admin/assign_patients.php' class='btn'>👥 View Assignment Page</a>";
echo "<a href='test_assignment_creation.php' class='btn'>🔧 Run Full Assignment Creation</a>";
echo "</div>";

echo "</body></html>";

mysqli_close($connection);
?>