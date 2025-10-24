<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();

// Get dashboard statistics
$stats = [];

// Total students
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$stats['total_students'] = $stmt->fetch()['total'];

// Approved students
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'approved'");
$stats['approved_students'] = $stmt->fetch()['total'];

// Pending students
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'pending'");
$stats['pending_students'] = $stmt->fetch()['total'];

// Total companies
$stmt = $db->query("SELECT COUNT(*) as total FROM companies");
$stats['total_companies'] = $stmt->fetch()['total'];

// Active drives
$stmt = $db->query("SELECT COUNT(*) as total FROM placement_drives WHERE status = 'active'");
$stats['active_drives'] = $stmt->fetch()['total'];

// Total applications
$stmt = $db->query("SELECT COUNT(*) as total FROM applications");
$stats['total_applications'] = $stmt->fetch()['total'];

// Placed students
$stmt = $db->query("SELECT COUNT(DISTINCT user_id) as total FROM applications WHERE status = 'selected'");
$stats['placed_students'] = $stmt->fetch()['total'];

// Recent activities
$stmt = $db->prepare("
    SELECT al.*, u.name as user_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

// Recent applications
$stmt = $db->prepare("
    SELECT a.*, u.name as student_name, pd.title as job_title, c.name as company_name
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN placement_drives pd ON a.drive_id = pd.id
    JOIN companies c ON pd.company_id = c.id
    ORDER BY a.applied_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_applications = $stmt->fetchAll();

// Upcoming drive deadlines
$stmt = $db->prepare("
    SELECT pd.*, c.name as company_name
    FROM placement_drives pd
    JOIN companies c ON pd.company_id = c.id
    WHERE pd.status = 'active' AND pd.deadline >= CURDATE()
    ORDER BY pd.deadline ASC
    LIMIT 5
");
$stmt->execute();
$upcoming_deadlines = $stmt->fetchAll();

// Monthly statistics for chart
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(applied_at, '%Y-%m') as month,
        COUNT(*) as applications
    FROM applications 
    WHERE applied_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(applied_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_stats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="relative overflow-hidden shadow-2xl">
        <!-- Beautiful gradient background -->
         <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
            <!-- Glass morphism overlay -->
            <div class="backdrop-blur-sm bg-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <!-- Logo Section -->
                        <div class="flex items-center">
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-2 mr-3">
                                <i class="fas fa-graduation-cap text-2xl text-white"></i>
                            </div>
                            <h1 class="text-xl font-bold text-white drop-shadow-lg"><?php echo SITE_NAME; ?> <span class="text-yellow-300"></span></h1>
                        </div>
                        
                        <!-- Desktop Navigation Links -->
                        <div class="hidden md:flex items-center space-x-1">
                            <a href="dashboard.php" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg font-medium transition duration-300 hover:bg-white/30 border border-white/20">
                                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                            </a>
                            <a href="students.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
                                <i class="fas fa-users mr-2"></i>Students
                            </a>
                            <a href="companies.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
                                <i class="fas fa-building mr-2"></i>Companies
                            </a>
                            <a href="drives.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
                                <i class="fas fa-briefcase mr-2"></i>Drives
                            </a>
                            <a href="applications.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
                                <i class="fas fa-file-alt mr-2"></i>Applications
                            </a>
                            <a href="reports.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
                                <i class="fas fa-chart-bar mr-2"></i>Reports
                            </a>
                        </div>
                        
                       
<!-- User Profile Section -->
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <button onclick="toggleUserMenu()" class="flex items-center bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 px-4 py-2 rounded-lg transition duration-300 border border-white/20">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=ffffff&color=6366f1" 
                                         alt="Profile" class="w-8 h-8 rounded-full mr-2 border-2 border-white/30">
                                    <span class="hidden md:block font-medium"><?php echo $_SESSION['name']; ?></span>
                                    <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                                
                                <!-- Dropdown Menu with gradient -->
                                <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-2xl z-[9999] border border-gray-200 overflow-hidden">
                                    <div class="py-2">
                                        <a href="profile.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 transition duration-200">
                                            <i class="fas fa-user mr-3 text-indigo-500"></i>Profile
                                        </a>
                                        <a href="settings.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 transition duration-200">
                                            <i class="fas fa-cog mr-3 text-purple-500"></i>Settings
                                        </a>
                                        <div class="border-t border-gray-200 my-1"></div>
                                        <a href="../auth/logout.php" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 transition duration-200">
                                            <i class="fas fa-sign-out-alt mr-3"></i>Logout
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mobile menu button -->
                        <div class="md:hidden">
                            <button onclick="toggleMobileMenu()" class="bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 p-2 rounded-lg transition duration-300">
                                <i class="fas fa-bars text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile menu -->
                <div id="mobile-menu" class="md:hidden hidden border-t border-white/20">
                    <div class="bg-white/10 backdrop-blur-sm px-2 pt-2 pb-3 space-y-1">
                        <a href="dashboard.php" class="flex items-center px-3 py-2 text-white bg-white/20 rounded-lg font-medium">
                            <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                        </a>
                        <a href="students.php" class="flex items-center px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                            <i class="fas fa-users mr-3"></i>Students
                        </a>
                        <a href="companies.php" class="flex items-center px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                            <i class="fas fa-building mr-3"></i>Companies
                        </a>
                        <a href="drives.php" class="flex items-center px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                            <i class="fas fa-briefcase mr-3"></i>Drives
                        </a>
                        <a href="applications.php" class="flex items-center px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                            <i class="fas fa-file-alt mr-3"></i>Applications
                        </a>
                        <a href="reports.php" class="flex items-center px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                            <i class="fas fa-chart-bar mr-3"></i>Reports
                        </a>
                        <div class="border-t border-white/20 my-2"></div>
                        <a href="../auth/logout.php" class="flex items-center px-3 py-2 text-red-300 hover:text-red-200 hover:bg-red-500/20 rounded-lg transition duration-200">
                            <i class="fas fa-sign-out-alt mr-3"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Decorative elements -->
            <div class="absolute top-0 left-0 w-32 h-32 bg-white/5 rounded-full -translate-x-16 -translate-y-16"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-white/5 rounded-full translate-x-12 -translate-y-12"></div>
            <div class="absolute bottom-0 left-1/3 w-16 h-16 bg-white/5 rounded-full translate-y-8"></div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome back, <?php echo $_SESSION['name']; ?>!</h1>
            <p class="text-gray-600">Here's an overview of your placement management system</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Students Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-orange-400 via-red-400 to-pink-500 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-users text-2xl mr-3"></i>
                                <span class="text-lg font-semibold opacity-90">Total Students</span>
                            </div>
                            <p class="text-3xl font-bold mb-1"><?php echo $stats['total_students']; ?></p>
                            <p class="text-sm opacity-80">
                                <i class="fas fa-check-circle mr-1"></i><?php echo $stats['approved_students']; ?> approved
                            </p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                            <i class="fas fa-graduation-cap text-3xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Companies Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-building text-2xl mr-3"></i>
                                <span class="text-lg font-semibold opacity-90">Companies</span>
                            </div>
                            <p class="text-3xl font-bold mb-1"><?php echo $stats['total_companies']; ?></p>
                            <p class="text-sm opacity-80">
                                <i class="fas fa-handshake mr-1"></i>Partner Companies
                            </p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                            <i class="fas fa-briefcase text-3xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Active Drives Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-yellow-400 via-orange-400 to-red-500 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-rocket text-2xl mr-3"></i>
                                <span class="text-lg font-semibold opacity-90">Active Drives</span>
                            </div>
                            <p class="text-3xl font-bold mb-1"><?php echo $stats['active_drives']; ?></p>
                            <p class="text-sm opacity-80">
                                <i class="fas fa-calendar-alt mr-1"></i>Ongoing Recruitment
                            </p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                            <i class="fas fa-bullhorn text-3xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Placed Students Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-blue-400 via-purple-500 to-indigo-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-trophy text-2xl mr-3"></i>
                                <span class="text-lg font-semibold opacity-90">Placed Students</span>
                            </div>
                            <p class="text-3xl font-bold mb-1"><?php echo $stats['placed_students']; ?></p>
                            <?php if ($stats['approved_students'] > 0): ?>
                                <p class="text-sm opacity-80">
                                    <i class="fas fa-percentage mr-1"></i><?php echo round(($stats['placed_students'] / $stats['approved_students']) * 100, 1); ?>% success rate
                                </p>
                            <?php else: ?>
                                <p class="text-sm opacity-80">
                                    <i class="fas fa-star mr-1"></i>Success Stories
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                            <i class="fas fa-medal text-3xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-white/10 rounded-full"></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Pending Approvals Card -->
                <a href="students.php?status=pending" class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300 group">
                    <div class="bg-gradient-to-br from-amber-400 via-yellow-500 to-orange-500 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-user-clock text-xl mr-2"></i>
                                    <span class="text-sm font-semibold opacity-90">Pending</span>
                                </div>
                                <p class="text-2xl font-bold mb-1"><?php echo $stats['pending_students']; ?></p>
                                <p class="text-xs opacity-80">Students awaiting approval</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                                <i class="fas fa-hourglass-half text-2xl"></i>
                            </div>
                        </div>
                        <!-- Decorative elements -->
                        <div class="absolute -top-3 -right-3 w-16 h-16 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-white/10 rounded-full"></div>
                        <!-- Hover effect -->
                        <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    </div>
                </a>
                
                <!-- Create Drive Card -->
                <a href="drives.php?action=create" class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300 group">
                    <div class="bg-gradient-to-br from-emerald-400 via-green-500 to-cyan-500 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-plus text-xl mr-2"></i>
                                    <span class="text-sm font-semibold opacity-90">Create</span>
                                </div>
                                <p class="text-lg font-bold mb-1">New Drive</p>
                                <p class="text-xs opacity-80">Start placement drive</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                                <i class="fas fa-rocket text-2xl"></i>
                            </div>
                        </div>
                        <!-- Decorative elements -->
                        <div class="absolute -top-3 -right-3 w-16 h-16 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-white/10 rounded-full"></div>
                        <!-- Hover effect -->
                        <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    </div>
                </a>
                
                <!-- Add Company Card -->
                <a href="companies.php?action=create" class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300 group">
                    <div class="bg-gradient-to-br from-sky-400 via-blue-500 to-indigo-600 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-building text-xl mr-2"></i>
                                    <span class="text-sm font-semibold opacity-90">Add</span>
                                </div>
                                <p class="text-lg font-bold mb-1">Company</p>
                                <p class="text-xs opacity-80">Register new partner</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                                <i class="fas fa-handshake text-2xl"></i>
                            </div>
                        </div>
                        <!-- Decorative elements -->
                        <div class="absolute -top-3 -right-3 w-16 h-16 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-white/10 rounded-full"></div>
                        <!-- Hover effect -->
                        <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    </div>
                </a>
                
                <!-- View Reports Card -->
                <a href="reports.php" class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300 group">
                    <div class="bg-gradient-to-br from-violet-400 via-purple-500 to-pink-600 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-chart-bar text-xl mr-2"></i>
                                    <span class="text-sm font-semibold opacity-90">View</span>
                                </div>
                                <p class="text-lg font-bold mb-1">Reports</p>
                                <p class="text-xs opacity-80">Analytics & insights</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                                <i class="fas fa-chart-line text-2xl"></i>
                            </div>
                        </div>
                        <!-- Decorative elements -->
                        <div class="absolute -top-3 -right-3 w-16 h-16 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-white/10 rounded-full"></div>
                        <!-- Hover effect -->
                        <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    </div>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Applications Chart -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Applications Trend</h2>
                </div>
                <div class="p-6">
                    <canvas id="applicationsChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Upcoming Deadlines -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">Upcoming Deadlines</h2>
                        <a href="drives.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View All</a>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($upcoming_deadlines)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No upcoming deadlines</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_deadlines as $deadline): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo $deadline['title']; ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo $deadline['company_name']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900"><?php echo formatDate($deadline['deadline']); ?></p>
                                        <?php 
                                        $days_left = ceil((strtotime($deadline['deadline']) - time()) / (60 * 60 * 24));
                                        $color_class = $days_left <= 3 ? 'text-red-600' : ($days_left <= 7 ? 'text-yellow-600' : 'text-green-600');
                                        ?>
                                        <p class="text-xs <?php echo $color_class; ?>"><?php echo $days_left; ?> days left</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
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
                            <p class="text-gray-500">No recent applications</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_applications as $app): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo $app['student_name']; ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo $app['job_title']; ?> - <?php echo $app['company_name']; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo timeAgo($app['applied_at']); ?></p>
                                    </div>
                                    <div>
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
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Activities -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Activities</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No recent activities</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <p class="text-sm text-gray-900">
                                            <span class="font-medium"><?php echo $activity['user_name'] ?: 'System'; ?></span>
                                            <?php echo $activity['action']; ?>
                                            <?php if ($activity['table_name']): ?>
                                                in <?php echo $activity['table_name']; ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-gray-500"><?php echo timeAgo($activity['created_at']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // State management for dropdowns
    let userMenuOpen = false;
    let mobileMenuOpen = false;

    function toggleUserMenu() {
        const menu = document.getElementById('user-menu');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (!menu) {
            console.error('user-menu element not found!');
            return;
        }
        
        // Close mobile menu if open
        if (mobileMenuOpen && mobileMenu) {
            mobileMenu.classList.add('hidden');
            mobileMenuOpen = false;
        }
        
        // Toggle user menu
        if (userMenuOpen) {
            menu.classList.add('hidden');
            userMenuOpen = false;
        } else {
            menu.classList.remove('hidden');
            userMenuOpen = true;
        }
    }

    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        const userMenu = document.getElementById('user-menu');
        
        // Close user menu if open
        if (userMenuOpen) {
            userMenu.classList.add('hidden');
            userMenuOpen = false;
        }
        
        // Toggle mobile menu
        if (mobileMenuOpen) {
            menu.classList.add('hidden');
            mobileMenuOpen = false;
        } else {
            menu.classList.remove('hidden');
            mobileMenuOpen = true;
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('user-menu');
        const mobileMenu = document.getElementById('mobile-menu');
        
        // Check if click is on user menu button or inside user menu
        const isUserMenuClick = event.target.closest('button[onclick="toggleUserMenu()"]') || 
                               userMenu.contains(event.target);
        
        // Check if click is on mobile menu button or inside mobile menu
        const isMobileMenuClick = event.target.closest('button[onclick="toggleMobileMenu()"]') || 
                                 mobileMenu.contains(event.target);
        
        // Close user menu if clicking outside
        if (userMenuOpen && !isUserMenuClick) {
            userMenu.classList.add('hidden');
            userMenuOpen = false;
        }
        
        // Close mobile menu if clicking outside
        if (mobileMenuOpen && !isMobileMenuClick) {
            mobileMenu.classList.add('hidden');
            mobileMenuOpen = false;
        }
    });

    // Close dropdowns when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const userMenu = document.getElementById('user-menu');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (userMenuOpen) {
                userMenu.classList.add('hidden');
                userMenuOpen = false;
            }
            
            if (mobileMenuOpen) {
                mobileMenu.classList.add('hidden');
                mobileMenuOpen = false;
            }
        }
    });

    // Applications Chart
    const ctx = document.getElementById('applicationsChart').getContext('2d');
    const applicationsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                foreach ($monthly_stats as $stat) {
                    echo "'" . date('M Y', strtotime($stat['month'] . '-01')) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Applications',
                data: [
                    <?php 
                    foreach ($monthly_stats as $stat) {
                        echo $stat['applications'] . ",";
                    }
                    ?>
                ],
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
