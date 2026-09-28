<?php

if (!isset($page))  $page  = '';
if (!isset($title)) $title = 'Dashboard';

$theme = $_SESSION["theme"] ?? "light";
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo htmlspecialchars($title); ?> — Budget Tracker</title>
<link rel="icon" type="image/x-icon" href="1.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="brand">
    <img src="1.ico" alt="Budget Tracker" width="26" height="26" class="logo-mark">
    <h1>Budget Tracker</h1>
  </div>

  <div class="header-right">
    <div class="user-chip">
      <div class="avatar"><?php echo strtoupper(substr($_SESSION["full_name"] ?? "?", 0, 1)); ?></div>
      <?php echo htmlspecialchars($_SESSION["full_name"] ?? "Guest"); ?>
    </div>

    <a href="logout.php" class="link-btn">Log out</a>

    <form class="switch-form" method="post" action="toggle_theme.php">
      <input type="hidden" name="redirect" value="<?php echo htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>">
      <button type="submit" class="switch" title="Toggle light / dark"><span class="knob"></span></button>
    </form>
  </div>
</header>

<main>

  <!-- Navigation tabs -->
  <div class="tabs">
    <a class="tab-btn <?php echo $page === 'overview'     ? 'active' : ''; ?>" href="index.php">Overview</a>
    <a class="tab-btn <?php echo $page === 'transactions' ? 'active' : ''; ?>" href="transactions.php">Transactions</a>
    <a class="tab-btn <?php echo $page === 'budgets'      ? 'active' : ''; ?>" href="budgets.php">Budgets</a>
  </div>