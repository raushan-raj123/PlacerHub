<?php
require_once '../config/config.php';
requireLogin();

if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$db = getDB();

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    redirect('notifications.php');
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    redirect('notifications.php');
}

// Get notifications
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Get unread count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <a href="index.php" class="text-gray-700 hover:text-indigo-600">Dashboard</a>
                    <a href="profile.php" class="text-gray-700 hover:text-indigo-600">Profile</a>
                    <a href="drives.php" class="text-gray-700 hover:text-indigo-600">Job Drives</a>
                    <a href="applications.php" class="text-gray-700 hover:text-indigo-600">My Applications</a>
                    <a href="notifications.php" class="text-indigo-600 font-medium">
                        <i class="fas fa-bell"></i>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center text-gray-700 hover:text-indigo-600">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=6366f1&color=fff" 
                                 alt="Profile" class="w-8 h-8 rounded-full mr-2">
                            <span class="hidden md:block"><?php echo $_SESSION['name']; ?></span>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Notifications</h1>
                <p class="text-gray-600">
                    <?php if ($unread_count > 0): ?>
                        You have <?php echo $unread_count; ?> unread notification<?php echo $unread_count > 1 ? 's' : ''; ?>
                    <?php else: ?>
                        All notifications are read
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($unread_count > 0): ?>
                <form method="POST">
                    <button type="submit" name="mark_all_read" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-check-double mr-2"></i>Mark All Read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Notifications -->
        <div class="space-y-4">
            <?php if (empty($notifications)): ?>
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-bell text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No notifications</h3>
                    <p class="text-gray-600">You're all caught up! New notifications will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition duration-300 
                                <?php echo $notification['status'] === 'unread' ? 'border-l-4 border-indigo-500' : ''; ?>">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start flex-1">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                                                    <?php 
                                                    switch($notification['type']) {
                                                        case 'success': echo 'bg-green-100 text-green-600'; break;
                                                        case 'warning': echo 'bg-yellow-100 text-yellow-600'; break;
                                                        case 'error': echo 'bg-red-100 text-red-600'; break;
                                                        default: echo 'bg-blue-100 text-blue-600'; break;
                                                    }
                                                    ?>">
                                            <i class="fas 
                                                <?php 
                                                switch($notification['type']) {
                                                    case 'success': echo 'fa-check-circle'; break;
                                                    case 'warning': echo 'fa-exclamation-triangle'; break;
                                                    case 'error': echo 'fa-times-circle'; break;
                                                    default: echo 'fa-info-circle'; break;
                                                }
                                                ?>"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4 flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                            <?php echo $notification['title']; ?>
                                            <?php if ($notification['status'] === 'unread'): ?>
                                                <span class="ml-2 w-2 h-2 bg-indigo-500 rounded-full inline-block"></span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-gray-600 mb-2"><?php echo $notification['message']; ?></p>
                                        <p class="text-sm text-gray-500"><?php echo timeAgo($notification['created_at']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2 ml-4">
                                    <?php if ($notification['status'] === 'unread'): ?>
                                        <a href="?mark_read=<?php echo $notification['id']; ?>" 
                                           class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                            Mark as read
                                        </a>
                                    <?php endif; ?>
                                    
                                    <div class="text-gray-400">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            if (!event.target.closest('[onclick="toggleUserMenu()"]')) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
