<?php 
require_once __DIR__ . '/../layouts/header.php';

// CSRF token: generate if it does not exist
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<div class="container" style="margin-top:200px; max-width:500px; margin-bottom:200px;">
  <h2 class="text-center mb-4">Reset Password</h2>
  <?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

  <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
  <?php endif; ?>

  <form action="<?php echo Url::getBaseUrl(); ?>/reset-password" method="POST">
    <div class="mb-3">
      <label class="form-label">New Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Confirm Password</label>
      <input type="password" name="confirm_password" class="form-control" required>
    </div>

    <button class="btn btn-primary w-100">Reset Password</button>
  </form>
</div>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>
