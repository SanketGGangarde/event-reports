<?php
// session already started in header.php
require_once __DIR__ . '/../../init/_dbconnect.php';
require_once __DIR__ . '/../../core/Url.php';

/**
 * Check if principal exists
 * Only used when user is NOT logged in
 */
$principalExists = false;

// Prefer mysqli if available, then PDO (including $GLOBALS['pdo'])
// Keep checks tolerant to different include scopes.
try {
  if (isset($conn) && ($conn instanceof mysqli)) {
    $res = $conn->query("SELECT id FROM users WHERE role='principal' LIMIT 1");
    if ($res && $res->num_rows > 0) {
      $principalExists = true;
    }
  }
} catch (Throwable $e) {
  // ignore and try PDO next
}

if (!$principalExists) {
  $pdoConn = null;
  if (isset($pdo) && $pdo instanceof PDO) {
    $pdoConn = $pdo;
  } elseif (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdoConn = $GLOBALS['pdo'];
  }

  if ($pdoConn) {
    try {
      $stmt = $pdoConn->prepare("SELECT id FROM users WHERE role = :role LIMIT 1");
      $stmt->execute([':role' => 'principal']);
      if ($stmt->fetchColumn() !== false) {
        $principalExists = true;
      }
    } catch (Throwable $e) {
      // ignore DB errors here — default to false
    }
  }
}
?>

<nav class="navbar navbar-expand-lg fixed-top custom-navbar" >
  <div class="container-fluid py-2" id="nav1">

    <!-- Logo -->
<a class="navbar-brand d-flex align-items-center"
   href="<?= Url::to('home') ?>">
  <img
    src="/public/images/keystone_logo.jpeg"
    alt="Logo"
    class="navbar-logo me-2"
  >
</a>

    <!-- Mobile toggle -->
    <button class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
            aria-controls="navbarNav"
            aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">

      <!-- Left links -->
      <div class="navbar-nav">
        <a class="nav-link nav-home"
           href="<?= Url::to('home') ?>">
          Home
        </a>
      </div>

      <!-- Right side -->
      <div class="ms-auto d-flex align-items-center gap-2">

        <?php if (isset($_SESSION['user_id'])): ?>

          <!-- Logged in → Sidebar -->
          <button class="btn btn-outline-secondary"
                  data-bs-toggle="offcanvas"
                  data-bs-target="#sidebar">
            ☰
          </button>

        <?php elseif (!$principalExists): ?>

          <!-- No principal yet -->
          <a href="<?= Url::to('signup') ?>"
             class="btn btn-primary btn-sm">
            Get Started
          </a>

        <?php else: ?>

          <!-- Principal exists but not logged in -->
          <a href="<?= Url::to('login') ?>"
             class="" id="login-btn">
            Login
          </a>

        <?php endif; ?>

      </div>

    </div>
  </div>
</nav>
