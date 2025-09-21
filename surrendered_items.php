<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// ✅ Firestore REST API
$itemsUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/items?key=YOUR_API_KEY";
$usersUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/users?key=YOUR_API_KEY";

// Fetch items
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $itemsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$itemsResponse = curl_exec($ch);
curl_close($ch);

$itemsData = json_decode($itemsResponse, true);

// Fetch users
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $usersUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$usersResponse = curl_exec($ch);
curl_close($ch);

$usersData = json_decode($usersResponse, true);

// ✅ Map users by userId
$usersMap = [];
if (isset($usersData['documents'])) {
    foreach ($usersData['documents'] as $doc) {
        $fields = $doc['fields'];
        $userId = $doc['name']; 
        $userId = basename($userId); // extract ID

        $usersMap[$userId] = [
            "fullName" => $fields['fullName']['stringValue'] ?? 'N/A',
            "email"    => $fields['email']['stringValue'] ?? 'N/A',
            "contact"  => $fields['contactNumber']['stringValue'] ?? 'N/A',
            "address"  => $fields['location']['stringValue'] ?? 'N/A',
        ];
    }
}

// ✅ Filter: Found = always show, Lost = only if approved
$requests = [];
if (isset($itemsData['documents'])) {
    foreach ($itemsData['documents'] as $doc) {
        $fields = $doc['fields'];
        $type   = $fields['type']['stringValue'] ?? '';
        $status = $fields['status']['stringValue'] ?? '';
        $userId = $fields['userId']['stringValue'] ?? '';

if (
    ($type === "found" && $status === "approved")) 
    {            $requests[] = [
                "title"       => $fields['title']['stringValue'] ?? '',
                "description" => $fields['description']['stringValue'] ?? '',
                "location"    => $fields['location']['stringValue'] ?? '',
                "createdAt"   => $fields['createdAt']['timestampValue'] ?? '',
                "thumbnail"   => $fields['images']['stringValue'] ?? 'assets/dashboard/images/no-image.png',
                "reward"      => $fields['reward']['stringValue'] ?? '',
                "type"        => $type,
                "status"      => $status,
                "surrendered" => $fields['turnOverDetails']['mapValue']['fields']['relatedBrgy']['stringValue'] ?? 'N/A',
                "finder"      => $fields['turnOverDetails']['mapValue']['fields']['staff']['stringValue'] ?? 'Unknown',
                "contact"     => $fields['turnOverDetails']['mapValue']['fields']['contact']['stringValue'] ?? 'N/A',
                "proof"       => isset($fields['proof']['arrayValue']['values'])
                                    ? array_map(fn($p) => $p['stringValue'], $fields['proof']['arrayValue']['values'])
                                    : [],

                // ✅ Attach user details from users collection
                "claimant"    => $usersMap[$userId] ?? [
                    "fullName" => "N/A",
                    "email"    => "N/A",
                    "contact"  => "N/A",
                    "address"  => "N/A",
                ],
            ];
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
                    <a href="claimant_approval.php" style="text-decoration: none;">Request</a>
                    <div class="ml-auto bg-orange-500 text-xs px-2 py-1 rounded-full">5</div>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg bg-white bg-opacity-20 flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-chart-bar w-5"></i>
                    <a href="surrendered_items.php" style="text-decoration: none;">Reports</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
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
            <div class="rounded-xl p-6 border border-gray-700" style="background: linear-gradient(to bottom, #13212B, #13212B);">
                <div class="flex space-x-4 mb-4">
                    <a href="surrendered_items.php" class="px-5 py-2.5 rounded-full text-sm font-medium bg-black text-white">List Surrendered Items</a>
                    <a href="users_reviews.php" class="px-5 py-2.5 rounded-full text-sm font-medium text-white hover:bg-gray-700">User Reviews</a>
                </div>

                <div class="overflow-x-auto">
                    <?php foreach ($requests as $index => $req): ?>
                    <div class="mb-4" x-data="{ open: false }">
                        <!-- Summary Row -->
                        <div @click="open = !open" class="bg-black text-white rounded-lg shadow-md p-4 flex items-center justify-between cursor-pointer">
                            <!-- Left Content -->
                            <div class="flex items-center space-x-4">
                                <img src="<?= $req['thumbnail'] ?>" class="w-20 h-20 rounded-lg object-cover">
                                <div>
                                    <h2 class="text-lg font-bold"><?= htmlspecialchars($req['title']) ?> - Surrendered</h2>
                                    <p class="text-sm text-gray-400">Surrendered: <?= htmlspecialchars($req['surrendered']) ?></p>
                                    <div class="flex items-center space-x-3 text-xs text-gray-400 mt-1">
                                    <span class="flex items-center"><i class="fa-regular fa-calendar mr-1"></i> <?= date("m/d/Y", strtotime($req['createdAt'])) ?></span>
                                    <span class="flex items-center"><i class="fa-regular fa-clock mr-1"></i> <?= date("h:i A", strtotime($req['createdAt'])) ?></span>
                                        <span class="flex items-center"><i class="fa-solid fa-location-dot mr-1"></i> <?= htmlspecialchars($req['location']) ?></span>
                                    </div>
                                    <div class="flex items-center text-xs text-gray-400 mt-1">
                                        <i class="fa-solid fa-user mr-1"></i> <?= htmlspecialchars($req['finder']) ?> &nbsp;&nbsp; 
                                        <i class="fa-solid fa-phone mr-1"></i> <?= htmlspecialchars($req['contact']) ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Toggle Arrow -->
                            <div>
                                <span x-show="!open" class="text-xl">▶</span>
                                <span x-show="open" class="text-xl">▼</span>
                            </div>
                        </div>

                        <!-- Expanded Details -->
                        <div x-show="open" x-transition class="bg-gray-800 text-white p-6 rounded-b-lg shadow-md">
                            <h3 class="font-semibold mb-2">Good Samaritan Description of the Item</h3>
                            <p class="text-sm text-gray-300 mb-4"><?= htmlspecialchars($req['description']) ?></p>

                            <h3 class="font-semibold mb-2">Good Samaritan Details</h3>
                            <p class="text-gray-200"><?= htmlspecialchars($req['claimant']['fullName']) ?></p>
                            <p class="text-sm text-gray-400">📞 <?= htmlspecialchars($req['claimant']['contact']) ?></p>
                            <p class="text-sm text-gray-400">✉️ <?= htmlspecialchars($req['claimant']['email']) ?></p>
                            <p class="text-sm text-gray-400">🏠 <?= htmlspecialchars($req['claimant']['address']) ?></p>

                            <div class="mt-4">
                                <h3 class="font-semibold mb-2">Proof pictures of Found Item</h3>
                                <div class="flex space-x-3">
                                    <img src="<?= $req['thumbnail'] ?>" class="w-32 rounded-lg shadow-md">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="//unpkg.com/alpinejs" defer></script>

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