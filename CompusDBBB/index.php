<?php
require_once 'config/config.php';

// Redirect to appropriate dashboard if logged in
$auth = new Auth();
if ($auth->isLoggedIn()) {
    $redirect = $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php';
    header('Location: ' . BASE_URL . $redirect);
    exit;
}

// Get system settings
$siteName = getSystemSetting('site_name', APP_NAME);
$registrationEnabled = getSystemSetting('registration_enabled', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $siteName; ?> - Database Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 25%, #7c3aed 50%, #c026d3 75%, #e11d48 100%);
        }
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M40 40L20 20v40h40V20z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="gradient-bg hero-pattern min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg border-b border-white border-opacity-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-database text-2xl text-white mr-3"></i>
                        <span class="text-xl font-bold text-white"><?php echo $siteName; ?></span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="text-white hover:text-pink-200 px-4 py-2 rounded-lg transition duration-200 hover:bg-white hover:bg-opacity-10">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <?php if ($registrationEnabled): ?>
                    <a href="register.php" class="bg-gradient-to-r from-pink-500 to-rose-500 text-white hover:from-pink-600 hover:to-rose-600 px-6 py-2 rounded-lg font-semibold transition duration-200 shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i>Sign Up
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="glass-card rounded-3xl p-12 mb-16">
                <h1 class="text-5xl font-bold text-white mb-6">
                    Powerful Database Management
                </h1>
                <p class="text-xl text-pink-100 mb-8 max-w-3xl mx-auto">
                    Streamline your data operations with our comprehensive management system. 
                    Built for efficiency, security, and scalability.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="login.php" class="bg-gradient-to-r from-pink-500 to-rose-500 text-white hover:from-pink-600 hover:to-rose-600 px-8 py-4 rounded-lg font-semibold text-lg transition duration-200 transform hover:scale-105 shadow-xl">
                        <i class="fas fa-rocket mr-2"></i>Get Started
                    </a>
                    <?php if ($registrationEnabled): ?>
                    <a href="register.php" class="border-2 border-white text-white hover:bg-white hover:text-purple-600 px-8 py-4 rounded-lg font-semibold text-lg transition duration-200 hover:shadow-xl">
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Powerful Features</h2>
                <p class="text-xl text-pink-100">Everything you need to manage your data effectively</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- User Management -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl">
                    <div class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 text-4xl mb-4">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">User Management</h3>
                    <p class="text-gray-600">
                        Complete user authentication system with role-based access control, 
                        profile management, and security features.
                    </p>
                </div>

                <!-- Data Operations -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl">
                    <div class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 text-4xl mb-4">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Data Operations</h3>
                    <p class="text-gray-600">
                        Full CRUD operations with advanced search, filtering, pagination, 
                        and real-time updates without page reloads.
                    </p>
                </div>

                <!-- Reports & Analytics -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl">
                    <div class="text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-blue-600 text-4xl mb-4">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Reports & Analytics</h3>
                    <p class="text-gray-600">
                        Generate comprehensive reports and export data in multiple formats 
                        including PDF, CSV, and Excel.
                    </p>
                </div>

                <!-- Security -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl">
                    <div class="text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-pink-600 text-4xl mb-4">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Security</h3>
                    <p class="text-gray-600">
                        Advanced security features including password encryption, 
                        session management, and activity logging.
                    </p>
                </div>

                <!-- Support System -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl">
                    <div class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-600 to-orange-600 text-4xl mb-4">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Support System</h3>
                    <p class="text-gray-600">
                        Built-in ticket system for user support with tracking, 
                        notifications, and feedback management.
                    </p>
                </div>

                <!-- Admin Dashboard -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl">
                    <div class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 text-4xl mb-4">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Admin Dashboard</h3>
                    <p class="text-gray-600">
                        Comprehensive admin panel with system management, 
                        user control, and backup & restore capabilities.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass-card rounded-3xl p-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                    <div>
                        <div class="text-4xl font-bold text-white mb-2">
                            <i class="fas fa-users text-pink-200 mr-2"></i>100%
                        </div>
                        <p class="text-pink-100">Secure Authentication</p>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-white mb-2">
                            <i class="fas fa-mobile-alt text-pink-200 mr-2"></i>100%
                        </div>
                        <p class="text-pink-100">Mobile Responsive</p>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-white mb-2">
                            <i class="fas fa-clock text-pink-200 mr-2"></i>24/7
                        </div>
                        <p class="text-pink-100">System Availability</p>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-white mb-2">
                            <i class="fas fa-shield-alt text-pink-200 mr-2"></i>100%
                        </div>
                        <p class="text-pink-100">Data Protection</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="glass-card rounded-3xl p-12">
                <h2 class="text-3xl font-bold text-white mb-6">Ready to Get Started?</h2>
                <p class="text-xl text-pink-100 mb-8">
                    Join thousands of users who trust our platform for their data management needs.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="login.php" class="bg-gradient-to-r from-pink-500 to-rose-500 text-white hover:from-pink-600 hover:to-rose-600 px-8 py-4 rounded-lg font-semibold text-lg transition duration-200 transform hover:scale-105 shadow-xl">
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In Now
                    </a>
                    <?php if ($registrationEnabled): ?>
                    <a href="register.php" class="border-2 border-white text-white hover:bg-white hover:text-purple-600 px-8 py-4 rounded-lg font-semibold text-lg transition duration-200 hover:shadow-xl">
                        <i class="fas fa-user-plus mr-2"></i>Create Free Account
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-gray-900 via-purple-900 to-gray-900 py-16 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="md:col-span-2">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-database text-3xl text-pink-400 mr-3"></i>
                        <span class="text-2xl font-bold text-white"><?php echo $siteName; ?></span>
                    </div>
                    <p class="text-gray-300 mb-6 max-w-md">
                        A comprehensive database management system designed for modern businesses. 
                        Secure, scalable, and user-friendly platform for all your data management needs.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="bg-gradient-to-r from-pink-500 to-rose-500 text-white p-3 rounded-full hover:from-pink-600 hover:to-rose-600 transition duration-200">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white p-3 rounded-full hover:from-blue-600 hover:to-cyan-600 transition duration-200">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="bg-gradient-to-r from-purple-500 to-indigo-500 text-white p-3 rounded-full hover:from-purple-600 hover:to-indigo-600 transition duration-200">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="bg-gradient-to-r from-gray-700 to-gray-800 text-white p-3 rounded-full hover:from-gray-800 hover:to-gray-900 transition duration-200">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="login.php" class="text-gray-300 hover:text-pink-400 transition duration-200">Login</a></li>
                        <li><a href="register.php" class="text-gray-300 hover:text-pink-400 transition duration-200">Register</a></li>
                        <li><a href="forgot-password.php" class="text-gray-300 hover:text-pink-400 transition duration-200">Forgot Password</a></li>
                        <li><a href="#features" class="text-gray-300 hover:text-pink-400 transition duration-200">Features</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Support</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-300 hover:text-pink-400 transition duration-200">Help Center</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-pink-400 transition duration-200">Documentation</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-pink-400 transition duration-200">Contact Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-pink-400 transition duration-200">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-pink-400 transition duration-200">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div class="border-t border-gray-700 mt-12 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm mb-4 md:mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. All rights reserved. Made with ❤️ for better data management.
                    </p>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center text-gray-400 text-sm">
                            <i class="fas fa-shield-alt text-green-400 mr-2"></i>
                            <span>SSL Secured</span>
                        </div>
                        <div class="flex items-center text-gray-400 text-sm">
                            <i class="fas fa-clock text-blue-400 mr-2"></i>
                            <span>24/7 Support</span>
                        </div>
                        <div class="flex items-center text-gray-400 text-sm">
                            <i class="fas fa-database text-purple-400 mr-2"></i>
                            <span>99.9% Uptime</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
