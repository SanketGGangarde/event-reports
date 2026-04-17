<?php 
require_once __DIR__ . '/../layouts/header.php';

// CSRF token: generate if it does not exist
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<div class="container" style="max-width:600px;">
  <br><br>
  
  <!-- Flash Messages -->
  <?php if (!empty($_SESSION['errors'])): ?>
    <div class="alert alert-danger" role="alert">
      <ul class="mb-0">
        <?php foreach ($_SESSION['errors'] as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php unset($_SESSION['errors']); ?>
  <?php endif; ?>

  <h2 class="mb-4 text-center">Principal Signup</h2>

    <form class="row g-3 needs-validation" action="/event-reports/signup" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input type="text" name="username" class="form-control" id="username" required>
      <div class="valid-feedback">
        Looks good!
      </div>
      <div class="invalid-feedback">
        Please enter a valid username (minimum 3 characters).
      </div>
    </div>

    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <div class="input-group has-validation">
        <input type="text" name="email" class="form-control" id="email" required placeholder="Enter username only" onkeydown="preventAtSymbol(event)" onpaste="preventPasting(event)" oninput="preventSpecialChars(event)">
        <span class="input-group-text">@kse.in</span>
        <div class="valid-feedback">
          Looks good!
        </div>
        <div class="invalid-feedback">
          Please enter only the username part (before @). Special characters and spaces are not allowed.
        </div>
      </div>
      <small class="text-muted">Note: Type only the username part (before @). The @kse.in domain will be automatically added.</small>
    </div>

    <div class="mb-3">
      <label for="recovery_email" class="form-label">Recovery Email</label>
      <input type="email" name="recovery_email" class="form-control" id="recovery_email" required placeholder="Enter recovery email address">
      <div class="valid-feedback">
        Looks good!
      </div>
      <div class="invalid-feedback">
        Please enter a valid recovery email address.
      </div>
      <small class="text-muted">This email will be used for password recovery and account security.</small>
    </div>

    

    <div class="mb-3">
      <label for="contact_number" class="form-label">Contact Number</label>
      <input type="tel" name="contact_number" class="form-control" id="contact_number" required 
             maxlength="10" pattern="[0-9]+" placeholder="Enter your contact number">
      <div class="valid-feedback">
        Looks good!
      </div>
      <div class="invalid-feedback">
        Please enter a valid contact number (exactly 10 digits).
      </div>
      <div class="form-text">Please enter a valid phone number</div>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" minlength="8" class="form-control" id="password" required>
      <div class="valid-feedback">
        Looks good!
      </div>
      <div class="invalid-feedback">
        Password must be at least 8 characters long.
      </div>
    </div>

    <div class="mb-3">
      <label for="confirm_password" class="form-label">Confirm Password</label>
      <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
      <div class="valid-feedback">
        Passwords match!
      </div>
      <div class="invalid-feedback">
        Passwords do not match.
      </div>
    </div>

    <div class="mb-3">
      <label for="profile_image" class="form-label">Profile Image</label>
      <input type="file" name="profile_image" class="form-control" id="profile_image" accept="image/*" required>
      <div class="valid-feedback">
        Looks good!
      </div>
      <div class="invalid-feedback">
        Please select a profile image (JPEG or PNG format, max 2MB).
      </div>
    </div>


    <div class="mb-3">
      <label for="sign_image" class="form-label">Signature Image</label>
      <input type="file" name="sign_image" class="form-control" id="sign_image" accept="image/*" required>
      <div class="valid-feedback">
        Looks good!
      </div>
      <div class="invalid-feedback">
        Please select a signature image (JPEG or PNG format, max 2MB).
      </div>
    </div>

    <button class="btn btn-primary w-100">
      Create Principal Account
    </button>

  </form>
  <br><br>
</div>



<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>
