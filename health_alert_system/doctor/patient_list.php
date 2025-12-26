<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get assigned patients with their latest health data
$patients_query = "SELECT u.id, u.name, u.email, u.created_at as registered_date,
                         COUNT(hd.id) as total_records,
                         MAX(hd.created_at) as last_entry,
                         COUNT(CASE WHEN hd.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_records
                  FROM users u
                  JOIN doctor_patients dp ON u.id = dp.patient_id
                  LEFT JOIN health_data hd ON u.id = hd.patient_id
                  WHERE dp.doctor_id = $user_id AND u.role = 'patient'
                  GROUP BY u.id, u.name, u.email, u.created_at
                  ORDER BY last_entry DESC, u.name ASC";

$patients_result = mysqli_query($connection, $patients_query);

// Get total patient count
$total_patients = mysqli_num_rows($patients_result);

include '../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">My Patients</h1>
                <p class="text-gray-600">Manage and monitor your assigned patients</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-4">
                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                    <?php echo $total_patients; ?> Patients
                </span>
                <a href="dashboard.php" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if ($total_patients > 0): ?>
    <!-- Patients Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Patient List</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Patient
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Activity Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Health Records
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Last Entry
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    // Reset result pointer
                    mysqli_data_seek($patients_result, 0);
                    while ($patient = mysqli_fetch_assoc($patients_result)): 
                    ?>
                    <?php
                    // Determine activity status
                    $activity_status = 'Inactive';
                    $activity_color = 'red';
                    $days_since_last = null;
                    
                    if ($patient['last_entry']) {
                        $days_since_last = floor((time() - strtotime($patient['last_entry'])) / (60 * 60 * 24));
                        
                        if ($days_since_last <= 1) {
                            $activity_status = 'Very Active';
                            $activity_color = 'green';
                        } elseif ($days_since_last <= 3) {
                            $activity_status = 'Active';
                            $activity_color = 'blue';
                        } elseif ($days_since_last <= 7) {
                            $activity_status = 'Moderate';
                            $activity_color = 'yellow';
                        } else {
                            $activity_status = 'Inactive';
                            $activity_color = 'red';
                        }
                    }
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-medium text-sm">
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
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                       bg-<?php echo $activity_color; ?>-100 
                                       text-<?php echo $activity_color; ?>-800">
                                <?php echo $activity_status; ?>
                            </span>
                            <?php if ($patient['recent_records'] > 0): ?>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?php echo $patient['recent_records']; ?> records this week
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo $patient['total_records']; ?> total
                            </div>
                            <div class="text-sm text-gray-500">
                                Since <?php echo date('M Y', strtotime($patient['registered_date'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($patient['last_entry']): ?>
                                <div class="text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($patient['last_entry'])); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php 
                                    if ($days_since_last == 0) {
                                        echo 'Today';
                                    } elseif ($days_since_last == 1) {
                                        echo 'Yesterday';
                                    } else {
                                        echo $days_since_last . ' days ago';
                                    }
                                    ?>
                                </div>
                            <?php else: ?>
                                <div class="text-sm text-gray-500">
                                    No data recorded
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="patient_stats.php?patient_id=<?php echo $patient['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded transition-colors">
                                View Stats
                            </a>
                            <a href="send_alert.php?patient_id=<?php echo $patient['id']; ?>" 
                               class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 px-3 py-1 rounded transition-colors">
                                Send Alert
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <?php
        // Calculate summary statistics
        mysqli_data_seek($patients_result, 0);
        $active_count = 0;
        $inactive_count = 0;
        $total_records = 0;
        
        while ($patient = mysqli_fetch_assoc($patients_result)) {
            $total_records += $patient['total_records'];
            
            if ($patient['last_entry']) {
                $days_since = floor((time() - strtotime($patient['last_entry'])) / (60 * 60 * 24));
                if ($days_since <= 7) {
                    $active_count++;
                } else {
                    $inactive_count++;
                }
            } else {
                $inactive_count++;
            }
        }
        ?>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $active_count; ?></p>
                    <p class="text-gray-600">Active Patients</p>
                    <p class="text-xs text-gray-500">Data within 7 days</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $inactive_count; ?></p>
                    <p class="text-gray-600">Need Attention</p>
                    <p class="text-xs text-gray-500">No recent data</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $total_records; ?></p>
                    <p class="text-gray-600">Total Records</p>
                    <p class="text-xs text-gray-500">All patients combined</p>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No Patients Assigned</h3>
        <p class="text-gray-600 mb-6">You don't have any patients assigned to you yet. Contact your administrator to get patient assignments.</p>
        <a href="dashboard.php" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors">
            Back to Dashboard
        </a>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">💡 Patient Management Tips</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">Activity Status Guide:</h4>
                <ul class="space-y-1">
                    <li>• <span class="text-green-600">Very Active:</span> Data within 1 day</li>
                    <li>• <span class="text-blue-600">Active:</span> Data within 3 days</li>
                    <li>• <span class="text-yellow-600">Moderate:</span> Data within 7 days</li>
                    <li>• <span class="text-red-600">Inactive:</span> No data for 7+ days</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Best Practices:</h4>
                <ul class="space-y-1">
                    <li>• Check inactive patients regularly</li>
                    <li>• Send encouraging alerts to active patients</li>
                    <li>• Review health trends in patient stats</li>
                    <li>• Follow up on concerning patterns</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>