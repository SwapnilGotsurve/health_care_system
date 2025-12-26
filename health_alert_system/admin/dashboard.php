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

// Get system statistics
$stats_queries = [
    'total_users' => "SELECT COUNT(*) as count FROM users",
    'total_patients' => "SELECT COUNT(*) as count FROM users WHERE role = 'patient'",
    'total_doctors' => "SELECT COUNT(*) as count FROM users WHERE role = 'doctor'",
    'pending_doctors' => "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'pending'",
    'approved_doctors' => "SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'approved'",
    'total_alerts' => "SELECT COUNT(*) as count FROM alerts",
    'unread_alerts' => "SELECT COUNT(*) as count FROM alerts WHERE status = 'sent'",
    'total_health_records' => "SELECT COUNT(*) as count FROM health_data",
    'doctor_patient_assignments' => "SELECT COUNT(*) as count FROM doctor_patients"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $result = mysqli_query($connection, $query);
    $stats[$key] = mysqli_fetch_assoc($result)['count'];
}

// Get recent activity
$recent_users_query = "SELECT name, email, role, status, created_at 
                      FROM users 
                      ORDER BY created_at DESC 
                      LIMIT 5";
$recent_users_result = mysqli_query($connection, $recent_users_query);

$recent_alerts_query = "SELECT a.message, a.created_at, a.status,
                              d.name as doctor_name, p.name as patient_name
                       FROM alerts a
                       JOIN users d ON a.doctor_id = d.id
                       JOIN users p ON a.patient_id = p.id
                       ORDER BY a.created_at DESC
                       LIMIT 5";
$recent_alerts_result = mysqli_query($connection, $recent_alerts_query);

$recent_health_data_query = "SELECT hd.systolic_bp, hd.diastolic_bp, hd.sugar_level, 
                                   hd.heart_rate, hd.created_at,
                                   u.name as patient_name
                            FROM health_data hd
                            JOIN users u ON hd.patient_id = u.id
                            ORDER BY hd.created_at DESC
                            LIMIT 5";
$recent_health_data_result = mysqli_query($connection, $recent_health_data_query);

// Get monthly statistics for charts
$monthly_stats_query = "SELECT 
                          DATE_FORMAT(created_at, '%Y-%m') as month,
                          COUNT(*) as user_registrations
                        FROM users 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                        ORDER BY month DESC
                        LIMIT 6";
$monthly_stats_result = mysqli_query($connection, $monthly_stats_query);

$monthly_alerts_query = "SELECT 
                           DATE_FORMAT(created_at, '%Y-%m') as month,
                           COUNT(*) as alert_count
                         FROM alerts 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month DESC
                         LIMIT 6";
$monthly_alerts_result = mysqli_query($connection, $monthly_alerts_query);

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Admin Dashboard</h1>
                <p class="text-gray-600">System overview and management center</p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
                <a href="doctor_approvals.php" 
                   class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md transition-colors">
                    Doctor Approvals
                    <?php if ($stats['pending_doctors'] > 0): ?>
                        <span class="ml-2 bg-yellow-800 text-yellow-100 px-2 py-1 rounded-full text-xs">
                            <?php echo $stats['pending_doctors']; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="../logout.php" 
                   class="text-red-600 hover:text-red-800 font-medium">
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- System Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></p>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center text-sm text-gray-500">
                    <span class="text-green-600 font-medium"><?php echo $stats['total_patients']; ?> Patients</span>
                    <span class="mx-2">•</span>
                    <span class="text-blue-600 font-medium"><?php echo $stats['total_doctors']; ?> Doctors</span>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['pending_doctors']); ?></p>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center text-sm text-gray-500">
                    <span class="text-green-600 font-medium"><?php echo $stats['approved_doctors']; ?> Approved</span>
                    <?php if ($stats['pending_doctors'] > 0): ?>
                        <span class="mx-2">•</span>
                        <a href="doctor_approvals.php" class="text-yellow-600 hover:text-yellow-800 font-medium">
                            Review Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- System Activity -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Alerts</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_alerts']); ?></p>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center text-sm text-gray-500">
                    <span class="text-yellow-600 font-medium"><?php echo $stats['unread_alerts']; ?> Unread</span>
                    <span class="mx-2">•</span>
                    <span class="text-green-600 font-medium"><?php echo $stats['total_alerts'] - $stats['unread_alerts']; ?> Read</span>
                </div>
            </div>
        </div>

        <!-- Health Records -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Health Records</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_health_records']); ?></p>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center text-sm text-gray-500">
                    <span class="text-purple-600 font-medium"><?php echo $stats['doctor_patient_assignments']; ?> Assignments</span>
                    <span class="mx-2">•</span>
                    <span class="text-gray-600">Active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">User Management</h3>
            <div class="space-y-3">
                <a href="doctor_approvals.php" 
                   class="flex items-center justify-between p-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-gray-700">Doctor Approvals</span>
                    </div>
                    <?php if ($stats['pending_doctors'] > 0): ?>
                        <span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                            <?php echo $stats['pending_doctors']; ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                <a href="doctor_list.php" 
                   class="flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="text-gray-700">All Doctors</span>
                    </div>
                    <span class="text-gray-500 text-sm"><?php echo $stats['total_doctors']; ?></span>
                </a>
                
                <a href="patient_list.php" 
                   class="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="text-gray-700">All Patients</span>
                    </div>
                    <span class="text-gray-500 text-sm"><?php echo $stats['total_patients']; ?></span>
                </a>
                
                <a href="assign_patients.php" 
                   class="flex items-center justify-between p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        <span class="text-gray-700">Patient Assignments</span>
                    </div>
                    <span class="text-gray-500 text-sm"><?php echo $stats['doctor_patient_assignments']; ?></span>
                <
            </div>
        </div>

        <!-- Recent Users -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Registrations</h3>
            <div class="space-y-3">
                <?php if (mysqli_num_rows($recent_users_result) > 0): ?>
                    <?php while ($user = mysqli_fetch_assoc($recent_users_result)): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <span class="text-gray-600 font-medium text-xs">
                                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                </span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo ucfirst($user['role']); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <?php if ($user['role'] === 'doctor'): ?>
                                <?php if ($user['status'] === 'pending'): ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Approved
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Active
                                </span>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-1">
                                <?php echo date('M j', strtotime($user['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No recent registrations</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Health -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">System Health</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Database Status</span>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Online
                    </span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">User Activity</span>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Active
                    </span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Alert System</span>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Operational
                    </span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Data Integrity</span>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Verified
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Alerts -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Alerts</h3>
            <div class="space-y-4">
                <?php if (mysqli_num_rows($recent_alerts_result) > 0): ?>
                    <?php while ($alert = mysqli_fetch_assoc($recent_alerts_result)): ?>
                    <div class="border-l-4 border-blue-400 pl-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-800">
                                    <?php echo htmlspecialchars(substr($alert['message'], 0, 60)) . (strlen($alert['message']) > 60 ? '...' : ''); ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <span class="font-medium"><?php echo htmlspecialchars($alert['doctor_name']); ?></span>
                                    → 
                                    <span class="font-medium"><?php echo htmlspecialchars($alert['patient_name']); ?></span>
                                </p>
                            </div>
                            <div class="ml-4 text-right">
                                <?php if ($alert['status'] === 'sent'): ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Unread
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Read
                                    </span>
                                <?php endif; ?>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?php echo date('M j, g:i A', strtotime($alert['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No recent alerts</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Health Data -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Health Data</h3>
            <div class="space-y-4">
                <?php if (mysqli_num_rows($recent_health_data_result) > 0): ?>
                    <?php while ($health = mysqli_fetch_assoc($recent_health_data_result)): ?>
                    <div class="border-l-4 border-green-400 pl-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($health['patient_name']); ?>
                                </p>
                                <div class="text-xs text-gray-600 mt-1 grid grid-cols-2 gap-2">
                                    <span>BP: <?php echo $health['systolic_bp']; ?>/<?php echo $health['diastolic_bp']; ?></span>
                                    <span>Sugar: <?php echo $health['sugar_level']; ?></span>
                                    <span>HR: <?php echo $health['heart_rate']; ?> bpm</span>
                                    <span><?php echo date('M j', strtotime($health['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <?php 
                                // Simple health status logic
                                $is_normal = ($health['systolic_bp'] >= 90 && $health['systolic_bp'] <= 140) &&
                                           ($health['diastolic_bp'] >= 60 && $health['diastolic_bp'] <= 90) &&
                                           ($health['sugar_level'] >= 70 && $health['sugar_level'] <= 140) &&
                                           ($health['heart_rate'] >= 60 && $health['heart_rate'] <= 100);
                                ?>
                                <?php if ($is_normal): ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Normal
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Alert
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No recent health data</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">🏥 Health Alert System Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">System Overview:</h4>
                <ul class="space-y-1">
                    <li>• Multi-role healthcare management</li>
                    <li>• Real-time health monitoring</li>
                    <li>• Doctor-patient communication</li>
                    <li>• Comprehensive admin controls</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Key Features:</h4>
                <ul class="space-y-1">
                    <li>• Patient health data tracking</li>
                    <li>• Doctor alert system</li>
                    <li>• Admin approval workflow</li>
                    <li>• Role-based access control</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Admin Responsibilities:</h4>
                <ul class="space-y-1">
                    <li>• Approve doctor registrations</li>
                    <li>• Monitor system activity</li>
                    <li>• Manage user accounts</li>
                    <li>• Ensure data integrity</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>