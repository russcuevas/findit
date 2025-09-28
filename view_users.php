<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$projectId = 'findit-96080';
$apiKey = 'AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g';

$userId = $_GET['id'] ?? '';
if (empty($userId)) {
    echo "No user ID provided.";
    exit;
}

// 🔹 Handle ban action directly here
if (isset($_GET['ban']) && $_GET['ban'] == 1 && !empty($userId)) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users/$userId?updateMask.fieldPaths=isBan&key=$apiKey";

    $updateData = [
        "fields" => [
            "isBan" => ["booleanValue" => true]
        ]
    ];

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json\r\n",
            "method"  => "PATCH",
            "content" => json_encode($updateData)
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        die("Failed to ban user.");
    } else {
        // Redirect back with success
        header("Location: ?id=" . urlencode($userId) . "&status=banned");
        exit;
    }
}

// 🔹 Fetch user data
$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users/$userId?key=$apiKey";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (!$data || !isset($data['fields'])) {
    echo "User not found.";
    exit;
}

$fields = $data['fields'];

function getValue($field, $type = 'stringValue') {
    return $field[$type] ?? 'N/A';
}

function formatDate($timestamp) {
    try {
        $dt = new DateTime($timestamp);
        return $dt->format('F j, Y \a\t g:i A');
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

// Wallet fields
$wallet = $fields['wallet']['mapValue']['fields'] ?? [];
$notificationSettings = $fields['notificationSettings']['mapValue']['fields'] ?? [];
$profileImage = getValue($fields['profileImage'] ?? ['stringValue' => ''], 'stringValue');
$isBanned = isset($fields['isBan']['booleanValue']) && $fields['isBan']['booleanValue'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FindIT Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-black text-white p-10">
    <div class="max-w-5xl mx-auto bg-gray-900 rounded-lg shadow-lg p-8 border border-gray-700">
        <h1 class="text-3xl font-bold mb-6 text-center text-purple-400">User Details</h1>

        <div class="flex flex-col md:flex-row gap-10">
            <!-- LEFT: Profile Summary -->
            <div class="md:w-1/3 text-center">
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile Image" class="w-48 h-48 mx-auto rounded-full border-4 border-purple-500 object-cover mb-4">
                <p class="text-xl font-semibold"><?= htmlspecialchars(getValue($fields['username'] ?? $fields['fullName'])) ?></p>
                <p class="text-sm text-gray-400 mt-1"><?= htmlspecialchars(getValue($fields['email'])) ?></p>
                <p class="text-xs text-gray-500">User ID: <?= htmlspecialchars(getValue($fields['userId'])) ?></p>

                <div class="mt-4 text-sm">
                    <p><strong>Verified:</strong> <?= $fields['isVerified']['booleanValue'] ? '✅ Yes' : '❌ No' ?></p>
                    <p><strong>Banned:</strong> <?= $isBanned ? '🚫 Yes' : '✅ No' ?></p>
                </div>

                <!-- Ban Link -->
                <?php if (!$isBanned): ?>
                <div class="mt-6 text-center">
                    <a href="?id=<?= urlencode($userId) ?>&ban=1"
                       id="banLink"
                       class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-full inline-block">
                       🚫 Ban Account
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Detailed Info -->
            <div class="md:w-2/3 grid grid-cols-2 gap-6 text-sm">
                <div><strong>Full Name:</strong><br><?= htmlspecialchars(getValue($fields['fullName'])) ?></div>
                <div><strong>Contact Number:</strong><br><?= htmlspecialchars(getValue($fields['contactNumber'])) ?></div>

                <div><strong>Location:</strong><br><?= htmlspecialchars(getValue($fields['location'] ?? ['stringValue' => 'N/A'])) ?></div>
                <div><strong>Created At:</strong><br><?= formatDate(getValue($fields['createdAt'], 'timestampValue')) ?></div>

                <div><strong>Verification Token:</strong><br><?= htmlspecialchars(getValue($fields['verificationToken'] ?? ['stringValue' => ''])) ?></div>
                <div><strong>User Type:</strong><br><?= htmlspecialchars(getValue($fields['userType'] ?? ['stringValue' => 'User'])) ?></div>
            </div>
        </div>

        <!-- WALLET INFO -->
        <div class="mt-10 border-t border-gray-600 pt-6">
            <h2 class="text-xl font-semibold text-purple-300 mb-4">Wallet Info</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-sm">
                <div><strong>Status:</strong><br><?= htmlspecialchars(getValue($wallet['status'] ?? ['stringValue' => 'N/A'])) ?></div>
                <div><strong>Merits:</strong><br><?= htmlspecialchars(getValue($wallet['merits'] ?? ['integerValue' => 0], 'integerValue')) ?></div>
                <div><strong>Badge Level:</strong><br><?= htmlspecialchars(getValue($wallet['badgeLevel'] ?? ['integerValue' => 0], 'integerValue')) ?></div>
                <div><strong>Badge Progress:</strong><br><?= htmlspecialchars(getValue($wallet['badgeProgress'] ?? ['integerValue' => 0], 'integerValue')) ?> / 10000</div>
                <div><strong>Last Updated:</strong><br><?= formatDate(getValue($wallet['lastUpdated'] ?? [], 'timestampValue')) ?></div>
            </div>
        </div>

        <!-- NOTIFICATION SETTINGS -->
        <div class="mt-10 border-t border-gray-600 pt-6">
            <h2 class="text-xl font-semibold text-purple-300 mb-4">Notification Settings</h2>
            <div class="grid grid-cols-2 gap-6 text-sm">
                <div><strong>Push Notifications:</strong><br><?= $notificationSettings['pushNotifications']['booleanValue'] ? '✅ Enabled' : '❌ Disabled' ?></div>
                <div><strong>Email Notifications:</strong><br><?= $notificationSettings['emailNotifications']['booleanValue'] ? '✅ Enabled' : '❌ Disabled' ?></div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="verified_users.php" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-full">⬅ Back to List</a>
        </div>
    </div>

    <script>
    // Handle Ban Confirmation
    document.getElementById("banLink")?.addEventListener("click", function (e) {
        e.preventDefault(); // stop default action
        const targetUrl = this.getAttribute("href");

        Swal.fire({
            title: "Are you sure?",
            text: "Do you really want to ban this account?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, ban it!"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = targetUrl;
            }
        });
    });

    // Show success popup after banning
    <?php if (isset($_GET['status']) && $_GET['status'] === 'banned'): ?>
    Swal.fire({
        icon: "success",
        title: "Banned!",
        text: "The user has been banned successfully.",
        confirmButtonText: "OK"
    });
    <?php endif; ?>
    </script>
</body>
</html>
