<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $application_id = intval($_POST['application_id']);
    $new_status = sanitize($_POST['new_status']);
    
    if (in_array($new_status, ['applied', 'shortlisted', 'selected', 'rejected'])) {
        try {
            // Get application details for notification
            $stmt = $db->prepare("
                SELECT a.user_id, pd.title, c.name as company_name 
                FROM applications a 
                JOIN placement_drives pd ON a.drive_id = pd.id 
                JOIN companies c ON pd.company_id = c.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$application_id]);
            $app_details = $stmt->fetch();
            
            // Update status
            $stmt = $db->prepare("UPDATE applications SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $application_id]);
            
            // Create notification
            $message = "Your application for {$app_details['title']} at {$app_details['company_name']} has been updated to: " . ucfirst($new_status);
            $notification_type = $new_status === 'selected' ? 'success' : ($new_status === 'rejected' ? 'error' : 'info');
            
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Application Status Update', ?, ?)");
            $stmt->execute([$app_details['user_id'], $message, $notification_type]);
            
            $success = 'Application status updated successfully!';
        } catch (Exception $e) {
            $error = 'Failed to update application status.';
            logError($e->getMessage());
        }
    }
}

// Filters
$drive_filter = isset($_GET['drive_id']) ? intval($_GET['drive_id']) : 0;
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where_conditions = [];
$params = [];

if ($drive_filter > 0) {
    $where_conditions[] = "a.drive_id = ?";
    $params[] = $drive_filter;
}

if ($status_filter) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR pd.title LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get applications
$query = "
    SELECT a.*, u.name as student_name, u.email as student_email, u.roll_no, u.cgpa, u.course, u.branch,
           pd.title as job_title, pd.job_role, pd.package_min, pd.package_max,
           c.name as company_name
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN placement_drives pd ON a.drive_id = pd.id
    JOIN companies c ON pd.company_id = c.id
    $where_clause
    ORDER BY a.applied_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Get drives for filter
$stmt = $db->query("
    SELECT pd.id, pd.title, c.name as company_name 
    FROM placement_drives pd 
    JOIN companies c ON pd.company_id = c.id 
    ORDER BY pd.created_at DESC
");
$drives = $stmt->fetchAll();

// Get statistics
$stats = [];
$stmt = $db->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status");
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stats['total'] = array_sum($status_counts);
$stats['applied'] = $status_counts['applied'] ?? 0;
$stats['shortlisted'] = $status_counts['shortlisted'] ?? 0;
$stats['selected'] = $status_counts['selected'] ?? 0;
$stats['rejected'] = $status_counts['rejected'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications Management - <?php echo SITE_NAME; ?></title>
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
                            <a href="students.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
                                <i class="fas fa-users mr-2"></i>Students
                            </a>
                            <a href="companies.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
                                <i class="fas fa-building mr-2"></i>Companies
                            </a>
                            <a href="drives.php" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition duration-300">
                                <i class="fas fa-briefcase mr-2"></i>Drives
                            </a>
                            <a href="applications.php" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg font-medium transition duration-300 hover:bg-white/30 border border-white/20">
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Applications Management</h1>
            <p class="text-gray-600">Review and manage student job applications</p>
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

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <!-- Total Applications Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-slate-500 via-gray-600 to-zinc-700 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-file-alt text-lg mr-2"></i>
                                <span class="text-sm font-semibold opacity-90">Total</span>
                            </div>
                            <p class="text-2xl font-bold mb-1"><?php echo $stats['total']; ?></p>
                            <p class="text-xs opacity-80">Applications</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <i class="fas fa-clipboard-list text-xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-2 -right-2 w-12 h-12 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-3 -left-3 w-8 h-8 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Applied Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-blue-400 via-cyan-500 to-teal-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-clock text-lg mr-2"></i>
                                <span class="text-sm font-semibold opacity-90">Applied</span>
                            </div>
                            <p class="text-2xl font-bold mb-1"><?php echo $stats['applied']; ?></p>
                            <p class="text-xs opacity-80">In Process</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <i class="fas fa-paper-plane text-xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-2 -right-2 w-12 h-12 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-3 -left-3 w-8 h-8 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Shortlisted Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-star text-lg mr-2"></i>
                                <span class="text-sm font-semibold opacity-90">Shortlisted</span>
                            </div>
                            <p class="text-2xl font-bold mb-1"><?php echo $stats['shortlisted']; ?></p>
                            <p class="text-xs opacity-80">Candidates</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <i class="fas fa-award text-xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-2 -right-2 w-12 h-12 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-3 -left-3 w-8 h-8 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Selected Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-check text-lg mr-2"></i>
                                <span class="text-sm font-semibold opacity-90">Selected</span>
                            </div>
                            <p class="text-2xl font-bold mb-1"><?php echo $stats['selected']; ?></p>
                            <p class="text-xs opacity-80">Success</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <i class="fas fa-trophy text-xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-2 -right-2 w-12 h-12 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-3 -left-3 w-8 h-8 bg-white/10 rounded-full"></div>
                </div>
            </div>
            
            <!-- Rejected Card -->
            <div class="relative overflow-hidden rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                <div class="bg-gradient-to-br from-red-400 via-pink-500 to-rose-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <i class="fas fa-times text-lg mr-2"></i>
                                <span class="text-sm font-semibold opacity-90">Rejected</span>
                            </div>
                            <p class="text-2xl font-bold mb-1"><?php echo $stats['rejected']; ?></p>
                            <p class="text-xs opacity-80">Applications</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <i class="fas fa-ban text-xl"></i>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-2 -right-2 w-12 h-12 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-3 -left-3 w-8 h-8 bg-white/10 rounded-full"></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo $search; ?>"
                           placeholder="Student name, email, job title..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label for="drive_id" class="block text-sm font-medium text-gray-700 mb-2">Drive</label>
                    <select id="drive_id" name="drive_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Drives</option>
                        <?php foreach ($drives as $drive): ?>
                            <option value="<?php echo $drive['id']; ?>" <?php echo ($drive_filter == $drive['id']) ? 'selected' : ''; ?>>
                                <?php echo $drive['title']; ?> - <?php echo $drive['company_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Status</option>
                        <option value="applied" <?php echo ($status_filter === 'applied') ? 'selected' : ''; ?>>Applied</option>
                        <option value="shortlisted" <?php echo ($status_filter === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="selected" <?php echo ($status_filter === 'selected') ? 'selected' : ''; ?>>Selected</option>
                        <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Applications Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-file-alt text-4xl mb-4"></i>
                                    <p>No applications found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($app['student_name']); ?>&background=6366f1&color=fff&size=40" 
                                                 alt="Profile" class="w-10 h-10 rounded-full mr-3">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo $app['student_name']; ?></div>
                                                <div class="text-sm text-gray-500"><?php echo $app['student_email']; ?></div>
                                                <div class="text-xs text-gray-400">Roll: <?php echo $app['roll_no']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $app['job_title']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $app['company_name']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $app['job_role']; ?></div>
                                        <?php if ($app['package_min'] && $app['package_max']): ?>
                                            <div class="text-xs text-gray-400">₹<?php echo $app['package_min']; ?>-<?php echo $app['package_max']; ?>L</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $app['course']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $app['branch']; ?></div>
                                        <div class="text-sm text-gray-500">CGPA: <?php echo $app['cgpa'] ? number_format($app['cgpa'], 2) : 'N/A'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDateTime($app['applied_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="showStatusModal(<?php echo $app['id']; ?>, '<?php echo $app['status']; ?>', '<?php echo addslashes($app['student_name']); ?>')" 
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button onclick="viewApplication(<?php echo $app['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Update Application Status</h3>
                    <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" id="application_id" name="application_id">
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Student: <span id="student_name" class="font-medium"></span></p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="new_status" class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                        <select id="new_status" name="new_status" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="applied">Applied</option>
                            <option value="shortlisted">Shortlisted</option>
                            <option value="selected">Selected</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" name="update_status" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function showStatusModal(applicationId, currentStatus, studentName) {
            document.getElementById('application_id').value = applicationId;
            document.getElementById('student_name').textContent = studentName;
            document.getElementById('new_status').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        function viewApplication(applicationId) {
            // In a real implementation, this would show detailed application info
            alert('View application details for ID: ' + applicationId);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            if (!event.target.closest('[onclick="toggleUserMenu()"]')) {
                userMenu.classList.add('hidden');
            }
        });

        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeStatusModal();
            }
        });
    </script>
</body>
</html>
