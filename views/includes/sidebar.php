<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . Url::to('login'));
    exit;
}

// Make sure PDO is available
require_once __DIR__ . '/../../init/_dbconnect.php';
require_once __DIR__ . '/../../core/Url.php';

// Get PDO from globals (as done in your home.php)
if (empty($GLOBALS['pdo']) || !($GLOBALS['pdo'] instanceof PDO)) {
    die("Critical error: Database connection not available in sidebar.");
}
$pdo = $GLOBALS['pdo'];

$userId = $_SESSION['user_id']; // Use UUID as string, not integer

try {
    $stmt = $pdo->prepare("
        SELECT username, role, profile_image 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$user) {
        session_destroy();
        header("Location: " . Url::to('login'));
        exit;
    }
} catch (PDOException $e) {
    // In production → log error, show friendly message
    // For development → show error
    die("Database error in sidebar: " . htmlspecialchars($e->getMessage()));
}
?>

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">User Panel</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body">

    <!-- Profile -->
    <div class="text-center mb-4">
      <?php if (!empty($user['profile_image'])): ?>
        <img style="height: 192px; width: 192px; object-fit: cover; border-radius: 50%;"
             src="<?= htmlspecialchars($user['profile_image'])  ?>"
             class="profile-image mb-3 shadow-sm"
             alt="Profile Image"
             onerror="this.onerror=null; this.src='https://via.placeholder.com/192?text=User';">
      <?php else: ?>
        <!-- Fallback avatar when no profile image -->
        <div class="avatar-fallback d-flex align-items-center justify-content-center mb-3 shadow-sm"
             style="height: 192px; width: 192px; border-radius: 50%; background:#6c757d; color:white; font-size:4rem; margin:0 auto;">
          <?= strtoupper(substr($user['username'], 0, 1)) ?>
        </div>
      <?php endif; ?>

      <h6 class="mb-1 fw-bold"><?= htmlspecialchars($user['username']) ?></h6>
      <small class="text-muted"><?= ucfirst($user['role']) ?></small>
    </div>

    <hr class="my-4">

    <!-- Menu -->
    <div class="list-group list-group-flush">

      <a href="<?= Url::to('dashboard') ?>"
         class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
        <span>📊</span> Dashboard
      </a>

      <a href="<?= Url::to('manage/profile') ?>"
         class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
        <span>👤</span> Manage Profile
      </a>

      <?php if ($user['role'] === 'principal'): ?>
        <a href="<?= Url::to('manage/departments') ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
          <span>🏢</span> Manage Departments
        </a>
        
        <a href="<?= Url::to('manage/hods') ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
          <span>👨‍🏫</span> Manage HODs
        </a>
      <?php endif; ?>

      <?php if ($user['role'] === 'hod'): ?>
        <a href="<?= Url::to('manage/coordinators') ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
          <span>👥</span> Manage Coordinators
        </a>
        <a href="<?= Url::to('manage/events') ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
          <span>📅</span> Manage Events
        </a>
      <?php endif; ?>

      <?php if ($user['role'] === 'coordinator'): ?>
        <a href="<?= Url::to('documents/checklist') ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
          <span>✨</span> Create Event
        </a>
      <?php endif; ?>

      <a href="<?= Url::to('logout') ?>"
         class="list-group-item list-group-item-action text-danger d-flex align-items-center gap-3 py-3">
        <span>🚪</span> Logout
      </a>

    </div>
  </div>
</div>