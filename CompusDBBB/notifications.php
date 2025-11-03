<?php
require_once 'config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = sanitizeInput($_POST['action']);
        $notificationId = intval($_POST['notification_id'] ?? 0);
        
        if ($action === 'mark_read' && $notificationId > 0) {
            $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $user['id']]);
        } elseif ($action === 'mark_all_read') {
            $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ? AND status = 'unread'");
            $stmt->execute([$user['id']]);
        } elseif ($action === 'delete' && $notificationId > 0) {
            $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $user['id']]);
        }
        
        // Redirect to prevent form resubmission
        header('Location: notifications.php');
        exit;
    }
}

// Get notifications with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
$stmt->execute([$user['id']]);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get notifications
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$user['id'], $limit, $offset]);
$notifications = $stmt->fetchAll();

// Get unread count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
$stmt->execute([$user['id']]);
$unreadCount = $stmt->fetch()['count'];

$pageTitle = 'Notifications';
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
        .notification-item {
            transition: all 0.2s ease;
        }
        .notification-item:hover {
            transform: translateX(4px);
        }
        .notification-unread {
            border-left: 4px solid #3b82f6;
            background-color: #eff6ff;
        }
        .notification-read {
            border-left: 4px solid #e5e7eb;
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
                <a href="notifications.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-bell mr-3"></i>Notifications
                    <?php if ($unreadCount > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-user mr-3"></i>Profile
                </a>
                <a href="settings.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
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
                    <?php if ($unreadCount > 0): ?>
                    <span class="ml-3 px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full">
                        <?php echo $unreadCount; ?> unread
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if ($unreadCount > 0): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-check-double mr-2"></i>Mark All Read
                        </button>
                    </form>
                    <?php endif; ?>
                    
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
                                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-cog mr-2"></i>Settings
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
                    <li class="text-gray-900">Notifications</li>
                </ol>
            </nav>

            <!-- Notifications List -->
            <div class="bg-white rounded-lg shadow">
                <?php if (empty($notifications)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-bell-slash text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
                    <p class="text-gray-500">You're all caught up! No new notifications at the moment.</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['status'] === 'unread' ? 'notification-unread' : 'notification-read'; ?> p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <div class="flex-shrink-0 mr-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                                            <?php 
                                            switch($notification['type']) {
                                                case 'success': echo 'bg-green-100 text-green-600'; break;
                                                case 'warning': echo 'bg-yellow-100 text-yellow-600'; break;
                                                case 'error': echo 'bg-red-100 text-red-600'; break;
                                                default: echo 'bg-blue-100 text-blue-600';
                                            }
                                            ?>">
                                            <i class="fas fa-<?php 
                                                switch($notification['type']) {
                                                    case 'success': echo 'check-circle'; break;
                                                    case 'warning': echo 'exclamation-triangle'; break;
                                                    case 'error': echo 'times-circle'; break;
                                                    default: echo 'info-circle';
                                                }
                                            ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-medium text-gray-900 mb-1">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php if ($notification['status'] === 'unread'): ?>
                                            <span class="inline-block w-2 h-2 bg-blue-500 rounded-full ml-2"></span>
                                            <?php endif; ?>
                                        </h4>
                                        <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <div class="flex items-center text-sm text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?php echo timeAgo($notification['created_at']); ?>
                                            <span class="mx-2">•</span>
                                            <?php echo formatDateTime($notification['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center space-x-2 ml-4">
                                <?php if ($notification['status'] === 'unread'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50" title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this notification?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                    <?php echo generatePagination($page, $totalPages, 'notifications.php', []); ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Notification Settings -->
            <div class="mt-6 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-cog mr-2 text-gray-600"></i>Notification Settings
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Email Notifications</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                <span class="ml-2 text-sm text-gray-600">System updates</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                <span class="ml-2 text-sm text-gray-600">Account activities</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600">Weekly reports</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Push Notifications</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                <span class="ml-2 text-sm text-gray-600">Instant alerts</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                <span class="ml-2 text-sm text-gray-600">Support responses</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600">Marketing updates</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </div>
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

        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            // In a real application, you might use AJAX to check for new notifications
            // For now, we'll just reload the page if there are unread notifications
            <?php if ($unreadCount > 0): ?>
            const currentUnread = <?php echo $unreadCount; ?>;
            // You could implement AJAX polling here
            <?php endif; ?>
        }, 30000);

        // Mark notification as read when clicked
        document.querySelectorAll('.notification-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                // Don't trigger if clicking on action buttons
                if (!e.target.closest('form') && !e.target.closest('button')) {
                    const unreadIndicator = item.querySelector('.notification-unread');
                    if (unreadIndicator) {
                        // Auto-mark as read when clicked (you could implement AJAX here)
                    }
                }
            });
        });
    </script>
</body>
</html>
