<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Both fields are required.';
    } else {
        // Connect to PostgreSQL (suppress warning and read error with pg_last_error)
        $conn = @pg_connect("host=localhost dbname=userdb user=postgres password=April27");
        if (!$conn) {
            $error = 'Database connection failed: ' . pg_last_error();
        } else {
            // Get stored password hash for this username
            $result = pg_query_params($conn,
                "SELECT password FROM users WHERE username = $1",
                [$username]
            );

            if (!$result) {
                $error = 'Database query failed: ' . pg_last_error($conn);
            } elseif (pg_num_rows($result) === 1) {
                $row = pg_fetch_assoc($result);
                $stored = $row['password'];

                $verified = false;

                if (!empty($stored) && password_verify($password, $stored)) {
                    $verified = true;
                } else {
                    if ($stored === md5($password)) {
                        $verified = true;
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        pg_query_params($conn, "UPDATE users SET password = $1 WHERE username = $2", [$newHash, $username]);
                    }
                }

                if ($verified) {
                    $_SESSION['username'] = $username;
                    header('Location: resume.php');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }

            pg_close($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <form method="POST" class="login-form">
      <h2>Login</h2>
      <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <label for="username">Username:</label>
      <input type="text" name="username" id="username" required>
      <label for="password">Password:</label>
      <input type="password" name="password" id="password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>