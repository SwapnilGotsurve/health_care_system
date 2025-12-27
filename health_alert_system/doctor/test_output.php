<?php
// Simple test file to check if HTML output is working correctly
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Set proper headers
header('Content-Type: text/html; charset=UTF-8');

// Start output buffering to catch any issues
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Output Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">HTML Output Test</h1>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Test Results</h2>
            
            <!-- Test basic HTML rendering -->
            <div class="mb-4">
                <p class="text-green-600">✅ Basic HTML is rendering correctly</p>
            </div>
            
            <!-- Test PHP variables -->
            <div class="mb-4">
                <p class="text-blue-600">User ID: <?php echo $_SESSION['user_id']; ?></p>
                <p class="text-blue-600">User Name: <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            </div>
            
            <!-- Test links -->
            <div class="mb-4">
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Back to Dashboard
                </a>
            </div>
            
            <!-- Test complex HTML structure -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-blue-800">Test Complete</h3>
                        <p class="mt-1 text-sm text-blue-700">If you can see this message properly formatted, HTML output is working correctly.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test database connection -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Database Test</h2>
            <?php
            $test_query = "SELECT COUNT(*) as count FROM alerts WHERE doctor_id = ?";
            $test_stmt = mysqli_prepare($connection, $test_query);
            if ($test_stmt) {
                mysqli_stmt_bind_param($test_stmt, "i", $_SESSION['user_id']);
                mysqli_stmt_execute($test_stmt);
                $test_result = mysqli_stmt_get_result($test_stmt);
                $test_data = mysqli_fetch_assoc($test_result);
                mysqli_stmt_close($test_stmt);
                
                echo '<p class="text-green-600">✅ Database connection working</p>';
                echo '<p class="text-gray-600">Total alerts: ' . $test_data['count'] . '</p>';
            } else {
                echo '<p class="text-red-600">❌ Database connection failed</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>
<?php
// End output buffering and send content
ob_end_flush();
?>