<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Ensure user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Pagination settings
$patients_per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $patients_per_page;

// Filter settings
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$doctor_filter = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;

// Build WHERE clause for filters
$where_conditions = ["role = 'patient'"];

if (!empty($search_query)) {
    $search_escaped = mysqli_real_escape_string($connection, $search_query);
    $where_conditions[] = "(name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$count_result = mysqli_query($connection, $count_query);
$total_patients = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_patients / $patients_per_page);

// Get patients with health data and doctor assignment counts
$patients_query = "SELECT u.id, u.name, u.email, u.created_at,
                          COUNT(DISTINCT hd.id) as health_records_count,
                          COUNT(DISTINCT dp.doctor_id) as assigned_doctors_count,
                          COUNT(DISTINCT a.id) as alerts_received_count,
                          MAX(hd.created_at) as last_health_record
                   FROM users u
                   LEFT JOIN health_data hd ON u.id = hd.patient_id
                   LEFT JOIN doctor_patients dp ON u.id = dp.patient_id
                   LEFT JOIN alerts a ON u.id = a.patient_id
                   WHERE $where_clause
                   GROUP BY u.id, u.name, u.email, u.created_at
                   ORDER BY u.created_at DESC
                   LIMIT $patients_per_page OFFSET $offset";

$patients_result = mysqli_query($connection, $patients_query);

// Get doctors for filter dropdown
$doctors_query = "SELECT id, name FROM users WHERE role = 'doctor' AND status = 'approved' ORDER BY name ASC";
$doctors_result = mysqli_query($connection, $doctors_query);

// Get statistics
$stats_query = "SELECT 
                   COUNT(*) as total_patients,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_this_month,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_this_week
               FROM users 
               WHERE role = 'patient'";

$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get health data statistics
$health_stats_query = "SELECT 
                          COUNT(DISTINCT patient_id) as patients_with_data,
                          COUNT(*) as total_health_records,
                          COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_records
                       FROM health_data";

$health_stats_result = mysqli_query($connection, $health_stats_query);
$health_stats = mysqli_fetch_assoc($health_stats_result);

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">All Patients</h1>
                <p class="text-gray-600">Manage and view all registered patients in the system</p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
                <a href="doctor_list.php" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    View Doctors
                </a>
                <a href="dashboard.php" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Patients</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_patients']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">With Health Data</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($health_stats['patients_with_data']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">New This Week</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['new_this_week']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Recent Records</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($health_stats['recent_records']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Patients</label>
                <input type="text" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       placeholder="Search by name or email..."
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Doctor Filter -->
            <div>
                <label for="doctor_id" class="block text-sm font-medium text-gray-700 mb-2">Assigned Doctor</label>
                <select id="doctor_id" name="doctor_id" 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="0">All Doctors</option>
                    <?php 
                    mysqli_data_seek($doctors_result, 0);
                    while ($doctor = mysqli_fetch_assoc($doctors_result)): 
                    ?>
                        <option value="<?php echo $doctor['id']; ?>" 
                                <?php echo ($doctor_filter == $doctor['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($doctor['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    Apply Filters
                </button>
                <a href="patient_list.php" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <?php if (mysqli_num_rows($patients_result) > 0): ?>
    <!-- Patients List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                Patients List 
                <?php if ($total_patients > 0): ?>
                    <span class="text-sm text-gray-500">
                        (<?php echo number_format($total_patients); ?> total)
                    </span>
                <?php endif; ?>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Patient
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Health Records
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Assigned Doctors
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Alerts Received
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Last Activity
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Registered
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <span class="text-green-600 font-medium text-sm">
                                            <?php echo strtoupper(substr($patient['name'], 0, 2)); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($patient['name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($patient['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <span class="text-sm text-gray-900"><?php echo number_format($patient['health_records_count']); ?></span>
                                <?php if ($patient['health_records_count'] > 0): ?>
                                    <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        No Data
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <?php echo number_format($patient['assigned_doctors_count']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                <?php echo number_format($patient['alerts_received_count']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($patient['last_health_record']): ?>
                                <div class="flex flex-col">
                                    <span><?php echo date('M j, Y', strtotime($patient['last_health_record'])); ?></span>
                                    <span class="text-xs text-gray-400">
                                        <?php 
                                        $days_ago = floor((time() - strtotime($patient['last_health_record'])) / 86400);
                                        if ($days_ago == 0) {
                                            echo 'Today';
                                        } elseif ($days_ago == 1) {
                                            echo 'Yesterday';
                                        } else {
                                            echo $days_ago . ' days ago';
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400">No activity</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex flex-col">
                                <span><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></span>
                                <span class="text-xs text-gray-400">
                                    <?php 
                                    $days_ago = floor((time() - strtotime($patient['created_at'])) / 86400);
                                    if ($days_ago == 0) {
                                        echo 'Today';
                                    } elseif ($days_ago == 1) {
                                        echo 'Yesterday';
                                    } else {
                                        echo $days_ago . ' days ago';
                                    }
                                    ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="viewPatientDetails(<?php echo $patient['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900">
                                    Details
                                </button>
                                
                                <span class="text-gray-300">|</span>
                                
                                <button onclick="viewHealthData(<?php echo $patient['id']; ?>)" 
                                        class="text-green-600 hover:text-green-900">
                                    Health Data
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Showing <?php echo number_format($offset + 1); ?> to 
            <?php echo number_format(min($offset + $patients_per_page, $total_patients)); ?> of 
            <?php echo number_format($total_patients); ?> patients
        </div>

        <div class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Previous
                </a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Next
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- No Patients State -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No Patients Found</h3>
        <p class="text-gray-600 mb-6">
            <?php if (!empty($search_query) || $doctor_filter > 0): ?>
                No patients match your current filters. Try adjusting your search criteria.
            <?php else: ?>
                No patients have registered in the system yet.
            <?php endif; ?>
        </p>
        <div class="space-x-4">
            <?php if (!empty($search_query) || $doctor_filter > 0): ?>
                <a href="patient_list.php" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors">
                    Clear Filters
                </a>
            <?php endif; ?>
            <a href="dashboard.php" 
               class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-md transition-colors">
                Back to Dashboard
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Patient Management Guidelines -->
    <div class="mt-8 bg-green-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-green-900 mb-4">👥 Patient Management Guidelines</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-green-800">
            <div>
                <h4 class="font-medium mb-2">Data Privacy:</h4>
                <ul class="space-y-1">
                    <li>• Protect patient health information</li>
                    <li>• Monitor data access patterns</li>
                    <li>• Ensure HIPAA compliance</li>
                    <li>• Regular privacy audits</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Health Monitoring:</h4>
                <ul class="space-y-1">
                    <li>• Track patient engagement</li>
                    <li>• Monitor health data frequency</li>
                    <li>• Identify inactive patients</li>
                    <li>• Ensure proper doctor assignments</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">System Management:</h4>
                <ul class="space-y-1">
                    <li>• Regular system health checks</li>
                    <li>• Monitor alert effectiveness</li>
                    <li>• Ensure data integrity</li>
                    <li>• Maintain system performance</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Patient Details Modal -->
<div id="patientModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Patient Details</h3>
                <button onclick="closePatientModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="modalContent" class="text-sm text-gray-600">
                Loading patient details...
            </div>
        </div>
    </div>
</div>

<script>
function viewPatientDetails(patientId) {
    document.getElementById('patientModal').classList.remove('hidden');
    document.getElementById('modalContent').innerHTML = 'Loading patient details...';
    
    // In a real application, you would fetch patient details via AJAX
    // For this demo, we'll show a placeholder
    setTimeout(() => {
        document.getElementById('modalContent').innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="font-medium">Patient ID:</span> ${patientId}
                    </div>
                    <div>
                        <span class="font-medium">Registration Date:</span> View in table
                    </div>
                </div>
                <div class="border-t pt-4">
                    <h4 class="font-medium mb-2">Quick Actions:</h4>
                    <div class="space-x-2">
                        <button class="bg-green-600 text-white px-3 py-1 rounded text-sm">View Health Records</button>
                        <button class="bg-blue-600 text-white px-3 py-1 rounded text-sm">View Doctor Assignments</button>
                        <button class="bg-purple-600 text-white px-3 py-1 rounded text-sm">View Alert History</button>
                    </div>
                </div>
            </div>
        `;
    }, 500);
}

function viewHealthData(patientId) {
    // In a real application, this would redirect to a health data view page
    alert(`Viewing health data for patient ID: ${patientId}`);
}

function closePatientModal() {
    document.getElementById('patientModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('patientModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePatientModal();
    }
});

// Auto-refresh every 5 minutes to check for updates
setTimeout(function() {
    window.location.reload();
}, 300000); // 5 minutes
</script>

<?php include '../includes/footer.php'; ?>