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
$doctors_per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $doctors_per_page;

// Filter settings
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause for filters
$where_conditions = ["role = 'doctor'"];

if ($status_filter !== 'all') {
    $status_filter = mysqli_real_escape_string($connection, $status_filter);
    $where_conditions[] = "status = '$status_filter'";
}

if (!empty($search_query)) {
    $search_escaped = mysqli_real_escape_string($connection, $search_query);
    $where_conditions[] = "(name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$count_result = mysqli_query($connection, $count_query);
$total_doctors = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_doctors / $doctors_per_page);

// Get doctors with patient assignment counts
$doctors_query = "SELECT u.id, u.name, u.email, u.status, u.created_at,
                         COUNT(dp.patient_id) as patient_count,
                         COUNT(a.id) as alert_count
                  FROM users u
                  LEFT JOIN doctor_patients dp ON u.id = dp.doctor_id
                  LEFT JOIN alerts a ON u.id = a.doctor_id
                  WHERE $where_clause
                  GROUP BY u.id, u.name, u.email, u.status, u.created_at
                  ORDER BY u.created_at DESC
                  LIMIT $doctors_per_page OFFSET $offset";

$doctors_result = mysqli_query($connection, $doctors_query);

// Get statistics
$stats_query = "SELECT 
                   COUNT(*) as total_doctors,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
               FROM users 
               WHERE role = 'doctor'";

$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">All Doctors</h1>
                <p class="text-gray-600">Manage and view all registered doctors in the system</p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
                <a href="doctor_approvals.php" 
                   class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md transition-colors">
                    Pending Approvals
                    <?php if ($stats['pending_count'] > 0): ?>
                        <span class="ml-2 bg-yellow-800 text-yellow-100 px-2 py-1 rounded-full text-xs">
                            <?php echo $stats['pending_count']; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="patient_list.php" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                    View Patients
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
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Doctors</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_doctors']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['approved_count']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending_count']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">New This Month</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['new_this_month']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Doctors</label>
                <input type="text" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       placeholder="Search by name or email..."
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Status</option>
                    <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    Apply Filters
                </button>
                <a href="doctor_list.php" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <?php if (mysqli_num_rows($doctors_result) > 0): ?>
    <!-- Doctors List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                Doctors List 
                <?php if ($total_doctors > 0): ?>
                    <span class="text-sm text-gray-500">
                        (<?php echo number_format($total_doctors); ?> total)
                    </span>
                <?php endif; ?>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Doctor
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Patients
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Alerts Sent
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
                    <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-medium text-sm">
                                            <?php echo strtoupper(substr($doctor['name'], 0, 2)); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($doctor['name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($doctor['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($doctor['status'] === 'approved'): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Approved
                                </span>
                            <?php else: ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <?php echo number_format($doctor['patient_count']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                <?php echo number_format($doctor['alert_count']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex flex-col">
                                <span><?php echo date('M j, Y', strtotime($doctor['created_at'])); ?></span>
                                <span class="text-xs text-gray-400">
                                    <?php 
                                    $days_ago = floor((time() - strtotime($doctor['created_at'])) / 86400);
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
                                <?php if ($doctor['status'] === 'pending'): ?>
                                    <a href="doctor_approvals.php" 
                                       class="text-yellow-600 hover:text-yellow-900">
                                        Review
                                    </a>
                                <?php else: ?>
                                    <span class="text-green-600">
                                        Active
                                    </span>
                                <?php endif; ?>
                                
                                <span class="text-gray-300">|</span>
                                
                                <button onclick="viewDoctorDetails(<?php echo $doctor['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900">
                                    Details
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
            <?php echo number_format(min($offset + $doctors_per_page, $total_doctors)); ?> of 
            <?php echo number_format($total_doctors); ?> doctors
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
    <!-- No Doctors State -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No Doctors Found</h3>
        <p class="text-gray-600 mb-6">
            <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                No doctors match your current filters. Try adjusting your search criteria.
            <?php else: ?>
                No doctors have registered in the system yet.
            <?php endif; ?>
        </p>
        <div class="space-x-4">
            <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                <a href="doctor_list.php" 
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

    <!-- Doctor Management Guidelines -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">👨‍⚕️ Doctor Management Guidelines</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">Status Management:</h4>
                <ul class="space-y-1">
                    <li>• Approved doctors can access patient data</li>
                    <li>• Pending doctors await admin approval</li>
                    <li>• Monitor doctor activity regularly</li>
                    <li>• Review patient assignments</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Activity Monitoring:</h4>
                <ul class="space-y-1">
                    <li>• Track patient assignments</li>
                    <li>• Monitor alert frequency</li>
                    <li>• Review communication patterns</li>
                    <li>• Ensure appropriate usage</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Best Practices:</h4>
                <ul class="space-y-1">
                    <li>• Approve legitimate medical professionals</li>
                    <li>• Maintain accurate records</li>
                    <li>• Regular system audits</li>
                    <li>• Ensure data privacy compliance</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Doctor Details Modal -->
<div id="doctorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Doctor Details</h3>
                <button onclick="closeDoctorModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="modalContent" class="text-sm text-gray-600">
                Loading doctor details...
            </div>
        </div>
    </div>
</div>

<script>
function viewDoctorDetails(doctorId) {
    document.getElementById('doctorModal').classList.remove('hidden');
    document.getElementById('modalContent').innerHTML = 'Loading doctor details...';
    
    // In a real application, you would fetch doctor details via AJAX
    // For this demo, we'll show a placeholder
    setTimeout(() => {
        document.getElementById('modalContent').innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="font-medium">Doctor ID:</span> ${doctorId}
                    </div>
                    <div>
                        <span class="font-medium">Registration Date:</span> View in table
                    </div>
                </div>
                <div class="border-t pt-4">
                    <h4 class="font-medium mb-2">Quick Actions:</h4>
                    <div class="space-x-2">
                        <button class="bg-blue-600 text-white px-3 py-1 rounded text-sm">View Patient Assignments</button>
                        <button class="bg-green-600 text-white px-3 py-1 rounded text-sm">View Alert History</button>
                    </div>
                </div>
            </div>
        `;
    }, 500);
}

function closeDoctorModal() {
    document.getElementById('doctorModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('doctorModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDoctorModal();
    }
});

// Auto-refresh every 5 minutes to check for updates
setTimeout(function() {
    window.location.reload();
}, 300000); // 5 minutes
</script>

<?php include '../includes/footer.php'; ?>