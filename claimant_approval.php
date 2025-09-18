<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$requests = [
    [
        "type" => "CELLPHONE",
        "surrendered" => "Brgy Hall of Concepcion Baliwag City",
        "date" => "3/18/25",
        "time" => "2:00 PM",
        "location" => "Glorieta Park, Baliwag City",
        "finder" => "Elma Batungbakal",
        "contact" => "0923 445 5123",
        "description" => "Ang cellphone po na ito ay dilaw, dumating po ako sa loob ng Glorieta Baliwag...",
        "claimant" => [
            "name" => "Naja Chu Evangelista",
            "phone" => "0909 221 1223",
            "email" => "najachu@email.com",
            "address" => "Tiang, Baliwag City"
        ],
        "proof" => [
            "assets/dashboard/images/lost-item1.png",
            "assets/dashboard/images/lost-item2.png"
        ],
        "thumbnail" => "assets/dashboard/images/lost-item1.png"
    ],
    [
        "type" => "WALLET",
        "surrendered" => "Brgy Hall of Poblacion Baliwag City",
        "date" => "3/20/25",
        "time" => "11:30 AM",
        "location" => "SM Baliwag",
        "finder" => "Juan Dela Cruz",
        "contact" => "0917 888 9999",
        "description" => "Brown wallet with several IDs inside found at SM Baliwag.",
        "claimant" => [
            "name" => "Maria Santos",
            "phone" => "0918 222 3344",
            "email" => "maria@email.com",
            "address" => "Poblacion, Baliwag City"
        ],
        "proof" => [
            "assets/dashboard/images/lost-item1.png",
            "assets/dashboard/images/lost-item2.png"
        ],
        "thumbnail" => "assets/dashboard/images/lost-item2.png"
    ]
];
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
                <div class="sidebar-item px-4 py-3 rounded-lg bg-white bg-opacity-20 flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-paper-plane w-5"></i>
                    <a href="claimant_approval.php" style="text-decoration: none;">Request</a>
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
                    <a href="" class="px-5 py-2.5 rounded-full text-sm font-medium bg-black text-white">Claimant Approval Request</a>
                    <a href="" class="px-5 py-2.5 rounded-full text-sm font-medium text-white hover:bg-gray-700">Post Approval Request</a>
                    <a href="" class="px-5 py-2.5 rounded-full text-sm font-medium text-white hover:bg-gray-700">Pending Approval</a>
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
                                    <h2 class="text-lg font-bold"><?= htmlspecialchars($req['type']) ?></h2>
                                    <p class="text-sm text-gray-400">Surrendered: <?= htmlspecialchars($req['surrendered']) ?></p>
                                    <div class="flex items-center space-x-3 text-xs text-gray-400 mt-1">
                                        <span class="flex items-center"><i class="fa-regular fa-calendar mr-1"></i> <?= $req['date'] ?></span>
                                        <span class="flex items-center"><i class="fa-regular fa-clock mr-1"></i> <?= $req['time'] ?></span>
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
                            <h3 class="font-semibold mb-2">Claimant Description of the Item</h3>
                            <p class="text-sm text-gray-300 mb-4"><?= htmlspecialchars($req['description']) ?></p>

                            <h3 class="font-semibold mb-2">Claimant Details</h3>
                            <p class="text-gray-200"><?= htmlspecialchars($req['claimant']['name']) ?></p>
                            <p class="text-sm text-gray-400">📞 <?= htmlspecialchars($req['claimant']['phone']) ?></p>
                            <p class="text-sm text-gray-400">✉️ <?= htmlspecialchars($req['claimant']['email']) ?></p>
                            <p class="text-sm text-gray-400">🏠 <?= htmlspecialchars($req['claimant']['address']) ?></p>

                            <div class="mt-4">
                                <h3 class="font-semibold mb-2">Proof pictures of Lost Item</h3>
                                <div class="flex space-x-3">
                                    <?php foreach ($req['proof'] as $proof): ?>
                                        <img src="<?= $proof ?>" class="w-32 rounded-lg shadow-md">
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="flex space-x-3 mt-4">
                                <button class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm">Approved Request</button>
                                <button class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg text-sm">Denied</button>
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