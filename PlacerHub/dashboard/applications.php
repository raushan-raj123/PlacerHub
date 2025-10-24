<?php
require_once '../config/config.php';
requireLogin();

if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$db = getDB();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Build query
$where_conditions = ["a.user_id = ?"];
$params = [$_SESSION['user_id']];

if ($status_filter) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(*) as total
    FROM applications a
    WHERE $where_clause
";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get applications
$query = "
    SELECT a.*, pd.title, pd.job_role, pd.package_min, pd.package_max, pd.drive_date, pd.deadline,
           c.name as company_name, c.location as company_location
    FROM applications a
    JOIN placement_drives pd ON a.drive_id = pd.id
    JOIN companies c ON pd.company_id = c.id
    WHERE $where_clause
    ORDER BY a.applied_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - <?php echo SITE_NAME; ?></title>
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
                    <a href="drives.php" class="text-gray-700 hover:text-indigo-600">Job Drives</a>
                    <a href="applications.php" class="text-indigo-600 font-medium">My Applications</a>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">My Applications</h1>
            <p class="text-gray-600">Track your job applications and their status</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="flex items-end space-x-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" 
                            class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Status</option>
                        <option value="applied" <?php echo ($status_filter === 'applied') ? 'selected' : ''; ?>>Applied</option>
                        <option value="shortlisted" <?php echo ($status_filter === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="selected" <?php echo ($status_filter === 'selected') ? 'selected' : ''; ?>>Selected</option>
                        <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                
                <?php if ($status_filter): ?>
                    <a href="applications.php" class="text-indigo-600 hover:text-indigo-800 text-sm">Clear filter</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Applications -->
        <div class="space-y-6">
            <?php if (empty($applications)): ?>
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No applications found</h3>
                    <p class="text-gray-600 mb-4">You haven't applied for any jobs yet.</p>
                    <a href="drives.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-search mr-2"></i>Browse Job Drives
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition duration-300">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo $app['title']; ?></h3>
                                            <div class="flex items-center text-gray-600 mb-2">
                                                <i class="fas fa-building mr-2"></i>
                                                <span class="font-medium"><?php echo $app['company_name']; ?></span>
                                                <?php if ($app['company_location']): ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                    <span><?php echo $app['company_location']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center text-gray-600 mb-4">
                                                <i class="fas fa-user-tie mr-2"></i>
                                                <span><?php echo $app['job_role']; ?></span>
                                                <?php if ($app['package_min'] && $app['package_max']): ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-rupee-sign mr-1"></i>
                                                    <span><?php echo $app['package_min']; ?>-<?php echo $app['package_max']; ?> LPA</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                <?php 
                                                switch($app['status']) {
                                                    case 'applied': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'shortlisted': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'selected': echo 'bg-green-100 text-green-800'; break;
                                                    case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                }
                                                ?>">
                                                <?php 
                                                switch($app['status']) {
                                                    case 'applied': echo '<i class="fas fa-clock mr-1"></i>Applied'; break;
                                                    case 'shortlisted': echo '<i class="fas fa-star mr-1"></i>Shortlisted'; break;
                                                    case 'selected': echo '<i class="fas fa-check mr-1"></i>Selected'; break;
                                                    case 'rejected': echo '<i class="fas fa-times mr-1"></i>Rejected'; break;
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                        <div>
                                            <span class="font-medium">Applied:</span>
                                            <span class="ml-1"><?php echo formatDateTime($app['applied_at']); ?></span>
                                        </div>
                                        <?php if ($app['drive_date']): ?>
                                            <div>
                                                <span class="font-medium">Drive Date:</span>
                                                <span class="ml-1"><?php echo formatDate($app['drive_date']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="font-medium">Deadline:</span>
                                            <span class="ml-1"><?php echo formatDate($app['deadline']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($app['status'] === 'selected'): ?>
                                        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                                            <p class="text-sm text-green-800">
                                                <i class="fas fa-trophy mr-2"></i>
                                                Congratulations! You have been selected for this position.
                                            </p>
                                        </div>
                                    <?php elseif ($app['status'] === 'shortlisted'): ?>
                                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                            <p class="text-sm text-yellow-800">
                                                <i class="fas fa-star mr-2"></i>
                                                You have been shortlisted! Wait for further updates.
                                            </p>
                                        </div>
                                    <?php elseif ($app['status'] === 'rejected'): ?>
                                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                                            <p class="text-sm text-red-800">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                Unfortunately, you were not selected for this position.
                                            </p>
                                        </div>
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
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md <?php echo $i === $page ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

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
