<?php
require_once '../config/config.php';
requireLogin();

if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$db = getDB();
$error = '';
$success = '';

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $course = sanitize($_POST['course']);
        $branch = sanitize($_POST['branch']);
        $cgpa = floatval($_POST['cgpa']);
        
        if (empty($name) || empty($phone) || empty($course) || empty($branch)) {
            $error = 'Please fill in all required fields';
        } elseif ($cgpa < 0 || $cgpa > 10) {
            $error = 'CGPA must be between 0 and 10';
        } else {
            try {
                $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, course = ?, branch = ?, cgpa = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $course, $branch, $cgpa, $_SESSION['user_id']]);
                
                $_SESSION['name'] = $name;
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
            } catch (Exception $e) {
                $error = 'Failed to update profile. Please try again.';
                logError($e->getMessage());
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all password fields';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                
                $success = 'Password changed successfully!';
                
            } catch (Exception $e) {
                $error = 'Failed to change password. Please try again.';
                logError($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
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
                    <a href="profile.php" class="text-indigo-600 font-medium">Profile</a>
                    <a href="drives.php" class="text-gray-700 hover:text-indigo-600">Job Drives</a>
                    <a href="applications.php" class="text-gray-700 hover:text-indigo-600">My Applications</a>
                    <a href="notifications.php" class="text-gray-700 hover:text-indigo-600">
                        <i class="fas fa-bell"></i>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center text-gray-700 hover:text-indigo-600">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=6366f1&color=fff" 
                                 alt="Profile" class="w-8 h-8 rounded-full mr-2">
                            <span class="hidden md:block"><?php echo $user['name']; ?></span>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a href="settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i>Settings
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Profile Settings</h1>
            <p class="text-gray-600">Manage your account information and preferences</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Picture -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Profile Picture</h2>
                    <div class="text-center">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=6366f1&color=fff&size=150" 
                             alt="Profile" class="w-32 h-32 rounded-full mx-auto mb-4">
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-camera mr-2"></i>Change Photo
                        </button>
                        <p class="text-xs text-gray-500 mt-2">JPG, PNG up to 5MB</p>
                    </div>
                </div>
                
                <!-- Account Status -->
                <div class="bg-white rounded-lg shadow p-6 mt-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Account Status</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Status</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php 
                                switch($user['status']) {
                                    case 'approved': echo 'bg-green-100 text-green-800'; break;
                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                }
                                ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Email Verified</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $user['email_verified'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $user['email_verified'] ? 'Verified' : 'Not Verified'; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Member Since</span>
                            <span class="text-sm text-gray-900"><?php echo formatDate($user['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Personal Information</h2>
                    </div>
                    <div class="p-6">
                        <form method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Full Name *
                                    </label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?php echo $user['name']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address
                                    </label>
                                    <input type="email" id="email" name="email" disabled 
                                           value="<?php echo $user['email']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                                    <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                                </div>
                                
                                <div>
                                    <label for="roll_no" class="block text-sm font-medium text-gray-700 mb-2">
                                        Roll Number
                                    </label>
                                    <input type="text" id="roll_no" name="roll_no" disabled 
                                           value="<?php echo $user['roll_no']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                                    <p class="text-xs text-gray-500 mt-1">Roll number cannot be changed</p>
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number *
                                    </label>
                                    <input type="tel" id="phone" name="phone" required 
                                           value="<?php echo $user['phone']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                
                                <div>
                                    <label for="course" class="block text-sm font-medium text-gray-700 mb-2">
                                        Course *
                                    </label>
                                    <select id="course" name="course" required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select Course</option>
                                        <option value="B.Tech" <?php echo ($user['course'] === 'B.Tech') ? 'selected' : ''; ?>>B.Tech</option>
                                        <option value="M.Tech" <?php echo ($user['course'] === 'M.Tech') ? 'selected' : ''; ?>>M.Tech</option>
                                        <option value="BCA" <?php echo ($user['course'] === 'BCA') ? 'selected' : ''; ?>>BCA</option>
                                        <option value="MCA" <?php echo ($user['course'] === 'MCA') ? 'selected' : ''; ?>>MCA</option>
                                        <option value="MBA" <?php echo ($user['course'] === 'MBA') ? 'selected' : ''; ?>>MBA</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">
                                        Branch *
                                    </label>
                                    <select id="branch" name="branch" required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select Branch</option>
                                        <option value="Computer Science" <?php echo ($user['branch'] === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Information Technology" <?php echo ($user['branch'] === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                        <option value="Electronics" <?php echo ($user['branch'] === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                                        <option value="Mechanical" <?php echo ($user['branch'] === 'Mechanical') ? 'selected' : ''; ?>>Mechanical</option>
                                        <option value="Civil" <?php echo ($user['branch'] === 'Civil') ? 'selected' : ''; ?>>Civil</option>
                                        <option value="Electrical" <?php echo ($user['branch'] === 'Electrical') ? 'selected' : ''; ?>>Electrical</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="cgpa" class="block text-sm font-medium text-gray-700 mb-2">
                                        CGPA
                                    </label>
                                    <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" max="10" 
                                           value="<?php echo $user['cgpa']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" name="update_profile" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md font-medium">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-lg shadow mt-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Change Password</h2>
                    </div>
                    <div class="p-6">
                        <form method="POST">
                            <div class="space-y-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Current Password *
                                    </label>
                                    <input type="password" id="current_password" name="current_password" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        New Password *
                                    </label>
                                    <input type="password" id="new_password" name="new_password" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="text-xs text-gray-500 mt-1">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</p>
                                </div>
                                
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Confirm New Password *
                                    </label>
                                    <input type="password" id="confirm_password" name="confirm_password" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" name="change_password" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md font-medium">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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
