<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: loginPage.php');
    exit;
}

$conn = @pg_connect("host=localhost dbname=userdb user=postgres password=April27");
if (!$conn) {
    die('DB connection failed: ' . pg_last_error());
}

$username = $_SESSION['username'];
$result = pg_query_params($conn, "SELECT id, name, contact, email, skills, education FROM users WHERE username = $1", [$username]);
$user = ($result && pg_num_rows($result) === 1) ? pg_fetch_assoc($result) : null;
pg_close($conn);

$rawSkills = trim($user['skills'] ?? '');
if ($rawSkills === '') {
    $skillItems = [];
} else {
    $parts = preg_split('/[\r\n]+|[,;]+/', $rawSkills);
    $skillItems = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
}

$rawEdu = trim($user['education'] ?? '');
$eduItems = $rawEdu === '' ? [] : preg_split('/[\r\n]+/', $rawEdu);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resume - <?php echo htmlspecialchars($user['name'] ?? $username); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <header class="header">
      <img src="profile picture.jpg" alt="<?php echo htmlspecialchars($user['name'] ?? 'Profile'); ?>" class="profile-pic">
      <h1><?php echo htmlspecialchars($user['name'] ?? 'No name'); ?></h1>
      <p>Munlawin San Nicolas, Batangas</p>
      <p>Contact: <?php echo htmlspecialchars($user['contact'] ?? ''); ?> | Email: <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
      <p>Date of Birth: June 05, 2005 | Age: 20 | Citizenship: Filipino</p>
    </header>

    <section class="education">
      <h2>Educational Background</h2>
      <?php if (count($eduItems) === 0): ?>
        <p>No education listed.</p>
      <?php else: ?>
        <?php foreach ($eduItems as $edu): $edu = trim($edu); if ($edu === '') continue; ?>
          <div class="education-item">
            <p><?php echo nl2br(htmlspecialchars($edu)); ?></p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section class="skills">
      <h2>Skills</h2>
      <?php if (count($skillItems) === 0): ?>
        <p>No skills listed.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($skillItems as $s): ?>
            <li><?php echo htmlspecialchars($s); ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <div class="actions">
      <button onclick="window.location.href='editResume.php'">Edit Resume</button>
      <button onclick="window.location.href='logout.php'">Logout</button>
    </div>
  </div>
</body>
</html>