<?php
session_start();

if (!isset($_SESSION['barangay'])) {
    header("Location: login.php");
    exit;
}

$barangayName = $_SESSION['barangay'];

// ✅ Firestore URL (Claims)
$claimsUrl = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/claims?key=YOUR_API_KEY";

// Fetch claims
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $claimsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

$claims = [];
if (isset($data['documents'])) {
    foreach ($data['documents'] as $doc) {
        $fields = $doc['fields'] ?? [];
        $relatedBrgy = $fields['turnOverDetails']['mapValue']['fields']['relatedBrgy']['stringValue'] ?? '';

 $relatedBrgy = $fields['turnOverDetails']['mapValue']['fields']['relatedBrgy']['stringValue'] ?? '';
$status = $fields['status']['stringValue'] ?? '';

if ($relatedBrgy === $barangayName && $status === 'approved') {
    $claims[] = [
        "docId"        => basename($doc['name']),
        "claimId"      => $fields['claimId']['stringValue'] ?? '',
        "claimantName" => $fields['claimantName']['stringValue'] ?? '',
        "email"        => $fields['email']['stringValue'] ?? '',
        "address"      => $fields['address']['stringValue'] ?? '',
        "contact"      => $fields['contactNumber']['stringValue'] ?? '',
        "title"        => $fields['title']['stringValue'] ?? '',
        "description"  => $fields['description']['stringValue'] ?? '',
        "location"     => $fields['location']['stringValue'] ?? '',
        "imageLost"    => $fields['imageLost']['stringValue'] ?? 'assets/dashboard/images/no-image.png',
        "status"       => $status,
        "createdAt"    => $fields['createdAt']['timestampValue'] ?? '',
        "qr_code"      => $fields['qr_code']['stringValue'] ?? '',
        "staff"        => $fields['turnOverDetails']['mapValue']['fields']['staff']['stringValue'] ?? 'Unknown',
        "staffContact" => $fields['turnOverDetails']['mapValue']['fields']['contact']['stringValue'] ?? 'N/A',
        "proofImages"  => isset($fields['proofImages']['arrayValue']['values'])
            ? array_map(fn($p) => $p['stringValue'], $fields['proofImages']['arrayValue']['values'])
            : [],
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
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="text-white overflow-hidden" style="background-color: black;">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-60 p-6 shadow-2xl" style="background: linear-gradient(to bottom, #406F91, #13212B);">
            <img src="../assets/dashboard/images/logo-dashboard.png" alt="FindIT Logo"
                style="height: 120px; width: 250px;" />
            <br>
            <!-- Avatar with Status & Label -->
            <div class="flex items-center space-x-3">
                <div class="relative inline-block">
                    <img src="../assets/dashboard/images/woman.png" alt="Admin Avatar"
                        class="w-16 h-16 rounded-full border-2 border-white object-cover" />
                </div>
                <span class="text-black text-lg font-medium">Barangay</span>
            </div>
            <br>
            <!-- Navigation Menu -->
            <nav class="space-y-2">
                <div class="sidebar-item px-4 py-3 rounded-lg bg-white bg-opacity-20 flex items-center space-x-3">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <a href="dashboard.php" style="text-decoration: none;">Information</a>
                </div>
                <div class="sidebar-item px-4 py-3 rounded-lg flex items-center space-x-3 cursor-pointer">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <a href="logout.php" style="text-decoration: none;">Logout</a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6 overflow-y-auto">
            <h1 class="text-3xl font-bold mb-6">Claim Information - <?= htmlspecialchars($barangayName) ?></h1>

            <?php if (empty($claims)): ?>
                <p class="text-red-400">No claims found for this barangay.</p>
            <?php else: ?>
                <div class="flex space-x-4 mb-4">
                    <a href="dashboard.php" class="px-5 py-2.5 rounded-full text-sm font-medium text-white hover:bg-gray-700">Found Items</a>
                    <a href="claim_information.php" class="px-5 py-2.5 rounded-full text-sm font-medium bg-gray-700 text-white">Claim Information</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($claims as $claim): ?>
                        <div class="bg-gray-800 rounded-lg shadow-lg p-4">
                            <h2 class="text-xl font-semibold mb-2"><?= htmlspecialchars($claim['title']) ?></h2>
                            <p class="text-gray-400"><?= htmlspecialchars($claim['description']) ?></p>

                            <div class="mt-3">
                                <img src="<?= htmlspecialchars($claim['imageLost']) ?>" 
                                     alt="Lost Item" 
                                     class="w-full h-48 object-cover rounded">
                            </div>

                            <div class="mt-3">
                                <strong>Claimant:</strong> <?= htmlspecialchars($claim['claimantName']) ?><br>
                                <strong>Email:</strong> <?= htmlspecialchars($claim['email']) ?><br>
                                <strong>Contact:</strong> <?= htmlspecialchars($claim['contact']) ?><br>
                                <strong>Address:</strong> <?= htmlspecialchars($claim['address']) ?><br>
                            </div>

                            <div class="mt-3">
                                <strong>Turned Over By:</strong> <?= htmlspecialchars($claim['staff']) ?><br>
                                <strong>Staff Contact:</strong> <?= htmlspecialchars($claim['staffContact']) ?><br>
                                <strong>Status:</strong> 
                                <span class="px-2 py-1 rounded text-sm <?= $claim['status'] === 'approved' ? 'bg-green-600' : 'bg-yellow-600' ?>">
                                    Approved by the admin
                                </span>
                            </div>

                            <?php if (!empty($claim['qr_code'])): ?>
                                <div class="mt-3">
                                    <strong>QR Code:</strong><br>
                                    <img src="../assets/images/qr/<?= htmlspecialchars($claim['qr_code']) ?>" 
                                         alt="QR Code" class="w-32 h-32 mt-2">
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($claim['proofImages'])): ?>
                                <div class="mt-3">
                                    <strong>Proof Images:</strong><br>
                                    <div class="grid grid-cols-2 gap-2 mt-2">
                                        <?php foreach ($claim['proofImages'] as $proof): ?>
                                            <img src="<?= htmlspecialchars($proof) ?>" 
                                                 alt="Proof" 
                                                 class="w-full h-24 object-cover rounded">
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3 text-sm text-gray-400">
                                Created At: <?= htmlspecialchars(date("F j, Y, g:i a", strtotime($claim['createdAt']))) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="//unpkg.com/alpinejs" defer></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".deny-btn").forEach(function(btn) {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    const form = btn.closest("form");

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you really want to deny this request? This action cannot be undone.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, deny it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const input = document.createElement("input");
                            input.type = "hidden";
                            input.name = "action";
                            input.value = "deny";
                            form.appendChild(input);
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>

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
