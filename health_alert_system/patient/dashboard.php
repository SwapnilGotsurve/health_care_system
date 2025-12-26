<?php
/**
 * Patient Dashboard - Main Overview Page
 * 
 * This page serves as the central hub for patient users, providing:
 * - Health data overview and statistics
 * - Recent health records display
 * - Alert notifications and counts
 * - Quick navigation to key features
 * 
 * Security: Requires patient role authentication via auth_check.php
 * 
 * @author Health Alert System Team
 * @version 1.0
 */

// Include authentication middleware - ensures user is logged in as patient
require_once '../includes/auth_check.php';

// Include database connection
require_once '../config/db.php';

// Set page metadata for header template
$page_title = 'Patient Dashboard';

// Extract user information from authenticated session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

/**
 * SECTION 1: Health Data Statistics
 * 
 * Retrieve recent health records and calculate summary statistics
 * for display in the dashboard overview cards.
 */

// Get recent health data (last 5 entries) for quick overview
$health_query = "SELECT * FROM health_data WHERE patient_id = $user_id ORDER BY created_at DESC LIMIT 5";
$health_result = mysqli_query($connection, $health_query);

// Get total health records count for statistics display
$health_count_query = "SELECT COUNT(*) as total FROM health_data WHERE patient_id = $user_id";
$health_count_result = mysqli_query($connection, $health_count_query);
$health_count = mysqli_fetch_assoc($health_count_result)['total'];

/**
 * SECTION 2: Alert Management Statistics
 * 
 * Calculate alert counts for notification badges and overview cards.
 * Separates unread alerts (requiring attention) from total alerts.
 */

// Get unread alerts count for notification badge
$alerts_query = "SELECT COUNT(*) as unread FROM alerts WHERE patient_id = $user_id AND status = 'sent'";
$alerts_result = mysqli_query($connection, $alerts_query);
$unread_alerts = mysqli_fetch_assoc($alerts_result)['unread'];

// Get total alerts count for statistics
$total_alerts_query = "SELECT COUNT(*) as total FROM alerts WHERE patient_id = $user_id";
$total_alerts_result = mysqli_query($connection, $total_alerts_query);
$total_alerts = mysqli_fetch_assoc($total_alerts_result)['total'];

/**
 * SECTION 3: Health Status Evaluation
 * 
 * Retrieve the most recent health data to display current health status
 * using the health status badge component.
 */

// Get latest health data for current status evaluation
$latest_health_query = "SELECT * FROM health_data WHERE patient_id = $user_id ORDER BY created_at DESC LIMIT 1";
$latest_health_result = mysqli_query($connection, $latest_health_query);
$latest_health = mysqli_fetch_assoc($latest_health_result);

// Include page header with navigation and styling
include '../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Welcome Header Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 animate-fade-in-up">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p class="text-gray-600">Here's your health overview for today.</p>
        
        <!-- Current Health Status Display -->
        <?php if ($latest_health): ?>
            <div class="mt-3">
                <span class="text-sm text-gray-500 mr-2">Current Health Status:</span>
                <?php 
                // Display health status badge using reusable component
                echo render_health_status_badge($latest_health); 
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <?php
        echo render_data_card(
            'Health Records',
            $health_count,
            'Total entries',
            'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'primary'
        );
        
        $status_text = $latest_health ? 'Last Updated' : 'No Data';
        $status_subtitle = $latest_health ? date('M j, g:i A', strtotime($latest_health['created_at'])) : 'Add your first record';
        echo render_data_card(
            'Health Status',
            $status_text,
            $status_subtitle,
            'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            'success'
        );
        
        echo render_data_card(
            'New Alerts',
            $unread_alerts,
            'Unread messages',
            'M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z',
            $unread_alerts > 0 ? 'warning' : 'primary'
        );
        
        echo render_data_card(
            'Total Alerts',
            $total_alerts,
            'All messages',
            'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
            'primary'
        );
        ?>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <a href="add_health_data.php" class="group bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-lg shadow-md p-6 transition-all duration-300 hover-lift animate-fade-in-up stagger-1">
            <div class="flex items-center">
                <div class="p-2 bg-white bg-opacity-20 rounded-lg mr-4 group-hover:bg-opacity-30 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Add Health Data</h3>
                    <p class="text-primary-100">Record your daily metrics</p>
                </div>
            </div>
        </a>

        <a href="health_history.php" class="group bg-gradient-to-r from-success-600 to-success-700 hover:from-success-700 hover:to-success-800 text-white rounded-lg shadow-md p-6 transition-all duration-300 hover-lift animate-fade-in-up stagger-2">
            <div class="flex items-center">
                <div class="p-2 bg-white bg-opacity-20 rounded-lg mr-4 group-hover:bg-opacity-30 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">View History</h3>
                    <p class="text-success-100">See your health trends</p>
                </div>
            </div>
        </a>

        <a href="alerts.php" class="group bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-lg shadow-md p-6 transition-all duration-300 hover-lift animate-fade-in-up stagger-3">
            <div class="flex items-center">
                <div class="p-2 bg-white bg-opacity-20 rounded-lg mr-4 group-hover:bg-opacity-30 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">View Alerts</h3>
                    <p class="text-purple-100">Check doctor messages</p>
                    <?php if ($unread_alerts > 0): ?>
                        <span class="inline-block mt-1 px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full animate-pulse">
                            <?php echo $unread_alerts; ?> new
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Health Data -->
    <?php if (mysqli_num_rows($health_result) > 0): ?>
    <div class="bg-white rounded-lg shadow-md p-6 animate-fade-in-up stagger-4">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Health Data</h2>
        <?php
        $headers = ['Date', 'Blood Pressure', 'Sugar Level', 'Heart Rate', 'Status'];
        $rows = [];
        
        mysqli_data_seek($health_result, 0); // Reset result pointer
        while ($health = mysqli_fetch_assoc($health_result)) {
            $rows[] = [
                date('M j, Y g:i A', strtotime($health['created_at'])),
                $health['systolic_bp'] . '/' . $health['diastolic_bp'] . ' mmHg',
                $health['sugar_level'] . ' mg/dL',
                $health['heart_rate'] . ' BPM',
                render_health_status_badge($health)
            ];
        }
        
        echo render_data_table($headers, $rows);
        ?>
        <div class="mt-4 text-center">
            <a href="health_history.php" class="inline-flex items-center text-primary-600 hover:text-primary-800 font-medium transition-colors hover-underline">
                View All Health Records
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center animate-fade-in-up stagger-4">
        <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Health Data Yet</h3>
        <p class="text-gray-600 mb-6">Start tracking your health by adding your first health record.</p>
        <?php echo render_button('Add Health Data', 'button', 'primary', 'lg', false, ['onclick' => "window.location.href='add_health_data.php'"]); ?>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>