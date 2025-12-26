<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM health_data WHERE patient_id = $user_id";
$count_result = mysqli_query($connection, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get health data in chronological order (newest first)
$health_query = "SELECT * FROM health_data 
                WHERE patient_id = $user_id 
                ORDER BY created_at DESC 
                LIMIT $records_per_page OFFSET $offset";
$health_result = mysqli_query($connection, $health_query);

// Function to determine health status based on values
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

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Health History</h1>
                <p class="text-gray-600">Complete record of your health data over time</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="add_health_data.php" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    Add New Data
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600"><?php echo $total_records; ?></p>
                <p class="text-gray-600 text-sm">Total Records</p>
            </div>
        </div>
        
        <?php if ($total_records > 0): ?>
        <?php
        // Get latest record for current status
        $latest_query = "SELECT * FROM health_data WHERE patient_id = $user_id ORDER BY created_at DESC LIMIT 1";
        $latest_result = mysqli_query($connection, $latest_query);
        $latest = mysqli_fetch_assoc($latest_result);
        $latest_status = getHealthStatus($latest['systolic_bp'], $latest['diastolic_bp'], 
                                       $latest['sugar_level'], $latest['heart_rate']);
        ?>
        
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-<?php echo $latest_status['color']; ?>-600">
                    <?php echo $latest_status['status']; ?>
                </p>
                <p class="text-gray-600 text-sm">Current Status</p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600">
                    <?php echo $latest['systolic_bp'] . '/' . $latest['diastolic_bp']; ?>
                </p>
                <p class="text-gray-600 text-sm">Latest BP (mmHg)</p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600">
                    <?php echo $latest['sugar_level']; ?>
                </p>
                <p class="text-gray-600 text-sm">Latest Sugar (mg/dL)</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Health Data Table -->
    <?php if (mysqli_num_rows($health_result) > 0): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Health Records</h2>
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
                        <a href="?page=<?php echo $page - 1; ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" 
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
                                <a href="?page=<?php echo $page - 1; ?>" 
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
                                    <a href="?page=<?php echo $i; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" 
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
            </path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No Health Data Yet</h3>
        <p class="text-gray-600 mb-6">Start tracking your health by adding your first health record.</p>
        <a href="add_health_data.php" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors">
            Add Health Data
        </a>
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

    <!-- Navigation -->
    <div class="mt-6 flex justify-center space-x-4">
        <a href="dashboard.php" 
           class="text-blue-600 hover:text-blue-800 font-medium">
            ← Back to Dashboard
        </a>
        <a href="alerts.php" 
           class="text-blue-600 hover:text-blue-800 font-medium">
            View Alerts →
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>