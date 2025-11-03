<?php
require_once 'config/config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';
$success = '';
$step = 'email'; // email, code, reset

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');
        
        if ($action === 'send_reset_code') {
            $email = sanitizeInput($_POST['email'] ?? '');
            
            if (empty($email)) {
                $error = 'Please enter your email address.';
            } elseif (!validateEmail($email)) {
                $error = 'Please enter a valid email address.';
            } else {
                try {
                    $db = getDB();
                    
                    // Check if email exists
                    $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ? AND status = 'active'");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        // Generate reset code
                        $resetCode = sprintf('%06d', mt_rand(100000, 999999));
                        $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes
                        
                        // Store reset code in session (in production, use database)
                        $_SESSION['password_reset'] = [
                            'user_id' => $user['id'],
                            'email' => $email,
                            'code' => $resetCode,
                            'expires_at' => $expiresAt,
                            'attempts' => 0
                        ];
                        
                        // In production, send email with reset code
                        // For demo, we'll show the code
                        $success = "A 6-digit reset code has been sent to your email address. For demo purposes, your code is: <strong>{$resetCode}</strong>";
                        $step = 'code';
                        
                        // Log activity
                        $stmt = $db->prepare("
                            INSERT INTO activity_logs (user_id, action, ip_address, user_agent) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $user['id'],
                            'password_reset_requested',
                            $_SERVER['REMOTE_ADDR'] ?? '',
                            $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    } else {
                        // Don't reveal if email exists or not for security
                        $success = "If an account with that email exists, a reset code has been sent.";
                        $step = 'code';
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred. Please try again later.';
                }
            }
        } elseif ($action === 'verify_code') {
            $code = sanitizeInput($_POST['code'] ?? '');
            
            if (empty($code)) {
                $error = 'Please enter the reset code.';
            } elseif (!isset($_SESSION['password_reset'])) {
                $error = 'Reset session expired. Please start over.';
                $step = 'email';
            } else {
                $resetData = $_SESSION['password_reset'];
                
                // Check if expired
                if (time() > strtotime($resetData['expires_at'])) {
                    unset($_SESSION['password_reset']);
                    $error = 'Reset code has expired. Please request a new one.';
                    $step = 'email';
                } elseif ($resetData['attempts'] >= 3) {
                    unset($_SESSION['password_reset']);
                    $error = 'Too many failed attempts. Please request a new reset code.';
                    $step = 'email';
                } elseif ($code !== $resetData['code']) {
                    $_SESSION['password_reset']['attempts']++;
                    $error = 'Invalid reset code. Please try again.';
                    $step = 'code';
                } else {
                    $success = 'Code verified! Please enter your new password.';
                    $step = 'reset';
                }
            }
        } elseif ($action === 'reset_password') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($newPassword) || empty($confirmPassword)) {
                $error = 'Please fill in all fields.';
                $step = 'reset';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match.';
                $step = 'reset';
            } elseif (!isset($_SESSION['password_reset'])) {
                $error = 'Reset session expired. Please start over.';
                $step = 'email';
            } else {
                // Validate password strength
                if (strlen($newPassword) < PASSWORD_MIN_LENGTH ||
                    !preg_match('/[A-Z]/', $newPassword) ||
                    !preg_match('/[a-z]/', $newPassword) ||
                    !preg_match('/[0-9]/', $newPassword) ||
                    !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                    $error = 'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.';
                    $step = 'reset';
                } else {
                    try {
                        $db = getDB();
                        $resetData = $_SESSION['password_reset'];
                        
                        // Update password
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$hashedPassword, $resetData['user_id']]);
                        
                        // Log activity
                        $stmt = $db->prepare("
                            INSERT INTO activity_logs (user_id, action, ip_address, user_agent) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $resetData['user_id'],
                            'password_reset_completed',
                            $_SERVER['REMOTE_ADDR'] ?? '',
                            $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                        
                        // Create notification
                        $stmt = $db->prepare("
                            INSERT INTO notifications (user_id, title, message, type) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $resetData['user_id'],
                            'Password Reset',
                            'Your password has been successfully reset.',
                            'success'
                        ]);
                        
                        // Clear reset session
                        unset($_SESSION['password_reset']);
                        
                        $success = 'Password reset successfully! You can now log in with your new password.';
                        $step = 'complete';
                        
                    } catch (Exception $e) {
                        $error = 'An error occurred while resetting your password. Please try again.';
                        $step = 'reset';
                    }
                }
            }
        }
    }
}

// Check if we have an active reset session
if (isset($_SESSION['password_reset']) && $step === 'email') {
    $step = 'code';
}

$pageTitle = 'Forgot Password';
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
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-active {
            background-color: #4f46e5;
            color: white;
        }
        .step-completed {
            background-color: #10b981;
            color: white;
        }
        .step-pending {
            background-color: #e5e7eb;
            color: #6b7280;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-lg mb-4">
                <i class="fas fa-key text-2xl text-indigo-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Reset Password</h1>
            <p class="text-indigo-100">Recover access to your account</p>
        </div>

        <!-- Step Indicator -->
        <div class="flex justify-center mb-8">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                        <?php echo in_array($step, ['email']) ? 'step-active' : (in_array($step, ['code', 'reset', 'complete']) ? 'step-completed' : 'step-pending'); ?>">
                        <?php echo in_array($step, ['code', 'reset', 'complete']) ? '<i class="fas fa-check"></i>' : '1'; ?>
                    </div>
                    <span class="ml-2 text-sm text-white">Email</span>
                </div>
                <div class="w-8 h-0.5 bg-white bg-opacity-30"></div>
                <div class="flex items-center">
                    <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                        <?php echo in_array($step, ['code']) ? 'step-active' : (in_array($step, ['reset', 'complete']) ? 'step-completed' : 'step-pending'); ?>">
                        <?php echo in_array($step, ['reset', 'complete']) ? '<i class="fas fa-check"></i>' : '2'; ?>
                    </div>
                    <span class="ml-2 text-sm text-white">Verify</span>
                </div>
                <div class="w-8 h-0.5 bg-white bg-opacity-30"></div>
                <div class="flex items-center">
                    <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                        <?php echo in_array($step, ['reset']) ? 'step-active' : (in_array($step, ['complete']) ? 'step-completed' : 'step-pending'); ?>">
                        <?php echo in_array($step, ['complete']) ? '<i class="fas fa-check"></i>' : '3'; ?>
                    </div>
                    <span class="ml-2 text-sm text-white">Reset</span>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div class="glass-effect rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 'email'): ?>
            <!-- Step 1: Email Input -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Enter Your Email</h3>
                <p class="text-gray-600 mb-6">We'll send you a 6-digit code to reset your password.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="send_reset_code">
                    
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email Address
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="Enter your email address">
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-paper-plane mr-2"></i>Send Reset Code
                    </button>
                </form>
            </div>

            <?php elseif ($step === 'code'): ?>
            <!-- Step 2: Code Verification -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Enter Reset Code</h3>
                <p class="text-gray-600 mb-6">Enter the 6-digit code sent to your email address.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="verify_code">
                    
                    <div class="mb-6">
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-shield-alt mr-2"></i>Reset Code
                        </label>
                        <input type="text" id="code" name="code" required maxlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-center text-2xl font-mono"
                               placeholder="000000"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-check mr-2"></i>Verify Code
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <button onclick="location.reload()" class="text-indigo-600 hover:text-indigo-500 text-sm">
                        Didn't receive the code? Try again
                    </button>
                </div>
            </div>

            <?php elseif ($step === 'reset'): ?>
            <!-- Step 3: New Password -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Set New Password</h3>
                <p class="text-gray-600 mb-6">Choose a strong password for your account.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div class="mb-4">
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>New Password
                        </label>
                        <input type="password" id="new_password" name="new_password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="Enter new password"
                               onkeyup="checkPasswordStrength()">
                        <div class="mt-2">
                            <div class="h-2 bg-gray-200 rounded-full">
                                <div id="strengthBar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1" id="strengthText">
                                Password must be at least 8 characters with uppercase, lowercase, number, and special character.
                            </p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirm Password
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="Confirm new password"
                               onkeyup="checkPasswordMatch()">
                        <p class="text-xs mt-1" id="matchText"></p>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-key mr-2"></i>Reset Password
                    </button>
                </form>
            </div>

            <?php elseif ($step === 'complete'): ?>
            <!-- Step 4: Success -->
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-2xl text-green-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Password Reset Complete!</h3>
                <p class="text-gray-600 mb-6">Your password has been successfully reset. You can now log in with your new password.</p>
                
                <a href="login.php" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 inline-block">
                    <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                </a>
            </div>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="login.php" class="text-indigo-600 hover:text-indigo-500 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Login
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-indigo-100 text-sm">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength++;
            else feedback.push('at least 8 characters');
            
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('lowercase letter');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('uppercase letter');
            
            if (/[0-9]/.test(password)) strength++;
            else feedback.push('number');
            
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('special character');
            
            const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];
            
            if (password.length > 0) {
                strengthBar.style.width = widths[strength - 1] || '20%';
                strengthBar.style.backgroundColor = colors[strength - 1] || '#ef4444';
            } else {
                strengthBar.style.width = '0%';
            }
            
            if (feedback.length === 0) {
                strengthText.textContent = 'Strong password!';
                strengthText.className = 'text-xs text-green-600 mt-1';
            } else {
                strengthText.textContent = 'Missing: ' + feedback.join(', ');
                strengthText.className = 'text-xs text-red-600 mt-1';
            }
            
            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('matchText');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchText.textContent = 'Passwords match!';
                matchText.className = 'text-xs text-green-600 mt-1';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'text-xs text-red-600 mt-1';
            }
        }

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
