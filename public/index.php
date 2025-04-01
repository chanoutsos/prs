<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard | <?php echo SITE_NAME; ?></title>
  <base href="/prs/public/"> 
  <link rel="stylesheet" href="/prs/assets/css/style.css">
  <script src="assets/js/app.js"></script>
</head>
<body>
  <header class="header">
    <div class="container header-container">
      <div class="logo">PRS Dashboard</div>
      <nav class="nav">
        <ul>
          <li><a href="index.php">Dashboard</a></li>
          <li><a href="users.php">Users</a></li>
          <li><a href="vaccinations.php">Vaccinations</a></li>
          <li><a href="reports.php">Reports</a></li>
          <li><a href="logout.php">Logout</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="dashboard">
    <div class="container">
      <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
      <p>Your role is: <?php echo htmlspecialchars($user['role_id']); ?></p>
      <!-- Add your dashboard content here -->
    </div>
  </main>
</body>
</html>
