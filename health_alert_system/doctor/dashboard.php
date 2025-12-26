<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$page_title = 'Doctor Dashboard';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get assigned patients count
$patients_query = "SELECT COUNT(*) as total FROM doctor_patients WHERE doctor_id = $user_id";
$patients_result = mysqli_query($connection, $patients_query);
$total_patients = mysqli_fetch_assoc($patients_result)['total'];

// Get total alerts sent by this doctor
$sent_alerts_query = "SELECT COUNT(*) as total FROM alerts WHERE doctor_id = $user_id";
$sent_alerts_result = mysqli_query($connection, $sent_alerts_query);
$total_alerts_sent = mysqli_fetch_assoc($sent_alerts_result)['total'];

// Get unread alerts count (alerts with 'sent' status)
$unread_alerts_query = "SELECT COUNT(*) as total FROM alerts WHERE doctor_id = $user_id AND status = 'sent'";
$unread_alerts_result = mysqli_query($connection, $unread_alerts_query);
$unread_alerts_count = mysqli_fetch_assoc($unread_alerts_result)['total'];

// Get recent patient health data count (last 24 hours)
$recent_data_query = "SELECT COUNT(DISTINCT hd.patient_id) as count 
                     FROM health_data hd 
                     JOIN doctor_patients dp ON hd.patient_id = dp.patient_id 
                     WHERE dp.doctor_id = $user_id 
                     AND hd.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$recent_data_result = mysqli_query($connection, $recent_data_query);
$recent_data_count = mysqli_fetch_assoc($recent_data_result)['count'];

// Get patients with recent health data (last 7 days) for activity overview
$active_patients_query = "SELECT u.name, u.id, COUNT(hd.id) as record_count, MAX(hd.created_at) as last_entry
                         FROM users u
                         JOIN doctor_patients dp ON u.id = dp.patient_id
                         LEFT JOIN health_data hd ON u.id = hd.patient_id 
                         WHERE dp.doctor_id = $user_id 
                         AND hd.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                         GROUP BY u.id, u.name
                         ORDER BY last_entry DESC
                         LIMIT 5";
$active_patients_result = mysqli_query($connection, $active_patients_query);

// Get patients needing attention (no recent data in 7 days)
$inactive_patients_query = "SELECT u.name, u.id, MAX(hd.created_at) as last_entry
                           FROM users u
                           JOIN doctor_patients dp ON u.id = dp.patient_id
                           LEFT JOIN health_data hd ON u.id = hd.patient_id
                           WHERE dp.doctor_id = $user_id
                           GROUP BY u.id, u.name
                           HAVING last_entry IS NULL OR last_entry < DATE_SUB(NOW(), INTERVAL 7 DAY)
                           ORDER BY last_entry ASC
                           LIMIT 5";
$inactive_patients_result = mysqli_query($connection, $inactive_patients_query);

// Get recent alerts sent by this doctor
$recent_alerts_query = "SELECT a.message, a.created_at, u.name as patient_name, a.status
                       FROM alerts a
                       JOIN users u ON a.patient_id = u.id
                       WHERE a.doctor_id = $user_id
                       ORDER BY a.created_at DESC
                       LIMIT 5";
$recent_alerts_result = mysqli_query($connection, $recent_alerts_query);

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Welcome Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 animate-fade-in-up">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome, Dr. <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p class="text-gray-600">Here's your patient overview and recent activity.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <?php
        echo render_data_card(
            'Assigned Patients',
            $total_patients,
            'Total under care',
            'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
            'primary'
        );
        
        echo render_data_card(
            'Active Today',
            $recent_data_count,
            'Patients with new data',
            'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'success'
        );
        
        echo render_data_card(
            'Alerts Sent',
            $total_alerts_sent,
            'Total communications',
            'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
            'primary'
        );
        
        echo render_data_card(
            'Unread Alerts',
            $unread_alerts_count,
            'Awaiting patient response',
            'M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z',
            $unread_alerts_count > 0 ? 'warning' : 'primary'
        );
        ?>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <a href="patient_list.php" class="group bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-lg shadow-md p-6 transition-all duration-300 hover-lift animate-fade-in-up stagger-1">
            <div class="flex items-center">
                <div class="p-2 bg-white bg-opacity-20 rounded-lg mr-4 group-hover:bg-opacity-30 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">View Patients</h3>
                    <p class="text-primary-100">Manage assigned patients</p>
                </div>
            </div>
        </a>

        <a href="send_alert.php" class="group bg-gradient-to-r from-success-600 to-success-700 hover:from-success-700 hover:to-success-800 text-white rounded-lg shadow-md p-6 transition-all duration-300 hover-lift animate-fade-in-up stagger-2">
            <div class="flex items-center">
                <div class="p-2 bg-white bg-opacity-20 rounded-lg mr-4 group-hover:bg-opacity-30 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Send Alert</h3>
                    <p class="text-success-100">Send health alerts to patients</p>
                </div>
            </div>
        </a>

        <a href="sent_alerts.php" class="group bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-lg shadow-md p-6 transition-all duration-300 hover-lift animate-fade-in-up stagger-3">
            <div class="flex items-center">
                <div class="p-2 bg-white bg-opacity-20 rounded-lg mr-4 group-hover:bg-opacity-30 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Alert History</h3>
                    <p class="text-purple-100">View sent alerts</p>
                </div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Active Patients -->
        <div class="bg-white rounded-lg shadow-md p-6 animate-fade-in-up stagger-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <div class="w-6 h-6 bg-green-100 rounded-lg flex items-center justify-center mr-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
                Recent Patient Activity
            </h2>
            <?php if (mysqli_num_rows($active_patients_result) > 0): ?>
                <div class="space-y-3">
                    <?php while ($patient = mysqli_fetch_assoc($active_patients_result)): ?>
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border-l-4 border-green-400 hover-lift">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse-slow"></div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></p>
                                <p class="text-sm text-gray-600">
                                    <?php echo render_status_badge($patient['record_count'] . ' records', 'success'); ?>
                                    <span class="ml-2 text-gray-500">
                                        Last: <?php echo date('M j, g:i A', strtotime($patient['last_entry'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <?php echo render_button('View', 'button', 'primary', 'sm', false, ['onclick' => "window.location.href='patient_stats.php?patient_id={$patient['id']}'"]) ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <?php echo render_loading_skeleton(3, ['80%', '60%', '40%']); ?>
                    <p class="text-gray-500 mt-4">No recent patient activity</p>
                </div>
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <a href="patient_list.php" class="inline-flex items-center text-primary-600 hover:text-primary-800 font-medium transition-colors hover-underline">
                    View All Patients
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Patients Needing Attention -->
        <div class="bg-white rounded-lg shadow-md p-6 animate-fade-in-up stagger-5">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <div class="w-6 h-6 bg-yellow-100 rounded-lg flex items-center justify-center mr-2">
                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                Patients Needing Attention
            </h2>
            <?php if (mysqli_num_rows($inactive_patients_result) > 0): ?>
                <div class="space-y-3">
                    <?php while ($patient = mysqli_fetch_assoc($inactive_patients_result)): ?>
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border-l-4 border-yellow-400 hover-lift">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></p>
                                <p class="text-sm text-gray-600">
                                    <?php if ($patient['last_entry']): ?>
                                        <?php echo render_status_badge('Last: ' . date('M j, Y', strtotime($patient['last_entry'])), 'warning'); ?>
                                    <?php else: ?>
                                        <?php echo render_status_badge('No data recorded', 'danger'); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php echo render_button('Send Alert', 'button', 'warning', 'sm', false, ['onclick' => "window.location.href='send_alert.php?patient_id={$patient['id']}'"]) ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-green-600 font-medium">All patients are active!</p>
                    <p class="text-gray-500 text-sm">All your patients have recent health data</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="bg-white rounded-lg shadow-md p-6 animate-fade-in-up stagger-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Alerts Sent</h2>
        <?php if (mysqli_num_rows($recent_alerts_result) > 0): ?>
            <?php
            $headers = ['Patient', 'Message', 'Sent', 'Status'];
            $rows = [];
            
            mysqli_data_seek($recent_alerts_result, 0);
            while ($alert = mysqli_fetch_assoc($recent_alerts_result)) {
                $status_badge = $alert['status'] === 'sent' 
                    ? render_status_badge('Unread', 'warning') 
                    : render_status_badge('Read', 'success');
                    
                $rows[] = [
                    htmlspecialchars($alert['patient_name']),
                    htmlspecialchars(substr($alert['message'], 0, 60)) . (strlen($alert['message']) > 60 ? '...' : ''),
                    date('M j, g:i A', strtotime($alert['created_at'])),
                    $status_badge
                ];
            }
            
            echo render_data_table($headers, $rows);
            ?>
            <div class="mt-6 text-center">
                <a href="sent_alerts.php" class="inline-flex items-center text-primary-600 hover:text-primary-800 font-medium transition-colors hover-underline">
                    View All Sent Alerts
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Alerts Sent Yet</h3>
                <p class="text-gray-600 mb-6">Start communicating with your patients by sending health alerts.</p>
                <?php echo render_button('Send First Alert', 'button', 'primary', 'lg', false, ['onclick' => "window.location.href='send_alert.php'"]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>