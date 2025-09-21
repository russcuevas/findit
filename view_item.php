<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$projectId = 'findit-96080';
$apiKey = 'AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g';

$itemId = $_GET['itemId'] ?? '';

if (empty($itemId)) {
    echo "No item specified.";
    exit;
}

$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/meritShop/$itemId?key=$apiKey";
$response = file_get_contents($url);

if ($response === false) {
    echo "Failed to fetch item.";
    exit;
}

$data = json_decode($response, true);
$fields = $data['fields'] ?? [];

$item = [
    'itemId' => $itemId,
    'name' => $fields['name']['stringValue'] ?? 'Unnamed',
    'description' => $fields['description']['stringValue'] ?? 'No description',
    'category' => $fields['category']['stringValue'] ?? 'Uncategorized',
    'price' => $fields['price']['integerValue'] ?? 0,
    'stock' => $fields['stock']['integerValue'] ?? 0,
    'image' => $fields['image']['stringValue'] ?? 'assets/shop-items/default.png',
    'createdAt' => $fields['createdAt']['timestampValue'] ?? null
];

function formatTimestamp($timestamp) {
    if (!$timestamp) return "Unknown";
    $date = new DateTime($timestamp);
    return $date->format('F j, Y \a\t g:i A');
}

// Fetch all items for sidebar
$listUrl = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/meritShop?key=$apiKey";
$listResponse = file_get_contents($listUrl);
$listData = json_decode($listResponse, true);

$items = [];
if (isset($listData['documents'])) {
    foreach ($listData['documents'] as $document) {
        $fields = $document['fields'] ?? [];

        $sidebarItemId = basename($document['name']);
        $name = $fields['name']['stringValue'] ?? 'Unnamed';
        $image = $fields['image']['stringValue'] ?? 'assets/shop-items/default.png';
        $stock = (int)($fields['stock']['integerValue'] ?? 0);

        $items[] = [
            'itemId' => $sidebarItemId,
            'name' => $name,
            'image' => $image,
            'stock' => $stock
        ];
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
            <img src="assets/dashboard/images/logo-dashboard.png" alt="FindIT Logo" style="height: 120px; width: 250px;" />
            <br>
            <div class="flex items-center space-x-3">
                <div class="relative inline-block">
                    <img src="assets/dashboard/images/woman.png" alt="Admin Avatar"
                        class="w-16 h-16 rounded-full border-2 border-white object-cover" />
                </div>
                <span class="text-black text-lg font-medium">Admin</span>
            </div>
            <br>
                        <nav class="space-y-2">
                <div class="sidebar-item px-4 py-3 rounded-lg  flex items-center space-x-3">
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
                <div class="sidebar-item px-4 py-3 rounded-lg bg-white bg-opacity-20 flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <a href="merit_shop.php" style="text-decoration: none;">Merit Shop</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg  flex items-center space-x-3 cursor-pointer">
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
            <div class="flex justify-between items-start mb-8">
                <div class="text-4xl font-bold text-white">Shop Inventory</div>
                                <a href="add_item.php" class="bg-green-500 text-white font-semibold py-3 rounded-lg hover:bg-green-600 transition text-center" style="padding: 10px; float: right;">
                                    Add new item
                                </a>
            </div>

            <div class="grid grid-cols-12 gap-6">
                <!-- Sidebar: Item List -->
                <div class="col-span-5 rounded-xl p-6 border border-gray-700" style="background: linear-gradient(to bottom, #13212B, #13212B);">
                    <div class="h-[600px] overflow-y-auto pr-2">
                        <div class="grid grid-cols-3 gap-4">
                            <?php foreach ($items as $sidebarItem): ?>
                                <a href="view_item.php?itemId=<?= urlencode($sidebarItem['itemId']) ?>"
                                   class="bg-white p-4 rounded-lg flex flex-col items-center shadow">
                                    <img src="<?= htmlspecialchars($sidebarItem['image']) ?>" class="h-20 w-20 object-contain mb-2"
                                         alt="<?= htmlspecialchars($sidebarItem['name']) ?>" />
                                    <p style="color: #13212B;" class="text-sm font-semibold"><?= htmlspecialchars($sidebarItem['stock']) ?> pcs</p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-span-7">
                    <div class="rounded-xl p-6 h-full border border-gray-700 flex flex-col justify-between" style="background: linear-gradient(to bottom, #13212B, #13212B); padding: 100px;">
                        
                    <div class="flex-1 p-10">
                            <div class="max-w-4xl mx-auto bg-[#13212B] border border-gray-700 rounded-xl p-10">
                                <div class="flex gap-10 items-start">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-60 w-60 object-contain rounded-lg border" />
                                    <div class="flex-1 space-y-4">
                                        <h1 class="text-3xl font-bold"><?= htmlspecialchars($item['name']) ?></h1>
                                        <p><span class="text-gray-400">Category:</span> <?= htmlspecialchars($item['category']) ?></p>
                                        <p><span class="text-gray-400">Merit Points:</span> <?= htmlspecialchars($item['price']) ?></p>
                                        <p><span class="text-gray-400">Stocks:</span> <?= htmlspecialchars($item['stock']) ?> pcs</p>
                                        <p class="text-gray-300"><?= nl2br(htmlspecialchars($item['description'])) ?></p>

                                        <!-- Action Buttons -->
                                        <div class="flex space-x-4 mt-6">
                                            <a href="edit_item.php?itemId=<?= urlencode($itemId) ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">Edit Item</a>
                                            <a href="#" id="delete-button" data-itemid="<?= htmlspecialchars($itemId) ?>" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</a>
                                        </div>
                                    </div>
                                </div>
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

    <script>
    document.getElementById('delete-button').addEventListener('click', function(e) {
        e.preventDefault();
        const itemId = this.getAttribute('data-itemid');

        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone. The item will be permanently deleted.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_item.php?itemId=' + encodeURIComponent(itemId);
            }
        });
    });
    </script>


</body>
</html>