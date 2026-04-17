<?php 
require_once __DIR__ . '/../layouts/header.php';

// Generate CSRF token if not exists
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<div class="container" style="margin-top:200px; max-width:500px; margin-bottom:200px;">

<h2 class="text-center mb-4">Login</h2>

<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

<?php if (!empty($_GET['error'])): ?>
<div class="alert alert-danger">
Invalid email or password.
</div>
<?php endif; ?>

<form action="/event-reports/login"
      method="POST"
      class="needs-validation"
      novalidate>

<input type="hidden"
       name="csrf_token"
       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<!-- Email -->
<div class="mb-3">
<label class="form-label">Email</label>

<input type="email"
       name="email"
       class="form-control"
       required
       maxlength="100">

<div class="invalid-feedback">
Please enter a valid email address.
</div>

<small class="text-muted">
Enter your full email (e.g., username@kse.in)
</small>
</div>

<!-- Password -->
<div class="mb-3">
<label class="form-label">Password</label>

<input type="password"
       name="password"
       class="form-control"
       required
       minlength="6">

<div class="invalid-feedback">
Password is required (minimum 8 characters).
</div>
</div>

<!-- Forgot Password -->
<div class="mb-3 text-end">
<a href="<?= Url::to('forgot-password') ?>"
   class="text-decoration-none">
<i class="fas fa-key"></i> Forgot password?
</a>
</div>

<button class="btn btn-primary w-100">
Login
</button>

</form>

</div>

<br><br>

<script>
// Bootstrap 5 Validation Script
(() => {
'use strict';
const forms = document.querySelectorAll('.needs-validation');

Array.from(forms).forEach(form => {
form.addEventListener('submit', event => {
if (!form.checkValidity()) {
event.preventDefault();
event.stopPropagation();
}
form.classList.add('was-validated');
}, false);
});
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>