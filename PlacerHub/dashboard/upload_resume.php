<?php
require_once '../config/config.php';
require_once '../includes/upload_handler.php';
requireLogin();

if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$db = getDB();
$error = '';
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resume'])) {
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploader = new FileUploadHandler();
        $result = $uploader->uploadFile($_FILES['resume'], 'resume', $_SESSION['user_id']);
        
        if ($result['success']) {
            try {
                // Delete old resume if exists
                $stmt = $db->prepare("SELECT resume FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $old_resume = $stmt->fetch()['resume'];
                
                if ($old_resume) {
                    $uploader->deleteFile($old_resume, 'resume');
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE users SET resume = ? WHERE id = ?");
                $stmt->execute([$result['filename'], $_SESSION['user_id']]);
                
                // Log activity
                $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id) VALUES (?, 'uploaded resume', 'users', ?)");
                $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                
                $success = 'Resume uploaded successfully!';
            } catch (Exception $e) {
                $error = 'Failed to save resume information.';
                logError($e->getMessage());
            }
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please select a resume file to upload.';
    }
}

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploader = new FileUploadHandler();
        $result = $uploader->uploadFile($_FILES['photo'], 'photo', $_SESSION['user_id']);
        
        if ($result['success']) {
            try {
                // Delete old photo if exists
                $stmt = $db->prepare("SELECT profile_photo FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $old_photo = $stmt->fetch()['profile_photo'];
                
                if ($old_photo) {
                    $uploader->deleteFile($old_photo, 'photo');
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$result['filename'], $_SESSION['user_id']]);
                
                $success = 'Profile photo updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to save photo information.';
                logError($e->getMessage());
            }
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please select a photo file to upload.';
    }
}

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - <?php echo SITE_NAME; ?></title>
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
                    <a href="applications.php" class="text-gray-700 hover:text-indigo-600">My Applications</a>
                    <a href="notifications.php" class="text-gray-700 hover:text-indigo-600">
                        <i class="fas fa-bell"></i>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center text-gray-700 hover:text-indigo-600">
                            <?php if ($user['profile_photo']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/photos/<?php echo $user['profile_photo']; ?>" 
                                     alt="Profile" class="w-8 h-8 rounded-full mr-2 object-cover">
                            <?php else: ?>
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=6366f1&color=fff" 
                                     alt="Profile" class="w-8 h-8 rounded-full mr-2">
                            <?php endif; ?>
                            <span class="hidden md:block"><?php echo $user['name']; ?></span>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Upload Documents</h1>
            <p class="text-gray-600">Upload your resume and profile photo</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Resume Upload -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Resume Upload</h2>
                </div>
                <div class="p-6">
                    <?php if ($user['resume']): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-file-pdf text-red-600 text-2xl mr-3"></i>
                                    <div>
                                        <p class="font-medium text-gray-900">Current Resume</p>
                                        <p class="text-sm text-gray-600"><?php echo $user['resume']; ?></p>
                                    </div>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/uploads/resumes/<?php echo $user['resume']; ?>" 
                                   target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="resume" class="block text-sm font-medium text-gray-700 mb-2">
                                Select Resume File
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-indigo-400 transition duration-150">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-file-upload text-4xl text-gray-400 mb-4"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="resume" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload a file</span>
                                            <input id="resume" name="resume" type="file" class="sr-only" accept=".pdf,.doc,.docx" onchange="showFileName(this, 'resume-name')">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, DOC, DOCX up to 5MB</p>
                                    <p id="resume-name" class="text-sm text-indigo-600 font-medium"></p>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="upload_resume" 
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium">
                            <i class="fas fa-upload mr-2"></i>Upload Resume
                        </button>
                    </form>
                </div>
            </div>

            <!-- Profile Photo Upload -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Profile Photo</h2>
                </div>
                <div class="p-6">
                    <div class="text-center mb-6">
                        <?php if ($user['profile_photo']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/photos/<?php echo $user['profile_photo']; ?>" 
                                 alt="Profile" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-gray-200">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=6366f1&color=fff&size=128" 
                                 alt="Profile" class="w-32 h-32 rounded-full mx-auto mb-4">
                        <?php endif; ?>
                        <p class="text-sm text-gray-600">Current profile photo</p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">
                                Select Photo File
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-indigo-400 transition duration-150">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-camera text-4xl text-gray-400 mb-4"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="photo" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload a photo</span>
                                            <input id="photo" name="photo" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.gif" onchange="showFileName(this, 'photo-name')">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">JPG, PNG, GIF up to 2MB</p>
                                    <p id="photo-name" class="text-sm text-indigo-600 font-medium"></p>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="upload_photo" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium">
                            <i class="fas fa-camera mr-2"></i>Upload Photo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Upload Guidelines -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">
                <i class="fas fa-info-circle mr-2"></i>Upload Guidelines
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-blue-800">
                <div>
                    <h4 class="font-medium mb-2">Resume Guidelines:</h4>
                    <ul class="space-y-1">
                        <li>• Use PDF format for best compatibility</li>
                        <li>• Keep file size under 5MB</li>
                        <li>• Include contact information</li>
                        <li>• List relevant skills and experience</li>
                        <li>• Use a professional format</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-2">Photo Guidelines:</h4>
                    <ul class="space-y-1">
                        <li>• Use a professional headshot</li>
                        <li>• Ensure good lighting and clarity</li>
                        <li>• Keep file size under 2MB</li>
                        <li>• Use JPG or PNG format</li>
                        <li>• Maintain appropriate dress code</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function showFileName(input, displayId) {
            const display = document.getElementById(displayId);
            if (input.files && input.files[0]) {
                display.textContent = input.files[0].name;
            } else {
                display.textContent = '';
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            if (!event.target.closest('[onclick="toggleUserMenu()"]')) {
                userMenu.classList.add('hidden');
            }
        });

        // Drag and drop functionality
        ['resume', 'photo'].forEach(type => {
            const dropZone = document.querySelector(`label[for="${type}"]`).closest('.border-dashed');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('border-indigo-400', 'bg-indigo-50'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-indigo-400', 'bg-indigo-50'), false);
            });

            dropZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.getElementById(type).files = files;
                    showFileName(document.getElementById(type), `${type}-name`);
                }
            }, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
    </script>
</body>
</html>
