<?php
require_once '../config/config.php';
requireLogin();

if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$db = getDB();

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get dashboard statistics
$stats = [];

// Total applications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM applications WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats['total_applications'] = $stmt->fetch()['total'];

// Active drives
$stmt = $db->prepare("SELECT COUNT(*) as total FROM placement_drives WHERE status = 'active' AND deadline >= CURDATE()");
$stmt->execute();
$stats['active_drives'] = $stmt->fetch()['total'];

// Shortlisted applications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM applications WHERE user_id = ? AND status IN ('shortlisted', 'selected')");
$stmt->execute([$_SESSION['user_id']]);
$stats['shortlisted'] = $stmt->fetch()['total'];

// Unread notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND status = 'unread'");
$stmt->execute([$_SESSION['user_id']]);
$stats['unread_notifications'] = $stmt->fetch()['total'];

// Recent applications
$stmt = $db->prepare("
    SELECT a.*, pd.title, pd.job_role, c.name as company_name, pd.package_min, pd.package_max
    FROM applications a
    JOIN placement_drives pd ON a.drive_id = pd.id
    JOIN companies c ON pd.company_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_applications = $stmt->fetchAll();

// Upcoming drives
$stmt = $db->prepare("
    SELECT pd.*, c.name as company_name
    FROM placement_drives pd
    JOIN companies c ON pd.company_id = c.id
    WHERE pd.status = 'active' AND pd.deadline >= CURDATE()
    ORDER BY pd.deadline ASC
    LIMIT 5
");
$stmt->execute();
$upcoming_drives = $stmt->fetchAll();

// Recent notifications
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-graduation-cap text-2xl text-indigo-600 mr-3"></i>
                    <h1 class="text-xl font-bold text-gray-900"><?php echo SITE_NAME; ?></h1>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-indigo-600 font-medium">Dashboard</a>
                    <a href="profile.php" class="text-gray-700 hover:text-indigo-600">Profile</a>
                    <a href="drives.php" class="text-gray-700 hover:text-indigo-600">Job Drives</a>
                    <a href="applications.php" class="text-gray-700 hover:text-indigo-600">My Applications</a>
                    <a href="notifications.php" class="text-gray-700 hover:text-indigo-600 relative">
                        <i class="fas fa-bell"></i>
                        <?php if ($stats['unread_notifications'] > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $stats['unread_notifications']; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center text-gray-700 hover:text-indigo-600">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=6366f1&color=fff" 
                                 alt="Profile" class="w-8 h-8 rounded-full mr-2">
                            <span class="hidden md:block"><?php echo $user['name']; ?></span>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a href="settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button onclick="toggleMobileMenu()" class="text-gray-700 hover:text-indigo-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="index.php" class="block px-3 py-2 text-indigo-600 font-medium">Dashboard</a>
                <a href="profile.php" class="block px-3 py-2 text-gray-700">Profile</a>
                <a href="drives.php" class="block px-3 py-2 text-gray-700">Job Drives</a>
                <a href="applications.php" class="block px-3 py-2 text-gray-700">My Applications</a>
                <a href="notifications.php" class="block px-3 py-2 text-gray-700">Notifications</a>
                <a href="../auth/logout.php" class="block px-3 py-2 text-gray-700">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome back, <?php echo $user['name']; ?>!</h1>
            <p class="text-gray-600">Here's what's happening with your placement activities</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_applications']; ?></p>
                        <p class="text-gray-600">Total Applications</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-green-100 text-green-600 p-3 rounded-full">
                        <i class="fas fa-building text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['active_drives']; ?></p>
                        <p class="text-gray-600">Active Drives</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full">
                        <i class="fas fa-star text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['shortlisted']; ?></p>
                        <p class="text-gray-600">Shortlisted</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-red-100 text-red-600 p-3 rounded-full">
                        <i class="fas fa-bell text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['unread_notifications']; ?></p>
                        <p class="text-gray-600">Notifications</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Applications -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">Recent Applications</h2>
                        <a href="applications.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View All</a>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($recent_applications)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No applications yet</p>
                            <a href="drives.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Browse Job Drives</a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_applications as $app): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo $app['title']; ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo $app['company_name']; ?></p>
                                        <p class="text-xs text-gray-500">Applied: <?php echo formatDate($app['applied_at']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php 
                                            switch($app['status']) {
                                                case 'applied': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'shortlisted': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'selected': echo 'bg-green-100 text-green-800'; break;
                                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                            }
                                            ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                        <?php if ($app['package_min'] && $app['package_max']): ?>
                                            <p class="text-xs text-gray-500 mt-1">₹<?php echo $app['package_min']; ?>-<?php echo $app['package_max']; ?>L</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Drives -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">Upcoming Drives</h2>
                        <a href="drives.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View All</a>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($upcoming_drives)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-building text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No upcoming drives</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_drives as $drive): ?>
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="font-medium text-gray-900"><?php echo $drive['title']; ?></h3>
                                            <p class="text-sm text-gray-600"><?php echo $drive['company_name']; ?></p>
                                            <p class="text-xs text-gray-500">Deadline: <?php echo formatDate($drive['deadline']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <?php if ($drive['package_min'] && $drive['package_max']): ?>
                                                <p class="text-sm font-medium text-gray-900">₹<?php echo $drive['package_min']; ?>-<?php echo $drive['package_max']; ?>L</p>
                                            <?php endif; ?>
                                            <a href="drives.php?id=<?php echo $drive['id']; ?>" class="text-xs text-indigo-600 hover:text-indigo-800">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="mt-8 bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Notifications</h2>
                    <a href="notifications.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View All</a>
                </div>
            </div>
            <div class="p-6">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-bell text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No notifications</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg <?php echo $notification['status'] === 'unread' ? 'border-l-4 border-indigo-500' : ''; ?>">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-info text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="font-medium text-gray-900"><?php echo $notification['title']; ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo $notification['message']; ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo timeAgo($notification['created_at']); ?></p>
                                </div>
                                <?php if ($notification['status'] === 'unread'): ?>
                                    <div class="flex-shrink-0">
                                        <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (!event.target.closest('[onclick="toggleUserMenu()"]')) {
                userMenu.classList.add('hidden');
            }
            
            if (!event.target.closest('[onclick="toggleMobileMenu()"]')) {
                mobileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
