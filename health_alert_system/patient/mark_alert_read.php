<?php
/**
 * AJAX endpoint to mark alerts as read
 * Returns JSON response for dynamic UI updates
 */

require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Set JSON content type
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);
$alert_id = isset($input['alert_id']) ? (int)$input['alert_id'] : 0;

if ($alert_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid alert ID']);
    exit;
}

try {
    // Verify the alert belongs to this patient and is unread
    $verify_query = "SELECT id, status FROM alerts WHERE id = ? AND patient_id = ?";
    $verify_stmt = mysqli_prepare($connection, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, "ii", $alert_id, $user_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);
    
    if (mysqli_num_rows($verify_result) === 0) {
        echo json_encode(['success' => false, 'error' => 'Alert not found or access denied']);
        exit;
    }
    
    $alert = mysqli_fetch_assoc($verify_result);
    
    if ($alert['status'] === 'seen') {
        echo json_encode(['success' => false, 'error' => 'Alert already marked as read']);
        exit;
    }
    
    // Update alert status to 'seen'
    $update_query = "UPDATE alerts SET status = 'seen' WHERE id = ? AND patient_id = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ii", $alert_id, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Get updated counts for the response
        $unread_count_query = "SELECT COUNT(*) as count FROM alerts WHERE patient_id = ? AND status = 'sent'";
        $unread_stmt = mysqli_prepare($connection, $unread_count_query);
        mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
        mysqli_stmt_execute($unread_stmt);
        $unread_result = mysqli_stmt_get_result($unread_stmt);
        $unread_count = mysqli_fetch_assoc($unread_result)['count'];
        
        $read_count_query = "SELECT COUNT(*) as count FROM alerts WHERE patient_id = ? AND status = 'seen'";
        $read_stmt = mysqli_prepare($connection, $read_count_query);
        mysqli_stmt_bind_param($read_stmt, "i", $user_id);
        mysqli_stmt_execute($read_stmt);
        $read_result = mysqli_stmt_get_result($read_stmt);
        $read_count = mysqli_fetch_assoc($read_result)['count'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Alert marked as read successfully',
            'unread_count' => $unread_count,
            'read_count' => $read_count,
            'total_count' => $unread_count + $read_count
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update alert status']);
    }
    
} catch (Exception $e) {
    error_log("Mark Alert Read Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred while processing your request']);
}
?>