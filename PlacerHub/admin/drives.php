<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

// Handle drive creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_drive'])) {
        $company_id = intval($_POST['company_id']);
        $title = sanitize($_POST['title']);
        $job_role = sanitize($_POST['job_role']);
        $package_min = floatval($_POST['package_min']);
        $package_max = floatval($_POST['package_max']);
        $criteria = sanitize($_POST['criteria']);
        $min_cgpa = floatval($_POST['min_cgpa']);
        $eligible_branches = isset($_POST['eligible_branches']) ? implode(', ', $_POST['eligible_branches']) : '';
        $drive_date = $_POST['drive_date'];
        $deadline = $_POST['deadline'];
        $description = sanitize($_POST['description']);
        
        if (empty($title) || empty($job_role) || empty($company_id)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO placement_drives (company_id, title, job_role, package_min, package_max, criteria, min_cgpa, eligible_branches, drive_date, deadline, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$company_id, $title, $job_role, $package_min, $package_max, $criteria, $min_cgpa, $eligible_branches, $drive_date, $deadline, $description]);
                
                $drive_id = $db->lastInsertId();
                
                // Notify eligible students
                $eligible_students_query = "SELECT id FROM users WHERE role = 'student' AND status = 'approved'";
                $params = [];
                
                if ($min_cgpa > 0) {
                    $eligible_students_query .= " AND cgpa >= ?";
                    $params[] = $min_cgpa;
                }
                
                if (!empty($eligible_branches)) {
                    $branch_conditions = [];
                    foreach (explode(', ', $eligible_branches) as $branch) {
                        $branch_conditions[] = "branch = ?";
                        $params[] = trim($branch);
                    }
                    $eligible_students_query .= " AND (" . implode(' OR ', $branch_conditions) . ")";
                }
                
                $stmt = $db->prepare($eligible_students_query);
                $stmt->execute($params);
                $eligible_students = $stmt->fetchAll();
                
                // Send notifications
                foreach ($eligible_students as $student) {
                    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New Job Drive Available', 'A new placement drive \"$title\" is now available. Check it out!', 'info')");
                    $stmt->execute([$student['id']]);
                }
                
                $success = 'Placement drive created successfully and notifications sent to eligible students!';
            } catch (Exception $e) {
                $error = 'Failed to create placement drive.';
                logError($e->getMessage());
            }
        }
    }
    
    if (isset($_POST['update_status'])) {
        $drive_id = intval($_POST['drive_id']);
        $status = sanitize($_POST['status']);
        
        try {
            $stmt = $db->prepare("UPDATE placement_drives SET status = ? WHERE id = ?");
            $stmt->execute([$status, $drive_id]);
            
            $success = 'Drive status updated successfully!';
        } catch (Exception $e) {
            $error = 'Failed to update drive status.';
            logError($e->getMessage());
        }
    }
}

// Get drives with company info
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$company_filter = isset($_GET['company']) ? sanitize($_GET['company']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(pd.title LIKE ? OR pd.job_role LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "pd.status = ?";
    $params[] = $status_filter;
}

if ($company_filter) {
    $where_conditions[] = "c.name LIKE ?";
    $params[] = "%$company_filter%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "
    SELECT pd.*, c.name as company_name, c.location as company_location,
           (SELECT COUNT(*) FROM applications WHERE drive_id = pd.id) as total_applications,
           (SELECT COUNT(*) FROM applications WHERE drive_id = pd.id AND status = 'selected') as selected_count
    FROM placement_drives pd
    JOIN companies c ON pd.company_id = c.id
    $where_clause
    ORDER BY pd.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$drives = $stmt->fetchAll();

// Get companies for dropdown
$stmt = $db->query("SELECT id, name FROM companies ORDER BY name");
$companies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placement Drives - <?php echo SITE_NAME; ?></title>
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
                            <a href="drives.php" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg font-medium transition duration-300 hover:bg-white/30 border border-white/20">
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
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Placement Drives</h1>
                <p class="text-gray-600">Create and manage placement drives</p>
            </div>
            <button onclick="showCreateDriveModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                <i class="fas fa-plus mr-2"></i>Create Drive
            </button>
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
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo $search; ?>"
                           placeholder="Drive title, role, or company..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Status</option>
                        <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo ($status_filter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                    <input type="text" id="company" name="company" value="<?php echo $company_filter; ?>"
                           placeholder="Company name..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Drives List -->
        <div class="space-y-6">
            <?php if (empty($drives)): ?>
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-briefcase text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No placement drives found</h3>
                    <p class="text-gray-600 mb-4">Create your first placement drive to get started.</p>
                    <button onclick="showCreateDriveModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-plus mr-2"></i>Create Drive
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($drives as $drive): ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition duration-300">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo $drive['title']; ?></h3>
                                            <div class="flex items-center text-gray-600 mb-2">
                                                <i class="fas fa-building mr-2"></i>
                                                <span class="font-medium"><?php echo $drive['company_name']; ?></span>
                                                <?php if ($drive['company_location']): ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                    <span><?php echo $drive['company_location']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center text-gray-600 mb-4">
                                                <i class="fas fa-user-tie mr-2"></i>
                                                <span><?php echo $drive['job_role']; ?></span>
                                                <?php if ($drive['package_min'] && $drive['package_max']): ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-rupee-sign mr-1"></i>
                                                    <span><?php echo $drive['package_min']; ?>-<?php echo $drive['package_max']; ?> LPA</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                <?php 
                                                switch($drive['status']) {
                                                    case 'active': echo 'bg-green-100 text-green-800'; break;
                                                    case 'completed': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                }
                                                ?>">
                                                <?php echo ucfirst($drive['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-600 mb-4">
                                        <div>
                                            <span class="font-medium">Applications:</span>
                                            <span class="ml-1"><?php echo $drive['total_applications']; ?></span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Selected:</span>
                                            <span class="ml-1"><?php echo $drive['selected_count']; ?></span>
                                        </div>
                                        <?php if ($drive['drive_date']): ?>
                                            <div>
                                                <span class="font-medium">Drive Date:</span>
                                                <span class="ml-1"><?php echo formatDate($drive['drive_date']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="font-medium">Deadline:</span>
                                            <span class="ml-1"><?php echo formatDate($drive['deadline']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($drive['description']): ?>
                                        <p class="text-gray-600 text-sm mb-4"><?php echo substr($drive['description'], 0, 150); ?>
                                            <?php if (strlen($drive['description']) > 150): ?>...<?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Eligibility Criteria -->
                                    <div class="flex flex-wrap gap-2 text-sm">
                                        <?php if ($drive['min_cgpa']): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-700">
                                                <i class="fas fa-chart-line mr-1"></i>Min CGPA: <?php echo $drive['min_cgpa']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($drive['eligible_branches']): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-700">
                                                <i class="fas fa-code-branch mr-1"></i><?php echo $drive['eligible_branches']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mt-4 lg:mt-0 lg:ml-6 flex flex-col gap-2">
                                    <a href="applications.php?drive_id=<?php echo $drive['id']; ?>" 
                                       class="px-4 py-2 border border-indigo-600 text-indigo-600 rounded-md hover:bg-indigo-50 font-medium text-center">
                                        <i class="fas fa-users mr-2"></i>View Applications
                                    </a>
                                    
                                    <?php if ($drive['status'] === 'active'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="drive_id" value="<?php echo $drive['id']; ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" name="update_status" 
                                                    onclick="return confirm('Mark this drive as completed?')"
                                                    class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium">
                                                <i class="fas fa-check mr-2"></i>Mark Complete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="drive_id" value="<?php echo $drive['id']; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" name="update_status" 
                                                onclick="return confirm('Cancel this drive?')"
                                                class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-medium">
                                            <i class="fas fa-times mr-2"></i>Cancel
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Drive Modal -->
    <div id="driveModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Create Placement Drive</h3>
                    <button onclick="closeDriveModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">Company *</label>
                            <select id="company_id" name="company_id" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Company</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>"><?php echo $company['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Drive Title *</label>
                            <input type="text" id="title" name="title" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="job_role" class="block text-sm font-medium text-gray-700 mb-2">Job Role *</label>
                            <input type="text" id="job_role" name="job_role" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="package_min" class="block text-sm font-medium text-gray-700 mb-2">Package (Min LPA)</label>
                            <input type="number" id="package_min" name="package_min" step="0.1" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="package_max" class="block text-sm font-medium text-gray-700 mb-2">Package (Max LPA)</label>
                            <input type="number" id="package_max" name="package_max" step="0.1" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="min_cgpa" class="block text-sm font-medium text-gray-700 mb-2">Minimum CGPA</label>
                            <input type="number" id="min_cgpa" name="min_cgpa" step="0.01" min="0" max="10" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="drive_date" class="block text-sm font-medium text-gray-700 mb-2">Drive Date</label>
                            <input type="date" id="drive_date" name="drive_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">Application Deadline *</label>
                            <input type="date" id="deadline" name="deadline" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Eligible Branches</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="eligible_branches[]" value="Computer Science" class="mr-2">
                                Computer Science
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="eligible_branches[]" value="Information Technology" class="mr-2">
                                Information Technology
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="eligible_branches[]" value="Electronics" class="mr-2">
                                Electronics
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="eligible_branches[]" value="Mechanical" class="mr-2">
                                Mechanical
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="eligible_branches[]" value="Civil" class="mr-2">
                                Civil
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="eligible_branches[]" value="Electrical" class="mr-2">
                                Electrical
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label for="criteria" class="block text-sm font-medium text-gray-700 mb-2">Eligibility Criteria</label>
                        <textarea id="criteria" name="criteria" rows="2" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    
                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeDriveModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" name="create_drive" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium">
                            Create Drive
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

        function showCreateDriveModal() {
            document.getElementById('driveModal').classList.remove('hidden');
        }

        function closeDriveModal() {
            document.getElementById('driveModal').classList.add('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            if (!event.target.closest('[onclick="toggleUserMenu()"]')) {
                userMenu.classList.add('hidden');
            }
        });

        // Close modal when clicking outside
        document.getElementById('driveModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeDriveModal();
            }
        });

        // Set minimum date to today
        document.getElementById('drive_date').min = new Date().toISOString().split('T')[0];
        document.getElementById('deadline').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
