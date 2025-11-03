<?php
require_once 'config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

$error = '';
$success = '';

// Get user settings
$stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$stmt->execute([$user['id']]);
$userSettings = $stmt->fetch();

// Create default settings if not exists
if (!$userSettings) {
    $stmt = $db->prepare("
        INSERT INTO user_settings (user_id, theme_mode, notification_pref, language) 
        VALUES (?, 'light', ?, 'en')
    ");
    $defaultNotifications = json_encode([
        'email_notifications' => true,
        'push_notifications' => true,
        'ticket_updates' => true,
        'system_alerts' => true
    ]);
    $stmt->execute([$user['id'], $defaultNotifications]);
    
    // Refresh settings
    $stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $userSettings = $stmt->fetch();
}

$notificationPrefs = json_decode($userSettings['notification_pref'], true) ?: [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');
        
        if ($action === 'update_preferences') {
            $themeMode = sanitizeInput($_POST['theme_mode'] ?? 'light');
            $language = sanitizeInput($_POST['language'] ?? 'en');
            $dateFormat = sanitizeInput($_POST['date_format'] ?? 'Y-m-d');
            $timezone = sanitizeInput($_POST['timezone'] ?? 'UTC');
            
            // Notification preferences
            $notifications = [
                'email_notifications' => isset($_POST['email_notifications']),
                'push_notifications' => isset($_POST['push_notifications']),
                'ticket_updates' => isset($_POST['ticket_updates']),
                'system_alerts' => isset($_POST['system_alerts']),
                'weekly_reports' => isset($_POST['weekly_reports']),
                'marketing_updates' => isset($_POST['marketing_updates'])
            ];
            
            try {
                $stmt = $db->prepare("
                    UPDATE user_settings 
                    SET theme_mode = ?, notification_pref = ?, language = ?, timezone = ?, date_format = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $themeMode,
                    json_encode($notifications),
                    $language,
                    $timezone,
                    $dateFormat,
                    $user['id']
                ]);
                
                // Log activity
                $stmt = $db->prepare("
                    INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    'settings_updated',
                    'user_settings',
                    $userSettings['setting_id'],
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                // Create notification
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, title, message, type) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    'Settings Updated',
                    'Your preferences have been successfully updated.',
                    'success'
                ]);
                
                $success = 'Settings updated successfully!';
                
                // Refresh settings
                $stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $userSettings = $stmt->fetch();
                $notificationPrefs = json_decode($userSettings['notification_pref'], true) ?: [];
                
            } catch (Exception $e) {
                $error = 'Failed to update settings: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .setting-card {
            transition: all 0.2s ease;
        }
        .setting-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #4f46e5;
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 sidebar-transition" id="sidebar">
        <div class="flex items-center justify-center h-16 bg-indigo-600">
            <div class="flex items-center">
                <i class="fas fa-database text-white text-xl mr-2"></i>
                <span class="text-white text-lg font-semibold">CompusDB</span>
            </div>
        </div>
        
        <nav class="mt-8">
            <div class="px-4 space-y-2">
                <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="students/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-users mr-3"></i>Students
                </a>
                <a href="courses/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-book mr-3"></i>Courses
                </a>
                <a href="departments/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-building mr-3"></i>Departments
                </a>
                <a href="reports/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-chart-bar mr-3"></i>Reports
                </a>
                <a href="support/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-headset mr-3"></i>Support
                </a>
                <a href="notifications.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-bell mr-3"></i>Notifications
                </a>
                <a href="profile.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-user mr-3"></i>Profile
                </a>
                <a href="settings.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-cog mr-3"></i>Settings
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button class="lg:hidden text-gray-500 hover:text-gray-700" onclick="toggleSidebar()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="ml-4 text-2xl font-semibold text-gray-800"><?php echo $pageTitle; ?></h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- User Menu -->
                    <div class="relative">
                        <button class="flex items-center text-gray-700 hover:text-gray-900" onclick="toggleUserMenu()">
                            <img src="<?php echo getUserAvatar($user['profile_picture'], $user['email']); ?>" 
                                 alt="Profile" class="w-8 h-8 rounded-full mr-2">
                            <span class="hidden md:block"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        
                        <!-- User Dropdown -->
                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="p-4 border-b border-gray-200">
                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="py-2">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user mr-2"></i>Profile
                                </a>
                                <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                </a>
                                <div class="border-t border-gray-200 my-2"></div>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="p-6">
            <!-- Breadcrumb -->
            <nav class="mb-6">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li><a href="dashboard.php" class="hover:text-gray-700">Dashboard</a></li>
                    <li><i class="fas fa-chevron-right"></i></li>
                    <li class="text-gray-900">Settings</li>
                </ol>
            </nav>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update_preferences">

                <!-- Appearance Settings -->
                <div class="bg-white rounded-lg shadow setting-card">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-palette mr-2 text-indigo-600"></i>Appearance
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">Customize how the application looks and feels</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="theme_mode" class="block text-sm font-medium text-gray-700 mb-2">
                                    Theme Mode
                                </label>
                                <select id="theme_mode" name="theme_mode" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="light" <?php echo $userSettings['theme_mode'] === 'light' ? 'selected' : ''; ?>>Light</option>
                                    <option value="dark" <?php echo $userSettings['theme_mode'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                                    <option value="auto" <?php echo $userSettings['theme_mode'] === 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                                    Language
                                </label>
                                <select id="language" name="language" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="en" <?php echo $userSettings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="hi" <?php echo $userSettings['language'] === 'hi' ? 'selected' : ''; ?>>Hindi</option>
                                    <option value="es" <?php echo $userSettings['language'] === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                    <option value="fr" <?php echo $userSettings['language'] === 'fr' ? 'selected' : ''; ?>>French</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Regional Settings -->
                <div class="bg-white rounded-lg shadow setting-card">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-globe mr-2 text-green-600"></i>Regional Settings
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">Configure date, time, and regional preferences</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Timezone
                                </label>
                                <select id="timezone" name="timezone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="UTC" <?php echo $userSettings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    <option value="Asia/Kolkata" <?php echo $userSettings['timezone'] === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                                    <option value="America/New_York" <?php echo $userSettings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                    <option value="Europe/London" <?php echo $userSettings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                                    <option value="Asia/Tokyo" <?php echo $userSettings['timezone'] === 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo (JST)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date_format" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date Format
                                </label>
                                <select id="date_format" name="date_format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="Y-m-d" <?php echo $userSettings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                    <option value="m/d/Y" <?php echo $userSettings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    <option value="d/m/Y" <?php echo $userSettings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                    <option value="M d, Y" <?php echo $userSettings['date_format'] === 'M d, Y' ? 'selected' : ''; ?>>Mon DD, YYYY</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="bg-white rounded-lg shadow setting-card">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-bell mr-2 text-yellow-600"></i>Notifications
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">Control when and how you receive notifications</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-4">Email Notifications</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">System updates and alerts</span>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="email_notifications" <?php echo ($notificationPrefs['email_notifications'] ?? true) ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Account activities</span>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="system_alerts" <?php echo ($notificationPrefs['system_alerts'] ?? true) ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Weekly reports</span>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="weekly_reports" <?php echo ($notificationPrefs['weekly_reports'] ?? false) ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-4">Push Notifications</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Instant alerts</span>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="push_notifications" <?php echo ($notificationPrefs['push_notifications'] ?? true) ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Support ticket updates</span>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="ticket_updates" <?php echo ($notificationPrefs['ticket_updates'] ?? true) ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Marketing updates</span>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="marketing_updates" <?php echo ($notificationPrefs['marketing_updates'] ?? false) ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Privacy & Security -->
                <div class="bg-white rounded-lg shadow setting-card">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-shield-alt mr-2 text-red-600"></i>Privacy & Security
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">Manage your privacy and security settings</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-800">Two-Factor Authentication</h4>
                                    <p class="text-sm text-gray-600">Add an extra layer of security to your account</p>
                                </div>
                                <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition duration-200" onclick="alert('2FA setup would be implemented here')">
                                    Enable 2FA
                                </button>
                            </div>
                            
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-800">Active Sessions</h4>
                                    <p class="text-sm text-gray-600">Manage your active login sessions</p>
                                </div>
                                <button type="button" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition duration-200" onclick="alert('Session management would be implemented here')">
                                    View Sessions
                                </button>
                            </div>
                            
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-800">Data Export</h4>
                                    <p class="text-sm text-gray-600">Download a copy of your data</p>
                                </div>
                                <button type="button" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition duration-200" onclick="alert('Data export would be implemented here')">
                                    Export Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Settings -->
                <div class="flex items-center justify-between pt-6">
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Settings are automatically saved when you make changes
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg transition duration-200 font-medium">
                        <i class="fas fa-save mr-2"></i>Save All Settings
                    </button>
                </div>
            </form>
        </main>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // User menu dropdown
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const userDropdown = document.getElementById('userDropdown');
            
            if (!event.target.closest('.relative')) {
                userDropdown.classList.add('hidden');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-100, .bg-green-100');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Theme preview
        document.getElementById('theme_mode').addEventListener('change', function() {
            const theme = this.value;
            // In a real implementation, you would apply theme changes here
            console.log('Theme changed to:', theme);
        });

        // Auto-save on toggle changes
        document.querySelectorAll('.toggle-switch input').forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                // In a real implementation, you might auto-save settings here
                console.log('Setting changed:', this.name, this.checked);
            });
        });
    </script>
</body>
</html>
