<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$admin = $_SESSION['admin'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindIT Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .notification-dot {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
            transition: all 0.3s ease;
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .activity-item {
            transition: all 0.3s ease;
        }
        .activity-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
        }
    </style>
</head>
<body class="text-white overflow-hidden" style="background-color: black;">
    <div class="flex h-screen">
        <div class="w-60 p-6 shadow-2xl" style="background: linear-gradient(to bottom, #406F91, #13212B);">
            <img src="assets/dashboard/images/logo-dashboard.png" alt="FindIT Logo"
                style="height: 120px; width: 250px;" />
                <br>
            <!-- Avatar with Status & Label -->
            <div class="flex items-center space-x-3">
                <div class="relative inline-block">
                <img src="assets/dashboard/images/woman.png" alt="Admin Avatar"
                    class="w-16 h-16 rounded-full border-2 border-white object-cover" />
                </div>
                <span class="text-black text-lg font-medium">Admin</span>
            </div>
            <br>
            <!-- Navigation Menu -->
            <nav class="space-y-2">
                <div class="sidebar-item px-4 py-3 rounded-lg bg-white bg-opacity-20 flex items-center space-x-3">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <a href="dashboard.php" style="text-decoration: none;">Dashboard</a>
                    <div class="ml-auto bg-red-500 text-xs px-2 py-1 rounded-full">10</div>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-paper-plane w-5"></i>
                    <span>Request</span>
                    <div class="ml-auto bg-orange-500 text-xs px-2 py-1 rounded-full">5</div>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Reports</span>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <a href="merit_shop.php" style="text-decoration: none;">Merit Shop</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-users w-5"></i>
                    <span>Accounts</span>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fa-solid fa-right-from-bracket"></i>                    
                    <a href="logout.php" style="text-decoration: none;">Logout</a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6 overflow-y-auto">
            <!-- Header Section -->
            <div class="flex justify-between items-start mb-8">
                <div class="text-4xl font-bold text-white">Dashboard</div>
                <div class="flex items-center space-x-6">
                    <!-- Time and Date -->
                    <div class="text-right">
                        <div class="text-2xl font-bold">8:45pm</div>
                        <div class="text-sm text-gray-400">April 1, 2025</div>
                    </div>
                    <!-- Notification -->
                    <div class="relative">
                        <button class="bg-gray-800 p-3 rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-calendar text-white text-xl"></i>
                        </button>
                        <span class="notification-dot absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-xs text-white rounded-full flex items-center justify-center">1</span>
                    </div>
                </div>
            </div>

            <!-- Top Notifications Panel -->
            <div class="bg-gray-800 rounded-xl p-4 mb-6 border border-gray-700">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                        <span><strong class="text-blue-400">Junjun Dela Cruz</strong> is requesting to approve the post in <strong class="text-green-400">cellphone</strong> listing.</span>
                        <span class="text-xs text-gray-400">09:45 Pm</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                        <span><strong class="text-blue-400">Naja Chu Evangelista</strong> is claiming the cellphone mentioned in Junjun Dela Cruz's <strong class="text-yellow-400">post</strong></span>
                        <span class="text-xs text-gray-400">09:45 Pm</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                        <span><strong class="text-blue-400">Marivic De Guzman</strong> surrendered a <strong class="text-green-400">wallet</strong> to authorities in Brgy. Concepcion.</span>
                        <span class="text-xs text-gray-400">09:45 Pm</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-6">
                <!-- Statistics Chart -->
                <div class="col-span-8 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Statistics</h2>
                        <div class="flex space-x-2">
                            <button class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">Found</button>
                            <button class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">Lost</button>
                        </div>
                    </div>
                    <canvas id="statisticsChart" class="w-full h-64"></canvas>
                </div>

                <!-- Right Side Panels -->
                <div class="col-span-4 space-y-6">
                    <!-- Reviews -->
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="relative inline-block">
                            Reviews
                            <span class="absolute top-0 right-0 bg-red-500 text-xs text-white px-2 py-1 rounded-full" style="top: -10px!important; right: -25px !important">2</span>
                            </h3>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-sm"><img src="assets/dashboard/images/user.png" alt=""></div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium">helpful application ❤️</div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-yellow-400">★★★★★</span>
                                        <span class="text-xs text-gray-400">4.5</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-sm"><img src="assets/dashboard/images/user.png" alt=""></div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium">love it!, easy to used</div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-yellow-400">★★★★★</span>
                                        <span class="text-xs text-gray-400">4.5</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-sm"><img src="assets/dashboard/images/user.png" alt=""></div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium">because of this app, I found my lost dog and thank you so much</div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-yellow-400">★★★★</span>
                                        <span class="text-xs text-gray-400">2.8</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-sm"><img src="assets/dashboard/images/user.png" alt=""></div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium">this is a great app for lost and found.</div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-yellow-400">★★★★</span>
                                        <span class="text-xs text-gray-400">4.2</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Summary with Stats -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="bg-gray-700 text-lg font-semibold" style="padding: 10px;">Activity Summary</h3>
                            <div class="flex space-x-2 text-xs">
                                <p>Filter</p>
                                <button class="bg-gray-700 px-2 py-1 rounded">Week</button>
                                <button class=" px-2 py-1 rounded">Month</button>
                                <button class=" px-2 py-1 rounded">Year</button>
                            </div>
                        </div>
                        
                        <!-- Stat Cards -->
                        <div class="grid grid-cols-1 gap-3">
                            <div class="stat-card bg-red-600 rounded-lg p-4 relative overflow-hidden">
                                <div class="text-right">
                                    <div class="text-3xl font-bold">23</div>
                                    <div class="text-xs opacity-90">Item Reported Lost</div>
                                </div>
                                <i class="fas fa-arrow-up absolute top-2 right-2 text-xs opacity-70"></i>
                            </div>
                            <div class="stat-card bg-green-600 rounded-lg p-4 relative overflow-hidden">
                                <div class="text-right">
                                    <div class="text-3xl font-bold">18</div>
                                    <div class="text-xs opacity-90">Item Reported Found</div>
                                </div>
                                <i class="fas fa-arrow-up absolute top-2 right-2 text-xs opacity-70"></i>
                            </div>
                            <div class="stat-card bg-blue-600 rounded-lg p-4 relative overflow-hidden">
                                <div class="text-right">
                                    <div class="text-3xl font-bold">3</div>
                                    <div class="text-xs opacity-90">Item Returned</div>
                                </div>
                                <i class="fas fa-arrow-up absolute top-2 right-2 text-xs opacity-70"></i>
                            </div>
                            <div class="stat-card bg-gray-600 rounded-lg p-4 relative overflow-hidden">
                                <div class="text-right">
                                    <div class="text-3xl font-bold">15</div>
                                    <div class="text-xs opacity-90">Unclaimed Item</div>
                                </div>
                                <i class="fas fa-arrow-down absolute top-2 right-2 text-xs opacity-70"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section -->
                <div class="col-span-8 rounded-xl p-6 border border-gray-700" style="background: linear-gradient(to bottom, #13212B, #13212B);">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-600">
                                    <th class="pb-3 font-medium text-gray-300" style="font-size: 30px;">Category</th>
                                    <th class="pb-3 text-center font-medium text-gray-300" style="font-size: 30px;">Lost</th>
                                    <th class="pb-3 text-center font-medium text-gray-300" style="font-size: 30px;">Found</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">Keys</td>
                                    <td class="py-3 text-center">20</td>
                                    <td class="py-3 text-center">16</td>
                                </tr>
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">Electronics</td>
                                    <td class="py-3 text-center">17</td>
                                    <td class="py-3 text-center">10</td>
                                </tr>
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">Jewelries</td>
                                    <td class="py-3 text-center">10</td>
                                    <td class="py-3 text-center">6</td>
                                </tr>
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">Wallets</td>
                                    <td class="py-3 text-center">15</td>
                                    <td class="py-3 text-center">13</td>
                                </tr>
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">IDs</td>
                                    <td class="py-3 text-center">18</td>
                                    <td class="py-3 text-center">10</td>
                                </tr>
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">Documents</td>
                                    <td class="py-3 text-center">7</td>
                                    <td class="py-3 text-center">4</td>
                                </tr>
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">Bags</td>
                                    <td class="py-3 text-center">5</td>
                                    <td class="py-3 text-center">3</td>
                                </tr>
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">Pets</td>
                                    <td class="py-3 text-center">2</td>
                                    <td class="py-3 text-center">0</td>
                                </tr>
                                <tr>
                                    <td class="py-3">Others</td>
                                    <td class="py-3 text-center">40</td>
                                    <td class="py-3 text-center">20</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- User Counts -->
                <div class="col-span-4">
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-lg font-semibold mb-6">User Counts</h3>
                        <div class="text-center mb-6">
                            <div class="text-5xl font-bold mb-2">5</div>
                            <div class="text-gray-400 text-sm">New Users</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-bold mb-2">25</div>
                            <div class="text-gray-400 text-sm">Total Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        // Add hover effects and interactions
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all items
                document.querySelectorAll('.sidebar-item').forEach(i => {
                    i.classList.remove('bg-white', 'bg-opacity-20');
                });
                // Add active class to clicked item
                this.classList.add('bg-white', 'bg-opacity-20');
            });
        });

        // Update time every second
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            }).toLowerCase();
            document.querySelector('.text-2xl.font-bold').textContent = timeString;
        }
        
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>