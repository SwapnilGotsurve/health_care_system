# API Documentation - Health Alert System

## Overview

This document provides comprehensive documentation for the Health Alert System's internal PHP functions, database operations, and component library. The system uses procedural PHP with MySQLi for database operations and follows a modular architecture.

## Authentication System

### Session Management

#### `auth_check.php`
**Purpose**: Middleware for role-based access control

**Functions**:
- Session validation
- Role-based page access control
- Automatic redirects for unauthorized access
- Session activity tracking

**Usage**:
```php
// Include at the top of every protected page
require_once '../includes/auth_check.php';
```

**Session Variables**:
- `$_SESSION['user_id']`: Unique user identifier
- `$_SESSION['role']`: User role (patient, doctor, admin)
- `$_SESSION['name']`: User's full name
- `$_SESSION['email']`: User's email address
- `$_SESSION['last_activity']`: Timestamp of last activity

### Login System

#### Login Process (`index.php`)
```php
// Validate credentials
$email = mysqli_real_escape_string($connection, $_POST['email']);
$password = $_POST['password'];

// Query user by email
$query = "SELECT * FROM users WHERE email = '$email'";
$result = mysqli_query($connection, $query);

// Verify password and create session
if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
}
```

#### Registration Process (`register.php`)
```php
// Hash password securely
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$query = "INSERT INTO users (name, email, password, role, status) 
          VALUES ('$name', '$email', '$hashed_password', '$role', '$status')";
```

## Database Operations

### Connection Management

#### Database Configuration (`config/db.php`)
```php
// Connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'health_alert_system';

// Establish connection
$connection = mysqli_connect($host, $username, $password, $database);

// Set character encoding
mysqli_set_charset($connection, "utf8");
```

### Health Data Operations

#### Insert Health Data
```php
/**
 * Insert new health record for patient
 * 
 * @param int $patient_id Patient's user ID
 * @param int $systolic_bp Systolic blood pressure (70-250)
 * @param int $diastolic_bp Diastolic blood pressure (40-150)
 * @param float $sugar_level Blood glucose level (50-500)
 * @param int $heart_rate Heart rate (30-220)
 * @return bool Success status
 */
function insert_health_data($patient_id, $systolic_bp, $diastolic_bp, $sugar_level, $heart_rate) {
    global $connection;
    
    $query = "INSERT INTO health_data (patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate) 
              VALUES ($patient_id, $systolic_bp, $diastolic_bp, $sugar_level, $heart_rate)";
    
    return mysqli_query($connection, $query);
}
```

#### Retrieve Patient Health History
```php
/**
 * Get paginated health history for patient
 * 
 * @param int $patient_id Patient's user ID
 * @param int $page Page number (1-based)
 * @param int $per_page Records per page
 * @return array Health records
 */
function get_patient_health_history($patient_id, $page = 1, $per_page = 10) {
    global $connection;
    
    $offset = ($page - 1) * $per_page;
    
    $query = "SELECT * FROM health_data 
              WHERE patient_id = $patient_id 
              ORDER BY created_at DESC 
              LIMIT $per_page OFFSET $offset";
    
    return mysqli_query($connection, $query);
}
```

### Alert Operations

#### Send Alert to Patient
```php
/**
 * Send alert from doctor to patient
 * 
 * @param int $doctor_id Doctor's user ID
 * @param int $patient_id Patient's user ID
 * @param string $message Alert message content
 * @return bool Success status
 */
function send_alert($doctor_id, $patient_id, $message) {
    global $connection;
    
    $message = mysqli_real_escape_string($connection, $message);
    
    $query = "INSERT INTO alerts (doctor_id, patient_id, message) 
              VALUES ($doctor_id, $patient_id, '$message')";
    
    return mysqli_query($connection, $query);
}
```

#### Update Alert Status
```php
/**
 * Mark alert as seen by patient
 * 
 * @param int $alert_id Alert ID
 * @param int $patient_id Patient's user ID (for security)
 * @return bool Success status
 */
function mark_alert_seen($alert_id, $patient_id) {
    global $connection;
    
    $query = "UPDATE alerts 
              SET status = 'seen' 
              WHERE id = $alert_id AND patient_id = $patient_id";
    
    return mysqli_query($connection, $query);
}
```

### User Management Operations

#### Doctor Approval System
```php
/**
 * Approve pending doctor account
 * 
 * @param int $doctor_id Doctor's user ID
 * @return bool Success status
 */
function approve_doctor($doctor_id) {
    global $connection;
    
    $query = "UPDATE users 
              SET status = 'active' 
              WHERE id = $doctor_id AND role = 'doctor' AND status = 'pending'";
    
    return mysqli_query($connection, $query);
}

/**
 * Reject pending doctor account
 * 
 * @param int $doctor_id Doctor's user ID
 * @return bool Success status
 */
function reject_doctor($doctor_id) {
    global $connection;
    
    $query = "DELETE FROM users 
              WHERE id = $doctor_id AND role = 'doctor' AND status = 'pending'";
    
    return mysqli_query($connection, $query);
}
```

#### Doctor-Patient Assignment
```php
/**
 * Assign patient to doctor
 * 
 * @param int $doctor_id Doctor's user ID
 * @param int $patient_id Patient's user ID
 * @return bool Success status
 */
function assign_patient_to_doctor($doctor_id, $patient_id) {
    global $connection;
    
    $query = "INSERT IGNORE INTO doctor_patients (doctor_id, patient_id) 
              VALUES ($doctor_id, $patient_id)";
    
    return mysqli_query($connection, $query);
}

/**
 * Remove patient assignment from doctor
 * 
 * @param int $doctor_id Doctor's user ID
 * @param int $patient_id Patient's user ID
 * @return bool Success status
 */
function unassign_patient_from_doctor($doctor_id, $patient_id) {
    global $connection;
    
    $query = "DELETE FROM doctor_patients 
              WHERE doctor_id = $doctor_id AND patient_id = $patient_id";
    
    return mysqli_query($connection, $query);
}
```

## UI Components Library

### Status Badge Components

#### `render_status_badge($status, $type, $pulse)`
**Purpose**: Generate styled status badges with consistent appearance

**Parameters**:
- `$status` (string): Text to display in badge
- `$type` (string): Badge color scheme (success, warning, danger, info, primary, gray)
- `$pulse` (bool): Whether to add pulse animation

**Returns**: HTML string for status badge

**Example Usage**:
```php
echo render_status_badge('Active', 'success', true);
echo render_status_badge('Pending', 'warning');
echo render_status_badge('Inactive', 'danger');
```

#### `render_health_status_badge($health_data)`
**Purpose**: Evaluate health data and display appropriate status badge

**Parameters**:
- `$health_data` (array): Health record with keys: systolic_bp, diastolic_bp, sugar_level, heart_rate

**Health Status Criteria**:
- **Critical**: Systolic ≥180, Diastolic ≥120, Sugar ≥300, HR ≥180 or ≤50
- **Warning**: Systolic 140-179, Diastolic 90-119, Sugar 180-299, HR 100-179 or 51-59
- **Normal**: All values within healthy ranges

**Returns**: HTML string for health status badge

**Example Usage**:
```php
$health_data = [
    'systolic_bp' => 120,
    'diastolic_bp' => 80,
    'sugar_level' => 95.5,
    'heart_rate' => 72
];
echo render_health_status_badge($health_data);
```

### Data Card Components

#### `render_data_card($title, $value, $subtitle, $icon, $color)`
**Purpose**: Generate consistent data display cards

**Parameters**:
- `$title` (string): Card title
- `$value` (string): Main value to display
- `$subtitle` (string): Additional information
- `$icon` (string): Icon class name
- `$color` (string): Color scheme

**Example Usage**:
```php
echo render_data_card('Total Records', '45', 'Health entries', 'heart', 'blue');
```

### Form Components

#### `render_form_input($name, $type, $label, $required, $value)`
**Purpose**: Generate consistent form input fields

**Parameters**:
- `$name` (string): Input name attribute
- `$type` (string): Input type (text, email, password, number)
- `$label` (string): Field label
- `$required` (bool): Whether field is required
- `$value` (string): Default value

**Example Usage**:
```php
echo render_form_input('email', 'email', 'Email Address', true, '');
echo render_form_input('systolic_bp', 'number', 'Systolic BP', true, '120');
```

## Security Features

### Input Validation

#### Health Data Validation
```php
/**
 * Validate health data input
 * 
 * @param array $data Health data to validate
 * @return array Validation result with errors
 */
function validate_health_data($data) {
    $errors = [];
    
    // Systolic BP validation (70-250 mmHg)
    if (!isset($data['systolic_bp']) || $data['systolic_bp'] < 70 || $data['systolic_bp'] > 250) {
        $errors[] = 'Systolic BP must be between 70-250 mmHg';
    }
    
    // Diastolic BP validation (40-150 mmHg)
    if (!isset($data['diastolic_bp']) || $data['diastolic_bp'] < 40 || $data['diastolic_bp'] > 150) {
        $errors[] = 'Diastolic BP must be between 40-150 mmHg';
    }
    
    // Sugar level validation (50-500 mg/dL)
    if (!isset($data['sugar_level']) || $data['sugar_level'] < 50 || $data['sugar_level'] > 500) {
        $errors[] = 'Sugar level must be between 50-500 mg/dL';
    }
    
    // Heart rate validation (30-220 bpm)
    if (!isset($data['heart_rate']) || $data['heart_rate'] < 30 || $data['heart_rate'] > 220) {
        $errors[] = 'Heart rate must be between 30-220 bpm';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
```

#### SQL Injection Prevention
```php
// Always escape user input
$email = mysqli_real_escape_string($connection, $_POST['email']);

// Use parameterized queries when possible
$stmt = mysqli_prepare($connection, "SELECT * FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
```

### XSS Prevention
```php
// Always escape output
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// For HTML attributes
echo htmlspecialchars($attribute_value, ENT_QUOTES, 'UTF-8');
```

## Error Handling

### Database Error Handling
```php
/**
 * Execute query with error handling
 * 
 * @param string $query SQL query
 * @return mixed Query result or false on error
 */
function execute_query($query) {
    global $connection;
    
    $result = mysqli_query($connection, $query);
    
    if (!$result) {
        error_log("Database Error: " . mysqli_error($connection));
        error_log("Query: " . $query);
        return false;
    }
    
    return $result;
}
```

### Form Validation Error Display
```php
/**
 * Display validation errors
 * 
 * @param array $errors Array of error messages
 */
function display_errors($errors) {
    if (!empty($errors)) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
        echo '<ul class="list-disc list-inside">';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}
```

## Performance Optimization

### Query Optimization
```php
// Use indexes for frequent queries
CREATE INDEX idx_patient_health ON health_data(patient_id, created_at);
CREATE INDEX idx_alert_status ON alerts(patient_id, status);

// Limit result sets
SELECT * FROM health_data WHERE patient_id = ? ORDER BY created_at DESC LIMIT 10;

// Use appropriate data types
DECIMAL(5,2) for sugar_level  // More precise than FLOAT
INT for blood pressure values // Sufficient range and performance
```

### Caching Strategies
```php
/**
 * Simple session-based caching for user data
 */
if (!isset($_SESSION['user_cache']) || time() - $_SESSION['cache_time'] > 300) {
    // Refresh cache every 5 minutes
    $_SESSION['user_cache'] = get_user_data($_SESSION['user_id']);
    $_SESSION['cache_time'] = time();
}
```

## Testing Framework

### Property-Based Testing
The system includes comprehensive PHPUnit tests that validate:

- **Authentication Properties**: Login/logout behavior
- **Data Integrity**: Health data storage and retrieval
- **Role-Based Access**: Permission enforcement
- **Alert System**: Message delivery and status updates
- **User Management**: Registration and approval workflows

### Test Execution
```bash
# Run all tests
phpunit --configuration phpunit.xml

# Run specific test suite
phpunit tests/AuthenticationTest.php

# Run with coverage report
phpunit --coverage-html coverage/
```

## Deployment Considerations

### Production Configuration
```php
// config/production.php
$config = [
    'db_host' => 'production-server',
    'db_user' => 'app_user',
    'db_pass' => 'secure_password',
    'session_timeout' => 1800, // 30 minutes
    'enable_logging' => true,
    'debug_mode' => false
];
```

### Security Hardening
- Use HTTPS in production
- Implement CSRF protection
- Set secure session configuration
- Regular security updates
- Input validation on all user data
- Rate limiting for login attempts

This documentation provides a comprehensive reference for developers working with the Health Alert System codebase.