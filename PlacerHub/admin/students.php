<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

// Handle student actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $student_id = intval($_POST['student_id']);
        $status = sanitize($_POST['status']);
        
        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            try {
                $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'student'");
                $stmt->execute([$status, $student_id]);
                
                // Create notification for student
                $message = $status === 'approved' ? 
                    'Your account has been approved. You can now access all features.' :
                    'Your account status has been updated to ' . $status . '.';
                
                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Account Status Update', ?, ?)");
                $stmt->execute([$student_id, $message, $status === 'approved' ? 'success' : 'info']);
                
                // Log activity
                $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id) VALUES (?, 'updated student status to $status', 'users', ?)");
                $stmt->execute([$_SESSION['user_id'], $student_id]);
                
                $success = 'Student status updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update student status.';
                logError($e->getMessage());
            }
        }
    }
}

// Pagination and filters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$course_filter = isset($_GET['course']) ? sanitize($_GET['course']) : '';
$branch_filter = isset($_GET['branch']) ? sanitize($_GET['branch']) : '';

// Build query
$where_conditions = ["role = 'student'"];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR roll_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($course_filter) {
    $where_conditions[] = "course = ?";
    $params[] = $course_filter;
}

if ($branch_filter) {
    $where_conditions[] = "branch = ?";
    $params[] = $branch_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get students
$query = "
    SELECT u.*, 
           (SELECT COUNT(*) FROM applications WHERE user_id = u.id) as total_applications,
           (SELECT COUNT(*) FROM applications WHERE user_id = u.id AND status = 'selected') as selected_applications
    FROM users u 
    WHERE $where_clause 
    ORDER BY u.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                            <a href="students.php" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg font-medium transition duration-300 hover:bg-white/30 border border-white/20">
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Students Management</h1>
            <p class="text-gray-600">Manage student registrations and approvals</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo $search; ?>"
                           placeholder="Name, email, or roll number..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div>
                    <label for="course" class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                    <select id="course" name="course" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Courses</option>
                        <option value="B.Tech" <?php echo ($course_filter === 'B.Tech') ? 'selected' : ''; ?>>B.Tech</option>
                        <option value="M.Tech" <?php echo ($course_filter === 'M.Tech') ? 'selected' : ''; ?>>M.Tech</option>
                        <option value="BCA" <?php echo ($course_filter === 'BCA') ? 'selected' : ''; ?>>BCA</option>
                        <option value="MCA" <?php echo ($course_filter === 'MCA') ? 'selected' : ''; ?>>MCA</option>
                        <option value="MBA" <?php echo ($course_filter === 'MBA') ? 'selected' : ''; ?>>MBA</option>
                    </select>
                </div>
                
                <div>
                    <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                    <select id="branch" name="branch" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Branches</option>
                        <option value="Computer Science" <?php echo ($branch_filter === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Information Technology" <?php echo ($branch_filter === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                        <option value="Electronics" <?php echo ($branch_filter === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                        <option value="Mechanical" <?php echo ($branch_filter === 'Mechanical') ? 'selected' : ''; ?>>Mechanical</option>
                        <option value="Civil" <?php echo ($branch_filter === 'Civil') ? 'selected' : ''; ?>>Civil</option>
                        <option value="Electrical" <?php echo ($branch_filter === 'Electrical') ? 'selected' : ''; ?>>Electrical</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <div class="mb-6 flex items-center justify-between">
            <p class="text-gray-600">
                Showing <?php echo count($students); ?> of <?php echo $total_records; ?> students
                <?php if ($search || $status_filter || $course_filter || $branch_filter): ?>
                    <a href="students.php" class="ml-2 text-indigo-600 hover:text-indigo-800 text-sm">Clear filters</a>
                <?php endif; ?>
            </p>
            
            <div class="flex space-x-2">
                <button onclick="exportStudents()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-download mr-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Students Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course & Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CGPA</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applications</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-users text-4xl mb-4"></i>
                                    <p>No students found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&background=6366f1&color=fff&size=40" 
                                                 alt="Profile" class="w-10 h-10 rounded-full mr-3">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo $student['name']; ?></div>
                                                <div class="text-sm text-gray-500"><?php echo $student['email']; ?></div>
                                                <div class="text-xs text-gray-400">Roll: <?php echo $student['roll_no']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $student['course']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $student['branch']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo $student['cgpa'] ? number_format($student['cgpa'], 2) : 'N/A'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $student['total_applications']; ?> applied</div>
                                        <?php if ($student['selected_applications'] > 0): ?>
                                            <div class="text-sm text-green-600"><?php echo $student['selected_applications']; ?> selected</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php 
                                            switch($student['status']) {
                                                case 'approved': echo 'bg-green-100 text-green-800'; break;
                                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                            }
                                            ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($student['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewStudent(<?php echo $student['id']; ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($student['status'] !== 'approved'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" name="update_status" 
                                                            onclick="return confirm('Approve this student?')"
                                                            class="text-green-600 hover:text-green-900">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($student['status'] !== 'rejected'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" name="update_status" 
                                                            onclick="return confirm('Reject this student?')"
                                                            class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
                
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&course=<?php echo urlencode($course_filter); ?>&branch=<?php echo urlencode($branch_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&course=<?php echo urlencode($course_filter); ?>&branch=<?php echo urlencode($branch_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md <?php echo $i === $page ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&course=<?php echo urlencode($course_filter); ?>&branch=<?php echo urlencode($branch_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Student Details Modal -->
    <div id="studentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Student Details</h3>
                    <button onclick="closeStudentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="studentModalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function viewStudent(studentId) {
            document.getElementById('studentModalContent').innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-indigo-600 mb-4"></i>
                    <p>Loading student details...</p>
                </div>
            `;
            document.getElementById('studentModal').classList.remove('hidden');
            
            // In a real implementation, you would fetch student details via AJAX
            setTimeout(() => {
                document.getElementById('studentModalContent').innerHTML = `
                    <div class="space-y-4">
                        <p class="text-gray-600">Detailed student information would be displayed here.</p>
                        <p class="text-sm text-gray-500">This includes full profile, application history, documents, etc.</p>
                    </div>
                `;
            }, 1000);
        }

        function closeStudentModal() {
            document.getElementById('studentModal').classList.add('hidden');
        }

        function exportStudents() {
            // In a real implementation, this would trigger a CSV/Excel export
            alert('Export functionality would be implemented here');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            if (!event.target.closest('[onclick="toggleUserMenu()"]')) {
                userMenu.classList.add('hidden');
            }
        });

        // Close modal when clicking outside
        document.getElementById('studentModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeStudentModal();
            }
        });
    </script>
</body>
</html>
