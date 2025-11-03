<?php
require_once '../config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

// Get statistics for reports
$stats = [];

// Student statistics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_students,
        SUM(CASE WHEN status = 'graduated' THEN 1 ELSE 0 END) as graduated_students,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_students,
        SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped_students
    FROM students
");
$stats['students'] = $stmt->fetch();

// Course statistics
$stmt = $db->query("SELECT COUNT(*) as total_courses FROM courses");
$stats['courses'] = $stmt->fetch();

// Department statistics
$stmt = $db->query("SELECT COUNT(*) as total_departments FROM departments");
$stats['departments'] = $stmt->fetch();

// User statistics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as regular_users
    FROM users
");
$stats['users'] = $stmt->fetch();

// Recent activity
$stmt = $db->query("
    SELECT COUNT(*) as total_activities,
           COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_activities,
           COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_activities
    FROM activity_logs
");
$stats['activities'] = $stmt->fetch();

// Students by department
$stmt = $db->query("
    SELECT department, COUNT(*) as count 
    FROM students 
    WHERE department IS NOT NULL AND department != ''
    GROUP BY department 
    ORDER BY count DESC
");
$studentsByDept = $stmt->fetchAll();

// Students by course
$stmt = $db->query("
    SELECT course, COUNT(*) as count 
    FROM students 
    WHERE course IS NOT NULL AND course != ''
    GROUP BY course 
    ORDER BY count DESC 
    LIMIT 10
");
$studentsByCourse = $stmt->fetchAll();

// Monthly registrations (last 12 months)
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM students 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthlyRegistrations = $stmt->fetchAll();

$pageTitle = 'Reports & Analytics';
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
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        .report-card { transition: all 0.3s ease; }
        .report-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
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
                <a href="../dashboard.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="../students/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-users mr-3"></i>Students
                </a>
                <a href="../courses/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-book mr-3"></i>Courses
                </a>
                <a href="../departments/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-building mr-3"></i>Departments
                </a>
                <a href="index.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-chart-bar mr-3"></i>Reports
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
                    <button onclick="exportReport()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-download mr-2"></i>Export Report
                    </button>
                    <button onclick="printReport()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="p-6">
            <!-- Overview Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="report-card bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">Total Students</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['students']['total_students']); ?></p>
                            <p class="text-blue-100 text-sm mt-1">
                                <?php echo $stats['students']['active_students']; ?> Active
                            </p>
                        </div>
                        <div class="bg-blue-400 bg-opacity-30 rounded-full p-3">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="report-card bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm">Total Courses</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['courses']['total_courses']); ?></p>
                            <p class="text-green-100 text-sm mt-1">Available Programs</p>
                        </div>
                        <div class="bg-green-400 bg-opacity-30 rounded-full p-3">
                            <i class="fas fa-book text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="report-card bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">Departments</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['departments']['total_departments']); ?></p>
                            <p class="text-purple-100 text-sm mt-1">Academic Units</p>
                        </div>
                        <div class="bg-purple-400 bg-opacity-30 rounded-full p-3">
                            <i class="fas fa-building text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="report-card bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100 text-sm">System Users</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['users']['total_users']); ?></p>
                            <p class="text-orange-100 text-sm mt-1">
                                <?php echo $stats['users']['admin_users']; ?> Admins
                            </p>
                        </div>
                        <div class="bg-orange-400 bg-opacity-30 rounded-full p-3">
                            <i class="fas fa-user-cog text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Status Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-pie mr-2 text-indigo-600"></i>Student Status Distribution
                    </h3>
                    <div class="relative h-64">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600"><?php echo $stats['students']['active_students']; ?></p>
                            <p class="text-sm text-gray-600">Active</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600"><?php echo $stats['students']['graduated_students']; ?></p>
                            <p class="text-sm text-gray-600">Graduated</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['students']['suspended_students']; ?></p>
                            <p class="text-sm text-gray-600">Suspended</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600"><?php echo $stats['students']['dropped_students']; ?></p>
                            <p class="text-sm text-gray-600">Dropped</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar mr-2 text-indigo-600"></i>Students by Department
                    </h3>
                    <div class="relative h-64">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Registration Trends -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-chart-line mr-2 text-indigo-600"></i>Student Registration Trends (Last 12 Months)
                </h3>
                <div class="relative h-80">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <!-- Top Courses and Activity Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-trophy mr-2 text-indigo-600"></i>Top 10 Courses by Enrollment
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($studentsByCourse as $index => $course): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-indigo-600 text-white text-xs rounded-full flex items-center justify-center mr-3">
                                    <?php echo $index + 1; ?>
                                </span>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($course['course']); ?></span>
                            </div>
                            <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-sm font-medium">
                                <?php echo $course['count']; ?> students
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-activity mr-2 text-indigo-600"></i>System Activity Summary
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                            <div>
                                <p class="font-medium text-blue-800">Total Activities</p>
                                <p class="text-sm text-blue-600">All time system activities</p>
                            </div>
                            <span class="text-2xl font-bold text-blue-600">
                                <?php echo number_format($stats['activities']['total_activities']); ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                            <div>
                                <p class="font-medium text-green-800">This Week</p>
                                <p class="text-sm text-green-600">Activities in last 7 days</p>
                            </div>
                            <span class="text-2xl font-bold text-green-600">
                                <?php echo number_format($stats['activities']['week_activities']); ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                            <div>
                                <p class="font-medium text-purple-800">This Month</p>
                                <p class="text-sm text-purple-600">Activities in last 30 days</p>
                            </div>
                            <span class="text-2xl font-bold text-purple-600">
                                <?php echo number_format($stats['activities']['month_activities']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-bolt mr-2 text-indigo-600"></i>Quick Report Actions
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="generateDetailedReport('students')" 
                            class="flex items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-200">
                        <i class="fas fa-users mr-3 text-blue-600"></i>
                        <span class="font-medium text-blue-800">Detailed Student Report</span>
                    </button>
                    
                    <button onclick="generateDetailedReport('courses')" 
                            class="flex items-center justify-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition duration-200">
                        <i class="fas fa-book mr-3 text-green-600"></i>
                        <span class="font-medium text-green-800">Course Analysis Report</span>
                    </button>
                    
                    <button onclick="generateDetailedReport('activity')" 
                            class="flex items-center justify-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-200">
                        <i class="fas fa-chart-line mr-3 text-purple-600"></i>
                        <span class="font-medium text-purple-800">Activity Log Report</span>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }

        // Student Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Graduated', 'Suspended', 'Dropped'],
                datasets: [{
                    data: [
                        <?php echo $stats['students']['active_students']; ?>,
                        <?php echo $stats['students']['graduated_students']; ?>,
                        <?php echo $stats['students']['suspended_students']; ?>,
                        <?php echo $stats['students']['dropped_students']; ?>
                    ],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Department Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($studentsByDept, 'department')) . "'"; ?>],
                datasets: [{
                    label: 'Students',
                    data: [<?php echo implode(',', array_column($studentsByDept, 'count')); ?>],
                    backgroundColor: '#6366f1',
                    borderRadius: 4
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
                        beginAtZero: true
                    }
                }
            }
        });

        // Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($monthlyRegistrations, 'month')) . "'"; ?>],
                datasets: [{
                    label: 'New Registrations',
                    data: [<?php echo implode(',', array_column($monthlyRegistrations, 'count')); ?>],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
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
                        beginAtZero: true
                    }
                }
            }
        });

        function exportReport() {
            window.open('../students/export.php?format=csv&all=1', '_blank');
        }

        function printReport() {
            window.print();
        }

        function generateDetailedReport(type) {
            alert(`Generating detailed ${type} report... This feature will be available soon!`);
        }
    </script>
</body>
</html>
