<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

if ($_POST) {
    // Validate input fields
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, ['patient', 'doctor'])) {
        $error = 'Invalid role selected.';
    } else {
        // Escape strings for database
        $name = mysqli_real_escape_string($connection, $name);
        $email = mysqli_real_escape_string($connection, $email);
        $password = mysqli_real_escape_string($connection, $password);
        $role = mysqli_real_escape_string($connection, $role);
        
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($connection, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Email already exists. Please use a different email.';
        } else {
            // Set status based on role - patients are approved, doctors are pending
            $status = ($role === 'doctor') ? 'pending' : 'approved';
            
            $insert_query = "INSERT INTO users (name, email, password, role, status) VALUES ('$name', '$email', '$password', '$role', '$status')";
            
            if (mysqli_query($connection, $insert_query)) {
                if ($role === 'doctor') {
                    $success = 'Registration successful! Your account is pending approval from an administrator.';
                } else {
                    $success = 'Registration successful! You can now login.';
                }
                // Clear form data on success
                $name = $email = $role = '';
            } else {
                $error = 'Registration failed. Please try again. Error: ' . mysqli_error($connection);
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6 mt-8">
    <h2 class="text-2xl font-bold text-center mb-6">Register</h2>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
            <input type="text" id="name" name="name" required maxlength="100"
                   value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="email" name="email" required maxlength="100"
                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="password" name="password" required minlength="6"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Minimum 6 characters</p>
        </div>
        
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
            <select id="role" name="role" required 
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select Role</option>
                <option value="patient" <?php echo (isset($role) && $role === 'patient') ? 'selected' : ''; ?>>Patient</option>
                <option value="doctor" <?php echo (isset($role) && $role === 'doctor') ? 'selected' : ''; ?>>Doctor</option>
            </select>
        </div>
        
        <button type="submit" 
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
            Register
        </button>
    </form>
    
    <div class="text-center mt-4">
        <a href="index.php" class="text-blue-600 hover:text-blue-800">Already have an account? Login here</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>