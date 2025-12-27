<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/error_handler.php';

$page_title = 'Doctor Dashboard';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Initialize error handling
$errors = [];
$success_messages = [];

// Initialize variables with default values
$total_patients = 0;
$total_alerts_sent = 0;
$unread_alerts_count = 0;
$recent_data_count = 0;
$active_patients_result = false;
$inactive_patients_result = false;
$recent_alerts_result = false;

try {
    // Check database connection first
    if (!$connection) {
        throw new Exception("Database connection failed");
    }

    // Get assigned patients count
    $patients_query = "SELECT COUNT(*) as total FROM doctor_patients WHERE doctor_id = ?";
    $patients_stmt = mysqli_prepare($connection, $patients_query);
    if ($patients_stmt) {
        mysqli_stmt_bind_param($patients_stmt, "i", $user_id);
        if (!mysqli_stmt_execute($patients_stmt)) {
            throw new Exception("Failed to execute patients count query: " . mysqli_stmt_error($patients_stmt));
        }
        $patients_result = mysqli_stmt_get_result($patients_stmt);
        $total_patients = mysqli_fetch_assoc($patients_result)['total'];
        mysqli_stmt_close($patients_stmt);
    } else {
        throw new Exception("Failed to prepare patients count query: " . mysqli_error($connection));
    }

    // Get total alerts sent by this doctor
    $sent_alerts_query = "SELECT COUNT(*) as total FROM alerts WHERE doctor_id = ?";
    $sent_alerts_stmt = mysqli_prepare($connection, $sent_alerts_query);
    if ($sent_alerts_stmt) {
        mysqli_stmt_bind_param($sent_alerts_stmt, "i", $user_id);
        if (!mysqli_stmt_execute($sent_alerts_stmt)) {
            throw new Exception("Failed to execute alerts count query: " . mysqli_stmt_error($sent_alerts_stmt));
        }
        $sent_alerts_result = mysqli_stmt_get_result($sent_alerts_stmt);
        $total_alerts_sent = mysqli_fetch_assoc($sent_alerts_result)['total'];
        mysqli_stmt_close($sent_alerts_stmt);
    } else {
        throw new Exception("Failed to prepare alerts count query: " . mysqli_error($connection));
    }

    // Get unread alerts count (alerts with 'sent' status)
    $unread_alerts_query = "SELECT COUNT(*) as total FROM alerts WHERE doctor_id = ? AND status = 'sent'";
    $unread_alerts_stmt = mysqli_prepare($connection, $unread_alerts_query);
    if ($unread_alerts_stmt) {
        mysqli_stmt_bind_param($unread_alerts_stmt, "i", $user_id);
        if (!mysqli_stmt_execute($unread_alerts_stmt)) {
            throw new Exception("Failed to execute unread alerts query: " . mysqli_stmt_error($unread_alerts_stmt));
        }
        $unread_alerts_result = mysqli_stmt_get_result($unread_alerts_stmt);
        $unread_alerts_count = mysqli_fetch_assoc($unread_alerts_result)['total'];
        mysqli_stmt_close($unread_alerts_stmt);
    } else {
        throw new Exception("Failed to prepare unread alerts query: " . mysqli_error($connection));
    }

    // Get recent patient health data count (last 24 hours)
    $recent_data_query = "SELECT COUNT(DISTINCT hd.patient_id) as count 
                         FROM health_data hd 
                         JOIN doctor_patients dp ON hd.patient_id = dp.patient_id 
                         WHERE dp.doctor_id = ? 
                         AND hd.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $recent_data_stmt = mysqli_prepare($connection, $recent_data_query);
    if ($recent_data_stmt) {
        mysqli_stmt_bind_param($recent_data_stmt, "i", $user_id);
        if (!mysqli_stmt_execute($recent_data_stmt)) {
            throw new Exception("Failed to execute recent data query: " . mysqli_stmt_error($recent_data_stmt));
        }
        $recent_data_result = mysqli_stmt_get_result($recent_data_stmt);
        $recent_data_count = mysqli_fetch_assoc($recent_data_result)['count'];
        mysqli_stmt_close($recent_data_stmt);
    } else {
        throw new Exception("Failed to prepare recent data query: " . mysqli_error($connection));
    }

    // Get patients with recent health data (last 7 days) for activity overview
    $active_patients_query = "SELECT u.name, u.id, COUNT(hd.id) as record_count, MAX(hd.created_at) as last_entry
                             FROM users u
                             JOIN doctor_patients dp ON u.id = dp.patient_id
                             LEFT JOIN health_data hd ON u.id = hd.patient_id 
                             WHERE dp.doctor_id = ? 
                             AND hd.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                             GROUP BY u.id, u.name
                             ORDER BY last_entry DESC
                             LIMIT 5";
    $active_patients_stmt = mysqli_prepare($connection, $active_patients_query);
    if ($active_patients_stmt) {
        mysqli_stmt_bind_param($active_patients_stmt, "i", $user_id);
        if (!mysqli_stmt_execute($active_patients_stmt)) {
            // This query might fail if no health data exists, which is OK
            $active_patients_result = false;
        } else {
            $active_patients_result = mysqli_stmt_get_result($active_patients_stmt);
        }
        mysqli_stmt_close($active_patients_stmt);
    } else {
        $active_patients_result = false;
    }

    // Get patients needing attention (no recent data in 7 days)
    $inactive_patients_query = "SELECT u.name, u.id, MAX(hd.created_at) as last_entry
                               FROM users u
                               JOIN doctor_patients dp ON u.id = dp.patient_id
                               LEFT JOIN health_data hd ON u.id = hd.patient_id
                               WHERE dp.doctor_id = ?
                               GROUP BY u.id, u.name
                               HAVING last_entry IS NULL OR last_entry < DATE_SUB(NOW(), INTERVAL 7 DAY)
                               ORDER BY last_entry ASC
                               LIMIT 5";
    $inactive_patients_stmt = mysqli_prepare($connection, $inactive_patients_query);
    if ($inactive_patients_stmt) {
        mysqli_stmt_bind_param($inactive_patients_stmt, "i", $user_id);
        if (!mysqli_stmt_execute($inactive_patients_stmt)) {
            // This query might fail if no patients exist, which is OK
            $inactive_patients_result = false;
        } else {
            $inactive_patients_result = mysqli_stmt_get_result($inactive_patients_stmt);
        }
        mysqli_stmt_close($inactive_patients_stmt);
    } else {
        $inactive_patients_result = false;
    }

    // Get recent alerts sent by this doctor
    $recent_alerts_query = "SELECT a.message, a.created_at, u.name as patient_name, a.status
                           FROM alerts a
                           JOIN users u ON a.patient_id = u.id
                           WHERE a.doctor_id = ?
                           ORDER BY a.created_at DESC
                           LIMIT 5";
    $recent_alerts_stmt = mysqli_prepare($connection, $recent_alerts_query);
    if ($recent_alerts_stmt) {
        mysqli_stmt_bind_param($recent_alerts_stmt, "i", $user_id);
        if (!mysqli_stmt_execute($recent_alerts_stmt)) {
            // This query might fail if no alerts exist, which is OK
            $recent_alerts_result = false;
        } else {
            $recent_alerts_result = mysqli_stmt_get_result($recent_alerts_stmt);
        }
        mysqli_stmt_close($recent_alerts_stmt);
    } else {
        $recent_alerts_result = false;
    }

} catch (Exception $e) {
    error_log("Doctor Dashboard Error: " . $e->getMessage());
    
    // Show user-friendly error instead of technical details
    $errors[] = "Unable to load dashboard data at this time. Please try refreshing the page.";
    
    // Ensure all variables are set to prevent further errors
    $total_patients = $total_patients ?? 0;
    $total_alerts_sent = $total_alerts_sent ?? 0;
    $unread_alerts_count = $unread_alerts_count ?? 0;
    $recent_data_count = $recent_data_count ?? 0;
    $active_patients_result = $active_patients_result ?? false;
    $inactive_patients_result = $inactive_patients_result ?? false;
    $recent_alerts_result = $recent_alerts_result ?? false;
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

    <!-- Welcome Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 animate-fade-in-up">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome, Dr. <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p class="text-gray-600">Here's your patient overview and recent activity.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Assigned Patients Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $total_patients; ?></p>
                    <p class="text-gray-600">Assigned Patients</p>
                    <p class="text-sm text-gray-500">Total under care</p>
                </div>
            </div>
        </div>
        
        <!-- Active Today Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $recent_data_count; ?></p>
                    <p class="text-gray-600">Active Today</p>
                    <p class="text-sm text-gray-500">Patients with new data</p>
                </div>
            </div>
        </div>
        
        <!-- Alerts Sent Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $total_alerts_sent; ?></p>
                    <p class="text-gray-600">Alerts Sent</p>
                    <p class="text-sm text-gray-500">Total communications</p>
                </div>
            </div>
        </div>
        
        <!-- Unread Alerts Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $unread_alerts_count; ?></p>
                    <p class="text-gray-600">Unread Alerts</p>
                    <p class="text-sm text-gray-500">Awaiting patient response</p>
                </div>
            </div>
        </div>
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
        <!-- Assigned Patients Overview -->
        <div class="bg-white rounded-lg shadow-md p-6 animate-fade-in-up stagger-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <div class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                Your Assigned Patients (<?php echo $total_patients; ?>)
            </h2>
            
            <?php if ($total_patients > 0): ?>
                <div class="space-y-3 mb-4">
                    <?php
                    // Get a quick overview of assigned patients with error handling
                    $patient_overview_query = "SELECT u.id, u.name, u.email, dp.created_at as assigned_date,
                                              COUNT(hd.id) as health_records,
                                              MAX(hd.created_at) as last_health_data
                                              FROM users u 
                                              JOIN doctor_patients dp ON u.id = dp.patient_id 
                                              LEFT JOIN health_data hd ON u.id = hd.patient_id
                                              WHERE dp.doctor_id = ? AND u.role = 'patient'
                                              GROUP BY u.id, u.name, u.email, dp.created_at
                                              ORDER BY dp.created_at DESC
                                              LIMIT 3";
                    
                    $patient_overview_stmt = mysqli_prepare($connection, $patient_overview_query);
                    if ($patient_overview_stmt) {
                        mysqli_stmt_bind_param($patient_overview_stmt, "i", $user_id);
                        mysqli_stmt_execute($patient_overview_stmt);
                        $patient_overview_result = mysqli_stmt_get_result($patient_overview_stmt);
                        
                        while ($patient = mysqli_fetch_assoc($patient_overview_result)):
                            $days_since_data = $patient['last_health_data'] ? 
                                floor((time() - strtotime($patient['last_health_data'])) / (60 * 60 * 24)) : null;
                            $status_color = $days_since_data === null ? 'gray' : 
                                           ($days_since_data > 7 ? 'warning' : 
                                           ($days_since_data > 3 ? 'info' : 'success'));
                        ?>
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200 hover-lift">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-blue-600 font-medium text-sm">
                                    <?php echo strtoupper(substr($patient['name'], 0, 2)); ?>
                                </span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo $patient['health_records']; ?> health records</p>
                                <p class="text-xs text-gray-500">
                                    <?php if ($patient['last_health_data']): ?>
                                        Last data: <?php echo date('M j', strtotime($patient['last_health_data'])); ?>
                                        <?php if ($days_since_data > 0): ?>
                                            (<?php echo $days_since_data; ?> days ago)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        No health data yet
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="patient_stats.php?patient_id=<?php echo $patient['id']; ?>" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View
                            </a>
                            <a href="send_alert.php?patient_id=<?php echo $patient['id']; ?>" 
                               class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded transition-colors">
                                Alert
                            </a>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                        mysqli_stmt_close($patient_overview_stmt);
                    } else {
                        echo display_user_friendly_error('database', 'Patient overview query failed', 'Unable to load patient overview at this time.');
                    }
                    ?>
                </div>
                
                <div class="text-center">
                    <a href="patient_list.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium transition-colors hover-underline">
                        View All Patients
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Patients Assigned</h3>
                    <p class="text-gray-600 mb-4">You don't have any patients assigned to you yet. Contact your administrator to get patient assignments.</p>
                    <div class="bg-blue-50 rounded-lg p-4 text-left">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-medium mb-1">What you can do with assigned patients:</p>
                                <ul class="space-y-1">
                                    <li>• Monitor their health data and trends</li>
                                    <li>• Send personalized health alerts and recommendations</li>
                                    <li>• Track their progress over time</li>
                                    <li>• Provide medical guidance based on their readings</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Active Patients -->
        <div class="bg-white rounded-lg shadow-md p-6 animate-fade-in-up stagger-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <div class="w-6 h-6 bg-green-100 rounded-lg flex items-center justify-center mr-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
                Recent Patient Activity
            </h2>
            <?php if ($active_patients_result && mysqli_num_rows($active_patients_result) > 0): ?>
                <div class="space-y-3">
                    <?php while ($patient = mysqli_fetch_assoc($active_patients_result)): ?>
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border-l-4 border-green-400 hover-lift">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse-slow"></div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></p>
                                <p class="text-sm text-gray-600">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?php echo $patient['record_count']; ?> records
                                    </span>
                                    <span class="ml-2 text-gray-500">
                                        Last: <?php echo date('M j, g:i A', strtotime($patient['last_entry'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <a href="patient_stats.php?patient_id=<?php echo $patient['id']; ?>" 
                           class="inline-flex items-center justify-center font-medium rounded-md transition-all duration-200 bg-blue-600 hover:bg-blue-700 text-white shadow-sm hover:shadow-md px-3 py-2 text-sm">
                            View
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="animate-pulse">
                        <div class="h-4 bg-gray-200 rounded mb-3" style="width: 80%"></div>
                        <div class="h-4 bg-gray-200 rounded mb-3" style="width: 60%"></div>
                        <div class="h-4 bg-gray-200 rounded mb-3" style="width: 40%"></div>
                    </div>
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
            <?php if ($inactive_patients_result && mysqli_num_rows($inactive_patients_result) > 0): ?>
                <div class="space-y-3">
                    <?php while ($patient = mysqli_fetch_assoc($inactive_patients_result)): ?>
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border-l-4 border-yellow-400 hover-lift">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></p>
                                <p class="text-sm text-gray-600">
                                    <?php if ($patient['last_entry']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Last: <?php echo date('M j, Y', strtotime($patient['last_entry'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            No data recorded
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="send_alert.php?patient_id=<?php echo $patient['id']; ?>" 
                           class="inline-flex items-center justify-center font-medium rounded-md transition-all duration-200 bg-yellow-500 hover:bg-yellow-600 text-white shadow-sm hover:shadow-md px-3 py-2 text-sm">
                            Send Alert
                        </a>
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
        <?php if ($recent_alerts_result && mysqli_num_rows($recent_alerts_result) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        mysqli_data_seek($recent_alerts_result, 0);
                        while ($alert = mysqli_fetch_assoc($recent_alerts_result)): 
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($alert['patient_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars(substr($alert['message'], 0, 60)) . (strlen($alert['message']) > 60 ? '...' : ''); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, g:i A', strtotime($alert['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($alert['status'] === 'sent'): ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Unread
                                    </span>
                                <?php elseif ($alert['status'] === 'seen'): ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Read
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <?php echo htmlspecialchars($alert['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
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
                <a href="send_alert.php" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-md font-medium transition-colors">
                    Send First Alert
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>