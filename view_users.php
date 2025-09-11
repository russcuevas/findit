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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FindIT Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <p><strong>Banned:</strong> <?= (isset($fields['isBan']['booleanValue']) && $fields['isBan']['booleanValue']) ? '🚫 Yes' : '✅ No' ?></p>
                </div>
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
<a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'verified_users.php') ?>" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-full">⬅ Back to List</a>
        </div>
    </div>
</body>
</html>

