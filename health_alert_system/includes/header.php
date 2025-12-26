<?php
// Include reusable components
require_once __DIR__ . '/components.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Health Alert System</title>
    <meta name="description" content="Smart Health Monitoring System for patients, doctors, and administrators">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateY(-10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        /* Focus styles */
        .focus-ring:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
            box-shadow: 0 0 0 2px #3b82f6, 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900 hidden sm:block">Health Alert System</h1>
                        <h1 class="text-lg font-bold text-gray-900 sm:hidden">HAS</h1>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Navigation Menu -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <?php
                        $current_page = basename($_SERVER['PHP_SELF']);
                        $role = $_SESSION['role'];
                        
                        // Define navigation items based on role
                        $nav_items = [];
                        
                        if ($role === 'patient') {
                            $nav_items = [
                                ['dashboard.php', 'Dashboard', 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M9 9h6v6H9z'],
                                ['add_health_data.php', 'Add Data', 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
                                ['health_history.php', 'History', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                                ['alerts.php', 'Alerts', 'M15 17h5l-5 5-5-5h5v-5a7.5 7.5 0 0 0-15 0v5h5l-5 5-5-5h5V7a9.5 9.5 0 0 1 19 0v10z']
                            ];
                        } elseif ($role === 'doctor') {
                            $nav_items = [
                                ['dashboard.php', 'Dashboard', 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M9 9h6v6H9z'],
                                ['patient_list.php', 'Patients', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                                ['send_alert.php', 'Send Alert', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                                ['sent_alerts.php', 'Sent Alerts', 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z']
                            ];
                        } elseif ($role === 'admin') {
                            $nav_items = [
                                ['dashboard.php', 'Dashboard', 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M9 9h6v6H9z'],
                                ['doctor_approvals.php', 'Approvals', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                ['assign_patients.php', 'Assignments', 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'],
                                ['doctor_list.php', 'Doctors', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                ['patient_list.php', 'Patients', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z']
                            ];
                        }
                        
                        foreach ($nav_items as $item):
                            $is_active = ($current_page === $item[0]);
                            $base_classes = "px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center";
                            $active_classes = $is_active ? "bg-primary-100 text-primary-700 shadow-sm" : "text-gray-600 hover:text-primary-600 hover:bg-gray-100";
                        ?>
                            <a href="<?php echo $item[0]; ?>" class="<?php echo $base_classes . ' ' . $active_classes; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $item[2]; ?>"></path>
                                </svg>
                                <?php echo $item[1]; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <!-- User Info -->
                    <div class="hidden sm:flex items-center space-x-3">
                        <div class="flex flex-col text-right">
                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                            <span class="text-xs text-gray-500 capitalize"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                        </div>
                        <div class="w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-medium text-sm">
                                <?php echo strtoupper(substr($_SESSION['name'], 0, 2)); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Logout Button -->
                    <a href="../logout.php" 
                       class="bg-danger-500 hover:bg-danger-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center focus-ring">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button type="button" 
                            class="bg-gray-100 inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-primary-600 hover:bg-gray-200 focus-ring"
                            onclick="toggleMobileMenu()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
                <?php else: ?>
                <!-- Guest Navigation -->
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="text-gray-600 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        Login
                    </a>
                    <a href="../register.php" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors focus-ring">
                        Register
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-gray-50 border-t border-gray-200">
                <?php foreach ($nav_items as $item):
                    $is_active = ($current_page === $item[0]);
                    $base_classes = "block px-3 py-2 rounded-md text-base font-medium transition-colors";
                    $active_classes = $is_active ? "bg-primary-100 text-primary-700" : "text-gray-600 hover:text-primary-600 hover:bg-gray-100";
                ?>
                    <a href="<?php echo $item[0]; ?>" class="<?php echo $base_classes . ' ' . $active_classes; ?>">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $item[2]; ?>"></path>
                            </svg>
                            <?php echo $item[1]; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <!-- Mobile User Info -->
                <div class="border-t border-gray-200 pt-4 pb-3">
                    <div class="flex items-center px-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-medium">
                                <?php echo strtoupper(substr($_SESSION['name'], 0, 2)); ?>
                            </span>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                            <div class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8 animate-fade-in">