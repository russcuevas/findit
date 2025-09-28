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
    die("No user ID provided");
}

// Firestore API endpoint
$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users/$userId?key=$apiKey";

// Update body (PATCH)
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

if ($response) {
    // Redirect back to user details with success
    header("Location: user_details.php?id=" . urlencode($userId) . "&status=banned");
    exit;
} else {
    die("Failed to update Firestore");
}
