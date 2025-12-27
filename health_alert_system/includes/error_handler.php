<?php
/**
 * User-Friendly Error Handler
 * 
 * This file provides comprehensive error handling and user-friendly
 * error messages for the Health Alert System.
 * 
 * Features:
 * - User-friendly error messages
 * - Error logging for debugging
 * - Graceful error recovery
 * - Consistent error display
 * 
 * @author Health Alert System Team
 * @version 1.0
 */

/**
 * Display user-friendly error message
 * 
 * @param string $error_type Type of error (database, validation, auth, etc.)
 * @param string $technical_message Technical error message for logging
 * @param string $user_message Optional custom user message
 * @return string HTML for user-friendly error display
 */
function display_user_friendly_error($error_type, $technical_message = '', $user_message = '') {
    // Log technical error for debugging
    if ($technical_message) {
        error_log("Health Alert System Error [$error_type]: $technical_message");
    }
    
    // Default user-friendly messages
    $friendly_messages = [
        'database' => 'We\'re experiencing technical difficulties. Please try again in a few moments.',
        'validation' => 'Please check your input and try again.',
        'auth' => 'Please log in to access this page.',
        'permission' => 'You don\'t have permission to access this page.',
        'not_found' => 'The requested information could not be found.',
        'network' => 'Connection issue detected. Please check your internet connection.',
        'session' => 'Your session has expired. Please log in again.',
        'file_upload' => 'There was an issue uploading your file. Please try again.',
        'general' => 'Something went wrong. Please try again or contact support if the problem persists.'
    ];
    
    // Use custom message or default friendly message
    $display_message = $user_message ?: ($friendly_messages[$error_type] ?? $friendly_messages['general']);
    
    // Return user-friendly error HTML
    return "
        <div class='bg-red-50 border border-red-200 rounded-lg p-4 mb-4 animate-fade-in-up'>
            <div class='flex items-start'>
                <div class='flex-shrink-0'>
                    <svg class='w-5 h-5 text-red-600 mt-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                    </svg>
                </div>
                <div class='ml-3 flex-1'>
                    <h3 class='text-sm font-medium text-red-800'>Oops! Something went wrong</h3>
                    <p class='mt-1 text-sm text-red-700'>$display_message</p>
                </div>
            </div>
        </div>
    ";
}

/**
 * Display user-friendly success message
 * 
 * @param string $message Success message to display
 * @return string HTML for success message display
 */
function display_success_message($message) {
    return "
        <div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-4 animate-fade-in-up'>
            <div class='flex items-start'>
                <div class='flex-shrink-0'>
                    <svg class='w-5 h-5 text-green-600 mt-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                    </svg>
                </div>
                <div class='ml-3 flex-1'>
                    <h3 class='text-sm font-medium text-green-800'>Success!</h3>
                    <p class='mt-1 text-sm text-green-700'>$message</p>
                </div>
            </div>
        </div>
    ";
}

/**
 * Display user-friendly warning message
 * 
 * @param string $message Warning message to display
 * @return string HTML for warning message display
 */
function display_warning_message($message) {
    return "
        <div class='bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4 animate-fade-in-up'>
            <div class='flex items-start'>
                <div class='flex-shrink-0'>
                    <svg class='w-5 h-5 text-yellow-600 mt-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z'></path>
                    </svg>
                </div>
                <div class='ml-3 flex-1'>
                    <h3 class='text-sm font-medium text-yellow-800'>Notice</h3>
                    <p class='mt-1 text-sm text-yellow-700'>$message</p>
                </div>
            </div>
        </div>
    ";
}

/**
 * Display user-friendly info message
 * 
 * @param string $message Info message to display
 * @return string HTML for info message display
 */
function display_info_message($message) {
    return "
        <div class='bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 animate-fade-in-up'>
            <div class='flex items-start'>
                <div class='flex-shrink-0'>
                    <svg class='w-5 h-5 text-blue-600 mt-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                    </svg>
                </div>
                <div class='ml-3 flex-1'>
                    <h3 class='text-sm font-medium text-blue-800'>Information</h3>
                    <p class='mt-1 text-sm text-blue-700'>$message</p>
                </div>
            </div>
        </div>
    ";
}

/**
 * Handle database connection errors gracefully
 * 
 * @param string $technical_error Technical error from mysqli
 * @return string HTML for database error display
 */
function handle_database_error($technical_error = '') {
    return display_user_friendly_error(
        'database', 
        $technical_error, 
        'We\'re having trouble connecting to our servers. Please try refreshing the page or contact support if the issue persists.'
    );
}

/**
 * Handle validation errors with helpful suggestions
 * 
 * @param array $validation_errors Array of validation error messages
 * @return string HTML for validation errors display
 */
function handle_validation_errors($validation_errors) {
    if (empty($validation_errors)) {
        return '';
    }
    
    $error_list = '';
    foreach ($validation_errors as $error) {
        $error_list .= "<li class='text-sm text-red-700'>$error</li>";
    }
    
    return "
        <div class='bg-red-50 border border-red-200 rounded-lg p-4 mb-4 animate-fade-in-up'>
            <div class='flex items-start'>
                <div class='flex-shrink-0'>
                    <svg class='w-5 h-5 text-red-600 mt-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                    </svg>
                </div>
                <div class='ml-3 flex-1'>
                    <h3 class='text-sm font-medium text-red-800'>Please fix the following issues:</h3>
                    <ul class='mt-2 list-disc list-inside'>
                        $error_list
                    </ul>
                </div>
            </div>
        </div>
    ";
}

/**
 * Create a user-friendly 404 page content
 * 
 * @param string $resource What was not found (patient, doctor, alert, etc.)
 * @return string HTML for 404 error display
 */
function display_not_found_error($resource = 'page') {
    return "
        <div class='text-center py-12'>
            <div class='w-24 h-24 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6'>
                <svg class='w-12 h-12 text-gray-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'></path>
                </svg>
            </div>
            <h2 class='text-2xl font-bold text-gray-900 mb-2'>$resource Not Found</h2>
            <p class='text-gray-600 mb-6'>The $resource you're looking for doesn't exist or may have been moved.</p>
            <div class='space-x-4'>
                <button onclick='history.back()' class='bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors'>
                    Go Back
                </button>
                <a href='dashboard.php' class='bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-md transition-colors'>
                    Go to Dashboard
                </a>
            </div>
        </div>
    ";
}

/**
 * Display loading state for better user experience
 * 
 * @param string $message Loading message
 * @return string HTML for loading display
 */
function display_loading_state($message = 'Loading...') {
    return "
        <div class='text-center py-8'>
            <div class='inline-flex items-center'>
                <svg class='animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'>
                    <circle class='opacity-25' cx='12' cy='12' r='10' stroke='currentColor' stroke-width='4'></circle>
                    <path class='opacity-75' fill='currentColor' d='M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z'></path>
                </svg>
                <span class='text-gray-600'>$message</span>
            </div>
        </div>
    ";
}

/**
 * Enhanced session message handling
 * 
 * @return string HTML for session messages (success, error, warning, info)
 */
function display_session_messages() {
    $output = '';
    
    if (isset($_SESSION['success'])) {
        $output .= display_success_message($_SESSION['success']);
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        $output .= display_user_friendly_error('general', $_SESSION['error'], $_SESSION['error']);
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['warning'])) {
        $output .= display_warning_message($_SESSION['warning']);
        unset($_SESSION['warning']);
    }
    
    if (isset($_SESSION['info'])) {
        $output .= display_info_message($_SESSION['info']);
        unset($_SESSION['info']);
    }
    
    return $output;
}
?>