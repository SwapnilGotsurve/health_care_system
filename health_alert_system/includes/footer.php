    </main>

    <!-- Scroll to Top Button -->
    <button id="scroll-to-top" class="no-print" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Scroll to top">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </button>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand Section -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Health Alert System</h3>
                    </div>
                    <p class="text-gray-600 text-sm mb-4 max-w-md">
                        A comprehensive health monitoring platform connecting patients, doctors, and administrators 
                        for better healthcare management and communication.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-primary-500 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary-500 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary-500 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['role'] === 'patient'): ?>
                                <li><a href="../patient/dashboard.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Dashboard</a></li>
                                <li><a href="../patient/add_health_data.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Add Health Data</a></li>
                                <li><a href="../patient/health_history.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Health History</a></li>
                                <li><a href="../patient/alerts.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">View Alerts</a></li>
                            <?php elseif ($_SESSION['role'] === 'doctor'): ?>
                                <li><a href="../doctor/dashboard.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Dashboard</a></li>
                                <li><a href="../doctor/patient_list.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">My Patients</a></li>
                                <li><a href="../doctor/send_alert.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Send Alert</a></li>
                                <li><a href="../doctor/sent_alerts.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Sent Alerts</a></li>
                            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                                <li><a href="../admin/dashboard.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Dashboard</a></li>
                                <li><a href="../admin/doctor_approvals.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Doctor Approvals</a></li>
                                <li><a href="../admin/doctor_list.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">All Doctors</a></li>
                                <li><a href="../admin/patient_list.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">All Patients</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="../index.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Login</a></li>
                            <li><a href="../register.php" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Help Center</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-primary-600 text-sm transition-colors">Contact Us</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center space-x-4 mb-4 md:mb-0">
                        <p class="text-gray-500 text-sm">
                            &copy; <?php echo date('Y'); ?> Health Alert System. All rights reserved.
                        </p>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            System Status: Online
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="text-sm text-gray-500">
                            Last login: <?php echo date('M j, Y g:i A'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- User-Friendly JavaScript Enhancements -->
    <script src="../assets/js/user-friendly.js"></script>
    
    <!-- Mobile Menu Toggle Script -->
    <script>
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileButton = event.target.closest('button');
            
            if (!mobileMenu.contains(event.target) && !mobileButton) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Add smooth scrolling for anchor links
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

        // Add fade-in animation for page content
        document.addEventListener('DOMContentLoaded', function() {
            const main = document.querySelector('main');
            if (main) {
                main.style.opacity = '0';
                main.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    main.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    main.style.opacity = '1';
                    main.style.transform = 'translateY(0)';
                }, 100);
            }
        });

        // Enhanced notification system with better UX
        window.showNotification = function(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full max-w-sm`;
            
            const colors = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white',
                warning: 'bg-yellow-500 text-white',
                info: 'bg-blue-500 text-white'
            };
            
            const icons = {
                success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
                info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };
            
            notification.className += ` ${colors[type] || colors.info}`;
            notification.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        ${icons[type] || icons.info}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Slide in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }, 5000);
        };

        // Global error handler for better user experience
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            if (window.HealthAlertSystem && window.HealthAlertSystem.showToast) {
                window.HealthAlertSystem.showToast('An unexpected error occurred. Please refresh the page.', 'error');
            }
        });

        // Handle network errors gracefully
        window.addEventListener('online', function() {
            showNotification('Connection restored', 'success');
        });

        window.addEventListener('offline', function() {
            showNotification('Connection lost. Please check your internet connection.', 'warning');
        });
    </script>
</body>
</html>