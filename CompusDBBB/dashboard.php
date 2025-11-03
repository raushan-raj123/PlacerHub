<?php
require_once 'config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

// Get dashboard statistics
$stats = [];

// Total students
$stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
$stats['total_students'] = $stmt->fetch()['count'];

// Total courses
$stmt = $db->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
$stats['total_courses'] = $stmt->fetch()['count'];

// Total departments
$stmt = $db->query("SELECT COUNT(*) as count FROM departments WHERE status = 'active'");
$stats['total_departments'] = $stmt->fetch()['count'];

// Unread notifications
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
$stmt->execute([$user['id']]);
$stats['unread_notifications'] = $stmt->fetch()['count'];

// Recent students
$stmt = $db->prepare("SELECT * FROM students ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recentStudents = $stmt->fetchAll();

// Recent notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$recentNotifications = $stmt->fetchAll();

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .notification-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
                <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
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
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button class="text-gray-500 hover:text-gray-700 relative" onclick="toggleNotifications()">
                            <i class="fas fa-bell text-xl"></i>
                            <?php if ($stats['unread_notifications'] > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center notification-badge">
                                <?php echo $stats['unread_notifications']; ?>
                            </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Notifications Dropdown -->
                        <div id="notificationsDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="p-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <?php if (empty($recentNotifications)): ?>
                                <div class="p-4 text-center text-gray-500">
                                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                    <p>No notifications</p>
                                </div>
                                <?php else: ?>
                                <?php foreach ($recentNotifications as $notification): ?>
                                <div class="p-4 border-b border-gray-100 hover:bg-gray-50 <?php echo $notification['status'] === 'unread' ? 'bg-blue-50' : ''; ?>">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-<?php echo $notification['type'] === 'success' ? 'check-circle text-green-500' : ($notification['type'] === 'warning' ? 'exclamation-triangle text-yellow-500' : ($notification['type'] === 'error' ? 'times-circle text-red-500' : 'info-circle text-blue-500')); ?>"></i>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($notification['title']); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <p class="text-xs text-gray-400 mt-1"><?php echo timeAgo($notification['created_at']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-4 border-t border-gray-200">
                                <a href="notifications.php" class="text-sm text-indigo-600 hover:text-indigo-500">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    
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

        <!-- Dashboard Content -->
        <main class="p-6">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-6 text-white mb-6">
                <h2 class="text-2xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
                <p class="text-indigo-100">Here's what's happening with your data today.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo number_format($stats['total_students']); ?></h3>
                            <p class="text-gray-600">Total Students</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-book text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo number_format($stats['total_courses']); ?></h3>
                            <p class="text-gray-600">Active Courses</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-building text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo number_format($stats['total_departments']); ?></h3>
                            <p class="text-gray-600">Departments</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-bell text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo number_format($stats['unread_notifications']); ?></h3>
                            <p class="text-gray-600">Notifications</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="students/add.php" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition duration-200">
                        <i class="fas fa-user-plus text-2xl text-blue-600 mb-2"></i>
                        <span class="text-sm font-medium text-blue-800">Add Student</span>
                    </a>
                    <a href="courses/add.php" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition duration-200">
                        <i class="fas fa-plus-circle text-2xl text-green-600 mb-2"></i>
                        <span class="text-sm font-medium text-green-800">Add Course</span>
                    </a>
                    <a href="reports/index.php" class="flex flex-col items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition duration-200">
                        <i class="fas fa-chart-bar text-2xl text-yellow-600 mb-2"></i>
                        <span class="text-sm font-medium text-yellow-800">View Reports</span>
                    </a>
                    <a href="support/create.php" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition duration-200">
                        <i class="fas fa-headset text-2xl text-purple-600 mb-2"></i>
                        <span class="text-sm font-medium text-purple-800">Get Support</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Students -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Students</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentStudents)): ?>
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p>No students found</p>
                            <a href="students/add.php" class="text-indigo-600 hover:text-indigo-500 mt-2 inline-block">Add your first student</a>
                        </div>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentStudents as $student): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($student['name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($student['course']); ?> - <?php echo htmlspecialchars($student['student_id']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500"><?php echo timeAgo($student['created_at']); ?></p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="students/index.php" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">View all students</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Chart -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Activity Overview</h3>
                    </div>
                    <div class="p-6">
                        <canvas id="activityChart" width="400" height="200"></canvas>
                    </div>
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

        // Notifications dropdown
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationsDropdown');
            dropdown.classList.toggle('hidden');
        }

        // User menu dropdown
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const notificationsDropdown = document.getElementById('notificationsDropdown');
            const userDropdown = document.getElementById('userDropdown');
            
            if (!event.target.closest('.relative')) {
                notificationsDropdown.classList.add('hidden');
                userDropdown.classList.add('hidden');
            }
        });

        // Activity Chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Students Added',
                    data: [12, 19, 3, 5, 2, 3, 7],
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
