<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

// Get comprehensive dashboard statistics
$stats = [];

// User statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$stats['admin_users'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stats['active_users'] = $stmt->fetch()['total'];

// Student statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM students");
$stats['total_students'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$stats['active_students'] = $stmt->fetch()['total'];

// Course and department statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM courses WHERE status = 'active'");
$stats['total_courses'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM departments WHERE status = 'active'");
$stats['total_departments'] = $stmt->fetch()['total'];

// Support statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM tickets");
$stats['total_tickets'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'pending'");
$stats['pending_tickets'] = $stmt->fetch()['total'];

// Activity statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$stats['today_activities'] = $stmt->fetch()['total'];

// System statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM backups");
$stats['total_backups'] = $stmt->fetch()['total'];

// Recent activities
$stmt = $db->prepare("
    SELECT al.*, u.full_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recentActivities = $stmt->fetchAll();

// Recent users
$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll();

// Course distribution
$stmt = $db->query("
    SELECT course, COUNT(*) as count 
    FROM students 
    WHERE status = 'active' 
    GROUP BY course 
    ORDER BY count DESC 
    LIMIT 5
");
$courseDistribution = $stmt->fetchAll();

// Monthly registration data for chart
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM students 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$monthlyData = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
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
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .admin-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 sidebar-transition" id="sidebar">
        <div class="flex items-center justify-center h-16 admin-badge">
            <div class="flex items-center">
                <i class="fas fa-shield-alt text-white text-xl mr-2"></i>
                <span class="text-white text-lg font-semibold">Admin Panel</span>
            </div>
        </div>
        
        <nav class="mt-8">
            <div class="px-4 space-y-2">
                <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="users.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-users-cog mr-3"></i>User Management
                </a>
                <a href="../students/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-users mr-3"></i>Students
                </a>
                <a href="courses.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-book mr-3"></i>Courses
                </a>
                <a href="departments.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-building mr-3"></i>Departments
                </a>
                <a href="tickets.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-headset mr-3"></i>Support Tickets
                    <?php if ($stats['pending_tickets'] > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $stats['pending_tickets']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-chart-bar mr-3"></i>Reports
                </a>
                <a href="activity-logs.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-history mr-3"></i>Activity Logs
                </a>
                <a href="backups.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-database mr-3"></i>Backups
                </a>
                <a href="settings.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-cog mr-3"></i>System Settings
                </a>
                <div class="border-t border-gray-200 my-4"></div>
                <a href="../dashboard.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-user mr-3"></i>User View
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
                    <span class="ml-3 px-3 py-1 bg-purple-100 text-purple-800 text-sm font-medium rounded-full">
                        <i class="fas fa-shield-alt mr-1"></i>Administrator
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Quick Actions -->
                    <div class="hidden md:flex items-center space-x-2">
                        <a href="../students/add.php" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm transition duration-200">
                            <i class="fas fa-plus mr-1"></i>Add Student
                        </a>
                        <a href="users.php?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm transition duration-200">
                            <i class="fas fa-user-plus mr-1"></i>Add User
                        </a>
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
                                <span class="inline-block mt-1 px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">Admin</span>
                            </div>
                            <div class="py-2">
                                <a href="../profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user mr-2"></i>Profile
                                </a>
                                <a href="../settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-cog mr-2"></i>Settings
                                </a>
                                <a href="../dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-eye mr-2"></i>User View
                                </a>
                                <div class="border-t border-gray-200 my-2"></div>
                                <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
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
            <div class="admin-badge rounded-lg p-6 text-white mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
                        <p class="text-indigo-100">System overview and management dashboard</p>
                    </div>
                    <div class="text-right">
                        <p class="text-indigo-100 text-sm">Last login</p>
                        <p class="text-white font-semibold"><?php echo date('M d, Y H:i'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Users Stats -->
                <div class="bg-white rounded-lg shadow stat-card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_users']); ?></h3>
                                <p class="text-gray-600">Total Users</p>
                                <p class="text-sm text-green-600"><?php echo $stats['active_users']; ?> active</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Stats -->
                <div class="bg-white rounded-lg shadow stat-card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-graduation-cap text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_students']); ?></h3>
                                <p class="text-gray-600">Total Students</p>
                                <p class="text-sm text-green-600"><?php echo $stats['active_students']; ?> active</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Stats -->
                <div class="bg-white rounded-lg shadow stat-card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-headset text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_tickets']); ?></h3>
                                <p class="text-gray-600">Support Tickets</p>
                                <p class="text-sm text-red-600"><?php echo $stats['pending_tickets']; ?> pending</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Stats -->
                <div class="bg-white rounded-lg shadow stat-card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['today_activities']); ?></h3>
                                <p class="text-gray-600">Today's Activity</p>
                                <p class="text-sm text-blue-600">System events</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- System Health -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-heartbeat mr-2 text-green-600"></i>System Health
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Database</span>
                            <span class="flex items-center text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>Online
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Web Server</span>
                            <span class="flex items-center text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>Running
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Backups</span>
                            <span class="flex items-center text-blue-600">
                                <i class="fas fa-info-circle mr-1"></i><?php echo $stats['total_backups']; ?> files
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Storage</span>
                            <span class="flex items-center text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>Available
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-bolt mr-2 text-yellow-600"></i>Quick Actions
                        </h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <a href="../students/add.php" class="w-full flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200">
                            <i class="fas fa-plus mr-2"></i>Add Student
                        </a>
                        <a href="users.php?action=add" class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                            <i class="fas fa-user-plus mr-2"></i>Add User
                        </a>
                        <a href="backups.php?action=create" class="w-full flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition duration-200">
                            <i class="fas fa-database mr-2"></i>Create Backup
                        </a>
                        <a href="reports.php" class="w-full flex items-center justify-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition duration-200">
                            <i class="fas fa-chart-bar mr-2"></i>Generate Report
                        </a>
                    </div>
                </div>

                <!-- Course Distribution -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-chart-pie mr-2 text-indigo-600"></i>Course Distribution
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($courseDistribution)): ?>
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-chart-pie text-2xl mb-2"></i>
                            <p>No course data available</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($courseDistribution as $course): ?>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($course['course']); ?></span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo min(100, ($course['count'] / $stats['active_students']) * 100); ?>%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-800"><?php echo $course['count']; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Charts and Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Registration Trend -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-chart-line mr-2 text-blue-600"></i>Student Registration Trend
                        </h3>
                    </div>
                    <div class="p-6">
                        <canvas id="registrationChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-history mr-2 text-green-600"></i>Recent Activity
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentActivities)): ?>
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-history text-2xl mb-2"></i>
                            <p>No recent activity</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-4 max-h-64 overflow-y-auto">
                            <?php foreach ($recentActivities as $activity): ?>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-<?php 
                                            echo strpos($activity['action'], 'login') !== false ? 'sign-in-alt' : 
                                                (strpos($activity['action'], 'created') !== false ? 'plus' : 
                                                (strpos($activity['action'], 'updated') !== false ? 'edit' : 
                                                (strpos($activity['action'], 'deleted') !== false ? 'trash' : 'cog')));
                                        ?> text-xs text-gray-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium"><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></span>
                                        <?php echo htmlspecialchars(str_replace('_', ' ', $activity['action'])); ?>
                                        <?php if ($activity['table_name']): ?>
                                        in <?php echo htmlspecialchars($activity['table_name']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-500"><?php echo timeAgo($activity['created_at']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="activity-logs.php" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">View all activity</a>
                        </div>
                        <?php endif; ?>
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

        // Registration Chart
        const ctx = document.getElementById('registrationChart').getContext('2d');
        const registrationChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php 
                    $labels = [];
                    foreach ($monthlyData as $data) {
                        $labels[] = "'" . date('M Y', strtotime($data['month'] . '-01')) . "'";
                    }
                    echo implode(', ', $labels);
                ?>],
                datasets: [{
                    label: 'New Registrations',
                    data: [<?php 
                        $counts = [];
                        foreach ($monthlyData as $data) {
                            $counts[] = $data['count'];
                        }
                        echo implode(', ', $counts);
                    ?>],
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
