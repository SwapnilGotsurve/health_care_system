<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Ensure user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Handle assignment actions
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['assign_patient'])) {
        $doctor_id = (int)$_POST['doctor_id'];
        $patient_id = (int)$_POST['patient_id'];
        
        // Check if assignment already exists
        $check_query = "SELECT id FROM doctor_patients WHERE doctor_id = $doctor_id AND patient_id = $patient_id";
        $check_result = mysqli_query($connection, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            $assign_query = "INSERT INTO doctor_patients (doctor_id, patient_id) VALUES ($doctor_id, $patient_id)";
            if (mysqli_query($connection, $assign_query)) {
                $message = "Patient assigned to doctor successfully!";
                $message_type = "success";
            } else {
                $message = "Error assigning patient: " . mysqli_error($connection);
                $message_type = "error";
            }
        } else {
            $message = "This patient is already assigned to this doctor.";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['remove_assignment'])) {
        $assignment_id = (int)$_POST['assignment_id'];
        
        $remove_query = "DELETE FROM doctor_patients WHERE id = $assignment_id";
        if (mysqli_query($connection, $remove_query)) {
            $message = "Assignment removed successfully!";
            $message_type = "success";
        } else {
            $message = "Error removing assignment: " . mysqli_error($connection);
            $message_type = "error";
        }
    }
}

// Get approved doctors
$doctors_query = "SELECT id, name, email FROM users WHERE role = 'doctor' AND status = 'approved' ORDER BY name";
$doctors_result = mysqli_query($connection, $doctors_query);
$doctors_count = mysqli_num_rows($doctors_result);

// Get all patients
$patients_query = "SELECT id, name, email FROM users WHERE role = 'patient' ORDER BY name";
$patients_result = mysqli_query($connection, $patients_query);
$patients_count = mysqli_num_rows($patients_result);

// Debug information
if ($doctors_count == 0 || $patients_count == 0) {
    $debug_message = "⚠️ SYSTEM ISSUE DETECTED: ";
    if ($doctors_count == 0) $debug_message .= "No approved doctors found. ";
    if ($patients_count == 0) $debug_message .= "No patients found. ";
    $debug_message .= "Please run the emergency fix script.";
    $message = $debug_message;
    $message_type = "error";
}

// Check if created_at column exists in doctor_patients table
$check_column_query = "SHOW COLUMNS FROM doctor_patients LIKE 'created_at'";
$column_check = mysqli_query($connection, $check_column_query);
$has_created_at = mysqli_num_rows($column_check) > 0;

// Get current assignments with doctor and patient names
if ($has_created_at) {
    $assignments_query = "SELECT dp.id,
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
                         ORDER BY dp.id DESC";
} else {
    $assignments_query = "SELECT dp.id,
                                d.name as doctor_name, d.email as doctor_email,
                                p.name as patient_name, p.email as patient_email,
                                COUNT(hd.id) as health_records,
                                COUNT(a.id) as alerts_sent,
                                NULL as created_at
                         FROM doctor_patients dp
                         JOIN users d ON dp.doctor_id = d.id
                         JOIN users p ON dp.patient_id = p.id
                         LEFT JOIN health_data hd ON p.id = hd.patient_id
                         LEFT JOIN alerts a ON dp.doctor_id = a.doctor_id AND dp.patient_id = a.patient_id
                         GROUP BY dp.id, d.name, d.email, p.name, p.email
                         ORDER BY dp.id DESC";
}
$assignments_result = mysqli_query($connection, $assignments_query);

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Doctor-Patient Assignments</h1>
                <p class="text-gray-600">Manage which patients are assigned to which doctors</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="dashboard.php" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-md <?php echo $message_type == 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Assignment Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New Assignment</h2>
        
        <?php if ($doctors_count == 0 || $patients_count == 0): ?>
        <!-- Emergency Fix Notice -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-red-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-medium text-red-800 mb-2">🚨 Assignment System Not Ready</h3>
                    <p class="text-red-700 mb-4">
                        The assignment system cannot function because:
                        <?php if ($doctors_count == 0): ?>
                        <br>• No approved doctors found in the system
                        <?php endif; ?>
                        <?php if ($patients_count == 0): ?>
                        <br>• No patients found in the system
                        <?php endif; ?>
                    </p>
                    <div class="flex space-x-3">
                        <a href="../emergency_fix.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium transition-colors">
                            🔧 Run Emergency Fix
                        </a>
                        <a href="../debug_assignment_system.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors">
                            🔍 Debug System
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Normal Assignment Form -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <p class="text-green-800">
                ✅ System Ready: Found <?php echo $doctors_count; ?> approved doctors and <?php echo $patients_count; ?> patients
            </p>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="doctor_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Doctor (<?php echo $doctors_count; ?> available)
                </label>
                <select name="doctor_id" id="doctor_id" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        <?php echo ($doctors_count == 0) ? 'disabled' : ''; ?>>
                    <option value="">Choose a doctor...</option>
                    <?php 
                    mysqli_data_seek($doctors_result, 0);
                    while ($doctor = mysqli_fetch_assoc($doctors_result)): 
                    ?>
                        <option value="<?php echo $doctor['id']; ?>">
                            <?php echo htmlspecialchars($doctor['name']); ?> (<?php echo htmlspecialchars($doctor['email']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Patient (<?php echo $patients_count; ?> available)
                </label>
                <select name="patient_id" id="patient_id" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        <?php echo ($patients_count == 0) ? 'disabled' : ''; ?>>
                    <option value="">Choose a patient...</option>
                    <?php 
                    mysqli_data_seek($patients_result, 0);
                    while ($patient = mysqli_fetch_assoc($patients_result)): 
                    ?>
                        <option value="<?php echo $patient['id']; ?>">
                            <?php echo htmlspecialchars($patient['name']); ?> (<?php echo htmlspecialchars($patient['email']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <button type="submit" name="assign_patient" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors <?php echo ($doctors_count == 0 || $patients_count == 0) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                        <?php echo ($doctors_count == 0 || $patients_count == 0) ? 'disabled' : ''; ?>>
                    Assign Patient
                </button>
            </div>
        </form>
    </div>

    <!-- Current Assignments -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Current Assignments</h2>
            <p class="text-gray-600 text-sm mt-1">
                Total assignments: <?php echo mysqli_num_rows($assignments_result); ?>
            </p>
        </div>
        
        <?php if (mysqli_num_rows($assignments_result) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Doctor
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Patient
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Activity
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Assigned Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($assignment = mysqli_fetch_assoc($assignments_result)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-medium text-sm">
                                            Dr
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($assignment['doctor_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($assignment['doctor_email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <span class="text-green-600 font-medium text-sm">
                                            <?php echo strtoupper(substr($assignment['patient_name'], 0, 2)); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($assignment['patient_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($assignment['patient_email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <div class="flex items-center space-x-4">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                                        <?php echo $assignment['health_records']; ?> records
                                    </span>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded">
                                        <?php echo $assignment['alerts_sent']; ?> alerts
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php 
                                if (isset($assignment['created_at']) && $assignment['created_at']) {
                                    echo date('M j, Y', strtotime($assignment['created_at']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php 
                                if (isset($assignment['created_at']) && $assignment['created_at']) {
                                    echo date('g:i A', strtotime($assignment['created_at']));
                                } else {
                                    echo 'Unknown time';
                                }
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <form method="POST" class="inline" 
                                  onsubmit="return confirm('Are you sure you want to remove this assignment?');">
                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                <button type="submit" name="remove_assignment" 
                                        class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition-colors">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        <!-- Empty State -->
        <div class="p-12 text-center">
            <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="text-xl font-medium text-gray-900 mb-2">No Assignments Yet</h3>
            <p class="text-gray-600 mb-6">Create the first doctor-patient assignment using the form above.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Assignment Statistics -->
    <?php
    // Get assignment statistics
    $stats_query = "SELECT 
                      COUNT(DISTINCT dp.doctor_id) as doctors_with_patients,
                      COUNT(DISTINCT dp.patient_id) as patients_assigned,
                      COUNT(*) as total_assignments,
                      AVG(patient_counts.patient_count) as avg_patients_per_doctor
                   FROM doctor_patients dp
                   LEFT JOIN (
                       SELECT doctor_id, COUNT(*) as patient_count 
                       FROM doctor_patients 
                       GROUP BY doctor_id
                   ) patient_counts ON dp.doctor_id = patient_counts.doctor_id";
    $stats_result = mysqli_query($connection, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);
    
    // Get unassigned counts
    $unassigned_doctors_query = "SELECT COUNT(*) as count FROM users 
                                WHERE role = 'doctor' AND status = 'approved' 
                                AND id NOT IN (SELECT DISTINCT doctor_id FROM doctor_patients)";
    $unassigned_doctors_result = mysqli_query($connection, $unassigned_doctors_query);
    $unassigned_doctors = mysqli_fetch_assoc($unassigned_doctors_result)['count'];
    
    $unassigned_patients_query = "SELECT COUNT(*) as count FROM users 
                                 WHERE role = 'patient' 
                                 AND id NOT IN (SELECT DISTINCT patient_id FROM doctor_patients)";
    $unassigned_patients_result = mysqli_query($connection, $unassigned_patients_query);
    $unassigned_patients = mysqli_fetch_assoc($unassigned_patients_result)['count'];
    ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['doctors_with_patients'] ?: 0; ?></p>
                    <p class="text-gray-600">Active Doctors</p>
                    <p class="text-xs text-gray-500"><?php echo $unassigned_doctors; ?> unassigned</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['patients_assigned'] ?: 0; ?></p>
                    <p class="text-gray-600">Assigned Patients</p>
                    <p class="text-xs text-gray-500"><?php echo $unassigned_patients; ?> unassigned</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_assignments'] ?: 0; ?></p>
                    <p class="text-gray-600">Total Assignments</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['avg_patients_per_doctor'] ? round($stats['avg_patients_per_doctor'], 1) : '0'; ?></p>
                    <p class="text-gray-600">Avg per Doctor</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Tips -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">💡 Assignment Management Tips</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">Best Practices:</h4>
                <ul class="space-y-1">
                    <li>• Balance patient load across doctors</li>
                    <li>• Consider doctor specializations</li>
                    <li>• Monitor unassigned patients regularly</li>
                    <li>• Review assignment effectiveness periodically</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">System Notes:</h4>
                <ul class="space-y-1">
                    <li>• Only approved doctors can be assigned patients</li>
                    <li>• Patients can be assigned to multiple doctors</li>
                    <li>• Removing assignments doesn't delete health data</li>
                    <li>• Assignment history is tracked for auditing</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>