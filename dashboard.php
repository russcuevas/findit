<?php
session_start();

// 🔒 Admin authentication check
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$admin = $_SESSION['admin'];

// 🔑 Firebase Config
$projectId = 'findit-96080';
$apiKey = 'AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g';

function getUserFullName($userId, $projectId, $apiKey) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users/$userId?key=$apiKey";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return $data['fields']['fullName']['stringValue'] ?? $userId;
}


// fetch notifications
function fetchNotifications($projectId, $apiKey) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/notifications?key=$apiKey";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['documents'] ?? [];
}



// fetch reviews
function fetchReviews($projectId, $apiKey) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/reviews?key=$apiKey";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $reviews = $data['documents'] ?? [];

    // Filter for isPinned = true
    $pinnedReviews = [];
    foreach ($reviews as $doc) {
        $fields = $doc['fields'] ?? [];
        if (isset($fields['isPinned']['booleanValue']) && $fields['isPinned']['booleanValue'] === true) {
            $pinnedReviews[] = $doc;
        }
    }

    return $pinnedReviews;
}


// fetch users
function fetchUsers($projectId, $apiKey) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users?key=$apiKey";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['documents'] ?? [];
}

$users = fetchUsers($projectId, $apiKey);

$totalUsers = 0;
$newUsers = 0;

foreach ($users as $doc) {
    $fields = $doc['fields'] ?? [];
    $isVerified = $fields['isVerified']['booleanValue'] ?? false;
    $createdAt = $fields['createdAt']['timestampValue'] ?? null;

    if ($isVerified) {
        $totalUsers++;

        // ✅ Example: consider "new" if created in the last 7 days
        if ($createdAt) {
            $createdTime = strtotime($createdAt);
            if ($createdTime >= strtotime('-7 days')) {
                $newUsers++;
            }
        }
    }
}



// fetch items
function fetchItems($projectId, $apiKey) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/items?key=$apiKey";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['documents'] ?? [];
}


// fetch graphs
function countByMonth($documents) {
    $foundByMonth = array_fill(1, 12, 0);
    $lostByMonth = array_fill(1, 12, 0);

    foreach ($documents as $doc) {
        $fields = $doc['fields'] ?? [];
        $type = $fields['type']['stringValue'] ?? '';
        $status = $fields['status']['stringValue'] ?? '';
        $createdAt = $fields['createdAt']['timestampValue'] ?? '';

        if ($status !== 'approved' || !in_array($type, ['found', 'lost']) || !$createdAt) continue;
        $timestamp = strtotime($createdAt);
        $month = (int) date('n', $timestamp);

        if ($type === 'found') {
            $foundByMonth[$month]++;
        } else {
            $lostByMonth[$month]++;
        }
    }

    return [$foundByMonth, $lostByMonth];
}

// 🔃 Get data
$documents = fetchItems($projectId, $apiKey);
[$foundByMonth, $lostByMonth] = countByMonth($documents);
$notifications = fetchNotifications($projectId, $apiKey);
$reviews = fetchReviews($projectId, $apiKey);
$categories = [];

foreach ($documents as $doc) {
    $fields = $doc['fields'] ?? [];
    $title = $fields['title']['stringValue'] ?? 'Unknown';
    $type  = $fields['type']['stringValue'] ?? '';
    $status = $fields['status']['stringValue'] ?? '';

    // ✅ Only count approved items
    if ($status !== 'approved') continue;

    if (!isset($categories[$title])) {
        $categories[$title] = ['lost' => 0, 'found' => 0];
    }

    if ($type === 'lost') {
        $categories[$title]['lost']++;
    } elseif ($type === 'found') {
        $categories[$title]['found']++;
    }
}

function filterActivitySummary($items, $claims, $period = 'today') {
    $counts = [
        'lost' => 0,
        'found' => 0,
        'returned' => 0,
        'unclaimed' => 0
    ];

    $now = time();

    // Helper to check period
    $checkPeriod = function($timestamp) use ($period) {
        switch ($period) {
            case 'week':
                return $timestamp >= strtotime('monday this week');
            case 'month':
                return date('Y-m', $timestamp) === date('Y-m');
            case 'year':
                return date('Y', $timestamp) === date('Y');
            case 'today':
            default:
                return date('Y-m-d', $timestamp) === date('Y-m-d');
        }
    };

    // ✅ Process items (lost & found)
    foreach ($items as $doc) {
        $fields = $doc['fields'] ?? [];
        $type = $fields['type']['stringValue'] ?? '';
        $status = $fields['status']['stringValue'] ?? '';
        $createdAt = $fields['createdAt']['timestampValue'] ?? '';

        if (!$createdAt) continue;
        $timestamp = strtotime($createdAt);

        if (!$checkPeriod($timestamp)) continue;

        if ($status === 'approved' && $type === 'lost') {
            $counts['lost']++;
        } elseif ($status === 'approved' && $type === 'found') {
            $counts['found']++;
        }
    }

    // ✅ Process claims (unclaimed & returned)
    foreach ($claims as $doc) {
        $fields = $doc['fields'] ?? [];
        $status = $fields['status']['stringValue'] ?? '';
        $createdAt = $fields['createdAt']['timestampValue'] ?? '';

        if (!$createdAt) continue;
        $timestamp = strtotime($createdAt);

        if (!$checkPeriod($timestamp)) continue;

        if ($status === 'pending') {
            $counts['unclaimed']++;
        } elseif ($status === 'approved') {
            $counts['returned']++;
        }
    }

    return $counts;
}

// 🔃 Get data from API
$itemsUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/items?key=AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g";
$claimsUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/claims?key=AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g";

$itemsData = json_decode(file_get_contents($itemsUrl), true);
$claimsData = json_decode(file_get_contents($claimsUrl), true);

$itemsDocs = $itemsData['documents'] ?? [];
$claimsDocs = $claimsData['documents'] ?? [];

// 🔃 Get selected filter from query (?filter=week,month,year,today)
$filter = $_GET['filter'] ?? 'today';
$activitySummary = filterActivitySummary($itemsDocs, $claimsDocs, $filter);
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
                    <a href="claimant_approval.php" style="text-decoration: none;">Request</a>
                    <div class="ml-auto bg-orange-500 text-xs px-2 py-1 rounded-full">5</div>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-chart-bar w-5"></i>
                    <a href="surrendered_items.php" style="text-decoration: none;">Reports</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <a href="merit_shop.php" style="text-decoration: none;">Merit Shop</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-users w-5"></i>
                    <a href="verified_users.php" style="text-decoration: none;">Accounts</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-cog w-5"></i>
                    <a href="settings.php" style="text-decoration: none;">Settings</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fa-solid fa-right-from-bracket"></i>                    
                    <a href="logout.php" style="text-decoration: none;">Logout</a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6 overflow-y-auto">
            <div class="flex flex-wrap gap-4 items-start text-white">
    <!-- Time + Calendar Panel -->
        <div style="padding: 20px; background-color: #13212B; margin-bottom: 10px; height: 21.5vh;" class="w-full max-w-sm">
            <div class="flex items-center justify-between h-full space-x-4">
                
                <!-- Time and Date Box -->
                <div class="flex-1 p-3 transition-colors text-white leading-tight">
                    <div id="current-time" class="font-semibold text-3xl sm:text-4xl md:text-5xl"></div>
                    <div id="current-date" class="text-gray-400 text-base sm:text-lg md:text-xl"></div>
                </div>

                <script>
                function updateTime() {
                    const now = new Date();

                    // Format time: h:mma
                    let hours = now.getHours();
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    const ampm = hours >= 12 ? 'pm' : 'am';
                    hours = hours % 12 || 12;
                    const timeString = `${hours}:${minutes}${ampm}`;

                    // Format date: Month day, Year
                    const options = { month: 'long', day: 'numeric', year: 'numeric' };
                    const dateString = now.toLocaleDateString('en-US', options);

                    document.getElementById('current-time').textContent = timeString;
                    document.getElementById('current-date').textContent = dateString;
                }

                // Update immediately
                updateTime();
                // Update every second
                setInterval(updateTime, 1000);
                </script>




                <!-- Calendar Icon with Notification -->
                <div class="relative">
                    <button class="bg-gray-800 p-5 rounded-md hover:bg-gray-700 transition-colors">
                        <i style="font-size: 50px;" class="fas fa-calendar text-white text-xl"></i>
                    </button>
                </div>
                
            </div>
        </div>


                <!-- Notifications Panel -->
                <div class="flex-1 min-w-[250px] bg-gray-800 rounded-xl p-4 border border-gray-700 space-y-3" style="background-color: #13212B !important; max-height: 205px; overflow-y: auto;">
                    <?php foreach ($notifications as $doc): 
                        $fields = $doc['fields'] ?? [];
                        $type = $fields['type']['stringValue'] ?? '';

                        if ($type !== 'admin') continue;

                        $message   = $fields['message']['stringValue'] ?? '';
                        $createdAt = $fields['createdAt']['timestampValue'] ?? '';
                        $sentBy    = $fields['sentBy']['stringValue'] ?? '';

                        // ✅ get full name from users table
                        $sentByFullName = getUserFullName($sentBy, $projectId, $apiKey);

                        $time = $createdAt ? date("h:i A", strtotime($createdAt)) : '';
                    ?>
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <span>
                                <strong class="text-blue-400"><?= htmlspecialchars($sentByFullName) ?></strong> <?= htmlspecialchars($message) ?>
                            </span>
                            <span class="text-xs text-gray-400 whitespace-nowrap"><?= $time ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <div class="grid grid-cols-12 gap-6">
                <!-- Statistics Chart -->
            <div class="col-span-8 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold p-4 rounded-xl" style="background-color: #13212B;">Statistics</h2>
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
                                <span class="absolute top-0 right-0 bg-red-500 text-xs text-white px-2 py-1 rounded-full" 
                                    style="top: -10px!important; right: -25px !important">
                                    <?= count($reviews) ?>
                                </span>
                            </h3>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($reviews as $doc): 
                                $fields = $doc['fields'] ?? [];
                                $description = $fields['description']['stringValue'] ?? '';
                                $stars = (int)($fields['stars']['integerValue'] ?? 0);
                                $userId = $fields['userId']['stringValue'] ?? '';
                                
                                // Generate star string ★★★★☆
                                $starDisplay = str_repeat("★", $stars) . str_repeat("☆", 5 - $stars);
                            ?>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-sm">
                                    <img src="assets/dashboard/images/user.png" alt="">
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium"><?= htmlspecialchars($description) ?></div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-yellow-400"><?= $starDisplay ?></span>
                                        <span class="text-xs text-gray-400"><?= number_format($stars, 1) ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <!-- Activity Summary with Stats -->
                    <div class="space-y-4">
                        <form method="get">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold p-4 rounded-xl" style="background-color: #13212B;">Activity Summary</h3>
                                <div class="flex space-x-2 text-xs">
                                    <p>Filter</p>
                                    <a href="?filter=week" class="px-2 py-1 rounded <?= $filter=='week'?'bg-gray-700':'' ?>">Week</a>
                                    <a href="?filter=month" class="px-2 py-1 rounded <?= $filter=='month'?'bg-gray-700':'' ?>">Month</a>
                                    <a href="?filter=year" class="px-2 py-1 rounded <?= $filter=='year'?'bg-gray-700':'' ?>">Year</a>
                                    <a href="?filter=today" class="px-2 py-1 rounded <?= $filter=='today'?'bg-gray-700':'' ?>">Today</a>
                                </div>
                            </div>
                            <br>
                            <!-- Stat Cards -->
                            <div class="grid grid-cols-1 gap-3">
                                <div class="stat-card bg-red-600 rounded-lg p-4 relative overflow-hidden">
                                    <div class="text-right">
                                        <div class="text-3xl font-bold"><?= $activitySummary['lost'] ?></div>
                                        <div class="text-xs opacity-90">Item Reported Lost</div>
                                    </div>
                                </div>
                                <div class="stat-card bg-green-600 rounded-lg p-4 relative overflow-hidden">
                                    <div class="text-right">
                                        <div class="text-3xl font-bold"><?= $activitySummary['found'] ?></div>
                                        <div class="text-xs opacity-90">Item Reported Found</div>
                                    </div>
                                </div>
                                <div class="stat-card bg-blue-600 rounded-lg p-4 relative overflow-hidden">
                                    <div class="text-right">
                                        <div class="text-3xl font-bold"><?= $activitySummary['returned'] ?></div>
                                        <div class="text-xs opacity-90">Item Returned</div>
                                    </div>
                                </div>
                                <div class="stat-card bg-gray-600 rounded-lg p-4 relative overflow-hidden">
                                    <div class="text-right">
                                        <div class="text-3xl font-bold"><?= $activitySummary['unclaimed'] ?></div>
                                        <div class="text-xs opacity-90">Unclaimed Item</div>
                                    </div>
                                </div>
                            </div>
                        </form>
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
            <?php foreach ($categories as $title => $counts): ?>
                <tr class="border-b border-gray-700">
                    <td class="py-3"><?= htmlspecialchars($title) ?></td>
                    <td class="py-3 text-center"><?= $counts['lost'] ?></td>
                    <td class="py-3 text-center"><?= $counts['found'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
                </div>

                <!-- User Counts -->
                <div class="col-span-4">
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-lg font-semibold mb-6">User Counts</h3>
                        <div class="text-center mb-6">
                            <div class="text-5xl font-bold mb-2"><?= $newUsers ?></div>
                            <div class="text-gray-400 text-sm">New Users</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-bold mb-2"><?= $totalUsers ?></div>
                            <div class="text-gray-400 text-sm">Total Users</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('statisticsChart').getContext('2d');

        const foundData = <?= json_encode(array_values($foundByMonth)) ?>;
        const lostData = <?= json_encode(array_values($lostByMonth)) ?>;

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                ],
                datasets: [
                    {
                        label: 'Found',
                        data: foundData,
                        backgroundColor: '#22c55e',
                        borderRadius: 4,
                        hidden: false
                    },
                    {
                        label: 'Lost',
                        data: lostData,
                        backgroundColor: '#fbbf24',
                        borderRadius: 4,
                        hidden: false
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#ccc' }
                    },
                    x: {
                        ticks: { color: '#ccc' }
                    }
                },
                plugins: {
                    legend: {
                        labels: { color: '#ccc' }
                    }
                }
            }
        });

        // Toggle dataset visibility on button click
        document.querySelector('button.bg-green-500').addEventListener('click', () => {
            chart.data.datasets[0].hidden = false;
            chart.data.datasets[1].hidden = true;
            chart.update();
        });

        document.querySelector('button.bg-yellow-500').addEventListener('click', () => {
            chart.data.datasets[0].hidden = true;
            chart.data.datasets[1].hidden = false;
            chart.update();
        });
    </script>
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