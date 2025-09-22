<?php
// ✅ No session needed, public page
if (!isset($_GET['id'])) {
    die("Invalid QR Code. No claim ID provided.");
}

$claimId = $_GET['id'];

// Firestore REST API URL (single claim)
$apiKey = "YOUR_API_KEY"; // 🔹 Replace with your actual API key
$claimUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/claims/$claimId?key=$apiKey";

// ✅ Handle Claim button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim'])) {
    $updateUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/claims/$claimId?updateMask.fieldPaths=status&key=$apiKey";

    $payload = json_encode([
        "fields" => [
            "status" => ["stringValue" => "claimed"]
        ]
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $updateUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    curl_close($ch);

    // ✅ Refresh page to show updated status
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// ✅ Fetch claim data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $claimUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!isset($data['fields'])) {
    die("Claim not found or invalid QR code.");
}

$fields = $data['fields'];
$claim = [
    "claimId"      => $fields['claimId']['stringValue'] ?? '',
    "claimantName" => $fields['claimantName']['stringValue'] ?? '',
    "email"        => $fields['email']['stringValue'] ?? '',
    "address"      => $fields['address']['stringValue'] ?? '',
    "contact"      => $fields['contactNumber']['stringValue'] ?? '',
    "title"        => $fields['title']['stringValue'] ?? '',
    "description"  => $fields['description']['stringValue'] ?? '',
    "location"     => $fields['location']['stringValue'] ?? '',
    "imageLost"    => $fields['imageLost']['stringValue'] ?? 'assets/dashboard/images/no-image.png',
    "status"       => $fields['status']['stringValue'] ?? '',
    "createdAt"    => $fields['createdAt']['timestampValue'] ?? '',
    "staff"        => $fields['turnOverDetails']['mapValue']['fields']['staff']['stringValue'] ?? 'Unknown',
    "staffContact" => $fields['turnOverDetails']['mapValue']['fields']['contact']['stringValue'] ?? 'N/A',
    "relatedBrgy"  => $fields['turnOverDetails']['mapValue']['fields']['relatedBrgy']['stringValue'] ?? '',
    "proofImages"  => isset($fields['proofImages']['arrayValue']['values'])
        ? array_map(fn($p) => $p['stringValue'], $fields['proofImages']['arrayValue']['values'])
        : [],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim QR Info</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 p-6">

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($claim['title']) ?> (QR Claim)</h1>
        <p class="text-gray-600"><?= htmlspecialchars($claim['description']) ?></p>

        <div class="mt-4">
            <img src="<?= htmlspecialchars($claim['imageLost']) ?>" 
                 alt="Lost Item" 
                 class="w-full h-64 object-cover rounded">
        </div>

        <div class="mt-4">
            <h2 class="font-semibold">Claimant Information</h2>
            <p><strong>Name:</strong> <?= htmlspecialchars($claim['claimantName']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($claim['email']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($claim['contact']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($claim['address']) ?></p>
        </div>

        <div class="mt-4">
            <h2 class="font-semibold">Turn Over Details</h2>
            <p><strong>Staff:</strong> <?= htmlspecialchars($claim['staff']) ?></p>
            <p><strong>Staff Contact:</strong> <?= htmlspecialchars($claim['staffContact']) ?></p>
            <p><strong>Barangay:</strong> <?= htmlspecialchars($claim['relatedBrgy']) ?></p>
        </div>

        <div class="mt-4">
            <h2 class="font-semibold">Status</h2>
            <span class="px-2 py-1 rounded text-sm 
                <?php if ($claim['status'] === 'approved') echo 'bg-green-600 text-white'; ?>
                <?php if ($claim['status'] === 'pending') echo 'bg-yellow-500 text-white'; ?>
                <?php if ($claim['status'] === 'claimed') echo 'bg-blue-600 text-white'; ?>
            ">
                <?= htmlspecialchars(ucfirst($claim['status'])) ?>
            </span>
        </div>

        <?php if (!empty($claim['proofImages'])): ?>
            <div class="mt-4">
                <h2 class="font-semibold">Proof Images</h2>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <?php foreach ($claim['proofImages'] as $proof): ?>
                        <img src="<?= htmlspecialchars($proof) ?>" 
                             alt="Proof" 
                             class="w-full h-32 object-cover rounded">
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4 text-gray-500 text-sm">
            Created At: <?= htmlspecialchars(date("F j, Y, g:i a", strtotime($claim['createdAt']))) ?>
        </div>

            <?php if ($claim['status'] === 'approved'): ?>
                <form method="POST" class="mt-3">
                    <button type="submit" name="claim" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        Claim
                    </button>
                </form>
            <?php endif; ?>
    </div>

</body>
</html>
