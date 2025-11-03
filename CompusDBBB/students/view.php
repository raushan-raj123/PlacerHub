<?php
require_once '../config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

$studentId = intval($_GET['id'] ?? 0);

if ($studentId <= 0) {
    header('Location: index.php?error=invalid_id');
    exit;
}

// Get student details
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: index.php?error=student_not_found');
    exit;
}

// Get created by user info
$createdBy = null;
if ($student['created_by']) {
    $stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$student['created_by']]);
    $createdBy = $stmt->fetch();
}

$pageTitle = 'View Student - ' . $student['name'];
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
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .info-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
                <a href="../dashboard.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="index.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-users mr-3"></i>Students
                </a>
                <a href="../courses/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-book mr-3"></i>Courses
                </a>
                <a href="../departments/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-building mr-3"></i>Departments
                </a>
                <a href="../reports/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-chart-bar mr-3"></i>Reports
                </a>
                <a href="../support/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-headset mr-3"></i>Support
                </a>
                <a href="../profile.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-user mr-3"></i>Profile
                </a>
                <a href="../settings.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
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
                    <h1 class="ml-4 text-2xl font-semibold text-gray-800">Student Details</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="edit.php?id=<?php echo $student['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-edit mr-2"></i>Edit Student
                    </a>
                    <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Students
                    </a>
                    
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
                                <a href="../profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user mr-2"></i>Profile
                                </a>
                                <a href="../settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-cog mr-2"></i>Settings
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

        <!-- Content -->
        <main class="p-6">
            <!-- Breadcrumb -->
            <nav class="mb-6">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li><a href="../dashboard.php" class="hover:text-gray-700">Dashboard</a></li>
                    <li><i class="fas fa-chevron-right"></i></li>
                    <li><a href="index.php" class="hover:text-gray-700">Students</a></li>
                    <li><i class="fas fa-chevron-right"></i></li>
                    <li class="text-gray-900"><?php echo htmlspecialchars($student['name']); ?></li>
                </ol>
            </nav>

            <!-- Student Profile Header -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-6 text-white mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-20 w-20 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                            <span class="text-2xl font-bold text-white">
                                <?php echo strtoupper(substr($student['name'], 0, 2)); ?>
                            </span>
                        </div>
                    </div>
                    <div class="ml-6">
                        <h2 class="text-3xl font-bold"><?php echo htmlspecialchars($student['name']); ?></h2>
                        <p class="text-indigo-100 text-lg"><?php echo htmlspecialchars($student['student_id']); ?></p>
                        <p class="text-indigo-100"><?php echo htmlspecialchars($student['course']); ?> - Year <?php echo $student['year_of_study']; ?></p>
                        <div class="mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Information Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Personal Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow info-card">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-user mr-2 text-indigo-600"></i>Personal Information
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Full Name</label>
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($student['name']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Student ID</label>
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($student['student_id']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                                    <p class="text-gray-900">
                                        <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" 
                                           class="text-indigo-600 hover:text-indigo-500">
                                            <?php echo htmlspecialchars($student['email']); ?>
                                        </a>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                                    <p class="text-gray-900">
                                        <?php if ($student['phone']): ?>
                                            <a href="tel:<?php echo htmlspecialchars($student['phone']); ?>" 
                                               class="text-indigo-600 hover:text-indigo-500">
                                                <?php echo htmlspecialchars($student['phone']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">Not provided</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Address</label>
                                    <p class="text-gray-900">
                                        <?php echo $student['address'] ? htmlspecialchars($student['address']) : '<span class="text-gray-400">Not provided</span>'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information -->
                    <div class="bg-white rounded-lg shadow info-card mt-6">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-graduation-cap mr-2 text-indigo-600"></i>Academic Information
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Course</label>
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($student['course']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Department</label>
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($student['department']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Year of Study</label>
                                    <p class="text-gray-900">Year <?php echo $student['year_of_study']; ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Admission Date</label>
                                    <p class="text-gray-900"><?php echo formatDate($student['admission_date']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & System Info -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow info-card">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-bolt mr-2 text-indigo-600"></i>Quick Actions
                            </h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <a href="edit.php?id=<?php echo $student['id']; ?>" 
                               class="w-full flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200">
                                <i class="fas fa-edit mr-2"></i>Edit Student
                            </a>
                            <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" 
                               class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                                <i class="fas fa-envelope mr-2"></i>Send Email
                            </a>
                            <?php if ($student['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($student['phone']); ?>" 
                               class="w-full flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition duration-200">
                                <i class="fas fa-phone mr-2"></i>Call Student
                            </a>
                            <?php endif; ?>
                            <button onclick="printStudent()" 
                                    class="w-full flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition duration-200">
                                <i class="fas fa-print mr-2"></i>Print Details
                            </button>
                            <button onclick="deleteStudent(<?php echo $student['id']; ?>)" 
                                    class="w-full flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200">
                                <i class="fas fa-trash mr-2"></i>Delete Student
                            </button>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="bg-white rounded-lg shadow info-card">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-info-circle mr-2 text-indigo-600"></i>System Information
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Created On</label>
                                <p class="text-gray-900 text-sm"><?php echo formatDateTime($student['created_at']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Last Updated</label>
                                <p class="text-gray-900 text-sm"><?php echo formatDateTime($student['updated_at']); ?></p>
                            </div>
                            <?php if ($createdBy): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Created By</label>
                                <p class="text-gray-900 text-sm"><?php echo htmlspecialchars($createdBy['full_name']); ?></p>
                            </div>
                            <?php endif; ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Student ID</label>
                                <p class="text-gray-900 text-sm font-mono">#<?php echo $student['id']; ?></p>
                            </div>
                        </div>
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

        // Print student details
        function printStudent() {
            window.print();
        }

        // Delete student
        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        csrf_token: '<?php echo generateCSRFToken(); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Student deleted successfully');
                        window.location.href = 'index.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error deleting student');
                });
            }
        }

        // Print styles
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                .sidebar-transition, header, #sidebarOverlay, .bg-gradient-to-r button {
                    display: none !important;
                }
                .lg\\:ml-64 {
                    margin-left: 0 !important;
                }
                .bg-white {
                    box-shadow: none !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
