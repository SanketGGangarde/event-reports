<?php 
require_once __DIR__ . '/../layouts/header.php';

// CSRF token: generate if it does not exist
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<div class="container" style="margin-top:200px; max-width:400px; margin-bottom:200px;">

<h2 class="text-center mb-4">Verify OTP</h2>

<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

<div class="mb-3 text-muted " style="font-size:14px; color: #ff4800 !important;
}">
If your email and recovery email were correct, you should receive a One-Time Password (OTP).
If you do not receive it, please verify your credentials and request again.
</div>

<form action="<?= Url::getBaseUrl(); ?>/verify-otp"
      method="POST"
      class="needs-validation"
      novalidate>

<!-- CSRF Token -->
<input type="hidden"
       name="csrf_token"
       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<!-- OTP Input -->
<div class="mb-3">
<label class="form-label">Enter OTP</label>

<input type="text"
       name="otp"
       class="form-control text-center"
       required
       pattern="\d{6}"
       maxlength="6"
       inputmode="numeric"
       autocomplete="one-time-code">

<div class="invalid-feedback">
Please enter the 6-digit OTP sent to your recovery email.
</div>

<small class="text-muted">
Enter the 6-digit numeric code.
</small>
</div>

<button class="btn btn-primary w-100">
Verify OTP
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