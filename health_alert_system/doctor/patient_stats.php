<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

// Verify this patient is assigned to this doctor
$verify_query = "SELECT u.name, u.email, u.created_at 
                FROM users u 
                JOIN doctor_patients dp ON u.id = dp.patient_id 
                WHERE dp.doctor_id = $user_id AND dp.patient_id = $patient_id AND u.role = 'patient'";

$verify_result = mysqli_query($connection, $verify_query);

if (mysqli_num_rows($verify_result) == 0) {
    header("Location: patient_list.php");
    exit();
}

$patient_info = mysqli_fetch_assoc($verify_result);

// Pagination for health data
$records_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total health records count
$count_query = "SELECT COUNT(*) as total FROM health_data WHERE patient_id = $patient_id";
$count_result = mysqli_query($connection, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get health data in chronological order
$health_query = "SELECT * FROM health_data 
                WHERE patient_id = $patient_id 
                ORDER BY created_at DESC 
                LIMIT $records_per_page OFFSET $offset";
$health_result = mysqli_query($connection, $health_query);

// Get summary statistics
$stats_query = "SELECT 
                  COUNT(*) as total_records,
                  AVG(systolic_bp) as avg_systolic,
                  AVG(diastolic_bp) as avg_diastolic,
                  AVG(sugar_level) as avg_sugar,
                  AVG(heart_rate) as avg_heart_rate,
                  MIN(created_at) as first_entry,
                  MAX(created_at) as last_entry
                FROM health_data 
                WHERE patient_id = $patient_id";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent trends (last 30 days)
$trends_query = "SELECT 
                   DATE(created_at) as date,
                   AVG(systolic_bp) as avg_systolic,
                   AVG(diastolic_bp) as avg_diastolic,
                   AVG(sugar_level) as avg_sugar,
                   AVG(heart_rate) as avg_heart_rate,
                   COUNT(*) as daily_records
                 FROM health_data 
                 WHERE patient_id = $patient_id 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date DESC
                 LIMIT 10";
$trends_result = mysqli_query($connection, $trends_query);

// Function to determine health status
function getHealthStatus($systolic, $diastolic, $sugar, $heart_rate) {
    if ($systolic > 140 || $diastolic > 90 || $sugar > 140 || $heart_rate > 100 ||
        $systolic < 90 || $diastolic < 60 || $sugar < 70 || $heart_rate < 60) {
        return ['status' => 'Alert', 'color' => 'red'];
    } else {
        return ['status' => 'Healthy', 'color' => 'green'];
    }
}

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($patient_info['name']); ?>
                </h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($patient_info['email']); ?></p>
                <p class="text-sm text-gray-500">
                    Patient since <?php echo date('M j, Y', strtotime($patient_info['created_at'])); ?>
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-4">
                <a href="send_alert.php?patient_id=<?php echo $patient_id; ?>" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                    Send Alert
                </a>
                <a href="patient_list.php" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Back to Patients
                </a>
            </div>
        </div>
    </div>

    <?php if ($total_records > 0): ?>
    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_records']; ?></p>
                    <p class="text-gray-600">Total Records</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">
                        <?php echo round($stats['avg_systolic']); ?>/<?php echo round($stats['avg_diastolic']); ?>
                    </p>
                    <p class="text-gray-600">Avg Blood Pressure</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo round($stats['avg_sugar'], 1); ?></p>
                    <p class="text-gray-600">Avg Sugar Level</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo round($stats['avg_heart_rate']); ?></p>
                    <p class="text-gray-600">Avg Heart Rate</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Trends -->
    <?php if (mysqli_num_rows($trends_result) > 0): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Trends (Last 30 Days)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Date</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Blood Pressure</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Sugar Level</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Heart Rate</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Records</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($trend = mysqli_fetch_assoc($trends_result)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <?php echo date('M j, Y', strtotime($trend['date'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <?php echo round($trend['avg_systolic']); ?>/<?php echo round($trend['avg_diastolic']); ?> mmHg
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <?php echo round($trend['avg_sugar'], 1); ?> mg/dL
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <?php echo round($trend['avg_heart_rate']); ?> BPM
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <?php echo $trend['daily_records']; ?> entries
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detailed Health Records -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Complete Health History</h2>
            <p class="text-gray-600 text-sm mt-1">
                Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $records_per_page, $total_records); ?> 
                of <?php echo $total_records; ?> records
            </p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date & Time
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Blood Pressure
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sugar Level
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Heart Rate
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($health = mysqli_fetch_assoc($health_result)): ?>
                    <?php
                    $status = getHealthStatus($health['systolic_bp'], $health['diastolic_bp'], 
                                            $health['sugar_level'], $health['heart_rate']);
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo date('M j, Y', strtotime($health['created_at'])); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo date('g:i A', strtotime($health['created_at'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo $health['systolic_bp'] . '/' . $health['diastolic_bp']; ?>
                            </div>
                            <div class="text-sm text-gray-500">mmHg</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo $health['sugar_level']; ?>
                            </div>
                            <div class="text-sm text-gray-500">mg/dL</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo $health['heart_rate']; ?>
                            </div>
                            <div class="text-sm text-gray-500">BPM</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                       bg-<?php echo $status['color']; ?>-100 
                                       text-<?php echo $status['color']; ?>-800">
                                <?php echo $status['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?patient_id=<?php echo $patient_id; ?>&page=<?php echo $page - 1; ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?patient_id=<?php echo $patient_id; ?>&page=<?php echo $page + 1; ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> 
                            to <span class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span> 
                            of <span class="font-medium"><?php echo $total_records; ?></span> results
                        </p>
                    </div>
                    
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php if ($page > 1): ?>
                                <a href="?patient_id=<?php echo $patient_id; ?>&page=<?php echo $page - 1; ?>" 
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
                                    <a href="?patient_id=<?php echo $patient_id; ?>&page=<?php echo $i; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?patient_id=<?php echo $patient_id; ?>&page=<?php echo $page + 1; ?>" 
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
    </div>

    <?php else: ?>
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No Health Data Available</h3>
        <p class="text-gray-600 mb-6">This patient hasn't recorded any health data yet.</p>
        <div class="space-x-4">
            <a href="send_alert.php?patient_id=<?php echo $patient_id; ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md transition-colors">
                Send Reminder Alert
            </a>
            <a href="patient_list.php" 
               class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-md transition-colors">
                Back to Patients
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Health Reference Guide -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">📊 Health Reference Ranges</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div class="bg-white rounded p-3">
                <h4 class="font-medium text-blue-800 mb-2">Blood Pressure</h4>
                <ul class="text-blue-700 space-y-1">
                    <li>• Normal: &lt;120/80 mmHg</li>
                    <li>• Elevated: 120-129/&lt;80</li>
                    <li>• High: ≥130/80 mmHg</li>
                </ul>
            </div>
            
            <div class="bg-white rounded p-3">
                <h4 class="font-medium text-blue-800 mb-2">Blood Sugar</h4>
                <ul class="text-blue-700 space-y-1">
                    <li>• Normal: 80-100 mg/dL</li>
                    <li>• Pre-diabetes: 100-125</li>
                    <li>• Diabetes: ≥126 mg/dL</li>
                </ul>
            </div>
            
            <div class="bg-white rounded p-3">
                <h4 class="font-medium text-blue-800 mb-2">Heart Rate</h4>
                <ul class="text-blue-700 space-y-1">
                    <li>• Normal: 60-100 BPM</li>
                    <li>• Athletic: 40-60 BPM</li>
                    <li>• Tachycardia: &gt;100 BPM</li>
                </ul>
            </div>
            
            <div class="bg-white rounded p-3">
                <h4 class="font-medium text-blue-800 mb-2">Status Indicators</h4>
                <ul class="text-blue-700 space-y-1">
                    <li>• <span class="text-green-600">●</span> Healthy: All normal</li>
                    <li>• <span class="text-red-600">●</span> Alert: Outside range</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>