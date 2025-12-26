<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Pagination settings
$alerts_per_page = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $alerts_per_page;

// Filter settings
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$patient_filter = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$date_filter = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';

// Build WHERE clause for filters
$where_conditions = ["a.doctor_id = $user_id"];

if ($status_filter !== 'all') {
    $status_filter = mysqli_real_escape_string($connection, $status_filter);
    $where_conditions[] = "a.status = '$status_filter'";
}

if ($patient_filter > 0) {
    $where_conditions[] = "a.patient_id = $patient_filter";
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

$count_result = mysqli_query($connection, $count_query);
$total_alerts = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_alerts / $alerts_per_page);

// Get sent alerts with patient information
$alerts_query = "SELECT a.id, a.message, a.status, a.created_at, 
                        u.name as patient_name, u.email as patient_email
                FROM alerts a
                JOIN users u ON a.patient_id = u.id
                WHERE $where_clause
                ORDER BY a.created_at DESC
                LIMIT $alerts_per_page OFFSET $offset";

$alerts_result = mysqli_query($connection, $alerts_query);

// Get patients for filter dropdown
$patients_query = "SELECT DISTINCT u.id, u.name, u.email 
                  FROM users u 
                  JOIN doctor_patients dp ON u.id = dp.patient_id 
                  WHERE dp.doctor_id = $user_id AND u.role = 'patient' 
                  ORDER BY u.name ASC";

$patients_result = mysqli_query($connection, $patients_query);

// Get statistics
$stats_query = "SELECT 
                   COUNT(*) as total_sent,
                   SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as unread_count,
                   SUM(CASE WHEN status = 'seen' THEN 1 ELSE 0 END) as read_count,
                   COUNT(DISTINCT patient_id) as patients_contacted
               FROM alerts 
               WHERE doctor_id = $user_id";

$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include '../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Sent Alerts History</h1>
                <p class="text-gray-600">View and manage all alerts you've sent to patients</p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
                <a href="send_alert.php" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                    Send New Alert
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Sent</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_sent']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-5a7.5 7.5 0 0 0-15 0v5h5l-5 5-5-5h5V7a9.5 9.5 0 0 1 19 0v10z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Unread</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['unread_count']); ?></p>
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
                    <p class="text-sm font-medium text-gray-600">Read</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['read_count']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Patients</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['patients_contacted']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Status</option>
                    <option value="sent" <?php echo ($status_filter === 'sent') ? 'selected' : ''; ?>>Unread</option>
                    <option value="seen" <?php echo ($status_filter === 'seen') ? 'selected' : ''; ?>>Read</option>
                </select>
            </div>

            <!-- Patient Filter -->
            <div>
                <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient</label>
                <select id="patient_id" name="patient_id" 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="0">All Patients</option>
                    <?php 
                    mysqli_data_seek($patients_result, 0);
                    while ($patient = mysqli_fetch_assoc($patients_result)): 
                    ?>
                        <option value="<?php echo $patient['id']; ?>" 
                                <?php echo ($patient_filter == $patient['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($patient['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div>
                <label for="date_range" class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <select id="date_range" name="date_range" 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" <?php echo ($date_filter === 'all') ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo ($date_filter === 'today') ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo ($date_filter === 'week') ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="month" <?php echo ($date_filter === 'month') ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="year" <?php echo ($date_filter === 'year') ? 'selected' : ''; ?>>Last Year</option>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    Apply Filters
                </button>
                <a href="sent_alerts.php" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <?php if (mysqli_num_rows($alerts_result) > 0): ?>
    <!-- Alerts List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                Sent Alerts 
                <?php if ($total_alerts > 0): ?>
                    <span class="text-sm text-gray-500">
                        (<?php echo number_format($total_alerts); ?> total)
                    </span>
                <?php endif; ?>
            </h2>
        </div>

        <div class="divide-y divide-gray-200">
            <?php while ($alert = mysqli_fetch_assoc($alerts_result)): ?>
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Patient Info -->
                        <div class="flex items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-medium text-sm">
                                        <?php echo strtoupper(substr($alert['patient_name'], 0, 2)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($alert['patient_name']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($alert['patient_email']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Alert Message -->
                        <div class="mb-3">
                            <p class="text-gray-800 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($alert['message'])); ?>
                            </p>
                        </div>

                        <!-- Alert Metadata -->
                        <div class="flex items-center text-sm text-gray-500 space-x-4">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                Alert ID: #<?php echo $alert['id']; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="flex-shrink-0 ml-4">
                        <?php if ($alert['status'] === 'sent'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                Unread
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Read
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <a href="send_alert.php?patient_id=<?php echo $alert['patient_id']; ?>" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Send Another Alert
                        </a>
                        <a href="patient_stats.php?patient_id=<?php echo $alert['patient_id']; ?>" 
                           class="text-green-600 hover:text-green-800 text-sm font-medium">
                            View Patient Data
                        </a>
                    </div>
                    <div class="text-xs text-gray-400">
                        <?php 
                        $time_ago = time() - strtotime($alert['created_at']);
                        if ($time_ago < 3600) {
                            echo floor($time_ago / 60) . ' minutes ago';
                        } elseif ($time_ago < 86400) {
                            echo floor($time_ago / 3600) . ' hours ago';
                        } else {
                            echo floor($time_ago / 86400) . ' days ago';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Showing <?php echo number_format($offset + 1); ?> to 
            <?php echo number_format(min($offset + $alerts_per_page, $total_alerts)); ?> of 
            <?php echo number_format($total_alerts); ?> alerts
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
    <!-- No Alerts State -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No Alerts Found</h3>
        <p class="text-gray-600 mb-6">
            <?php if ($status_filter !== 'all' || $patient_filter > 0 || $date_filter !== 'all'): ?>
                No alerts match your current filters. Try adjusting your search criteria.
            <?php else: ?>
                You haven't sent any alerts yet. Start communicating with your patients by sending health alerts.
            <?php endif; ?>
        </p>
        <div class="space-x-4">
            <a href="send_alert.php" 
               class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md transition-colors">
                Send First Alert
            </a>
            <?php if ($status_filter !== 'all' || $patient_filter > 0 || $date_filter !== 'all'): ?>
                <a href="sent_alerts.php" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-md transition-colors">
                    Clear Filters
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Export Options -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">📊 Export & Analytics</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">Quick Stats:</h4>
                <ul class="space-y-1">
                    <li>• Response Rate: <?php echo $stats['total_sent'] > 0 ? round(($stats['read_count'] / $stats['total_sent']) * 100, 1) : 0; ?>%</li>
                    <li>• Avg per Patient: <?php echo $stats['patients_contacted'] > 0 ? round($stats['total_sent'] / $stats['patients_contacted'], 1) : 0; ?></li>
                    <li>• Most Active: <?php echo date('M Y'); ?></li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Communication Tips:</h4>
                <ul class="space-y-1">
                    <li>• Keep messages clear and actionable</li>
                    <li>• Follow up on unread alerts</li>
                    <li>• Use positive reinforcement</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Best Practices:</h4>
                <ul class="space-y-1">
                    <li>• Send alerts at consistent times</li>
                    <li>• Personalize messages when possible</li>
                    <li>• Monitor patient engagement</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>