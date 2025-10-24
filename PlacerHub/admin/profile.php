<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get current admin details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();

if (!$admin) {
    header('Location: ../auth/login.php');
    exit();
}

// Add session-based profile picture if exists
if (isset($_SESSION['profile_picture'])) {
    $admin['profile_picture'] = $_SESSION['profile_picture'];
}

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required.";
    } else {
        // Check if email is already taken by another user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error_message = "Email is already taken by another user.";
        } else {
            // Update profile (only update existing columns)
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$name, $email, $phone, $user_id])) {
                $success_message = "Profile updated successfully!";
                // Refresh admin data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $admin = $stmt->fetch();
            } else {
                $error_message = "Failed to update profile.";
            }
        }
    }
}

// Handle password change
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long.";
    } elseif (!password_verify($current_password, $admin['password'])) {
        $error_message = "Current password is incorrect.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success_message = "Password changed successfully!";
        } else {
            $error_message = "Failed to change password.";
        }
    }
}

// Handle profile picture upload
if ($_POST && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error_message = "Only JPEG, PNG, and GIF images are allowed.";
        } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $error_message = "File size must be less than 5MB.";
        } else {
            $upload_dir = '../assets/uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'admin_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
           if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
    $new_path = 'assets/uploads/profiles/' . $new_filename;

    // Delete old profile picture if exists
    if (!empty($admin['profile_picture']) && file_exists('../' . $admin['profile_picture'])) {
        unlink('../' . $admin['profile_picture']);
    }

    // ✅ Save new picture path in database
    $stmt = $db->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$new_path, $user_id])) {
        $success_message = "Profile picture uploaded successfully!";
        
        // Update session and local admin data
        $_SESSION['profile_picture'] = $new_path;
        $admin['profile_picture'] = $new_path;
    } else {
        $error_message = "Failed to update profile picture in database.";
    }
} else {
    $error_message = "Failed to upload profile picture.";
}

        }
    } else {
        $error_message = "Please select a valid image file.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .animate-fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <i class="fas fa-graduation-cap text-2xl text-indigo-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900"><?php echo SITE_NAME; ?></span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                        <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                    </a>
                    <a href="students.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                        <i class="fas fa-users mr-1"></i>Students
                    </a>
                    <a href="companies.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                        <i class="fas fa-building mr-1"></i>Companies
                    </a>
                    <a href="profile.php" class="text-indigo-600 bg-indigo-50 px-3 py-2 rounded-md">
                        <i class="fas fa-user mr-1"></i>Profile
                    </a>
                    <a href="../auth/logout.php" class="text-red-600 hover:text-red-700 px-3 py-2 rounded-md">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
<?php
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Admin Profile</h1>
            <p class="mt-2 text-gray-600">Manage your account settings and preferences</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-circle mr-2 mt-0.5"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Picture Section -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Profile Picture</h2>
                    
                    <div class="text-center">
                        <div class="mb-4">
                            <?php if (isset($admin['profile_picture']) && $admin['profile_picture'] && file_exists('../' . $admin['profile_picture'])): ?>
                                <img src="../<?php echo htmlspecialchars($admin['profile_picture']); ?>" 
                                     alt="Profile Picture" 
                                     class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-indigo-100">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full mx-auto bg-indigo-100 flex items-center justify-center border-4 border-indigo-200">
                                    <i class="fas fa-user text-4xl text-indigo-600"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <input type="file" name="profile_picture" accept="image/*" 
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            <button type="submit" name="upload_picture" 
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-upload mr-2"></i>Upload Picture
                            </button>
                        </form>
                        
                        <p class="text-xs text-gray-500 mt-2">
                            Max file size: 5MB. Supported formats: JPEG, PNG, GIF
                        </p>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Account Info</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Role:</span>
                            <span class="font-semibold text-indigo-600 capitalize"><?php echo htmlspecialchars($admin['role']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-semibold text-green-600 capitalize"><?php echo htmlspecialchars($admin['status']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Member Since:</span>
                            <span class="font-semibold"><?php echo date('M Y', strtotime($admin['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="font-semibold"><?php echo date('M d, Y', strtotime($admin['updated_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Profile Information -->
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Profile Information</h2>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" id="name" name="name" required
                                       value="<?php echo htmlspecialchars($admin['name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($admin['email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_profile" 
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Change Password</h2>
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                                <input type="password" id="new_password" name="new_password" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="change_password" 
                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition duration-200">
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
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const passwordForm = document.querySelector('form[method="POST"]:has([name="change_password"])');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('New password must be at least 6 characters long!');
                return false;
            }
        });
    }
    
    // Profile picture preview
    const fileInput = document.querySelector('input[name="profile_picture"]');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('img[alt="Profile Picture"]');
                    if (img) {
                        img.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

    </div>
</div>

</body>
</html>
