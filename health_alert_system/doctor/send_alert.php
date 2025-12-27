<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

$error = '';
$success = '';
$selected_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

// Get assigned patients for this doctor
$patients_query = "SELECT u.id, u.name, u.email 
                  FROM users u 
                  JOIN doctor_patients dp ON u.id = dp.patient_id 
                  WHERE dp.doctor_id = $user_id AND u.role = 'patient' 
                  ORDER BY u.name ASC";

$patients_result = mysqli_query($connection, $patients_query);

// Verify selected patient is assigned to this doctor (if patient_id provided)
$selected_patient = null;
if ($selected_patient_id > 0) {
    $verify_query = "SELECT u.name, u.email 
                    FROM users u 
                    JOIN doctor_patients dp ON u.id = dp.patient_id 
                    WHERE dp.doctor_id = $user_id AND dp.patient_id = $selected_patient_id AND u.role = 'patient'";
    
    $verify_result = mysqli_query($connection, $verify_query);
    
    if (mysqli_num_rows($verify_result) > 0) {
        $selected_patient = mysqli_fetch_assoc($verify_result);
    } else {
        $selected_patient_id = 0; // Reset if invalid
    }
}

// Handle form submission
if ($_POST) {
    $patient_id = (int)$_POST['patient_id'];
    $message = trim($_POST['message']);
    
    // Validation
    if (empty($patient_id)) {
        $error = 'Please select a patient.';
    } elseif (empty($message)) {
        $error = 'Please enter an alert message.';
    } elseif (strlen($message) > 1000) {
        $error = 'Alert message must be 1000 characters or less.';
    } else {
        // Verify patient is assigned to this doctor
        $verify_query = "SELECT u.id FROM users u 
                        JOIN doctor_patients dp ON u.id = dp.patient_id 
                        WHERE dp.doctor_id = $user_id AND dp.patient_id = $patient_id AND u.role = 'patient'";
        
        $verify_result = mysqli_query($connection, $verify_query);
        
        if (mysqli_num_rows($verify_result) == 0) {
            $error = 'Invalid patient selection.';
        } else {
            // Insert alert
            $message_escaped = mysqli_real_escape_string($connection, $message);
            
            $insert_query = "INSERT INTO alerts (doctor_id, patient_id, message, status, created_at) 
                           VALUES ($user_id, $patient_id, '$message_escaped', 'sent', NOW())";
            
            if (mysqli_query($connection, $insert_query)) {
                $success = 'Alert sent successfully!';
                // Clear form data on success
                $message = '';
                $selected_patient_id = 0;
            } else {
                $error = 'Failed to send alert. Please try again. Error: ' . mysqli_error($connection);
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Send Health Alert</h1>
                <p class="text-gray-600">Send important health information and reminders to your patients</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="dashboard.php" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($patients_result) > 0): ?>
    <!-- Alert Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" class="space-y-6">
            <!-- Patient Selection -->
            <div>
                <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Patient <span class="text-red-500">*</span>
                </label>
                <select id="patient_id" name="patient_id" required 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Choose a patient...</option>
                    <?php 
                    // Reset result pointer
                    mysqli_data_seek($patients_result, 0);
                    while ($patient = mysqli_fetch_assoc($patients_result)): 
                    ?>
                        <option value="<?php echo $patient['id']; ?>" 
                                <?php echo ($selected_patient_id == $patient['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($patient['name']); ?> 
                            (<?php echo htmlspecialchars($patient['email']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php if ($selected_patient): ?>
                    <p class="mt-1 text-sm text-green-600">
                        ✓ Pre-selected: <?php echo htmlspecialchars($selected_patient['name']); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Alert Message -->
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                    Alert Message <span class="text-red-500">*</span>
                </label>
                <textarea id="message" name="message" required rows="6" maxlength="1000"
                          placeholder="Enter your health alert message for the patient..."
                          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                <div class="mt-1 flex justify-between">
                    <p class="text-xs text-gray-500">Maximum 1000 characters</p>
                    <p class="text-xs text-gray-500">
                        <span id="char-count">0</span>/1000 characters
                    </p>
                </div>
            </div>

            <!-- Quick Message Templates -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Quick Templates (Click to use)
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button type="button" class="template-btn text-left p-3 bg-blue-50 hover:bg-blue-100 rounded-md text-sm text-blue-800 transition-colors"
                            data-template="Please remember to take your medication as prescribed and record your daily health readings.">
                        📋 Medication Reminder
                    </button>
                    
                    <button type="button" class="template-btn text-left p-3 bg-yellow-50 hover:bg-yellow-100 rounded-md text-sm text-yellow-800 transition-colors"
                            data-template="Your recent blood pressure readings are concerning. Please schedule a follow-up appointment.">
                        ⚠️ Follow-up Required
                    </button>
                    
                    <button type="button" class="template-btn text-left p-3 bg-green-50 hover:bg-green-100 rounded-md text-sm text-green-800 transition-colors"
                            data-template="Great job on maintaining healthy readings! Keep up the excellent work with your health monitoring.">
                        ✅ Positive Feedback
                    </button>
                    
                    <button type="button" class="template-btn text-left p-3 bg-orange-50 hover:bg-orange-100 rounded-md text-sm text-orange-800 transition-colors"
                            data-template="Please monitor your blood sugar levels more closely and reduce sugar intake as discussed.">
                        🍎 Dietary Advice
                    </button>
                    
                    <button type="button" class="template-btn text-left p-3 bg-purple-50 hover:bg-purple-100 rounded-md text-sm text-purple-800 transition-colors"
                            data-template="I noticed you haven't recorded any health data recently. Please update your readings when possible.">
                        📊 Data Reminder
                    </button>
                    
                    <button type="button" class="template-btn text-left p-3 bg-indigo-50 hover:bg-indigo-100 rounded-md text-sm text-indigo-800 transition-colors"
                            data-template="Please contact our office to schedule your next appointment. Call (555) 123-4567.">
                        📞 Appointment Request
                    </button>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row gap-4">
                <button type="submit" 
                        class="flex-1 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                    Send Alert
                </button>
                
                <button type="button" onclick="clearForm()" 
                        class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    Clear Form
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Alerts -->
    <?php
    $recent_alerts_query = "SELECT a.message, a.created_at, u.name as patient_name, a.status
                           FROM alerts a
                           JOIN users u ON a.patient_id = u.id
                           WHERE a.doctor_id = $user_id
                           ORDER BY a.created_at DESC
                           LIMIT 5";
    $recent_alerts_result = mysqli_query($connection, $recent_alerts_query);
    ?>
    
    <?php if (mysqli_num_rows($recent_alerts_result) > 0): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Recent Alerts Sent</h2>
        <div class="space-y-3">
            <?php while ($alert = mysqli_fetch_assoc($recent_alerts_result)): ?>
            <div class="flex items-start justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex-1">
                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($alert['patient_name']); ?></p>
                    <p class="text-sm text-gray-700 mt-1">
                        <?php echo htmlspecialchars(substr($alert['message'], 0, 80)) . (strlen($alert['message']) > 80 ? '...' : ''); ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                    </p>
                </div>
                <div class="ml-4">
                    <?php if ($alert['status'] === 'sent'): ?>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Unread
                        </span>
                    <?php else: ?>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Read
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <div class="mt-4 text-center">
            <a href="sent_alerts.php" class="text-blue-600 hover:text-blue-800 font-medium">
                View All Sent Alerts →
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- No Patients State -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No Patients Assigned</h3>
        <p class="text-gray-600 mb-6">You don't have any patients assigned to you yet. Contact your administrator to get patient assignments.</p>
        <div class="space-x-4">
            <a href="dashboard.php" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors">
                Back to Dashboard
            </a>
            <a href="patient_list.php" 
               class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-md transition-colors">
                View Patients
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert Guidelines -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">💡 Alert Best Practices</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">Effective Alerts:</h4>
                <ul class="space-y-1">
                    <li>• Be clear and specific</li>
                    <li>• Include actionable instructions</li>
                    <li>• Use encouraging language</li>
                    <li>• Provide context when needed</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Alert Types:</h4>
                <ul class="space-y-1">
                    <li>• Medication reminders</li>
                    <li>• Health data requests</li>
                    <li>• Appointment scheduling</li>
                    <li>• Positive reinforcement</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    
    function updateCharCount() {
        const count = messageTextarea.value.length;
        charCount.textContent = count;
        
        if (count > 900) {
            charCount.classList.add('text-red-500');
            charCount.classList.remove('text-yellow-500');
        } else if (count > 800) {
            charCount.classList.add('text-yellow-500');
            charCount.classList.remove('text-red-500');
        } else {
            charCount.classList.remove('text-red-500', 'text-yellow-500');
        }
    }
    
    messageTextarea.addEventListener('input', updateCharCount);
    updateCharCount(); // Initial count
    
    // Template button event listeners
    const templateButtons = document.querySelectorAll('.template-btn');
    templateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const template = this.getAttribute('data-template');
            useTemplate(template);
        });
    });
});

// Template functions
function useTemplate(template) {
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    
    messageTextarea.value = template;
    charCount.textContent = template.length;
    
    // Update character count styling
    if (template.length > 900) {
        charCount.classList.add('text-red-500');
        charCount.classList.remove('text-yellow-500');
    } else if (template.length > 800) {
        charCount.classList.add('text-yellow-500');
        charCount.classList.remove('text-red-500');
    } else {
        charCount.classList.remove('text-red-500', 'text-yellow-500');
    }
    
    // Focus on textarea after template insertion
    messageTextarea.focus();
}

function clearForm() {
    document.getElementById('patient_id').value = '';
    document.getElementById('message').value = '';
    document.getElementById('char-count').textContent = '0';
    document.getElementById('char-count').classList.remove('text-red-500', 'text-yellow-500');
}

// Auto-save draft (optional enhancement)
let draftTimer;
function saveDraft() {
    const patientId = document.getElementById('patient_id').value;
    const message = document.getElementById('message').value;
    
    if (message.length > 10) {
        localStorage.setItem('alert_draft', JSON.stringify({
            patient_id: patientId,
            message: message,
            timestamp: Date.now()
        }));
    }
}

// Load draft on page load
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('alert_draft');
    if (draft) {
        const draftData = JSON.parse(draft);
        const age = Date.now() - draftData.timestamp;
        
        // Load draft if less than 1 hour old
        if (age < 3600000 && !document.getElementById('message').value) {
            if (confirm('You have a saved draft. Would you like to restore it?')) {
                document.getElementById('patient_id').value = draftData.patient_id;
                document.getElementById('message').value = draftData.message;
                document.getElementById('char-count').textContent = draftData.message.length;
            }
        }
    }
    
    // Auto-save every 30 seconds
    setInterval(saveDraft, 30000);
});

// Clear draft on successful send
<?php if ($success): ?>
localStorage.removeItem('alert_draft');
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>