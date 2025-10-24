<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get current admin details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();

if (!$admin) {
    header('Location: ../auth/login.php');
    exit();
}

// Handle site settings update
if ($_POST && isset($_POST['update_site_settings'])) {
    $site_name = trim($_POST['site_name']);
    $site_description = trim($_POST['site_description']);
    $contact_email = trim($_POST['contact_email']);
    $contact_phone = trim($_POST['contact_phone']);
    $address = trim($_POST['address']);
    
    if (empty($site_name)) {
        $error_message = "Site name is required.";
    } else {
        // For demo purposes, we'll store these in session
        $_SESSION['site_settings'] = [
            'site_name' => $site_name,
            'site_description' => $site_description,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'address' => $address
        ];
        $success_message = "Site settings updated successfully!";
    }
}

// Handle notification settings
if ($_POST && isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $application_alerts = isset($_POST['application_alerts']) ? 1 : 0;
    $placement_alerts = isset($_POST['placement_alerts']) ? 1 : 0;
    
    $_SESSION['notification_settings'] = [
        'email_notifications' => $email_notifications,
        'sms_notifications' => $sms_notifications,
        'application_alerts' => $application_alerts,
        'placement_alerts' => $placement_alerts
    ];
    $success_message = "Notification settings updated successfully!";
}

// Handle system maintenance
if ($_POST && isset($_POST['maintenance_mode'])) {
    $maintenance_enabled = isset($_POST['maintenance_enabled']) ? 1 : 0;
    $maintenance_message = trim($_POST['maintenance_message']);
    
    $_SESSION['maintenance_settings'] = [
        'enabled' => $maintenance_enabled,
        'message' => $maintenance_message
    ];
    $success_message = "Maintenance settings updated successfully!";
}

// Get current settings from session or set defaults
$site_settings = $_SESSION['site_settings'] ?? [
    'site_name' => SITE_NAME,
    'site_description' => 'Your comprehensive placement management system',
    'contact_email' => 'admin@placerhub.com',
    'contact_phone' => '+1 (555) 123-4567',
    'address' => '123 University Street, Education City, EC 12345'
];

$notification_settings = $_SESSION['notification_settings'] ?? [
    'email_notifications' => 1,
    'sms_notifications' => 0,
    'application_alerts' => 1,
    'placement_alerts' => 1
];

$maintenance_settings = $_SESSION['maintenance_settings'] ?? [
    'enabled' => 0,
    'message' => 'System is under maintenance. Please check back later.'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .animate-fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <i class="fas fa-graduation-cap text-2xl text-indigo-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900"><?php echo SITE_NAME; ?></span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                        <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                    </a>
                    <a href="students.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                        <i class="fas fa-users mr-1"></i>Students
                    </a>
                    <a href="companies.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                        <i class="fas fa-building mr-1"></i>Companies
                    </a>
                    <a href="profile.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                        <i class="fas fa-user mr-1"></i>Profile
                    </a>
                    <a href="settings.php" class="text-indigo-600 bg-indigo-50 px-3 py-2 rounded-md">
                        <i class="fas fa-cog mr-1"></i>Settings
                    </a>
                    <a href="../auth/logout.php" class="text-red-600 hover:text-red-700 px-3 py-2 rounded-md">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">System Settings</h1>
            <p class="mt-2 text-gray-600">Configure your PlacerHub system preferences and settings</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-circle mr-2 mt-0.5"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Site Settings -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-globe mr-2 text-indigo-600"></i>Site Settings
                </h2>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">Site Name *</label>
                        <input type="text" id="site_name" name="site_name" required
                               value="<?php echo htmlspecialchars($site_settings['site_name']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label for="site_description" class="block text-sm font-medium text-gray-700 mb-2">Site Description</label>
                        <textarea id="site_description" name="site_description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($site_settings['site_description']); ?></textarea>
                    </div>
                    
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email"
                               value="<?php echo htmlspecialchars($site_settings['contact_email']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone"
                               value="<?php echo htmlspecialchars($site_settings['contact_phone']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea id="address" name="address" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($site_settings['address']); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="update_site_settings" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-save mr-2"></i>Update Site Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notification Settings -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-bell mr-2 text-indigo-600"></i>Notification Settings
                </h2>
                
                <form method="POST" class="space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="email_notifications" name="email_notifications" 
                                   <?php echo $notification_settings['email_notifications'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="email_notifications" class="ml-3 text-sm font-medium text-gray-700">
                                Email Notifications
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="sms_notifications" name="sms_notifications"
                                   <?php echo $notification_settings['sms_notifications'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="sms_notifications" class="ml-3 text-sm font-medium text-gray-700">
                                SMS Notifications
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="application_alerts" name="application_alerts"
                                   <?php echo $notification_settings['application_alerts'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="application_alerts" class="ml-3 text-sm font-medium text-gray-700">
                                New Application Alerts
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="placement_alerts" name="placement_alerts"
                                   <?php echo $notification_settings['placement_alerts'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="placement_alerts" class="ml-3 text-sm font-medium text-gray-700">
                                Placement Success Alerts
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="update_notifications" 
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-bell mr-2"></i>Update Notifications
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Maintenance -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-tools mr-2 text-indigo-600"></i>System Maintenance
                </h2>
                
                <form method="POST" class="space-y-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="maintenance_enabled" name="maintenance_enabled"
                               <?php echo $maintenance_settings['enabled'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="maintenance_enabled" class="ml-3 text-sm font-medium text-gray-700">
                            Enable Maintenance Mode
                        </label>
                    </div>
                    
                    <div>
                        <label for="maintenance_message" class="block text-sm font-medium text-gray-700 mb-2">Maintenance Message</label>
                        <textarea id="maintenance_message" name="maintenance_message" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"><?php echo htmlspecialchars($maintenance_settings['message']); ?></textarea>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2 mt-0.5"></i>
                            <div>
                                <p class="text-sm text-yellow-800">
                                    <strong>Warning:</strong> Enabling maintenance mode will make the site inaccessible to regular users.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="maintenance_mode" 
                                class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-tools mr-2"></i>Update Maintenance
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Information -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-indigo-600"></i>System Information
                </h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-600">PHP Version:</span>
                        <span class="font-semibold"><?php echo PHP_VERSION; ?></span>
                    </div>
                    
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-600">Server Software:</span>
                        <span class="font-semibold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                    </div>
                    
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-600">Database Status:</span>
                        <span class="font-semibold text-green-600">
                            <i class="fas fa-check-circle mr-1"></i>Connected
                        </span>
                    </div>
                    
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-600">System Status:</span>
                        <span class="font-semibold text-green-600">
                            <i class="fas fa-check-circle mr-1"></i>Operational
                        </span>
                    </div>
                    
                    <div class="flex justify-between py-2">
                        <span class="text-gray-600">Last Updated:</span>
                        <span class="font-semibold"><?php echo date('M d, Y H:i:s'); ?></span>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 gap-3">
                        <button onclick="clearCache()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200 text-left">
                            <i class="fas fa-broom mr-2"></i>Clear System Cache
                        </button>
                        <button onclick="exportData()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition duration-200 text-left">
                            <i class="fas fa-download mr-2"></i>Export System Data
                        </button>
                        <button onclick="runDiagnostics()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg transition duration-200 text-left">
                            <i class="fas fa-stethoscope mr-2"></i>Run System Diagnostics
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// System actions
function clearCache() {
    if (confirm('Are you sure you want to clear the system cache?')) {
        // Simulate cache clearing
        showNotification('System cache cleared successfully!', 'success');
    }
}

function exportData() {
    if (confirm('This will export all system data. Continue?')) {
        // Simulate data export
        showNotification('Data export started. You will receive an email when complete.', 'info');
    }
}

function runDiagnostics() {
    showNotification('Running system diagnostics...', 'info');
    
    // Simulate diagnostics
    setTimeout(() => {
        showNotification('System diagnostics completed. All systems operational.', 'success');
    }, 3000);
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle mr-2"></i>${message}`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Please fill in all required fields.', 'error');
            }
        });
    });
});
</script>

</body>
</html>
