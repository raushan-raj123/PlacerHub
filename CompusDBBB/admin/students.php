<?php
require_once '../config/config.php';
requireLogin();
requireAdmin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = sanitizeInput($_POST['bulk_action']);
        $selectedIds = $_POST['selected_ids'] ?? [];
        
        if (!empty($selectedIds) && is_array($selectedIds)) {
            $selectedIds = array_map('intval', $selectedIds);
            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
            
            try {
                switch ($action) {
                    case 'activate':
                        $stmt = $db->prepare("UPDATE students SET status = 'active' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $success = count($selectedIds) . ' students activated successfully.';
                        break;
                        
                    case 'suspend':
                        $stmt = $db->prepare("UPDATE students SET status = 'suspended' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $success = count($selectedIds) . ' students suspended successfully.';
                        break;
                        
                    case 'graduate':
                        $stmt = $db->prepare("UPDATE students SET status = 'graduated' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $success = count($selectedIds) . ' students marked as graduated.';
                        break;
                        
                    case 'delete':
                        $stmt = $db->prepare("DELETE FROM students WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $success = count($selectedIds) . ' students deleted successfully.';
                        break;
                }
                
                // Log bulk action
                $stmt = $db->prepare("
                    INSERT INTO activity_logs (user_id, action, table_name, new_values, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    "bulk_student_$action",
                    'students',
                    json_encode(['ids' => $selectedIds]),
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
            } catch (Exception $e) {
                $error = 'Bulk action failed: ' . $e->getMessage();
            }
        }
    }
}

// Handle search and filters
$search = sanitizeInput($_GET['search'] ?? '');
$courseFilter = sanitizeInput($_GET['course'] ?? '');
$departmentFilter = sanitizeInput($_GET['department'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$yearFilter = sanitizeInput($_GET['year'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(100, max(10, intval($_GET['limit'] ?? DEFAULT_PAGE_SIZE)));
$offset = ($page - 1) * $limit;

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($courseFilter)) {
    $whereConditions[] = "course = ?";
    $params[] = $courseFilter;
}

if (!empty($departmentFilter)) {
    $whereConditions[] = "department = ?";
    $params[] = $departmentFilter;
}

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if (!empty($yearFilter)) {
    $whereConditions[] = "year_of_study = ?";
    $params[] = $yearFilter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM students $whereClause";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get students with creator info
$query = "
    SELECT s.*, u.full_name as created_by_name 
    FROM students s 
    LEFT JOIN users u ON s.created_by = u.id 
    $whereClause 
    ORDER BY s.created_at DESC 
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get filter options
$coursesStmt = $db->query("SELECT DISTINCT course FROM students ORDER BY course");
$courses = $coursesStmt->fetchAll();

$departmentsStmt = $db->query("SELECT DISTINCT department FROM students ORDER BY department");
$departments = $departmentsStmt->fetchAll();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
        SUM(CASE WHEN status = 'graduated' THEN 1 ELSE 0 END) as graduated,
        SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped
    FROM students
";
$stmt = $db->query($statsQuery);
$stats = $stmt->fetch();

$pageTitle = 'Student Management (Admin)';
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
        .admin-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
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
                <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="users.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-users-cog mr-3"></i>User Management
                </a>
                <a href="students.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-users mr-3"></i>Student Management
                </a>
                <a href="../students/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-eye mr-3"></i>Student View
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
                        <i class="fas fa-shield-alt mr-1"></i>Admin View
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="../students/add.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus mr-2"></i>Add Student
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="p-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total']); ?></p>
                            <p class="text-gray-600 text-sm">Total Students</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['active']); ?></p>
                            <p class="text-gray-600 text-sm">Active</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['suspended']); ?></p>
                            <p class="text-gray-600 text-sm">Suspended</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['graduated']); ?></p>
                            <p class="text-gray-600 text-sm">Graduated</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['dropped']); ?></p>
                            <p class="text-gray-600 text-sm">Dropped</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-7 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" id="search" name="search" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                               placeholder="Search students..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div>
                        <label for="course" class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                        <select id="course" name="course" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['course']); ?>" 
                                    <?php echo $courseFilter === $course['course'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select id="department" name="department" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                            <option value="<?php echo htmlspecialchars($department['department']); ?>" 
                                    <?php echo $departmentFilter === $department['department'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['department']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="graduated" <?php echo $statusFilter === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                            <option value="dropped" <?php echo $statusFilter === 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                        <select id="year" name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Years</option>
                            <option value="1" <?php echo $yearFilter === '1' ? 'selected' : ''; ?>>Year 1</option>
                            <option value="2" <?php echo $yearFilter === '2' ? 'selected' : ''; ?>>Year 2</option>
                            <option value="3" <?php echo $yearFilter === '3' ? 'selected' : ''; ?>>Year 3</option>
                            <option value="4" <?php echo $yearFilter === '4' ? 'selected' : ''; ?>>Year 4</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg w-full">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bulk Actions -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Select All</span>
                            </label>
                            <span id="selectedCount" class="text-sm text-gray-500">0 selected</span>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <select name="bulk_action" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Bulk Actions</option>
                                <option value="activate">Activate</option>
                                <option value="suspend">Suspend</option>
                                <option value="graduate">Mark as Graduated</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm" onclick="return confirmBulkAction()">
                                Apply
                            </button>
                            <button type="button" onclick="exportSelected()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                                <i class="fas fa-download mr-1"></i>Export Selected
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <?php if (empty($students)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-users text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No students found</h3>
                    <p class="text-gray-500 mb-4">No students match your current filters.</p>
                    <a href="../students/add.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg">
                        <i class="fas fa-plus mr-2"></i>Add Student
                    </a>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" id="headerCheckbox">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $student['id']; ?>" 
                                           class="student-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-indigo-600">
                                                    <?php echo strtoupper(substr($student['name'], 0, 2)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['student_id']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($student['course']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['department']); ?></div>
                                    <div class="text-sm text-gray-500">Year <?php echo $student['year_of_study']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($student['status']) {
                                            case 'active': echo 'bg-green-100 text-green-800'; break;
                                            case 'graduated': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'dropped': echo 'bg-red-100 text-red-800'; break;
                                            case 'suspended': echo 'bg-yellow-100 text-yellow-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($student['created_by_name'] ?? 'System'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatDate($student['created_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="../students/view.php?id=<?php echo $student['id']; ?>" 
                                           class="text-indigo-600 hover:text-indigo-900" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../students/edit.php?id=<?php echo $student['id']; ?>" 
                                           class="text-green-600 hover:text-green-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteStudent(<?php echo $student['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="bg-white px-4 py-3 border-t border-gray-200">
                    <?php echo generatePagination($page, $totalPages, 'students.php', $_GET); ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }

        // Checkbox handling
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });

        document.querySelectorAll('.student-checkbox').forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });

        function updateSelectedCount() {
            const selected = document.querySelectorAll('.student-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = selected + ' selected';
        }

        function confirmBulkAction() {
            const selected = document.querySelectorAll('.student-checkbox:checked').length;
            const action = document.querySelector('select[name="bulk_action"]').value;
            
            if (selected === 0) {
                alert('Please select at least one student.');
                return false;
            }
            
            if (!action) {
                alert('Please select an action.');
                return false;
            }
            
            return confirm(`Are you sure you want to ${action} ${selected} student(s)?`);
        }

        function exportSelected() {
            const selected = document.querySelectorAll('.student-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one student to export.');
                return;
            }
            
            const ids = Array.from(selected).map(cb => cb.value);
            const url = '../students/export.php?ids=' + ids.join(',');
            window.open(url, '_blank');
        }

        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                fetch('../students/delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, csrf_token: '<?php echo generateCSRFToken(); ?>' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
