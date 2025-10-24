<?php
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $roll_no = sanitize($_POST['roll_no']);
    $phone = sanitize($_POST['phone']);
    $course = sanitize($_POST['course']);
    $branch = sanitize($_POST['branch']);
    $cgpa = floatval($_POST['cgpa']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($roll_no) || empty($phone) || 
        empty($course) || empty($branch) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
    } elseif ($cgpa < 0 || $cgpa > 10) {
        $error = 'CGPA must be between 0 and 10';
    } else {
        try {
            $db = getDB();
            
            if (!$db) {
                throw new Exception('Database connection failed');
            }
            
            // Check if email or roll number already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR roll_no = ?");
            $stmt->execute([$email, $roll_no]);
            if ($stmt->fetch()) {
                $error = 'Email or Roll Number already registered';
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO users (name, email, roll_no, phone, course, branch, cgpa, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $result = $stmt->execute([$name, $email, $roll_no, $phone, $course, $branch, $cgpa, $hashed_password]);
                
                if ($result) {
                    $success = 'Registration successful! Please wait for admin approval to login.';
                    
                    // Clear form data
                    $name = $email = $roll_no = $phone = $course = $branch = '';
                    $cgpa = '';
                } else {
                    $error = 'Failed to save user data. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
            logError('Registration error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-2"><?php echo SITE_NAME; ?></h1>
                <h2 class="text-2xl font-semibold text-gray-700">Create your account</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in here</a>
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow-xl p-8">
                <?php if ($error): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2"></i>Full Name *
                            </label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo isset($name) ? $name : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Enter your full name">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2"></i>Email Address *
                            </label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo isset($email) ? $email : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Enter your email">
                        </div>
                        
                        <div>
                            <label for="roll_no" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-id-card mr-2"></i>Roll Number *
                            </label>
                            <input type="text" id="roll_no" name="roll_no" required 
                                   value="<?php echo isset($roll_no) ? $roll_no : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Enter your roll number">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-phone mr-2"></i>Phone Number *
                            </label>
                            <input type="tel" id="phone" name="phone" required 
                                   value="<?php echo isset($phone) ? $phone : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Enter your phone number">
                        </div>
                        
                        <div>
                            <label for="course" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-graduation-cap mr-2"></i>Course *
                            </label>
                            <select id="course" name="course" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Course</option>
                                <option value="B.Tech" <?php echo (isset($course) && $course === 'B.Tech') ? 'selected' : ''; ?>>B.Tech</option>
                                <option value="M.Tech" <?php echo (isset($course) && $course === 'M.Tech') ? 'selected' : ''; ?>>M.Tech</option>
                                <option value="BCA" <?php echo (isset($course) && $course === 'BCA') ? 'selected' : ''; ?>>BCA</option>
                                <option value="MCA" <?php echo (isset($course) && $course === 'MCA') ? 'selected' : ''; ?>>MCA</option>
                                <option value="MBA" <?php echo (isset($course) && $course === 'MBA') ? 'selected' : ''; ?>>MBA</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-code-branch mr-2"></i>Branch *
                            </label>
                            <select id="branch" name="branch" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Branch</option>
                                <option value="Computer Science" <?php echo (isset($branch) && $branch === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                <option value="Information Technology" <?php echo (isset($branch) && $branch === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                <option value="Electronics" <?php echo (isset($branch) && $branch === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                                <option value="Mechanical" <?php echo (isset($branch) && $branch === 'Mechanical') ? 'selected' : ''; ?>>Mechanical</option>
                                <option value="Civil" <?php echo (isset($branch) && $branch === 'Civil') ? 'selected' : ''; ?>>Civil</option>
                                <option value="Electrical" <?php echo (isset($branch) && $branch === 'Electrical') ? 'selected' : ''; ?>>Electrical</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="cgpa" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-chart-line mr-2"></i>CGPA
                            </label>
                            <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" max="10" 
                                   value="<?php echo isset($cgpa) ? $cgpa : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Enter your CGPA">
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2"></i>Password *
                            </label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Enter password">
                                <button type="button" onclick="togglePassword('password', 'toggleIcon1')" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2"></i>Confirm Password *
                            </label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Confirm password">
                                <button type="button" onclick="togglePassword('confirm_password', 'toggleIcon2')" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="terms" name="terms" required 
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="terms" class="ml-2 block text-sm text-gray-900">
                            I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms of Service</a> 
                            and <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
