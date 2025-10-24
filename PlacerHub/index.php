<?php
require_once 'config/config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/dashboard/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Placement Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'bounce-in': 'bounceIn 0.8s ease-out'
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-graduation-cap text-3xl text-indigo-600 mr-3"></i>
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo SITE_NAME; ?></h1>
                    </div>
                </div>
                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="#features" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition duration-150">Features</a>
                        <a href="#about" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition duration-150">About</a>
                        <a href="#contact" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition duration-150">Contact</a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="auth/login.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="auth/register.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150">
                        <i class="fas fa-user-plus mr-2"></i>Register
                    </a>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button onclick="toggleMobileMenu()" class="text-gray-700 hover:text-indigo-600 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="#features" class="text-gray-700 hover:text-indigo-600 block px-3 py-2 rounded-md text-base font-medium">Features</a>
                <a href="#about" class="text-gray-700 hover:text-indigo-600 block px-3 py-2 rounded-md text-base font-medium">About</a>
                <a href="#contact" class="text-gray-700 hover:text-indigo-600 block px-3 py-2 rounded-md text-base font-medium">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden min-h-screen flex items-center" 
             style="background-image: linear-gradient(rgba(79, 70, 229, 0.8), rgba(139, 92, 246, 0.8)), url('assets/images/graduation-celebration.jpg.jpg'); 
                    background-size: cover; 
                    background-position: center; 
                    background-attachment: fixed;">
        
        <!-- Background overlay for better text readability -->
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-900/70 to-purple-900/70"></div>
        
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center animate-fade-in">
                <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 animate-slide-up drop-shadow-2xl">
                    Welcome to <span class="text-yellow-300"><?php echo SITE_NAME; ?></span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-100 mb-8 max-w-3xl mx-auto animate-slide-up drop-shadow-lg">
                    Your comprehensive placement management system for seamless recruitment and career opportunities
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center animate-bounce-in">
                    <a href="auth/register.php" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 px-8 py-4 rounded-lg text-lg font-bold transition duration-300 transform hover:scale-105 shadow-2xl">
                        <i class="fas fa-rocket mr-2"></i>Get Started
                    </a>
                    <a href="auth/login.php" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white px-8 py-4 rounded-lg text-lg font-semibold border-2 border-white/50 transition duration-300 transform hover:scale-105 shadow-2xl">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login Now
                    </a>
                </div>
                
                <!-- Success Stats -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-center animate-fade-in">
                        <div class="text-3xl font-bold text-yellow-300 mb-2">
                            <i class="fas fa-users mr-2"></i>1000+
                        </div>
                        <p class="text-gray-200">Students Placed</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-center animate-fade-in" style="animation-delay: 0.2s;">
                        <div class="text-3xl font-bold text-green-300 mb-2">
                            <i class="fas fa-building mr-2"></i>200+
                        </div>
                        <p class="text-gray-200">Partner Companies</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-center animate-fade-in" style="animation-delay: 0.4s;">
                        <div class="text-3xl font-bold text-blue-300 mb-2">
                            <i class="fas fa-percentage mr-2"></i>95%
                        </div>
                        <p class="text-gray-200">Success Rate</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Background decoration -->
        <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-indigo-200 rounded-full opacity-10 animate-pulse"></div>
        <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-60 h-60 bg-purple-200 rounded-full opacity-10 animate-pulse"></div>
        
        <!-- Success particles -->
        <div class="absolute top-20 left-1/4 w-2 h-2 bg-yellow-400 rounded-full opacity-60 animate-bounce" style="animation-delay: 0.5s;"></div>
        <div class="absolute top-32 right-1/3 w-3 h-3 bg-green-400 rounded-full opacity-60 animate-bounce" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-32 left-1/3 w-2 h-2 bg-blue-400 rounded-full opacity-60 animate-bounce" style="animation-delay: 1.5s;"></div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Powerful Features</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Everything you need for effective placement management</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Student Features -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                    <div class="text-center">
                        <div class="bg-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user-graduate text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Student Dashboard</h3>
                        <p class="text-gray-600 mb-4">Comprehensive dashboard for students to track applications, view opportunities, and manage profiles</p>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Profile Management</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Job Applications</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Real-time Notifications</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Admin Features -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-100 p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                    <div class="text-center">
                        <div class="bg-green-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-cogs text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Admin Control</h3>
                        <p class="text-gray-600 mb-4">Complete administrative control over placements, companies, and student management</p>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Drive Management</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Company Relations</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Analytics & Reports</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Communication Features -->
                <div class="bg-gradient-to-br from-purple-50 to-violet-100 p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                    <div class="text-center">
                        <div class="bg-purple-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-comments text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Communication</h3>
                        <p class="text-gray-600 mb-4">Seamless communication between students, admins, and companies</p>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Notification System</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Support Tickets</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Feedback System</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Security Features -->
                <div class="bg-gradient-to-br from-red-50 to-rose-100 p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                    <div class="text-center">
                        <div class="bg-red-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-shield-alt text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Security</h3>
                        <p class="text-gray-600 mb-4">Enterprise-grade security with role-based access control</p>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Secure Authentication</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Data Protection</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Activity Logging</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Mobile Responsive -->
                <div class="bg-gradient-to-br from-yellow-50 to-amber-100 p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                    <div class="text-center">
                        <div class="bg-yellow-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-mobile-alt text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Mobile Ready</h3>
                        <p class="text-gray-600 mb-4">Fully responsive design that works perfectly on all devices</p>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Mobile Optimized</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Touch Friendly</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Fast Loading</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Analytics -->
                <div class="bg-gradient-to-br from-indigo-50 to-blue-100 p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                    <div class="text-center">
                        <div class="bg-indigo-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-bar text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Analytics</h3>
                        <p class="text-gray-600 mb-4">Comprehensive analytics and reporting for data-driven decisions</p>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Placement Statistics</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Performance Metrics</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Export Reports</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">About <?php echo SITE_NAME; ?></h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    <?php echo SITE_NAME; ?> is a comprehensive placement management system designed to streamline 
                    the recruitment process for educational institutions, students, and companies.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">Why Choose <?php echo SITE_NAME; ?>?</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Easy to Use</h4>
                                <p class="text-gray-600">Intuitive interface designed for users of all technical levels</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Comprehensive Features</h4>
                                <p class="text-gray-600">All-in-one solution for placement management needs</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Secure & Reliable</h4>
                                <p class="text-gray-600">Enterprise-grade security and reliable performance</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">24/7 Support</h4>
                                <p class="text-gray-600">Round-the-clock support for all your queries</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <div class="grid grid-cols-2 gap-6 text-center">
                        <div>
                            <div class="text-3xl font-bold text-indigo-600 mb-2">1000+</div>
                            <div class="text-gray-600">Students Placed</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-green-600 mb-2">500+</div>
                            <div class="text-gray-600">Companies</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-purple-600 mb-2">50+</div>
                            <div class="text-gray-600">Institutions</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-red-600 mb-2">99%</div>
                            <div class="text-gray-600">Satisfaction</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Get in Touch</h2>
                <p class="text-xl text-gray-600">Have questions? We're here to help!</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-indigo-100 text-indigo-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-envelope text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Email Us</h3>
                    <p class="text-gray-600">support@placerhub.com</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-green-100 text-green-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-phone text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Call Us</h3>
                    <p class="text-gray-600">+91 12345 67890</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-purple-100 text-purple-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-map-marker-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Visit Us</h3>
                    <p class="text-gray-600">123 Education Street, Tech City</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-graduation-cap text-3xl text-indigo-400 mr-3"></i>
                        <h3 class="text-2xl font-bold"><?php echo SITE_NAME; ?></h3>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Empowering students and institutions with comprehensive placement management solutions.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-150">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-150">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-150">
                            <i class="fab fa-linkedin-in text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-150">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white transition duration-150">Features</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white transition duration-150">About</a></li>
                        <li><a href="auth/login.php" class="text-gray-400 hover:text-white transition duration-150">Login</a></li>
                        <li><a href="auth/register.php" class="text-gray-400 hover:text-white transition duration-150">Register</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-150">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-150">Documentation</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white transition duration-150">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-150">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('shadow-xl');
            } else {
                nav.classList.remove('shadow-xl');
            }
        });
    </script>
</body>
</html>
