<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$admin = $_SESSION['admin'];

$projectId = 'findit-96080';
$apiKey = 'AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $itemId = $_POST['itemId'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = isset($_POST['price']) ? intval($_POST['price']) : 0;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;

    // Handle image URL or default (you can expand to file uploads later)
    $imageUrl = 'https://pics.drugstore.com/prodimg/509428/900.jpg'; // Default fallback

    if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/images/shop/';
        $fileTmpPath = $_FILES['itemImage']['tmp_name'];
        $fileName = basename($_FILES['itemImage']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // You can customize this file name to ensure uniqueness
        $newFileName = $itemId . '.' . $fileExt;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $imageUrl = $destPath; // This is relative; use full path if needed in Firestore
        }
    }
    // Firestore document URL with itemId
    $firestoreUrl = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/meritShop?documentId=$itemId&key=$apiKey";
    $createdAt = date('c'); 

    $data = [
        'fields' => [
            'itemId' => ['stringValue' => $itemId],
            'name' => ['stringValue' => $name],
            'description' => ['stringValue' => $description],
            'category' => ['stringValue' => $category],
            'price' => ['integerValue' => $price],
            'stock' => ['integerValue' => $stock],
            'isAvailable' => ['booleanValue' => true],
            'image' => ['stringValue' => $imageUrl],
            'createdAt' => ['timestampValue' => $createdAt]
        ]
    ];


    $ch = curl_init($firestoreUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    session_start();
    $_SESSION['success'] = "Added item successfully";
    header("Location: merit_shop.php");
    exit;
}

// Fetch existing items from Firestore for display
$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/meritShop?key=$apiKey";
$response = file_get_contents($url);
$data = json_decode($response, true);

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
                <div class="sidebar-item px-4 py-3 rounded-lg  flex items-center space-x-3">
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
                    <div class="rounded-xl p-6 h-full border border-gray-700 bg-[#13212B] text-white flex flex-col space-y-6">
                        <div class="text-xl font-bold mb-4">Add new item</div>

                        <!-- Item Name -->
                        <form action="add_item.php" method="POST" class="flex flex-col space-y-6 text-white" enctype="multipart/form-data">
                            <div>
                                <label class="block mb-1 text-sm" for="itemId">Item ID:</label>
                                <input type="text" name="itemId" id="itemId" required
                                    class="w-full px-4 py-2 rounded bg-gray-600 text-white focus:outline-none"
                                    placeholder="Unique item ID (e.g., can001)">
                            </div>

                            <div>
                                <label class="block mb-1 text-sm" for="itemName">Item Name:</label>
                                <input type="text" name="name" id="itemName" required
                                    class="w-full px-4 py-2 rounded bg-gray-600 text-white focus:outline-none"
                                    placeholder="Enter item name">
                            </div>

                            <div>
                                <label class="block mb-1 text-sm" for="description">Description:</label>
                                <textarea name="description" id="description"
                                        class="w-full px-4 py-2 rounded bg-gray-600 text-white focus:outline-none"
                                        placeholder="Enter item description"></textarea>
                            </div>

                            <div>
                                <label class="block mb-1 text-sm" for="category">Category:</label>
                                <input type="text" name="category" id="category"
                                    class="w-full px-4 py-2 rounded bg-gray-600 text-white focus:outline-none"
                                    placeholder="Enter item category (e.g., Food)">
                            </div>

                            <div>
                                <label class="block mb-1 text-sm" for="price">Price (Merit Value):</label>
                                <input type="number" name="price" id="price"
                                    class="w-full px-4 py-2 rounded bg-gray-600 text-white focus:outline-none"
                                    placeholder="Enter price (Merit Value)">
                            </div>

                            <div>
                                <label class="block mb-1 text-sm">Item Quantity:</label>
                                <div class="flex items-center space-x-3">
                                    <button type="button" onclick="adjustQuantity(-1)" class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center text-white">−</button>
                                    <span id="quantity" class="w-8 text-center">0</span>
                                    <button type="button" onclick="adjustQuantity(1)" class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center text-white">+</button>
                                </div>
                                <input type="hidden" name="stock" id="quantityInput" value="0">
                            </div>

                        <div class="flex items-center space-x-2 mt-4">
                            <label for="itemImage" class="cursor-pointer flex items-center space-x-2 text-white">
                                <i class="fas fa-image text-xl"></i>
                                <span class="font-medium">Insert Image</span>
                            </label>
                            <input type="file" name="itemImage" id="itemImage" class="hidden" accept="image/*">
                        </div>

                        <div class="flex justify-end mt-auto">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded">
                                Upload
                            </button>
                        </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quantity Control Script -->
<script>
    let quantity = 0;
    function adjustQuantity(amount) {
        quantity = Math.max(0, quantity + amount);
        document.getElementById('quantity').textContent = quantity;
        document.getElementById('quantityInput').value = quantity;
    }
</script>
</body>
</html>