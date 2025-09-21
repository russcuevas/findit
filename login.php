<?php
session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$projectId = 'findit-96080';
$apiKey = 'AIzaSyBnRceOZZNPF-qR65gKadBGwlYEADrqi_g';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $username && $password) {
    try {
        $admins = fetchAdminsFromFirestore($projectId, $apiKey);
        $adminData = authenticateAdmin($username, $password, $admins);
        if ($adminData) {
            $_SESSION['admin'] = $adminData;
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Invalid username or password.";
        }
    } catch (Exception $e) {
        $error_message = "Login failed: " . $e->getMessage();
    }
}

function fetchAdminsFromFirestore($projectId, $apiKey) {
$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/admins?key=$apiKey";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('Error connecting to Firestore: ' . curl_error($ch));
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode !== 200) {
        throw new Exception("Firestore API returned status code $statusCode");
    }

    $data = json_decode($response, true);
    if ($data === null) {
        throw new Exception("Failed to decode Firestore response.");
    }

    return $data;
}

function authenticateAdmin($username, $password, $admins) {
    if (!isset($admins['documents'])) {
        return false;
    }

    foreach ($admins['documents'] as $doc) {
        $fields = $doc['fields'];
        $dbAdminId = $fields['adminId']['stringValue'] ?? '';
        $dbPassword = $fields['password']['stringValue'] ?? '';
        $role = $fields['role']['stringValue'] ?? '';
        $email = $fields['email']['stringValue'] ?? '';

        if (strcasecmp($username, $dbAdminId) === 0 && $password === $dbPassword) {
            return [
                'adminId' => $dbAdminId,
                'role' => $role,
                'email' => $email
            ];
        }
    }

    return false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>FindIT Login</title>
  <link rel="stylesheet" href="assets/login/css/login.css">
</head>
<body>
  <div class="bg-left"></div>
  <div class="bg-right"></div>

  <div class="login-container">
    <img style="height: 100px;" src="assets/login/images/logo-dashboard.jpg" alt="FindIT Logo" class="logo" />
    <p>Enter your Username and password to log in</p>

    <?php if (!empty($error_message)): ?>
      <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form action="" method="POST" autocomplete="off">
      <input type="text" name="username" placeholder="Username" required autofocus />
      <input type="password" name="password" placeholder="Password" required />
      <!-- <a href="#" class="create-account">Create admin account?</a> -->
      <button type="submit">Sign in</button>
    </form>
  </div>
</body>
</html>
