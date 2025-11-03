<?php
require_once '../config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

// Handle search and filters
$search = sanitizeInput($_GET['search'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$categoryFilter = sanitizeInput($_GET['category'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(10, intval($_GET['limit'] ?? DEFAULT_PAGE_SIZE)));
$offset = ($page - 1) * $limit;

// Build query
$whereConditions = ['user_id = ?'];
$params = [$user['id']];

if (!empty($search)) {
    $whereConditions[] = "(subject LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if (!empty($categoryFilter)) {
    $whereConditions[] = "category = ?";
    $params[] = $categoryFilter;
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM tickets $whereClause";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get tickets
$query = "SELECT * FROM tickets $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$pageTitle = 'Support Center';
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
        .ticket-card { transition: all 0.2s ease; }
        .ticket-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
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
                <a href="../departments/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-building mr-3"></i>Departments
                </a>
                <a href="../reports/index.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-chart-bar mr-3"></i>Reports
                </a>
                <a href="index.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-headset mr-3"></i>Support
                </a>
                <a href="../notifications.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-bell mr-3"></i>Notifications
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
                    <h1 class="ml-4 text-2xl font-semibold text-gray-800"><?php echo $pageTitle; ?></h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="create.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus mr-2"></i>New Ticket
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
                            <div class="py-2">
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
                    <li class="text-gray-900">Support Center</li>
                </ol>
            </nav>

            <!-- Quick Help Section -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Need Help?</h2>
                        <p class="text-blue-100">Browse our knowledge base or create a support ticket</p>
                    </div>
                    <div class="text-right">
                        <a href="create.php" class="bg-white text-blue-600 hover:bg-blue-50 px-6 py-3 rounded-lg font-semibold transition duration-200">
                            <i class="fas fa-plus mr-2"></i>Create Ticket
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 text-center ticket-card">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-question-circle text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-800 mb-2">FAQ</h3>
                    <p class="text-sm text-gray-600 mb-3">Common questions and answers</p>
                    <a href="#faq" class="text-blue-600 hover:text-blue-500 text-sm font-medium">View FAQ</a>
                </div>

                <div class="bg-white rounded-lg shadow p-4 text-center ticket-card">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-book text-green-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-800 mb-2">User Guide</h3>
                    <p class="text-sm text-gray-600 mb-3">Step-by-step tutorials</p>
                    <a href="#guide" class="text-green-600 hover:text-green-500 text-sm font-medium">Read Guide</a>
                </div>

                <div class="bg-white rounded-lg shadow p-4 text-center ticket-card">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-video text-yellow-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-800 mb-2">Video Tutorials</h3>
                    <p class="text-sm text-gray-600 mb-3">Watch how-to videos</p>
                    <a href="#videos" class="text-yellow-600 hover:text-yellow-500 text-sm font-medium">Watch Videos</a>
                </div>

                <div class="bg-white rounded-lg shadow p-4 text-center ticket-card">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-headset text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-800 mb-2">Contact Support</h3>
                    <p class="text-sm text-gray-600 mb-3">Get personalized help</p>
                    <a href="create.php" class="text-purple-600 hover:text-purple-500 text-sm font-medium">Create Ticket</a>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Tickets</label>
                        <input type="text" id="search" name="search" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                               placeholder="Search by subject or description..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Categories</option>
                            <option value="technical" <?php echo $categoryFilter === 'technical' ? 'selected' : ''; ?>>Technical</option>
                            <option value="account" <?php echo $categoryFilter === 'account' ? 'selected' : ''; ?>>Account</option>
                            <option value="data_issue" <?php echo $categoryFilter === 'data_issue' ? 'selected' : ''; ?>>Data Issue</option>
                            <option value="general" <?php echo $categoryFilter === 'general' ? 'selected' : ''; ?>>General</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg w-full">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Support Tickets -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Your Support Tickets</h3>
                        <span class="text-sm text-gray-500"><?php echo number_format($totalRecords); ?> tickets found</span>
                    </div>
                </div>

                <?php if (empty($tickets)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-ticket-alt text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No support tickets</h3>
                    <p class="text-gray-500 mb-4">
                        <?php if (!empty($search) || !empty($statusFilter) || !empty($categoryFilter)): ?>
                            No tickets match your search criteria.
                        <?php else: ?>
                            You haven't created any support tickets yet.
                        <?php endif; ?>
                    </p>
                    <a href="create.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg transition duration-200">
                        <i class="fas fa-plus mr-2"></i>Create Your First Ticket
                    </a>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($tickets as $ticket): ?>
                    <div class="p-6 hover:bg-gray-50 transition duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <h4 class="text-lg font-medium text-gray-900 mr-3">
                                        <a href="view.php?id=<?php echo $ticket['ticket_id']; ?>" class="hover:text-indigo-600">
                                            <?php echo htmlspecialchars($ticket['subject']); ?>
                                        </a>
                                    </h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($ticket['status']) {
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'in_progress': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'resolved': echo 'bg-green-100 text-green-800'; break;
                                            case 'closed': echo 'bg-gray-100 text-gray-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 mb-3 line-clamp-2">
                                    <?php echo htmlspecialchars(substr($ticket['description'], 0, 200)); ?>
                                    <?php if (strlen($ticket['description']) > 200): ?>...<?php endif; ?>
                                </p>
                                
                                <div class="flex items-center text-sm text-gray-500 space-x-4">
                                    <span>
                                        <i class="fas fa-tag mr-1"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $ticket['category'])); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo timeAgo($ticket['created_at']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-hashtag mr-1"></i>
                                        #<?php echo $ticket['ticket_id']; ?>
                                    </span>
                                    <?php if ($ticket['priority'] !== 'medium'): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        <?php 
                                        switch($ticket['priority']) {
                                            case 'low': echo 'bg-gray-100 text-gray-800'; break;
                                            case 'high': echo 'bg-orange-100 text-orange-800'; break;
                                            case 'urgent': echo 'bg-red-100 text-red-800'; break;
                                        }
                                        ?>">
                                        <?php echo ucfirst($ticket['priority']); ?> Priority
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ml-4 flex items-center space-x-2">
                                <a href="view.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                   class="text-indigo-600 hover:text-indigo-900" title="View Ticket">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($ticket['status'] !== 'closed'): ?>
                                <a href="reply.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                   class="text-green-600 hover:text-green-900" title="Reply">
                                    <i class="fas fa-reply"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                    <?php echo generatePagination($page, $totalPages, 'index.php', $_GET); ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.toggle('hidden');
        }

        function toggleUserMenu() {
            document.getElementById('userDropdown').classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const userDropdown = document.getElementById('userDropdown');
            if (!event.target.closest('.relative')) {
                userDropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
