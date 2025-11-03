<?php
require_once '../config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? 'general');
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        
        // Validation
        if (empty($subject) || empty($description)) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($subject) < 5) {
            $error = 'Subject must be at least 5 characters long.';
        } elseif (strlen($description) < 20) {
            $error = 'Description must be at least 20 characters long.';
        } else {
            try {
                // Handle file upload if present
                $fileAttachment = null;
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadFile($_FILES['attachment'], ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'], 10 * 1024 * 1024);
                    if ($uploadResult['success']) {
                        $fileAttachment = $uploadResult['filename'];
                    } else {
                        $error = 'File upload failed: ' . $uploadResult['message'];
                    }
                }
                
                if (empty($error)) {
                    // Insert ticket
                    $stmt = $db->prepare("
                        INSERT INTO tickets (user_id, subject, description, category, priority, file_attachment, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    
                    $stmt->execute([$user['id'], $subject, $description, $category, $priority, $fileAttachment]);
                    $ticketId = $db->lastInsertId();
                    
                    // Log activity
                    $stmt = $db->prepare("
                        INSERT INTO activity_logs (user_id, action, table_name, record_id, new_values, ip_address, user_agent) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $user['id'],
                        'ticket_created',
                        'tickets',
                        $ticketId,
                        json_encode($_POST),
                        $_SERVER['REMOTE_ADDR'] ?? '',
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);
                    
                    // Create notification for user
                    $stmt = $db->prepare("
                        INSERT INTO notifications (user_id, title, message, type) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $user['id'],
                        'Support Ticket Created',
                        "Your support ticket '#{$ticketId}' has been created and is being reviewed by our team.",
                        'success'
                    ]);
                    
                    // Create notification for admins
                    $adminStmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");
                    $adminStmt->execute();
                    $admins = $adminStmt->fetchAll();
                    
                    foreach ($admins as $admin) {
                        $stmt = $db->prepare("
                            INSERT INTO notifications (user_id, title, message, type) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $admin['id'],
                            'New Support Ticket',
                            "A new support ticket '#{$ticketId}' has been created by {$user['full_name']}.",
                            'info'
                        ]);
                    }
                    
                    $success = "Support ticket created successfully! Ticket ID: #{$ticketId}";
                    
                    // Clear form data
                    $_POST = [];
                }
            } catch (Exception $e) {
                $error = 'Failed to create ticket: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Support Ticket';
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
        .form-section { transition: all 0.2s ease; }
        .form-section:hover { transform: translateY(-1px); }
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
                <a href="index.php" class="flex items-center px-4 py-2 text-gray-700 bg-indigo-50 border-r-4 border-indigo-500 rounded-l-lg">
                    <i class="fas fa-headset mr-3"></i>Support
                </a>
                <a href="../profile.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-user mr-3"></i>Profile
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
                    <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Support
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
                    <li><a href="index.php" class="hover:text-gray-700">Support</a></li>
                    <li><i class="fas fa-chevron-right"></i></li>
                    <li class="text-gray-900">Create Ticket</li>
                </ol>
            </nav>

            <!-- Help Banner -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-blue-800 mb-2">Before creating a ticket...</h3>
                        <div class="text-blue-700 space-y-1">
                            <p>• Check our <a href="#" class="underline">FAQ section</a> for common solutions</p>
                            <p>• Search existing tickets to see if your issue has been reported</p>
                            <p>• Provide detailed information to help us resolve your issue faster</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                    <div class="mt-2">
                        <a href="index.php" class="text-green-800 underline">View all tickets</a> |
                        <a href="create.php" class="text-green-800 underline">Create another ticket</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-ticket-alt mr-2 text-indigo-600"></i>Create New Support Ticket
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Provide detailed information about your issue</p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                    Category *
                                </label>
                                <select id="category" name="category" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Select Category</option>
                                    <option value="technical" <?php echo ($_POST['category'] ?? '') === 'technical' ? 'selected' : ''; ?>>Technical Issue</option>
                                    <option value="account" <?php echo ($_POST['category'] ?? '') === 'account' ? 'selected' : ''; ?>>Account Problem</option>
                                    <option value="data_issue" <?php echo ($_POST['data_issue'] ?? '') === 'data_issue' ? 'selected' : ''; ?>>Data Issue</option>
                                    <option value="general" <?php echo ($_POST['category'] ?? '') === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                                </select>
                            </div>

                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                                    Priority
                                </label>
                                <select id="priority" name="priority"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="low" <?php echo ($_POST['priority'] ?? 'medium') === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo ($_POST['priority'] ?? 'medium') === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo ($_POST['priority'] ?? 'medium') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Subject -->
                    <div class="form-section">
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                            Subject *
                        </label>
                        <input type="text" id="subject" name="subject" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="Brief description of your issue"
                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                               minlength="5" maxlength="255">
                        <p class="text-xs text-gray-500 mt-1">Minimum 5 characters, maximum 255 characters</p>
                    </div>

                    <!-- Description -->
                    <div class="form-section">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description *
                        </label>
                        <textarea id="description" name="description" rows="8" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                  placeholder="Please provide detailed information about your issue including:
• What you were trying to do
• What happened instead
• Steps to reproduce the issue
• Any error messages you received"
                                  minlength="20"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Minimum 20 characters. The more details you provide, the faster we can help you.</p>
                    </div>

                    <!-- File Attachment -->
                    <div class="form-section">
                        <label for="attachment" class="block text-sm font-medium text-gray-700 mb-2">
                            Attachment (Optional)
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition duration-200">
                            <input type="file" id="attachment" name="attachment" 
                                   class="hidden" 
                                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                                   onchange="updateFileName(this)">
                            <label for="attachment" class="cursor-pointer">
                                <div class="space-y-2">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium text-indigo-600 hover:text-indigo-500">Click to upload</span>
                                        or drag and drop
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        PNG, JPG, GIF, PDF, DOC, DOCX, TXT up to 10MB
                                    </p>
                                </div>
                            </label>
                            <div id="fileName" class="mt-2 text-sm text-gray-600 hidden"></div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-section bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-800 mb-3">
                            <i class="fas fa-user mr-2 text-gray-600"></i>Contact Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <p class="text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <p class="text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            We'll use this information to contact you about your ticket.
                        </p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            You'll receive email updates about your ticket status
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
                                Cancel
                            </a>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-paper-plane mr-2"></i>Create Ticket
                            </button>
                        </div>
                    </div>
                </form>
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

        function updateFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileName.textContent = 'Selected: ' + input.files[0].name;
                fileName.classList.remove('hidden');
            } else {
                fileName.classList.add('hidden');
            }
        }

        // Character counter for subject and description
        document.getElementById('subject').addEventListener('input', function() {
            const length = this.value.length;
            const minLength = 5;
            if (length < minLength) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#d1d5db';
            }
        });

        document.getElementById('description').addEventListener('input', function() {
            const length = this.value.length;
            const minLength = 20;
            if (length < minLength) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#d1d5db';
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-100, .bg-green-100');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>
