<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

// Handle company actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_company'])) {
        $name = sanitize($_POST['name']);
        $location = sanitize($_POST['location']);
        $type = sanitize($_POST['type']);
        $contact_email = sanitize($_POST['contact_email']);
        $contact_phone = sanitize($_POST['contact_phone']);
        $website = sanitize($_POST['website']);
        $description = sanitize($_POST['description']);
        
        if (empty($name) || empty($contact_email)) {
            $error = 'Company name and contact email are required';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO companies (name, location, type, contact_email, contact_phone, website, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $location, $type, $contact_email, $contact_phone, $website, $description]);
                
                $success = 'Company added successfully!';
            } catch (Exception $e) {
                $error = 'Failed to add company.';
                logError($e->getMessage());
            }
        }
    }
    
    if (isset($_POST['update_company'])) {
        $company_id = intval($_POST['company_id']);
        $name = sanitize($_POST['name']);
        $location = sanitize($_POST['location']);
        $type = sanitize($_POST['type']);
        $contact_email = sanitize($_POST['contact_email']);
        $contact_phone = sanitize($_POST['contact_phone']);
        $website = sanitize($_POST['website']);
        $description = sanitize($_POST['description']);
        $verified = isset($_POST['verified']) ? 1 : 0;
        
        try {
            $stmt = $db->prepare("UPDATE companies SET name = ?, location = ?, type = ?, contact_email = ?, contact_phone = ?, website = ?, description = ?, verified = ? WHERE id = ?");
            $stmt->execute([$name, $location, $type, $contact_email, $contact_phone, $website, $description, $verified, $company_id]);
            
            $success = 'Company updated successfully!';
        } catch (Exception $e) {
            $error = 'Failed to update company.';
            logError($e->getMessage());
        }
    }
}

// Get companies
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR location LIKE ? OR contact_email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter) {
    $where_conditions[] = "type = ?";
    $params[] = $type_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM placement_drives WHERE company_id = c.id) as total_drives,
           (SELECT COUNT(*) FROM placement_drives WHERE company_id = c.id AND status = 'active') as active_drives
    FROM companies c 
    $where_clause 
    ORDER BY c.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$companies = $stmt->fetchAll();

// Get company for editing if requested
$edit_company = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_company = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies Management - <?php echo SITE_NAME; ?></title>
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
                            <a href="companies.php" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg font-medium transition duration-300 hover:bg-white/30 border border-white/20">
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
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Companies Management</h1>
                <p class="text-gray-600">Manage company registrations and partnerships</p>
            </div>
            <button onclick="showAddCompanyModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                <i class="fas fa-plus mr-2"></i>Add Company
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
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo $search; ?>"
                           placeholder="Company name, location, or email..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select id="type" name="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Types</option>
                        <option value="IT" <?php echo ($type_filter === 'IT') ? 'selected' : ''; ?>>IT</option>
                        <option value="Manufacturing" <?php echo ($type_filter === 'Manufacturing') ? 'selected' : ''; ?>>Manufacturing</option>
                        <option value="Finance" <?php echo ($type_filter === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                        <option value="Healthcare" <?php echo ($type_filter === 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                        <option value="Other" <?php echo ($type_filter === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Companies Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($companies)): ?>
                <div class="col-span-full bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-building text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No companies found</h3>
                    <p class="text-gray-600 mb-4">Start by adding your first company partner.</p>
                    <button onclick="showAddCompanyModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-plus mr-2"></i>Add Company
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($companies as $company): ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition duration-300">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="bg-indigo-100 text-indigo-600 w-12 h-12 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-building text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $company['name']; ?></h3>
                                        <?php if ($company['verified']): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>Verified
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button onclick="editCompany(<?php echo $company['id']; ?>)" 
                                            class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteCompany(<?php echo $company['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="space-y-2 text-sm text-gray-600 mb-4">
                                <?php if ($company['type']): ?>
                                    <p><i class="fas fa-tag mr-2"></i><?php echo $company['type']; ?></p>
                                <?php endif; ?>
                                <?php if ($company['location']): ?>
                                    <p><i class="fas fa-map-marker-alt mr-2"></i><?php echo $company['location']; ?></p>
                                <?php endif; ?>
                                <p><i class="fas fa-envelope mr-2"></i><?php echo $company['contact_email']; ?></p>
                                <?php if ($company['website']): ?>
                                    <p><i class="fas fa-globe mr-2"></i><a href="<?php echo $company['website']; ?>" target="_blank" class="text-indigo-600 hover:underline"><?php echo $company['website']; ?></a></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($company['description']): ?>
                                <p class="text-sm text-gray-600 mb-4"><?php echo substr($company['description'], 0, 100); ?>
                                    <?php if (strlen($company['description']) > 100): ?>...<?php endif; ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <div class="text-sm text-gray-500">
                                    <?php echo $company['total_drives']; ?> total drives
                                </div>
                                <div class="text-sm text-green-600">
                                    <?php echo $company['active_drives']; ?> active
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Company Modal -->
    <div id="companyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Add Company</h3>
                    <button onclick="closeCompanyModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="companyForm" method="POST">
                    <input type="hidden" id="company_id" name="company_id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                            <input type="text" id="name" name="name" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select id="type" name="type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Type</option>
                                <option value="IT">IT</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Finance">Finance</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                            <input type="text" id="location" name="location" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">Contact Email *</label>
                            <input type="email" id="contact_email" name="contact_email" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                            <input type="tel" id="contact_phone" name="contact_phone" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                            <input type="url" id="website" name="website" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    
                    <div id="verifiedSection" class="mt-4 hidden">
                        <div class="flex items-center">
                            <input type="checkbox" id="verified" name="verified" 
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="verified" class="ml-2 block text-sm text-gray-900">Verified Company</label>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeCompanyModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn" name="add_company" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium">
                            Add Company
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

        function showAddCompanyModal() {
            document.getElementById('modalTitle').textContent = 'Add Company';
            document.getElementById('submitBtn').textContent = 'Add Company';
            document.getElementById('submitBtn').name = 'add_company';
            document.getElementById('verifiedSection').classList.add('hidden');
            document.getElementById('companyForm').reset();
            document.getElementById('companyModal').classList.remove('hidden');
        }

        function editCompany(companyId) {
            // In a real implementation, fetch company data via AJAX
            document.getElementById('modalTitle').textContent = 'Edit Company';
            document.getElementById('submitBtn').textContent = 'Update Company';
            document.getElementById('submitBtn').name = 'update_company';
            document.getElementById('company_id').value = companyId;
            document.getElementById('verifiedSection').classList.remove('hidden');
            document.getElementById('companyModal').classList.remove('hidden');
        }

        function closeCompanyModal() {
            document.getElementById('companyModal').classList.add('hidden');
        }

        function deleteCompany(companyId) {
            if (confirm('Are you sure you want to delete this company? This action cannot be undone.')) {
                // In a real implementation, send delete request via AJAX
                alert('Delete functionality would be implemented here');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            if (!event.target.closest('[onclick="toggleUserMenu()"]')) {
                userMenu.classList.add('hidden');
            }
        });

        // Close modal when clicking outside
        document.getElementById('companyModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeCompanyModal();
            }
        });
    </script>
</body>
</html>
