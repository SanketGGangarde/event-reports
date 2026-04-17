<?php 
require_once __DIR__ . '/../layouts/header.php';

// Generate CSRF token if not exists
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<div class="container" style="margin-top:200px; max-width:500px; margin-bottom:200px;">

<h2 class="text-center mb-4">Forgot Password</h2>

<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

<form action="<?= Url::getBaseUrl(); ?>/send-reset-otp"
      method="POST"
      class="needs-validation"
      novalidate>

<!-- CSRF Token -->
<input type="hidden"
       name="csrf_token"
       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<!-- Email -->
<div class="mb-3">
<label class="form-label">Registered Email</label>

<input type="email"
       name="email"
       class="form-control"
       required
       maxlength="100">

<div class="invalid-feedback">
Please enter your registered email address.
</div>

<small class="text-muted">
Enter the email associated with your account.
</small>
</div>

<!-- Recovery Email -->
<div class="mb-3">
<label class="form-label">Recovery Email</label>

<input type="email"
       name="recovery_email"
       class="form-control"
       required
       maxlength="100">

<div class="invalid-feedback">
Please enter your recovery email address.
</div>

<small class="text-muted">
This must match the recovery email set during registration.
</small>
</div>

<button class="btn btn-primary w-100">
Send OTP
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

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>