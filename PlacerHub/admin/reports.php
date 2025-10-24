<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();

// Get comprehensive statistics
$stats = [];

// Basic counts
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$stats['total_students'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'approved'");
$stats['approved_students'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM companies");
$stats['total_companies'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM placement_drives");
$stats['total_drives'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM applications");
$stats['total_applications'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT user_id) as total FROM applications WHERE status = 'selected'");
$stats['placed_students'] = $stmt->fetch()['total'];

// Placement rate
$stats['placement_rate'] = $stats['approved_students'] > 0 ? round(($stats['placed_students'] / $stats['approved_students']) * 100, 1) : 0;

// Course-wise placement statistics
$stmt = $db->query("
    SELECT u.course, 
           COUNT(*) as total_students,
           COUNT(CASE WHEN a.status = 'selected' THEN 1 END) as placed_students
    FROM users u
    LEFT JOIN applications a ON u.id = a.user_id AND a.status = 'selected'
    WHERE u.role = 'student' AND u.status = 'approved'
    GROUP BY u.course
    ORDER BY u.course
");
$course_stats = $stmt->fetchAll();

// Branch-wise placement statistics
$stmt = $db->query("
    SELECT u.branch, 
           COUNT(*) as total_students,
           COUNT(CASE WHEN a.status = 'selected' THEN 1 END) as placed_students
    FROM users u
    LEFT JOIN applications a ON u.id = a.user_id AND a.status = 'selected'
    WHERE u.role = 'student' AND u.status = 'approved'
    GROUP BY u.branch
    ORDER BY u.branch
");
$branch_stats = $stmt->fetchAll();

// Company-wise statistics
$stmt = $db->query("
    SELECT c.name, 
           COUNT(DISTINCT pd.id) as total_drives,
           COUNT(DISTINCT a.id) as total_applications,
           COUNT(CASE WHEN a.status = 'selected' THEN 1 END) as selected_students
    FROM companies c
    LEFT JOIN placement_drives pd ON c.id = pd.company_id
    LEFT JOIN applications a ON pd.id = a.drive_id
    GROUP BY c.id, c.name
    HAVING total_drives > 0
    ORDER BY selected_students DESC, total_applications DESC
    LIMIT 10
");
$company_stats = $stmt->fetchAll();

// Monthly application trends
$stmt = $db->query("
    SELECT DATE_FORMAT(applied_at, '%Y-%m') as month,
           COUNT(*) as applications,
           COUNT(CASE WHEN status = 'selected' THEN 1 END) as selections
    FROM applications 
    WHERE applied_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(applied_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_trends = $stmt->fetchAll();

// Top performing students
$stmt = $db->query("
    SELECT u.name, u.email, u.course, u.branch, u.cgpa,
           COUNT(a.id) as total_applications,
           COUNT(CASE WHEN a.status = 'selected' THEN 1 END) as selections
    FROM users u
    JOIN applications a ON u.id = a.user_id
    WHERE u.role = 'student'
    GROUP BY u.id
    HAVING selections > 0
    ORDER BY selections DESC, total_applications DESC
    LIMIT 10
");
$top_students = $stmt->fetchAll();

// Recent placements
$stmt = $db->query("
    SELECT u.name as student_name, pd.title, c.name as company_name, 
           pd.package_min, pd.package_max, a.updated_at
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN placement_drives pd ON a.drive_id = pd.id
    JOIN companies c ON pd.company_id = c.id
    WHERE a.status = 'selected'
    ORDER BY a.updated_at DESC
    LIMIT 10
");
$recent_placements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo SITE_NAME; ?></title>
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
                            <h1 class="text-xl font-bold text-white drop-shadow-lg"><?php echo SITE_NAME; ?> <span class="text-yellow-300">Admin</span></h1>
                        </div>
                        
                        <!-- Desktop Navigation Links -->
                        <div class="hidden md:flex items-center space-x-1">
                            <a href="dashboard.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
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
                            <a href="reports.php" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg font-medium transition duration-300 hover:bg-white/30 border border-white/20">
                                <i class="fas fa-chart-bar mr-2"></i>Reports
                            </a>
                        </div>
                        
                        <!-- Action Buttons & User Profile -->
                        <div class="flex items-center space-x-4">
                            <button onclick="exportReport()" class="bg-green-500/80 backdrop-blur-sm hover:bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300 border border-green-400/30">
                                <i class="fas fa-download mr-2"></i>Export
                            </button>
                            
                            <div class="relative">
                                <button onclick="toggleUserMenu()" class="flex items-center bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 px-4 py-2 rounded-lg transition duration-300 border border-white/20">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=ffffff&color=6366f1" 
                                         alt="Profile" class="w-8 h-8 rounded-full mr-2 border-2 border-white/30">
                                    <span class="hidden md:block font-medium"><?php echo $_SESSION['name']; ?></span>
                                    <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                                
                                <!-- Dropdown Menu with gradient -->
                                <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-2xl z-50 border border-gray-200 overflow-hidden">
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
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Reports & Analytics</h1>
            <p class="text-gray-600">Comprehensive placement statistics and insights</p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Students Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-blue-400 via-indigo-500 to-purple-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-users text-2xl mr-3"></i>
                                <span class="text-lg font-semibold opacity-90">Total Students</span>
                            </div>
                            <p class="text-3xl font-bold mb-1"><?php echo $stats['total_students']; ?></p>
                            <p class="text-sm opacity-80">
                                <i class="fas fa-graduation-cap mr-1"></i>Registered
                            </p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                            <i class="fas fa-user-graduate text-3xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Placed Students Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user-check text-2xl mr-3"></i>
                                <span class="text-lg font-semibold opacity-90">Placed Students</span>
                            </div>
                            <p class="text-3xl font-bold mb-1"><?php echo $stats['placed_students']; ?></p>
                            <p class="text-sm opacity-80">
                                <i class="fas fa-trophy mr-1"></i>Success Stories
                            </p>
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
            
            <!-- Placement Rate Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-purple-400 via-violet-500 to-indigo-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-percentage text-2xl mr-3"></i>
                                <span class="text-lg font-semibold opacity-90">Placement Rate</span>
                            </div>
                            <p class="text-3xl font-bold mb-1"><?php echo $stats['placement_rate']; ?>%</p>
                            <p class="text-sm opacity-80">
                                <i class="fas fa-chart-line mr-1"></i>Success Rate
                            </p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                            <i class="fas fa-chart-pie text-3xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Partner Companies Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-orange-400 via-amber-500 to-yellow-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-building text-2xl mr-3"></i>
                                <span class="text-lg font-semibold opacity-90">Partner Companies</span>
                            </div>
                            <p class="text-3xl font-bold mb-1"><?php echo $stats['total_companies']; ?></p>
                            <p class="text-sm opacity-80">
                                <i class="fas fa-handshake mr-1"></i>Partnerships
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
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Course-wise Placement Chart -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Course-wise Placements</h2>
                </div>
                <div class="p-6">
                    <canvas id="courseChart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Monthly Trends Chart -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Monthly Application Trends</h2>
                </div>
                <div class="p-6">
                    <canvas id="trendsChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Statistics Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Branch-wise Statistics -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Branch-wise Statistics</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Placed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($branch_stats as $branch): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $branch['branch'] ?: 'Not Specified'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $branch['total_students']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $branch['placed_students']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php 
                                        $rate = $branch['total_students'] > 0 ? round(($branch['placed_students'] / $branch['total_students']) * 100, 1) : 0;
                                        echo $rate . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Companies -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Top Recruiting Companies</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Drives</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applications</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hired</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($company_stats as $company): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $company['name']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $company['total_drives']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $company['total_applications']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $company['selected_students']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Placements -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Recent Placements</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recent_placements as $placement): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $placement['student_name']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $placement['title']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $placement['company_name']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($placement['package_min'] && $placement['package_max']): ?>
                                        ₹<?php echo $placement['package_min']; ?>-<?php echo $placement['package_max']; ?>L
                                    <?php else: ?>
                                        Not disclosed
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($placement['updated_at']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function exportReport() {
            // In a real implementation, this would generate and download a report
            alert('Export functionality would generate PDF/Excel reports with all statistics');
        }

        // Course-wise Placement Chart
        const courseCtx = document.getElementById('courseChart').getContext('2d');
        const courseChart = new Chart(courseCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($course_stats as $course): ?>
                        '<?php echo $course['course'] ?: 'Not Specified'; ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Total Students',
                    data: [
                        <?php foreach ($course_stats as $course): ?>
                            <?php echo $course['total_students']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(99, 102, 241, 0.5)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 1
                }, {
                    label: 'Placed Students',
                    data: [
                        <?php foreach ($course_stats as $course): ?>
                            <?php echo $course['placed_students']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(34, 197, 94, 0.5)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Monthly Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        const trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach ($monthly_trends as $trend): ?>
                        '<?php echo date('M Y', strtotime($trend['month'] . '-01')); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Applications',
                    data: [
                        <?php foreach ($monthly_trends as $trend): ?>
                            <?php echo $trend['applications']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.1,
                    fill: true
                }, {
                    label: 'Selections',
                    data: [
                        <?php foreach ($monthly_trends as $trend): ?>
                            <?php echo $trend['selections']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1,
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
                            stepSize: 1
                        }
                    }
                }
            }
        });

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
