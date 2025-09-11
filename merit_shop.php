<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$admin = $_SESSION['admin'];

$projectId = 'findit-96080';
$apiKey = 'AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g';

$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/meritShop?key=$apiKey";

// Fetch data from Firestore
$response = file_get_contents($url);
$data = json_decode($response, true);

// Extract and structure items
$items = [];
$totalItems = 0;
$lowStockCount = 0;

if (isset($data['documents'])) {
    foreach ($data['documents'] as $document) {
        $fields = $document['fields'] ?? [];

        $name = $fields['name']['stringValue'] ?? 'Unnamed';
        $image = $fields['image']['stringValue'] ?? 'assets/shop-items/default.png';
        $stock = (int) ($fields['stock']['integerValue'] ?? 0);
        $itemId = $fields['itemId']['stringValue'] ?? '';

        $items[] = [
            'itemId' => $itemId,
            'name' => $name,
            'image' => $image,
            'stock' => $stock
        ];

        $totalItems++;

        if ($stock < 10) {
            $lowStockCount++;
        }
    }
}

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
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3">
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
                <div class="sidebar-item px-4 py-3 rounded-lg bg-white bg-opacity-20 flex items-center space-x-3 cursor-pointer">
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
                <div class="text-4xl font-bold text-white">Shop Inventory</div>
            </div>

            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-5 rounded-xl p-6 border border-gray-700" style="background: linear-gradient(to bottom, #13212B, #13212B);">
                    <div class="h-[600px] overflow-y-auto pr-2">
                        <div class="grid grid-cols-3 gap-4">
                            <?php foreach ($items as $item): ?>
                                <a href="view_item.php?itemId=<?= urlencode($item['itemId']) ?>" 
                                class="bg-white p-4 rounded-lg flex flex-col items-center shadow">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" class="h-20 w-20 object-contain mb-2" alt="<?= htmlspecialchars($item['name']) ?>" />
                                    <p style="color: #13212B;" class="text-sm font-semibold"><?= htmlspecialchars($item['stock']) ?> pcs</p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-span-7">
                    <div class="rounded-xl p-6 h-full border border-gray-700 flex flex-col justify-between" style="background: linear-gradient(to bottom, #13212B, #13212B); padding: 100px;">
                        <a href="add_item.php" class="bg-green-500 text-white font-semibold py-3 rounded-lg hover:bg-green-600 transition text-center">
                            Add new item
                        </a>
                            <div class="mt-10 text-white space-y-6 text-lg" style="font-size: 50px;">
                            <div class="flex justify-between">
                                <span>Low Stocks</span>
                                <span><?= $lowStockCount ?></span>
                            </div>
                            <br>
                            <div class="flex justify-between">
                                <span>Items</span>
                                <span><?= $totalItems ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $_SESSION['success']; ?>'
            })
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (isset($_SESSION['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '<?php echo $_SESSION['error']; ?>'
            })
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

</body>
</html>