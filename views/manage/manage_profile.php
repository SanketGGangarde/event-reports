<?php
require_once __DIR__ . '/../../init/session.php';
require_once __DIR__ . '/../../init/_dbconnect.php';
require_once __DIR__ . '/../../views/layouts/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . Url::to('login'));
    exit;
}

global $view_user, $csrf_token, $errors, $success;

$view_user = $view_user ?? [];
$csrf_token = $csrf_token ?? '';
$errors = $errors ?? [];
$success = $success ?? '';
?>

<div class="container mt-5">
<div class="card shadow">

<div class="card-header bg-primary text-white">
<h4 class="mb-0">Manage Profile</h4>
</div>

<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

<div class="card-body">

<form class="needs-validation" method="POST" enctype="multipart/form-data" novalidate>

<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

<!-- Username + Role -->
<div class="row mb-3">
<div class="col-md-6">
<label for="username" class="form-label">Username</label>
<input type="text" name="username" class="form-control" id="username"
value="<?= htmlspecialchars($view_user['username']) ?>" required minlength="3">
<div class="invalid-feedback">
Username must be at least 3 characters.
</div>
</div>

<div class="col-md-6">
<label class="form-label">Role</label>
<input class="form-control" value="<?= ucfirst($view_user['role']) ?>" readonly>
</div>
</div>

<!-- Email -->
<div class="mb-3">
<label for="email" class="form-label">Email</label>
<div class="input-group">
<input type="text" name="email" class="form-control" id="email"
value="<?= str_replace('@kse.in', '', $view_user['email']) ?>" required>
<span class="input-group-text">@kse.in</span>
<div class="invalid-feedback">
Please enter your official email username.
</div>
</div>
</div>

<!-- Recovery Email -->
<div class="mb-3">
<label for="recovery_email" class="form-label">Recovery Email</label>
<input type="email" name="recovery_email" class="form-control"
id="recovery_email" value="<?= $view_user['recovery_email'] ?>">
<div class="invalid-feedback">
Please enter a valid recovery email address.
</div>
</div>

<!-- Contact Number -->
<div class="mb-3">
<label for="contact_number" class="form-label">Contact Number</label>
<input type="text" name="contact_number" class="form-control"
id="contact_number" value="<?= $view_user['contact_number'] ?>"
pattern="[0-9]{10}" required>
<div class="invalid-feedback">
Contact number must be 10 digits.
</div>
</div>

<hr>

<!-- Images -->
<div class="row mb-3">

<!-- Profile Image -->
<div class="col-md-6">
<label for="profile_image" class="form-label">Profile Image</label>

<input type="file" name="profile_image" class="form-control"
id="profile_image" accept="image/*">

<!-- Hidden current image -->
<input type="hidden" name="current_profile_image"
value="<?= htmlspecialchars($view_user['profile_image'] ?? '') ?>">

<!-- Preview Container -->
<div id="profilePreviewContainer" class="mt-2">
<?php if(!empty($view_user['profile_image'])): ?>
<img src="<?= $view_user['profile_image'] ?>"
style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:2px solid #007bff;">
<?php endif; ?>
</div>
</div>


<!-- Signature Image -->
<div class="col-md-6">
<label for="sign_image" class="form-label">Signature Image</label>

<input type="file" name="sign_image" class="form-control"
id="sign_image" accept="image/*">

<!-- Hidden current image -->
<input type="hidden" name="current_sign_image"
value="<?= htmlspecialchars($view_user['sign_image'] ?? '') ?>">

<!-- Preview Container -->
<div id="signPreviewContainer" class="mt-2">
<?php if(!empty($view_user['sign_image'])): ?>
<img src="<?= $view_user['sign_image'] ?>"
style="width:120px;height:60px;border:2px solid #007bff;object-fit:contain;">
<?php endif; ?>
</div>
</div>

</div>

<hr>

<h5 class="mb-3">Change Password</h5>

<div class="mb-3">
<label for="current_password" class="form-label">Current Password</label>

<div class="input-group">
<input type="password" name="current_password"
class="form-control" id="current_password" >

<button class="btn btn-outline-secondary" type="button"
onclick="togglePassword('current_password', this)">👁</button>
</div>

<div class="invalid-feedback">
Please enter your current password.
</div>
</div>

<div class="mb-3">
<label for="new_password" class="form-label">New Password</label>

<div class="input-group">
<input type="password" name="new_password"
class="form-control" id="new_password" minlength="8" >

<button class="btn btn-outline-secondary" type="button"
onclick="togglePassword('new_password', this)">👁</button>
</div>

<div class="invalid-feedback">
Password must be at least 8 characters.
</div>
</div>

<div class="mb-3">
<label for="confirm_password" class="form-label">Confirm New Password</label>

<div class="input-group">
<input type="password" name="confirm_password"
class="form-control" id="confirm_password" >

<button class="btn btn-outline-secondary" type="button"
onclick="togglePassword('confirm_password', this)">👁</button>
</div>

<div class="invalid-feedback">
Passwords do not match.
</div>

<div class="valid-feedback">
Passwords matched!
</div>
</div>

<button class="btn btn-primary w-100">Update Profile</button>

</form>

</div>
</div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {

    // Profile Preview
    document.getElementById("profile_image").addEventListener("change", function(e) {
        if (e.target.files.length > 0) {
            let reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById("profilePreviewContainer").innerHTML =
                    `<img src="${event.target.result}"
                    style="width:100px;height:100px;border-radius:50%;
                    object-fit:cover;border:2px solid #007bff;">`;
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // Signature Preview
    document.getElementById("sign_image").addEventListener("change", function(e) {
        if (e.target.files.length > 0) {
            let reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById("signPreviewContainer").innerHTML =
                    `<img src="${event.target.result}"
                    style="width:120px;height:60px;border:2px solid #007bff;
                    object-fit:contain;">`;
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

});
</script>
<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>


