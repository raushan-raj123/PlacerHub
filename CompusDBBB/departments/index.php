<?php
require_once '../config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

$error = '';
$success = '';

// Handle department operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');
        
        if ($action === 'add') {
            $dept_name = sanitizeInput($_POST['dept_name'] ?? '');
            $dept_code = sanitizeInput($_POST['dept_code'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $head_of_department = sanitizeInput($_POST['head_of_department'] ?? '');
            
            if (empty($dept_name) || empty($dept_code)) {
                $error = 'Please fill in all required fields.';
            } else {
                try {
                    // Check if department code already exists
                    $stmt = $db->prepare("SELECT id FROM departments WHERE dept_code = ?");
                    $stmt->execute([$dept_code]);
                    if ($stmt->fetch()) {
                        $error = 'Department code already exists.';
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO departments (dept_name, dept_code, description, head_of_department) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$dept_name, $dept_code, $description, $head_of_department]);
                        $success = 'Department added successfully!';
                        
                        // Log activity
                        $stmt = $db->prepare("
                            INSERT INTO activity_logs (user_id, action, table_name, record_id, new_values, ip_address, user_agent) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $user['id'],
                            'department_created',
                            'departments',
                            $db->lastInsertId(),
                            json_encode($_POST),
                            $_SERVER['REMOTE_ADDR'] ?? '',
                            $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    }
                } catch (Exception $e) {
                    $error = 'Failed to add department: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get departments with statistics
$query = "
    SELECT d.*,
           (SELECT COUNT(*) FROM courses WHERE department = d.dept_name) as course_count,
           (SELECT COUNT(*) FROM students WHERE department = d.dept_name) as student_count
    FROM departments d 
    ORDER BY d.dept_name ASC
";
$stmt = $db->query($query);
$departments = $stmt->fetchAll();

$pageTitle = 'Department Management';
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
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        .department-card { transition: all 0.3s ease; }
        .department-card:hover { transform: translateY(-2px); }
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
                <a href="index.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-building mr-3"></i>Departments
                </a>
                <a href="../reports/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
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
                    <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus mr-2"></i>Add Department
                    </button>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="p-6">
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

            <!-- Statistics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-building text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-gray-800"><?php echo count($departments); ?></p>
                            <p class="text-gray-600">Total Departments</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-book text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo array_sum(array_column($departments, 'course_count')); ?>
                            </p>
                            <p class="text-gray-600">Total Courses</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo array_sum(array_column($departments, 'student_count')); ?>
                            </p>
                            <p class="text-gray-600">Total Students</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo count($departments) > 0 ? round(array_sum(array_column($departments, 'student_count')) / count($departments)) : 0; ?>
                            </p>
                            <p class="text-gray-600">Avg Students/Dept</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Departments Grid -->
            <?php if (empty($departments)): ?>
            <div class="text-center py-12">
                <i class="fas fa-building text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No departments found</h3>
                <p class="text-gray-500 mb-4">Get started by adding your first department.</p>
                <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Add Department
                </button>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($departments as $dept): ?>
                <div class="department-card bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($dept['dept_name']); ?></h3>
                                <p class="text-indigo-100"><?php echo htmlspecialchars($dept['dept_code']); ?></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="editDepartment(<?php echo $dept['id']; ?>)" class="text-white hover:text-indigo-200">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteDepartment(<?php echo $dept['id']; ?>)" class="text-white hover:text-red-200">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <?php if ($dept['description']): ?>
                        <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($dept['description']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($dept['head_of_department']): ?>
                        <div class="mb-4">
                            <h4 class="font-medium text-gray-900 mb-2">Department Head</h4>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-user-tie w-4 mr-2"></i>
                                <span><?php echo htmlspecialchars($dept['head_of_department']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center p-3 bg-blue-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600"><?php echo $dept['course_count']; ?></div>
                                <div class="text-sm text-blue-800">Courses</div>
                            </div>
                            <div class="text-center p-3 bg-green-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-600"><?php echo $dept['student_count']; ?></div>
                                <div class="text-sm text-green-800">Students</div>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500">
                                Created on <?php echo formatDate($dept['created_at']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Department Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Add New Department</h3>
                        <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="dept_name" class="block text-sm font-medium text-gray-700 mb-1">Department Name *</label>
                                    <input type="text" id="dept_name" name="dept_name" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                           placeholder="e.g., Computer Science">
                                </div>
                                
                                <div>
                                    <label for="dept_code" class="block text-sm font-medium text-gray-700 mb-1">Department Code *</label>
                                    <input type="text" id="dept_code" name="dept_code" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                           placeholder="e.g., CS">
                                </div>
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea id="description" name="description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Department description..."></textarea>
                            </div>
                            
                            <div>
                                <label for="head_of_department" class="block text-sm font-medium text-gray-700 mb-1">Department Head</label>
                                <input type="text" id="head_of_department" name="head_of_department"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                       placeholder="e.g., Dr. John Smith">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end space-x-4 mt-6">
                            <button type="button" onclick="closeAddModal()" 
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                                Cancel
                            </button>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
                                Add Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }

        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }

        function editDepartment(id) {
            alert('Edit functionality coming soon!');
        }

        function deleteDepartment(id) {
            if (confirm('Are you sure you want to delete this department?')) {
                alert('Delete functionality coming soon!');
            }
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-100, .bg-green-100');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
