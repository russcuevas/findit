<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Firestore Barangays Collection
    $url = "https://firestore.googleapis.com/v1/projects/findit-96080/databases/(default)/documents/barangays?key=YOUR_API_KEY";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['documents'])) {
        foreach ($data['documents'] as $doc) {
            $fields   = $doc['fields'];
            $dbUser   = $fields['username']['stringValue'] ?? '';
            $dbPass   = $fields['password']['stringValue'] ?? '';
            $brgyName = $fields['name']['stringValue'] ?? '';

            if ($username === $dbUser && $password === $dbPass) {
                $_SESSION['barangay'] = $brgyName;
                header("Location: dashboard.php");
                exit;
            }
        }
    }

    $error = "Invalid login!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>FindIT Login</title>
  <link rel="stylesheet" href="../assets/login/css/login.css">
</head>
<body>
  <div class="bg-left"></div>
  <div class="bg-right"></div>

  <div class="login-container">
    <img style="height: 100px;" src="../assets/login/images/logo-dashboard.jpg" alt="FindIT Logo" class="logo" />
    <p>Enter your Username and Password to log in</p>

    <?php if (!empty($error)): ?>
      <div class="error-message" style="color:red;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Barangay Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
