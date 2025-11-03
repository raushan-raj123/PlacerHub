<?php
require_once '../config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

$error = '';
$success = '';

// Handle course operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');
        
        if ($action === 'add') {
            $course_name = sanitizeInput($_POST['course_name'] ?? '');
            $course_code = sanitizeInput($_POST['course_code'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $duration_years = intval($_POST['duration_years'] ?? 3);
            $department = sanitizeInput($_POST['department'] ?? '');
            
            if (empty($course_name) || empty($course_code) || empty($department)) {
                $error = 'Please fill in all required fields.';
            } else {
                try {
                    // Check if course code already exists
                    $stmt = $db->prepare("SELECT id FROM courses WHERE course_code = ?");
                    $stmt->execute([$course_code]);
                    if ($stmt->fetch()) {
                        $error = 'Course code already exists.';
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO courses (course_name, course_code, description, duration_years, department) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$course_name, $course_code, $description, $duration_years, $department]);
                        $success = 'Course added successfully!';
                        
                        // Log activity
                        $stmt = $db->prepare("
                            INSERT INTO activity_logs (user_id, action, table_name, record_id, new_values, ip_address, user_agent) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $user['id'],
                            'course_created',
                            'courses',
                            $db->lastInsertId(),
                            json_encode($_POST),
                            $_SERVER['REMOTE_ADDR'] ?? '',
                            $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    }
                } catch (Exception $e) {
                    $error = 'Failed to add course: ' . $e->getMessage();
                }
            }
        }
    }
}

// Handle search and filters
$search = sanitizeInput($_GET['search'] ?? '');
$departmentFilter = sanitizeInput($_GET['department'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(10, intval($_GET['limit'] ?? DEFAULT_PAGE_SIZE)));
$offset = ($page - 1) * $limit;

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(course_name LIKE ? OR course_code LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($departmentFilter)) {
    $whereConditions[] = "department = ?";
    $params[] = $departmentFilter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM courses $whereClause";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get courses
$query = "
    SELECT c.*,
           (SELECT COUNT(*) FROM students WHERE course = c.course_name) as student_count
    FROM courses c 
    $whereClause 
    ORDER BY c.course_name ASC 
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$stmt = $db->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Get departments for filter
$deptStmt = $db->query("SELECT DISTINCT department FROM courses ORDER BY department");
$departments = $deptStmt->fetchAll();

$pageTitle = 'Course Management';
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
                <a href="index.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-book mr-3"></i>Courses
                </a>
                <a href="../departments/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
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
                        <i class="fas fa-plus mr-2"></i>Add Course
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

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" id="search" name="search" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                               placeholder="Search courses..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select id="department" name="department" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                    <?php echo $departmentFilter === $dept['department'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg w-full">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Courses Grid -->
            <?php if (empty($courses)): ?>
            <div class="text-center py-12">
                <i class="fas fa-book text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No courses found</h3>
                <p class="text-gray-500 mb-4">Get started by adding your first course.</p>
                <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Add Course
                </button>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($courses as $course): ?>
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition duration-200">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                <p class="text-sm text-indigo-600 font-medium"><?php echo htmlspecialchars($course['course_code']); ?></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="editCourse(<?php echo $course['id']; ?>)" class="text-gray-400 hover:text-indigo-600">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteCourse(<?php echo $course['id']; ?>)" class="text-gray-400 hover:text-red-600">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($course['description'] ?: 'No description available'); ?></p>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center text-gray-500">
                                <i class="fas fa-building w-4 mr-2"></i>
                                <span><?php echo htmlspecialchars($course['department']); ?></span>
                            </div>
                            <div class="flex items-center text-gray-500">
                                <i class="fas fa-clock w-4 mr-2"></i>
                                <span><?php echo $course['duration_years']; ?> Years</span>
                            </div>
                            <div class="flex items-center text-gray-500">
                                <i class="fas fa-graduation-cap w-4 mr-2"></i>
                                <span><?php echo ucfirst($course['status']); ?></span>
                            </div>
                            <div class="flex items-center text-gray-500">
                                <i class="fas fa-users w-4 mr-2"></i>
                                <span><?php echo $course['student_count']; ?> Students</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500">
                                Created on <?php echo formatDate($course['created_at']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-6">
                <?php echo generatePagination($page, $totalPages, 'index.php', $_GET); ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Course Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Add New Course</h3>
                        <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="space-y-4">
                            <div>
                                <label for="course_name" class="block text-sm font-medium text-gray-700 mb-1">Course Name *</label>
                                <input type="text" id="course_name" name="course_name" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                       placeholder="e.g., Bachelor of Computer Applications">
                            </div>
                            
                            <div>
                                <label for="course_code" class="block text-sm font-medium text-gray-700 mb-1">Course Code *</label>
                                <input type="text" id="course_code" name="course_code" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                       placeholder="e.g., BCA">
                            </div>
                            
                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                                <select id="department" name="department" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select Department</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Business">Business</option>
                                    <option value="Arts">Arts</option>
                                    <option value="Science">Science</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="duration_years" class="block text-sm font-medium text-gray-700 mb-1">Duration (Years) *</label>
                                <select id="duration_years" name="duration_years" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select Duration</option>
                                    <option value="1">1 Year</option>
                                    <option value="2">2 Years</option>
                                    <option value="3" selected>3 Years</option>
                                    <option value="4">4 Years</option>
                                    <option value="5">5 Years</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea id="description" name="description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Course description..."></textarea>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end space-x-4 mt-6">
                            <button type="button" onclick="closeAddModal()" 
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                                Cancel
                            </button>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
                                Add Course
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

        function editCourse(id) {
            // Implement edit functionality
            alert('Edit functionality coming soon!');
        }

        function deleteCourse(id) {
            if (confirm('Are you sure you want to delete this course?')) {
                // Implement delete functionality
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
