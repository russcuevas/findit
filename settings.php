<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$projectId = 'findit-96080';
$apiKey = 'AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g';
$baseUrl = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users?key=$apiKey";

$response = file_get_contents($baseUrl);
$data = json_decode($response, true);

$users = [];

if (isset($data['documents'])) {
    foreach ($data['documents'] as $doc) {
        $fields = $doc['fields'] ?? [];

        $isVerified = $fields['isVerified']['booleanValue'] ?? false;
        $isBan = $fields['isBan']['booleanValue'] ?? false;  // default to false if not set

        if ($isVerified === false && $isBan === false) {
            $users[] = [
                'id' => basename($doc['name']),
                'fullName' => $fields['fullName']['stringValue'] ?? 'N/A',
                'contactNumber' => $fields['contactNumber']['stringValue'] ?? 'N/A',
                'email' => $fields['email']['stringValue'] ?? 'N/A',
                'createdAt' => isset($fields['createdAt']['timestampValue']) ? formatTimestamp($fields['createdAt']['timestampValue']) : 'Unknown'
            ];
        }
    }
}

function formatTimestamp($timestamp) {
    try {
        $date = new DateTime($timestamp);
        return $date->format('m/d/Y');
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>FindIT Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    
    <style>
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
            transition: all 0.3s ease;
        }
        /* Optional: override DataTables default styles for dark bg */
        table.dataTable {
            color: white !important;
        }
        table.dataTable thead {
            background-color: #13212B !important;
        }
        table.dataTable tbody tr {
            background-color: transparent;
        }
        table.dataTable tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        /* Pagination buttons */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: white !important;
        }
        .dataTables_wrapper .dataTables_filter input {
            background-color: #13212B;
            border: 1px solid #406F91;
            color: white;
            padding: 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="text-white overflow-hidden" style="background-color: black;">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-60 p-6 shadow-2xl" style="background: linear-gradient(to bottom, #406F91, #13212B);">
            <img src="assets/dashboard/images/logo-dashboard.png" alt="FindIT Logo" style="height: 120px; width: 250px;" />
            <br />
            <div class="flex items-center space-x-3">
                <img src="assets/dashboard/images/woman.png" class="w-16 h-16 rounded-full border-2 border-white object-cover" />
                <span class="text-black text-lg font-medium">Admin</span>
            </div>
            <br />
            <nav class="space-y-2">
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3">
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
                <div class="sidebar-item px-4 py-3 rounded-lg bg-white bg-opacity-20 flex items-center space-x-3 cursor-pointer">
                    <i class="fas fa-cog w-5"></i>
                    <a href="settings.php" style="text-decoration: none;">Settings</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fa-solid fa-right-from-bracket"></i>                    
                    <a href="logout.php" style="text-decoration: none;">Logout</a>
                </div>
            </nav>
        </div>
       
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                // Optional config
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ users",
                    paginate: {
                        previous: "Prev",
                        next: "Next"
                    }
                }
            });
        });
    </script>
</body>
</html>
