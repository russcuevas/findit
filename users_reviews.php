<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Firestore API key
$apiKey = "AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g";

// Handle "Approve Review" POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_review'])) {
    $reviewId = $_POST['reviewId'];
    
    $patchUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/reviews/$reviewId?updateMask.fieldPaths=isPinned&key=$apiKey";

    $data = [
        'fields' => [
            'isPinned' => ['booleanValue' => true]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $patchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        $_SESSION['error'] = "Failed to approve review: $error";
    } else {
        $_SESSION['success'] = "Review approved successfully!";
    }

    header("Location: users_reviews.php");
    exit;
}

// Fetch reviews from Firestore
$firestoreUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/reviews?key=$apiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $firestoreUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$reviewsData = json_decode($response, true);
$reviews = $reviewsData['documents'] ?? [];
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
                    <a href="surrendered_items.php" class="px-5 py-2.5 rounded-full text-sm font-medium text-white hover:bg-gray-700" >List Surrendered Items</a>
                    <a href="users_reviews.php" class="px-5 py-2.5 rounded-full text-sm font-medium bg-black text-white" >User Reviews</a>
                </div>

                <div class="overflow-x-auto">
                    <div class="space-y-4">
                <?php foreach ($reviews as $review): 
                    $fields = $review['fields'];
                    $description = $fields['description']['stringValue'] ?? '';
                    $stars = $fields['stars']['integerValue'] ?? 0;
                    $isPinned = isset($fields['isPinned']['booleanValue']) && $fields['isPinned']['booleanValue'];
                    $reviewId = basename($review['name']);
                ?>
                <div class="bg-gray-900 rounded-xl shadow-lg p-5 flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0 md:space-x-6">
    <!-- User Info -->
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-blue-500 flex-shrink-0">
                            <img src="assets/dashboard/images/user.png" alt="User Avatar" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p class="text-gray-200 font-medium"><?php echo htmlspecialchars($description); ?></p>
                            <div class="mt-1 flex items-center">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= $stars ? 'fas' : 'far'; ?> fa-star text-yellow-400 mr-1"></i>
                                <?php endfor; ?>
                                <span class="ml-2 text-gray-400 font-semibold"><?php echo htmlspecialchars($stars); ?>/5</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex-shrink-0">
                        <?php if (!$isPinned): ?>
                            <form method="POST">
                                <input type="hidden" name="reviewId" value="<?php echo $reviewId; ?>">
                                <button type="submit" name="approve_review" 
                                        class="bg-green-500 hover:bg-green-600 transition duration-300 text-white px-5 py-2 rounded-lg font-semibold shadow-md hover:shadow-lg">
                                    Approve Review
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-green-400 font-semibold bg-gray-800 px-4 py-2 rounded-lg">Already Pinned</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php endforeach; ?>
                    </div>
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