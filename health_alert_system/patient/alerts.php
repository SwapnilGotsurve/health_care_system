<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Handle alert status update (mark as seen)
if (isset($_POST['mark_seen']) && isset($_POST['alert_id'])) {
    $alert_id = (int)$_POST['alert_id'];
    
    // Verify the alert belongs to this patient
    $verify_query = "SELECT id FROM alerts WHERE id = $alert_id AND patient_id = $user_id";
    $verify_result = mysqli_query($connection, $verify_query);
    
    if (mysqli_num_rows($verify_result) > 0) {
        $update_query = "UPDATE alerts SET status = 'seen' WHERE id = $alert_id AND patient_id = $user_id";
        mysqli_query($connection, $update_query);
    }
}

// Pagination settings
$alerts_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $alerts_per_page;

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$status_filter = '';

switch ($filter) {
    case 'unread':
        $status_filter = "AND a.status = 'sent'";
        break;
    case 'read':
        $status_filter = "AND a.status = 'seen'";
        break;
    default:
        $status_filter = '';
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM alerts a 
               WHERE a.patient_id = $user_id $status_filter";
$count_result = mysqli_query($connection, $count_query);
$total_alerts = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_alerts / $alerts_per_page);

// Get alerts with doctor information
$alerts_query = "SELECT a.*, u.name as doctor_name 
                FROM alerts a 
                JOIN users u ON a.doctor_id = u.id 
                WHERE a.patient_id = $user_id $status_filter
                ORDER BY a.created_at DESC 
                LIMIT $alerts_per_page OFFSET $offset";

$alerts_result = mysqli_query($connection, $alerts_query);

// Get counts for filter tabs
$unread_count_query = "SELECT COUNT(*) as count FROM alerts WHERE patient_id = $user_id AND status = 'sent'";
$unread_count_result = mysqli_query($connection, $unread_count_query);
$unread_count = mysqli_fetch_assoc($unread_count_result)['count'];

$read_count_query = "SELECT COUNT(*) as count FROM alerts WHERE patient_id = $user_id AND status = 'seen'";
$read_count_result = mysqli_query($connection, $read_count_query);
$read_count = mysqli_fetch_assoc($read_count_result)['count'];

include '../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Health Alerts</h1>
                <p class="text-gray-600">Messages and recommendations from your healthcare providers</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <div class="flex items-center space-x-2">
                    <?php if ($unread_count > 0): ?>
                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo $unread_count; ?> new
                        </span>
                    <?php endif; ?>
                    <a href="dashboard.php" 
                       class="text-blue-600 hover:text-blue-800 font-medium">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow-md mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6">
                <a href="?filter=all" 
                   class="<?php echo ($filter === 'all') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> 
                          whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    All Alerts
                    <span class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">
                        <?php echo $total_alerts; ?>
                    </span>
                </a>
                
                <a href="?filter=unread" 
                   class="<?php echo ($filter === 'unread') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> 
                          whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Unread
                    <?php if ($unread_count > 0): ?>
                        <span class="ml-2 bg-red-100 text-red-800 py-0.5 px-2.5 rounded-full text-xs">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php else: ?>
                        <span class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">0</span>
                    <?php endif; ?>
                </a>
                
                <a href="?filter=read" 
                   class="<?php echo ($filter === 'read') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> 
                          whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Read
                    <span class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">
                        <?php echo $read_count; ?>
                    </span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Alerts List -->
    <?php if (mysqli_num_rows($alerts_result) > 0): ?>
    <div class="space-y-4">
        <?php while ($alert = mysqli_fetch_assoc($alerts_result)): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden 
                    <?php echo ($alert['status'] === 'sent') ? 'border-l-4 border-blue-500' : 'border-l-4 border-gray-300'; ?>">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Alert Header -->
                        <div class="flex items-center mb-3">
                            <div class="flex-shrink-0">
                                <?php if ($alert['status'] === 'sent'): ?>
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <?php else: ?>
                                    <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3 flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        From Dr. <?php echo htmlspecialchars($alert['doctor_name']); ?>
                                    </h3>
                                    <div class="flex items-center space-x-2">
                                        <?php if ($alert['status'] === 'sent'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                New
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Read
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500">
                                    <?php echo date('M j, Y \a\t g:i A', strtotime($alert['created_at'])); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Alert Message -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <p class="text-gray-800 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($alert['message'])); ?>
                            </p>
                        </div>

                        <!-- Alert Actions -->
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                Alert ID: #<?php echo $alert['id']; ?>
                            </div>
                            
                            <?php if ($alert['status'] === 'sent'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                    <button type="submit" name="mark_seen" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md transition-colors">
                                        Mark as Read
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-sm text-gray-500">
                                    ✓ Read on <?php echo date('M j, Y', strtotime($alert['created_at'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="bg-white rounded-lg shadow-md p-4 mt-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> 
                        to <span class="font-medium"><?php echo min($offset + $alerts_per_page, $total_alerts); ?></span> 
                        of <span class="font-medium"><?php echo $total_alerts; ?></span> alerts
                    </p>
                </div>
                
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <?php if ($filter === 'unread'): ?>
            <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-xl font-medium text-gray-900 mb-2">All Caught Up!</h3>
            <p class="text-gray-600 mb-6">You have no unread alerts at this time.</p>
        <?php elseif ($filter === 'read'): ?>
            <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <h3 class="text-xl font-medium text-gray-900 mb-2">No Read Alerts</h3>
            <p class="text-gray-600 mb-6">You haven't read any alerts yet.</p>
        <?php else: ?>
            <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z"></path>
            </svg>
            <h3 class="text-xl font-medium text-gray-900 mb-2">No Alerts Yet</h3>
            <p class="text-gray-600 mb-6">You haven't received any health alerts from your doctors.</p>
        <?php endif; ?>
        
        <div class="space-x-4">
            <a href="dashboard.php" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors">
                Go to Dashboard
            </a>
            <a href="health_history.php" 
               class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-md transition-colors">
                View Health History
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">💡 About Health Alerts</h3>
        <div class="text-sm text-blue-800 space-y-2">
            <p>• Health alerts are personalized messages from your healthcare providers</p>
            <p>• They may include health recommendations, medication reminders, or follow-up instructions</p>
            <p>• New alerts appear with a blue indicator and "New" badge</p>
            <p>• Click "Mark as Read" to acknowledge that you've seen the alert</p>
            <p>• You can filter alerts by read/unread status using the tabs above</p>
        </div>
    </div>
</div>

<script>
// Auto-refresh page if there are unread alerts (every 5 minutes)
<?php if ($unread_count > 0): ?>
setTimeout(function() {
    if (document.visibilityState === 'visible') {
        window.location.reload();
    }
}, 300000); // 5 minutes
<?php endif; ?>

// Smooth scroll to top after marking alert as read
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>