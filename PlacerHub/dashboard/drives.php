<?php
require_once '../config/config.php';
requireLogin();

if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$db = getDB();
$error = '';
$success = '';

// Handle job application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
    $drive_id = intval($_POST['drive_id']);
    
    try {
        // Check if already applied
        $stmt = $db->prepare("SELECT id FROM applications WHERE user_id = ? AND drive_id = ?");
        $stmt->execute([$_SESSION['user_id'], $drive_id]);
        
        if ($stmt->fetch()) {
            $error = 'You have already applied for this position';
        } else {
            // Check eligibility (basic check)
            $stmt = $db->prepare("
                SELECT pd.*, u.cgpa, u.branch 
                FROM placement_drives pd, users u 
                WHERE pd.id = ? AND u.id = ? AND pd.status = 'active' AND pd.deadline >= CURDATE()
            ");
            $stmt->execute([$drive_id, $_SESSION['user_id']]);
            $drive_data = $stmt->fetch();
            
            if (!$drive_data) {
                $error = 'Invalid or expired job drive';
            } else {
                // Check CGPA eligibility
                if ($drive_data['min_cgpa'] && $drive_data['cgpa'] < $drive_data['min_cgpa']) {
                    $error = 'You do not meet the minimum CGPA requirement (' . $drive_data['min_cgpa'] . ')';
                } else {
                    // Apply for the job
                    $stmt = $db->prepare("INSERT INTO applications (user_id, drive_id) VALUES (?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $drive_id]);
                    
                    // Create notification
                    $stmt = $db->prepare("
                        INSERT INTO notifications (user_id, title, message, type) 
                        VALUES (?, 'Application Submitted', 'Your application for {$drive_data['title']} has been submitted successfully', 'success')
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    
                    $success = 'Application submitted successfully!';
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Failed to submit application. Please try again.';
        logError($e->getMessage());
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$company_filter = isset($_GET['company']) ? sanitize($_GET['company']) : '';
$branch_filter = isset($_GET['branch']) ? sanitize($_GET['branch']) : '';

// Build query
$where_conditions = ["pd.status = 'active'", "pd.deadline >= CURDATE()"];
$params = [];

if ($search) {
    $where_conditions[] = "(pd.title LIKE ? OR pd.job_role LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($company_filter) {
    $where_conditions[] = "c.name LIKE ?";
    $params[] = "%$company_filter%";
}

if ($branch_filter) {
    $where_conditions[] = "(pd.eligible_branches IS NULL OR pd.eligible_branches LIKE ?)";
    $params[] = "%$branch_filter%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(*) as total
    FROM placement_drives pd
    JOIN companies c ON pd.company_id = c.id
    WHERE $where_clause
";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get drives
$query = "
    SELECT pd.*, c.name as company_name, c.location as company_location,
           (SELECT COUNT(*) FROM applications WHERE drive_id = pd.id AND user_id = ?) as applied
    FROM placement_drives pd
    JOIN companies c ON pd.company_id = c.id
    WHERE $where_clause
    ORDER BY pd.deadline ASC, pd.created_at DESC
    LIMIT $limit OFFSET $offset
";
$params[] = $_SESSION['user_id'];
$stmt = $db->prepare($query);
$stmt->execute($params);
$drives = $stmt->fetchAll();

// Get companies for filter
$stmt = $db->query("SELECT DISTINCT name FROM companies ORDER BY name");
$companies = $stmt->fetchAll();

// Get user data for eligibility check
$stmt = $db->prepare("SELECT cgpa, branch FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Drives - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-graduation-cap text-2xl text-indigo-600 mr-3"></i>
                    <h1 class="text-xl font-bold text-gray-900"><?php echo SITE_NAME; ?></h1>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-indigo-600">Dashboard</a>
                    <a href="profile.php" class="text-gray-700 hover:text-indigo-600">Profile</a>
                    <a href="drives.php" class="text-indigo-600 font-medium">Job Drives</a>
                    <a href="applications.php" class="text-gray-700 hover:text-indigo-600">My Applications</a>
                    <a href="notifications.php" class="text-gray-700 hover:text-indigo-600">
                        <i class="fas fa-bell"></i>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center text-gray-700 hover:text-indigo-600">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=6366f1&color=fff" 
                                 alt="Profile" class="w-8 h-8 rounded-full mr-2">
                            <span class="hidden md:block"><?php echo $_SESSION['name']; ?></span>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Job Drives</h1>
            <p class="text-gray-600">Explore and apply for placement opportunities</p>
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
                           placeholder="Job title, role, or company..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                    <select id="company" name="company" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Companies</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['name']; ?>" <?php echo ($company_filter === $company['name']) ? 'selected' : ''; ?>>
                                <?php echo $company['name']; ?>
                            </option>
                        <?php endforeach; ?>
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
        <div class="mb-6">
            <p class="text-gray-600">
                Showing <?php echo count($drives); ?> of <?php echo $total_records; ?> job drives
                <?php if ($search || $company_filter || $branch_filter): ?>
                    <a href="drives.php" class="ml-2 text-indigo-600 hover:text-indigo-800 text-sm">Clear filters</a>
                <?php endif; ?>
            </p>
        </div>

        <!-- Job Drives -->
        <div class="space-y-6">
            <?php if (empty($drives)): ?>
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-briefcase text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No job drives found</h3>
                    <p class="text-gray-600">Try adjusting your filters or check back later for new opportunities.</p>
                </div>
            <?php else: ?>
                <?php foreach ($drives as $drive): ?>
                    <?php
                    $is_eligible = true;
                    $eligibility_message = '';
                    
                    // Check CGPA eligibility
                    if ($drive['min_cgpa'] && $user_data['cgpa'] < $drive['min_cgpa']) {
                        $is_eligible = false;
                        $eligibility_message = "Minimum CGPA required: {$drive['min_cgpa']}";
                    }
                    
                    // Check branch eligibility
                    if ($drive['eligible_branches'] && !empty($drive['eligible_branches']) && 
                        strpos($drive['eligible_branches'], $user_data['branch']) === false) {
                        $is_eligible = false;
                        $eligibility_message = "Not eligible for your branch";
                    }
                    
                    $deadline_passed = strtotime($drive['deadline']) < time();
                    $already_applied = $drive['applied'] > 0;
                    ?>
                    
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition duration-300">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex-1">
                                    <div class="flex items-start justify-between">
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
                                            <?php if ($deadline_passed): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                    <i class="fas fa-clock mr-1"></i>Expired
                                                </span>
                                            <?php elseif ($already_applied): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Applied
                                                </span>
                                            <?php elseif (!$is_eligible): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-times mr-1"></i>Not Eligible
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                    <i class="fas fa-calendar mr-1"></i>Open
                                                </span>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-500 mt-1">
                                                Deadline: <?php echo formatDate($drive['deadline']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($drive['description']): ?>
                                        <div class="mb-4">
                                            <p class="text-gray-600 text-sm"><?php echo substr($drive['description'], 0, 200); ?>
                                                <?php if (strlen($drive['description']) > 200): ?>...<?php endif; ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Eligibility Criteria -->
                                    <div class="mb-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Eligibility Criteria:</h4>
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
                                    
                                    <?php if (!$is_eligible && $eligibility_message): ?>
                                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                            <p class="text-sm text-yellow-800">
                                                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $eligibility_message; ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-4 lg:mt-0 lg:ml-6 flex flex-col sm:flex-row gap-3">
                                    <button onclick="viewDriveDetails(<?php echo $drive['id']; ?>)" 
                                            class="px-4 py-2 border border-indigo-600 text-indigo-600 rounded-md hover:bg-indigo-50 font-medium">
                                        <i class="fas fa-eye mr-2"></i>View Details
                                    </button>
                                    
                                    <?php if (!$deadline_passed && !$already_applied && $is_eligible): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="drive_id" value="<?php echo $drive['id']; ?>">
                                            <button type="submit" name="apply_job" 
                                                    onclick="return confirm('Are you sure you want to apply for this position?')"
                                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium">
                                                <i class="fas fa-paper-plane mr-2"></i>Apply Now
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
                
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&company=<?php echo urlencode($company_filter); ?>&branch=<?php echo urlencode($branch_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&company=<?php echo urlencode($company_filter); ?>&branch=<?php echo urlencode($branch_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md <?php echo $i === $page ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&company=<?php echo urlencode($company_filter); ?>&branch=<?php echo urlencode($branch_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Drive Details Modal -->
    <div id="driveModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Job Drive Details</h3>
                    <button onclick="closeDriveModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="driveModalContent">
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

        function viewDriveDetails(driveId) {
            // For now, just show a placeholder. In a real implementation, 
            // you would fetch drive details via AJAX
            document.getElementById('driveModalContent').innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-indigo-600 mb-4"></i>
                    <p>Loading drive details...</p>
                </div>
            `;
            document.getElementById('driveModal').classList.remove('hidden');
            
            // Simulate loading (replace with actual AJAX call)
            setTimeout(() => {
                document.getElementById('driveModalContent').innerHTML = `
                    <div class="space-y-4">
                        <p class="text-gray-600">Detailed information about this job drive would be displayed here.</p>
                        <p class="text-sm text-gray-500">This is a placeholder. In a real implementation, you would fetch the complete drive details from the server.</p>
                    </div>
                `;
            }, 1000);
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
    </script>
</body>
</html>
