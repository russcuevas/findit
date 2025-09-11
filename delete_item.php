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

$itemUrl = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/meritShop/$itemId?key=$apiKey";
$itemResponse = file_get_contents($itemUrl);

if ($itemResponse === false) {
    echo "Failed to fetch item details.";
    exit;
}

$itemData = json_decode($itemResponse, true);
$imagePath = $itemData['fields']['image']['stringValue'] ?? '';

if ($imagePath && file_exists($imagePath)) {
    unlink($imagePath);
}

$deleteUrl = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/meritShop/$itemId?key=$apiKey";

$ch = curl_init($deleteUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode >= 200 && $httpcode < 300) {
    $_SESSION['success'] = "Deleted successfully";
    header("Location: merit_shop.php");
    exit;
} else {
    echo "Failed to delete item. Response: " . $response;
}
