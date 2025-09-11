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

function formatTimestamp($timestamp) {
    if (!$timestamp) return "Unknown";
    $date = new DateTime($timestamp);
    return $date->format('F j, Y \a\t g:i A');
}

$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/meritShop/$itemId?key=$apiKey";

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = intval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    // Default to existing image path if no new upload
    $existingResponse = file_get_contents($url);
    $existingData = json_decode($existingResponse, true);
    $existingFields = $existingData['fields'] ?? [];
    $existingImage = $existingFields['image']['stringValue'] ?? 'assets/shop-items/default.png';

    $imagePath = $existingImage;

    // Handle file upload if there is one
    if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/assets/images/shop/'; // Absolute path to upload directory
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmpPath = $_FILES['itemImage']['tmp_name'];
        $fileName = basename($_FILES['itemImage']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($fileExt, $allowedExts)) {
            $error = "Invalid file type. Allowed types: jpg, jpeg, png, gif, webp.";
        } else {
            // Create unique file name
            $newFileName = $itemId . '-' . time() . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Store relative path for Firestore (adjust if your public URL path is different)
                $imagePath = 'assets/images/shop/' . $newFileName;
            } else {
                $error = "Failed to move uploaded file.";
            }
        }
    }

    if (!$error) {
        $updateData = [
            'fields' => [
                'name' => ['stringValue' => $name],
                'description' => ['stringValue' => $description],
                'category' => ['stringValue' => $category],
                'price' => ['integerValue' => $price],
                'stock' => ['integerValue' => $stock],
                'image' => ['stringValue' => $imagePath],
                // You can keep createdAt untouched or update it if needed
            ]
        ];

        $jsonData = json_encode($updateData);

        $patchUrl = $url . '&updateMask.fieldPaths=name&updateMask.fieldPaths=description&updateMask.fieldPaths=category&updateMask.fieldPaths=price&updateMask.fieldPaths=stock&updateMask.fieldPaths=image';

        $ch = curl_init($patchUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 200 && $httpcode < 300) {
            session_start();
            $_SESSION['success'] = "Edit successfully";
            header("Location: view_item.php?itemId=" . urlencode($itemId));
            exit;
        } else {
            $error = "Failed to update item. Response: " . $response;
        }

    }
}

// Fetch existing item data to populate form
$response = file_get_contents($url);
if ($response === false) {
    echo "Failed to fetch item.";
    exit;
}

$data = json_decode($response, true);
$fields = $data['fields'] ?? [];

$item = [
    'itemId' => $itemId,
    'name' => $fields['name']['stringValue'] ?? '',
    'description' => $fields['description']['stringValue'] ?? '',
    'category' => $fields['category']['stringValue'] ?? '',
    'price' => $fields['price']['integerValue'] ?? 0,
    'stock' => $fields['stock']['integerValue'] ?? 0,
    'image' => $fields['image']['stringValue'] ?? 'assets/shop-items/default.png',
    'createdAt' => $fields['createdAt']['timestampValue'] ?? null
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>FindIT Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center p-6">
    <h1 class="text-4xl font-bold mb-8">Edit Item: <?= htmlspecialchars($item['name']) ?></h1>

    <?php if (!empty($error)): ?>
        <div class="bg-red-700 p-4 rounded mb-6 max-w-xl w-full">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="bg-gray-800 p-8 rounded shadow-md w-full max-w-xl space-y-6">
        <input type="hidden" name="itemId" value="<?= htmlspecialchars($itemId) ?>" />

        <div>
            <label class="block mb-1 font-semibold" for="name">Name</label>
            <input id="name" name="name" type="text" required
                class="w-full p-2 rounded bg-gray-700 text-white"
                value="<?= htmlspecialchars($item['name']) ?>" />
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="description">Description</label>
            <textarea id="description" name="description" rows="4"
                class="w-full p-2 rounded bg-gray-700 text-white"><?= htmlspecialchars($item['description']) ?></textarea>
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="category">Category</label>
            <input id="category" name="category" type="text" required
                class="w-full p-2 rounded bg-gray-700 text-white"
                value="<?= htmlspecialchars($item['category']) ?>" />
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="price">Merit Points (Price)</label>
            <input id="price" name="price" type="number" min="0" required
                class="w-full p-2 rounded bg-gray-700 text-white"
                value="<?= htmlspecialchars($item['price']) ?>" />
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="stock">Stock Quantity</label>
            <input id="stock" name="stock" type="number" min="0" required
                class="w-full p-2 rounded bg-gray-700 text-white"
                value="<?= htmlspecialchars($item['stock']) ?>" />
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="itemImage">Upload Image</label>
            <input id="itemImage" name="itemImage" type="file" accept="image/*"
                class="w-full p-2 rounded bg-gray-700 text-white" />
            <p class="mt-2 text-gray-400">Current Image:</p>
            <img src="<?= htmlspecialchars($item['image']) ?>" alt="Current Image" class="mt-2 max-h-40 object-contain border rounded" />
        </div>

        <div class="flex justify-between items-center">
            <a href="view_item.php?itemId=<?= urlencode($itemId) ?>" class="text-gray-400 hover:text-white">Cancel</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold">Save Changes</button>
        </div>
    </form>
</body>
</html>
