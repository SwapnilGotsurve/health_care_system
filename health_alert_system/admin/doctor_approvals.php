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

$success = '';
$error = '';

// Handle approval/rejection actions
if ($_POST) {
    $action = $_POST['action'];
    $doctor_id = (int)$_POST['doctor_id'];
    
    if ($action === 'approve') {
        // Approve doctor
        $approve_query = "UPDATE users SET status = 'approved' WHERE id = $doctor_id AND role = 'doctor' AND status = 'pending'";
        
        if (mysqli_query($connection, $approve_query)) {
            if (mysqli_affected_rows($connection) > 0) {
                $success = 'Doctor approved successfully!';
            } else {
                $error = 'Doctor not found or already processed.';
            }
        } else {
            $error = 'Failed to approve doctor. Please try again.';
        }
        
    } elseif ($action === 'reject') {
        // Reject doctor (delete account)
        $reject_query = "DELETE FROM users WHERE id = $doctor_id AND role = 'doctor' AND status = 'pending'";
        
        if (mysqli_query($connection, $reject_query)) {
            if (mysqli_affected_rows($connection) > 0) {
                $success = 'Doctor registration rejected and account removed.';
            } else {
                $error = 'Doctor not found or already processed.';
            }
        } else {
            $error = 'Failed to reject doctor. Please try again.';
        }
    }
}

// Get pending doctors
$pending_doctors_query = "SELECT id, name, email, created_at 
                         FROM users 
                         WHERE role = 'doctor' AND status = 'pending' 
                         ORDER BY created_at ASC";

$pending_doctors_result = mysqli_query($connection, $pending_doctors_query);

// Get statistics
$stats_query = "SELECT 
                   COUNT(*) as total_pending,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as pending_today,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as pending_week
               FROM users 
               WHERE role = 'doctor' AND status = 'pending'";

$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent approvals/rejections for activity log
$recent_activity_query = "SELECT 
                            u.name, u.email, u.status, u.created_at,
                            'approved' as action_type
                          FROM users u
                          WHERE u.role = 'doctor' AND u.status = 'approved'
                          AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                          ORDER BY u.created_at DESC
                          LIMIT 10";

$recent_activity_result = mysqli_query($connection, $recent_activity_query);

include '../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Doctor Approvals</h1>
                <p class="text-gray-600">Review and approve doctor registration requests</p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
                <a href="doctor_list.php" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    All Doctors
                </a>
                <a href="dashboard.php" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_pending']); ?></p>
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
                    <p class="text-sm font-medium text-gray-600">Today</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending_today']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">This Week</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending_week']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if (mysqli_num_rows($pending_doctors_result) > 0): ?>
    <!-- Pending Doctors List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                Pending Doctor Registrations
                <span class="text-sm text-gray-500 ml-2">
                    (<?php echo mysqli_num_rows($pending_doctors_result); ?> pending)
                </span>
            </h2>
        </div>

        <div class="divide-y divide-gray-200">
            <?php while ($doctor = mysqli_fetch_assoc($pending_doctors_result)): ?>
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <span class="text-yellow-600 font-medium text-lg">
                                    <?php echo strtoupper(substr($doctor['name'], 0, 2)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                <?php echo htmlspecialchars($doctor['name']); ?>
                            </h3>
                            <p class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($doctor['email']); ?>
                            </p>
                            <div class="flex items-center mt-2 text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Registered: <?php echo date('M j, Y g:i A', strtotime($doctor['created_at'])); ?>
                                <span class="mx-2">•</span>
                                <span class="text-yellow-600 font-medium">
                                    <?php 
                                    $hours_ago = floor((time() - strtotime($doctor['created_at'])) / 3600);
                                    if ($hours_ago < 1) {
                                        echo 'Less than 1 hour ago';
                                    } elseif ($hours_ago < 24) {
                                        echo $hours_ago . ' hours ago';
                                    } else {
                                        echo floor($hours_ago / 24) . ' days ago';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <!-- Approve Button -->
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to approve this doctor?');">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Approve
                            </button>
                        </form>

                        <!-- Reject Button -->
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reject this doctor? This will permanently delete their account.');">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                            <button type="submit" 
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Reject
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="mt-4 bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Registration Details</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div>
                            <span class="font-medium">Account Type:</span> Doctor
                        </div>
                        <div>
                            <span class="font-medium">Status:</span> 
                            <span class="text-yellow-600 font-medium">Pending Approval</span>
                        </div>
                        <div>
                            <span class="font-medium">Registration Date:</span> 
                            <?php echo date('F j, Y', strtotime($doctor['created_at'])); ?>
                        </div>
                        <div>
                            <span class="font-medium">Waiting Time:</span> 
                            <?php 
                            $waiting_hours = floor((time() - strtotime($doctor['created_at'])) / 3600);
                            if ($waiting_hours < 24) {
                                echo $waiting_hours . ' hours';
                            } else {
                                echo floor($waiting_hours / 24) . ' days, ' . ($waiting_hours % 24) . ' hours';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- No Pending Doctors -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center mb-6">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">All Caught Up!</h3>
        <p class="text-gray-600 mb-6">There are no pending doctor registrations to review at this time.</p>
        <div class="space-x-4">
            <a href="doctor_list.php" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors">
                View All Doctors
            </a>
            <a href="dashboard.php" 
               class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-md transition-colors">
                Back to Dashboard
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <?php if (mysqli_num_rows($recent_activity_result) > 0): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Approvals (Last 7 Days)</h3>
        <div class="space-y-3">
            <?php while ($activity = mysqli_fetch_assoc($recent_activity_result)): ?>
            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($activity['name']); ?>
                        </p>
                        <p class="text-xs text-gray-600">
                            <?php echo htmlspecialchars($activity['email']); ?>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Approved
                    </span>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                    </p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Approval Guidelines -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">👨‍⚕️ Doctor Approval Guidelines</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">Approval Criteria:</h4>
                <ul class="space-y-1">
                    <li>• Valid professional email address</li>
                    <li>• Complete registration information</li>
                    <li>• Reasonable registration timing</li>
                    <li>• No duplicate accounts</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Review Process:</h4>
                <ul class="space-y-1">
                    <li>• Review registration details carefully</li>
                    <li>• Approve legitimate medical professionals</li>
                    <li>• Reject suspicious or incomplete registrations</li>
                    <li>• Monitor for fraudulent activity</li>
                </ul>
            </div>
        </div>
        <div class="mt-4 p-4 bg-blue-100 rounded-lg">
            <p class="text-blue-900 text-sm">
                <strong>Important:</strong> Approved doctors will gain access to patient data and alert systems. 
                Only approve registrations from verified medical professionals.
            </p>
        </div>
    </div>
</div>

<script>
// Auto-refresh page every 5 minutes to check for new registrations
setTimeout(function() {
    window.location.reload();
}, 300000); // 5 minutes

// Add confirmation dialogs for actions
document.addEventListener('DOMContentLoaded', function() {
    // Add visual feedback for form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.innerHTML = button.innerHTML.replace(/Approve|Reject/, 'Processing...');
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>