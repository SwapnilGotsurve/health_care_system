<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/error_handler.php';

// Set proper content type and encoding
header('Content-Type: text/html; charset=UTF-8');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Pagination settings
$alerts_per_page = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $alerts_per_page;

// Filter settings with proper sanitization
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($connection, $_GET['status']) : 'all';
$patient_filter = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$date_filter = isset($_GET['date_range']) ? mysqli_real_escape_string($connection, $_GET['date_range']) : 'all';

// Initialize error handling
$errors = [];

try {
    // Build WHERE clause for filters
    $where_conditions = ["a.doctor_id = ?"];
    $bind_params = [$user_id];
    $bind_types = "i";

    if ($status_filter !== 'all') {
        $where_conditions[] = "a.status = ?";
        $bind_params[] = $status_filter;
        $bind_types .= "s";
    }

    if ($patient_filter > 0) {
        $where_conditions[] = "a.patient_id = ?";
        $bind_params[] = $patient_filter;
        $bind_types .= "i";
    }

    // Date range filter
    $date_condition = '';
    switch ($date_filter) {
        case 'today':
            $date_condition = "DATE(a.created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_condition = "a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $date_condition = "a.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
            break;
    }

    if ($date_condition) {
        $where_conditions[] = $date_condition;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total 
                   FROM alerts a 
                   JOIN users u ON a.patient_id = u.id 
                   WHERE $where_clause";
    
    $count_stmt = mysqli_prepare($connection, $count_query);
    if ($count_stmt) {
        mysqli_stmt_bind_param($count_stmt, $bind_types, ...$bind_params);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_alerts = mysqli_fetch_assoc($count_result)['total'];
        mysqli_stmt_close($count_stmt);
    } else {
        throw new Exception("Failed to prepare count query");
    }

    $total_pages = $total_alerts > 0 ? ceil($total_alerts / $alerts_per_page) : 0;

    // Get alerts with pagination
    $alerts_query = "SELECT a.id, a.message, a.status, a.created_at,
                            u.name as patient_name, u.email as patient_email, a.patient_id
                     FROM alerts a
                     JOIN users u ON a.patient_id = u.id
                     WHERE $where_clause
                     ORDER BY a.created_at DESC
                     LIMIT ? OFFSET ?";
    
    $bind_params[] = $alerts_per_page;
    $bind_params[] = $offset;
    $bind_types .= "ii";
    
    $alerts_stmt = mysqli_prepare($connection, $alerts_query);
    if ($alerts_stmt) {
        mysqli_stmt_bind_param($alerts_stmt, $bind_types, ...$bind_params);
        mysqli_stmt_execute($alerts_stmt);
        $alerts_result = mysqli_stmt_get_result($alerts_stmt);
    } else {
        throw new Exception("Failed to prepare alerts query");
    }

    // Get patients for filter dropdown
    $patients_query = "SELECT DISTINCT u.id, u.name 
                      FROM users u 
                      JOIN doctor_patients dp ON u.id = dp.patient_id 
                      WHERE dp.doctor_id = ? 
                      ORDER BY u.name";
    
    $patients_stmt = mysqli_prepare($connection, $patients_query);
    if ($patients_stmt) {
        mysqli_stmt_bind_param($patients_stmt, "i", $user_id);
        mysqli_stmt_execute($patients_stmt);
        $patients_result = mysqli_stmt_get_result($patients_stmt);
    } else {
        $patients_result = false;
        $errors[] = "Unable to load patients for filter";
    }

} catch (Exception $e) {
    error_log("Sent Alerts Error: " . $e->getMessage());
    $errors[] = "Unable to load alerts at this time. Please try refreshing the page.";
    $alerts_result = false;
    $total_alerts = 0;
    $total_pages = 0;
    $patients_result = false;
}

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Session Messages -->
    <?php echo display_session_messages(); ?>
    
    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <?php echo handle_validation_errors($errors); ?>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 animate-fade-in-up">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Sent Alerts</h1>
                <p class="text-gray-600">View and manage your sent health alerts</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="send_alert.php" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-md font-medium transition-colors inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Send New Alert
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 animate-fade-in-up stagger-1">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="sent" <?php echo $status_filter === 'sent' ? 'selected' : ''; ?>>Unread</option>
                    <option value="seen" <?php echo $status_filter === 'seen' ? 'selected' : ''; ?>>Read</option>
                </select>
            </div>

            <!-- Patient Filter -->
            <div>
                <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient</label>
                <select name="patient_id" id="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="0">All Patients</option>
                    <?php if ($patients_result): ?>
                        <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo $patient_filter == $patient['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div>
                <label for="date_range" class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <select name="date_range" id="date_range" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="year" <?php echo $date_filter === 'year' ? 'selected' : ''; ?>>Last Year</option>
                </select>
            </div>

            <!-- Filter Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium transition-colors" id="filterButton">
                    <span class="filter-text">Apply Filters</span>
                    <span class="filter-loading hidden">
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </button>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    <?php if ($alerts_result !== false): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 animate-fade-in-up stagger-2">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-blue-800">
                <?php if ($total_alerts > 0): ?>
                    Showing <?php echo min($alerts_per_page, max(0, $total_alerts - $offset)); ?> of <?php echo $total_alerts; ?> alerts
                    <?php if ($status_filter !== 'all' || $patient_filter > 0 || $date_filter !== 'all'): ?>
                        (filtered)
                    <?php endif; ?>
                <?php else: ?>
                    No alerts found
                    <?php if ($status_filter !== 'all' || $patient_filter > 0 || $date_filter !== 'all'): ?>
                        with current filters
                    <?php endif; ?>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alerts Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden animate-fade-in-up stagger-3">
        <?php if ($alerts_result && mysqli_num_rows($alerts_result) > 0): ?>
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($alert = mysqli_fetch_assoc($alerts_result)): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-primary-600 font-medium text-sm">
                                            <?php echo strtoupper(substr($alert['patient_name'], 0, 2)); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($alert['patient_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($alert['patient_email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs">
                                    <?php 
                                    $message = htmlspecialchars($alert['message']);
                                    echo strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div><?php echo date('M j, Y', strtotime($alert['created_at'])); ?></div>
                                <div class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($alert['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($alert['status'] === 'sent'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <div class="w-2 h-2 bg-yellow-400 rounded-full mr-1"></div>
                                        Unread
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <div class="w-2 h-2 bg-green-400 rounded-full mr-1"></div>
                                        Read
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                            
                                    <a href="send_alert.php?patient_id=<?php echo $alert['patient_id']; ?>" 
                                       class="text-green-600 hover:text-green-900 transition-colors">
                                        Send Another
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden">
                <?php 
                // Reset result pointer for mobile view
                mysqli_data_seek($alerts_result, 0);
                while ($alert = mysqli_fetch_assoc($alerts_result)): 
                ?>
                <div class="border-b border-gray-200 p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-primary-600 font-medium text-sm">
                                    <?php echo strtoupper(substr($alert['patient_name'], 0, 2)); ?>
                                </span>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($alert['patient_name']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($alert['status'] === 'sent'): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <div class="w-2 h-2 bg-yellow-400 rounded-full mr-1"></div>
                                Unread
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <div class="w-2 h-2 bg-green-400 rounded-full mr-1"></div>
                                Read
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm text-gray-900 mb-3">
                        <?php 
                        $message = htmlspecialchars($alert['message']);
                        echo strlen($message) > 120 ? substr($message, 0, 120) . '...' : $message;
                        ?>
                    </div>
                    <div class="flex space-x-4">
                        <button onclick="viewAlert(<?php echo $alert['id']; ?>, <?php echo json_encode($alert['message']); ?>, <?php echo json_encode($alert['patient_name']); ?>)" 
                                class="text-primary-600 hover:text-primary-900 text-sm font-medium transition-colors">
                            View Full Message
                        </button>
                        <a href="send_alert.php?patient_id=<?php echo $alert['patient_id']; ?>" 
                           class="text-green-600 hover:text-green-900 text-sm font-medium transition-colors">
                            Send Another
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&patient_id=<?php echo $patient_filter; ?>&date_range=<?php echo $date_filter; ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&patient_id=<?php echo $patient_filter; ?>&date_range=<?php echo $date_filter; ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                            <span class="font-medium"><?php echo min($offset + $alerts_per_page, $total_alerts); ?></span> of 
                            <span class="font-medium"><?php echo $total_alerts; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&patient_id=<?php echo $patient_filter; ?>&date_range=<?php echo $date_filter; ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&patient_id=<?php echo $patient_filter; ?>&date_range=<?php echo $date_filter; ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&patient_id=<?php echo $patient_filter; ?>&date_range=<?php echo $date_filter; ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Alerts Found</h3>
                <p class="text-gray-600 mb-6">
                    <?php if ($status_filter !== 'all' || $patient_filter > 0 || $date_filter !== 'all'): ?>
                        No alerts match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        You haven't sent any alerts yet. Start communicating with your patients.
                    <?php endif; ?>
                </p>
                <div class="space-x-4">
                    <?php if ($status_filter !== 'all' || $patient_filter > 0 || $date_filter !== 'all'): ?>
                        <a href="sent_alerts.php" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-md font-medium transition-colors">
                            Clear Filters
                        </a>
                    <?php endif; ?>
                    <a href="send_alert.php" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-md font-medium transition-colors">
                        Send Your First Alert
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Alert View Modal -->
<div id="alertModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Alert Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Patient:</label>
                <p id="modalPatient" class="text-gray-900"></p>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Message:</label>
                <div id="modalMessage" class="bg-gray-50 p-4 rounded-lg text-gray-900 whitespace-pre-wrap"></div>
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function viewAlert(alertId, message, patientName) {
    document.getElementById('modalPatient').textContent = patientName;
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('alertModal').classList.remove('hidden');
    // Focus trap for accessibility
    document.getElementById('alertModal').focus();
}

function closeModal() {
    document.getElementById('alertModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('alertModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Add loading state to filter form
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[method="GET"]');
    const filterButton = document.getElementById('filterButton');
    
    if (filterForm && filterButton) {
        filterForm.addEventListener('submit', function() {
            const filterText = filterButton.querySelector('.filter-text');
            const filterLoading = filterButton.querySelector('.filter-loading');
            
            if (filterText && filterLoading) {
                filterText.classList.add('hidden');
                filterLoading.classList.remove('hidden');
                filterButton.disabled = true;
            }
        });
    }
    
    // Auto-submit form when filters change (optional enhancement)
    const filterSelects = document.querySelectorAll('#status, #patient_id, #date_range');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Optional: Auto-submit on change
            // this.form.submit();
        });
    });
});

// Add smooth transitions for table rows
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 50}ms`;
        row.classList.add('animate-fade-in-up');
    });
});
</script>

<?php include '../includes/footer.php'; ?>