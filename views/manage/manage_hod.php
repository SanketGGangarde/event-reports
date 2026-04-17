<?php
require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="container-fluid mt-3">
<div class="row">

<!-- ================= ADD / UPDATE HOD FORM ================= -->
<div class="col-md-6">
<div class="card shadow-sm">

<div class="card-header bg-primary text-white">
<h5 class="mb-0" id="formTitle">
<i class="fas fa-user-plus me-2"></i>Add New HOD
</h5>
</div>

<div class="card-body">

<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

<form method="POST"
      enctype="multipart/form-data"
      id="hodForm"
      class="needs-validation"
      novalidate
      action="<?php echo Url::to('manage-hods'); ?>">

<input type="hidden" name="hod_id" id="hod_id">
<input type="hidden" name="csrf_token"
value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

<!-- NAME -->
<div class="mb-3">
<label class="form-label">Name</label>
<input type="text"
       class="form-control"
       name="username"
       id="username"
       minlength="3"
       required>
<div class="valid-feedback">Looks good!</div>
<div class="invalid-feedback">
Name must be at least 3 characters.
</div>
</div>

<!-- EMAIL -->
<div class="mb-3">
<label class="form-label">Email</label>
<div class="input-group has-validation">
<input type="text"
       name="email"
       id="email"
       class="form-control"
       pattern="^[a-zA-Z0-9._]+$"
       required>
<span class="input-group-text">@kse.in</span>
<div class="invalid-feedback">
Enter valid username only (no spaces or special characters).
</div>
</div>
</div>

<!-- RECOVERY EMAIL -->
<div class="mb-3">
<label class="form-label">Recovery Email</label>
<input type="email"
       class="form-control"
       name="recovery_email"
       id="recovery_email"
       required>
<div class="invalid-feedback">
Enter a valid recovery email address.
</div>
</div>

<!-- CONTACT -->
<div class="mb-3">
<label class="form-label">Contact Number</label>
<input type="tel"
       class="form-control"
       name="contact_number"
       id="contact_number"
       pattern="[0-9]{10}"
       maxlength="10"
       required>
<div class="invalid-feedback">
Enter a valid 10-digit phone number.
</div>
</div>

<!-- DEPARTMENT -->
<div class="mb-3">
<label class="form-label">Department</label>
<select class="form-select"
        name="department_id"
        id="department"
        required>
<option value="">Select</option>
<?php foreach($departments as $d): ?>
<option value="<?= $d['id'] ?>">
<?= htmlspecialchars($d['name']) ?>
</option>
<?php endforeach; ?>
</select>
<div class="invalid-feedback">
Please select a department.
</div>
</div>

<!-- PASSWORD -->
<div class="mb-2" id="passwordBox">
<label>Password</label>
<div class="input-group">
<input type="password" class="form-control" name="password" id="password" required minlength="8">

<button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
👁
</button>
</div>

<div class="invalid-feedback">
Password must be at least 8 characters long.
</div>
</div>

<div class="mb-2" id="confirmBox">
<label>Confirm Password</label>

<div class="input-group">
<input type="password" class="form-control" name="confirm_password" id="confirm_password" required>

<button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)">
👁
</button>
</div>

<div class="invalid-feedback">
Passwords do not match.
</div>
<div class="valid-feedback">
Passwords  match.
</div>
</div>


<!-- PROFILE -->
<div class="mb-3">
<label class="form-label">Profile Image</label>
<input type="file"
       class="form-control"
       name="profile_image"
       id="profile_image"
       accept="image/png, image/jpeg"
       >
<div class="invalid-feedback">
Upload profile image (JPEG/PNG, max 2MB).
</div>
</div>

<!-- SIGN -->
<div class="mb-3">
<label class="form-label">Signature Image</label>
<input type="file"
       class="form-control"
       name="sign_image"
       id="sign_image"
       accept="image/png, image/jpeg"
       >
<div class="invalid-feedback">
Upload signature image (JPEG/PNG, max 2MB).
</div>
</div>

<button class="btn btn-primary w-100"
        name="create_hod"
        id="submitBtn"
        type="submit">
Create HOD
</button>

</form>
</div>
</div>
</div>

<!-- ================= HOD LIST ================= -->

<div class="col-md-6">
<div class="card shadow-sm">
<div class="card-header bg-dark text-white">
<h5 class="mb-0">HOD List</h5>
</div>

<div class="card-body">
<table class="table table-bordered table-hover">

<thead class="table-light">
<tr>
<th>Profile</th>
<th>Name</th>
<th>Email</th>
<th>Department</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach($hods as $h): ?>
<tr>
<td>
<?php if (!empty($h['profile_image'])): ?>
<img src="<?= htmlspecialchars($h['profile_image']) ?>" 
     alt="Profile" 
     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
     onerror="this.style.display='none';">
<?php else: ?>
<div style="width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 12px;">
No Image
</div>
<?php endif; ?>
</td>
<td><?= htmlspecialchars($h['username']) ?></td>
<td><?= htmlspecialchars($h['email']) ?></td>
<td><?= htmlspecialchars($h['department_name']) ?></td>
<td>
<button type="button"
class="btn btn-sm btn-info editBtn"
data-id="<?= $h['id'] ?>"
data-name="<?= htmlspecialchars($h['username']) ?>"
data-email="<?= htmlspecialchars($h['email']) ?>"
data-recovery="<?= htmlspecialchars($h['recovery_email']) ?>"
data-contact="<?= htmlspecialchars($h['contact_number']) ?>"
data-dept="<?= $h['department_id'] ?>"
data-profile="<?= htmlspecialchars($h['profile_image'] ?? '') ?>"
data-sign="<?= htmlspecialchars($h['sign_image'] ?? '') ?>">
Update
</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>
</div>
</div>

</div>
</div>

<!-- ================= JAVASCRIPT ================= -->

<script>
// Add form validation and submission handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('hodForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        // Remove novalidate to enable HTML5 validation
        form.removeAttribute('novalidate');
        
        // Check if form is valid
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            
            // Manually trigger validation feedback
            const inputs = form.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                } else {
                    input.classList.add('is-valid');
                }
            });
            
            return false;
        }
        
        // Additional custom validation - only for create mode (when password fields exist)
        const password = document.querySelector('input[name="password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');
        
        // Only validate passwords if they exist in the form (create mode)
        if (password && confirmPassword) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                confirmPassword.classList.add('is-invalid');
                alert('Passwords do not match!');
                return false;
            }
        }
        
        // Check if department is selected for HOD creation
        const department = document.getElementById('department');
        const isHODCreation = submitBtn.name === 'create_hod' || submitBtn.name === 'update_hod';
        
        if (isHODCreation && department && !department.value) {
            e.preventDefault();
            department.classList.add('is-invalid');
            alert('Please select a department!');
            return false;
        }
        
        // If all validation passes, allow form submission
        return true;
    });
    
    // Add real-time validation feedback
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
        });
    });
});

document.querySelectorAll(".editBtn").forEach(btn => {

btn.onclick = function(){

document.getElementById("formTitle").innerText="Update HOD Details";
document.getElementById("submitBtn").innerText="Update HOD";
document.getElementById("submitBtn").name="update_hod";
document.getElementById("submitBtn").classList.replace("btn-primary","btn-success");

// Change form action to update endpoint
document.getElementById("hodForm").action = "<?php echo Url::to('manage/hods/update'); ?>";

document.getElementById("hod_id").value=this.dataset.id;
document.getElementById("username").value=this.dataset.name;
document.getElementById("email").value=this.dataset.email.replace("@kse.in","");
document.getElementById("recovery_email").value=this.dataset.recovery;
document.getElementById("contact_number").value=this.dataset.contact;
document.getElementById("department").value=this.dataset.dept;

/* Remove Password Fields from Form */
const passwordBox = document.getElementById("passwordBox");
const confirmBox = document.getElementById("confirmBox");
if (passwordBox) passwordBox.remove();
if (confirmBox) confirmBox.remove();

/* Handle Image Display for Update */
const profileImage = document.getElementById("profile_image");
const signImage = document.getElementById("sign_image");

// Store current image paths in hidden fields for the update
if (this.dataset.profile) {
    // Create a hidden field to store current profile image path
    let hiddenProfile = document.getElementById("current_profile_image");
    if (!hiddenProfile) {
        hiddenProfile = document.createElement("input");
        hiddenProfile.type = "hidden";
        hiddenProfile.name = "current_profile_image";
        hiddenProfile.id = "current_profile_image";
        profileImage.parentNode.appendChild(hiddenProfile);
    }
    hiddenProfile.value = this.dataset.profile;
    
    // Show current image preview
    const profilePreview = document.getElementById("profile_preview");
    if (!profilePreview) {
        const previewDiv = document.createElement("div");
        previewDiv.className = "mt-2";
        previewDiv.innerHTML = '<img id="profile_preview" src="' + this.dataset.profile + '" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #007bff;" alt="Current Profile">';
        profileImage.parentNode.appendChild(previewDiv);
    } else {
        profilePreview.src = this.dataset.profile;
        profilePreview.style.display = 'block';
    }
}

if (this.dataset.sign) {
    // Create a hidden field to store current signature image path
    let hiddenSign = document.getElementById("current_sign_image");
    if (!hiddenSign) {
        hiddenSign = document.createElement("input");
        hiddenSign.type = "hidden";
        hiddenSign.name = "current_sign_image";
        hiddenSign.id = "current_sign_image";
        signImage.parentNode.appendChild(hiddenSign);
    }
    hiddenSign.value = this.dataset.sign;
    
    // Show current image preview
    const signPreview = document.getElementById("sign_preview");
    if (!signPreview) {
        const previewDiv = document.createElement("div");
        previewDiv.className = "mt-2";
        previewDiv.innerHTML = '<img id="sign_preview" src="' + this.dataset.sign + '" style="width: 100px; height: 50px; border: 2px solid #007bff;" alt="Current Signature">';
        signImage.parentNode.appendChild(previewDiv);
    } else {
        signPreview.src = this.dataset.sign;
        signPreview.style.display = 'block';
    }
}

};

});





// Reset form to create mode when needed
function resetToCreateMode() {
document.getElementById("formTitle").innerText="Add New HOD";
document.getElementById("submitBtn").innerText="Create HOD";
document.getElementById("submitBtn").name="create_hod";
document.getElementById("submitBtn").classList.replace("btn-success","btn-primary");

// Change form action back to create endpoint
document.getElementById("hodForm").action = "<?php echo Url::to('manage/hods'); ?>";

document.getElementById("hod_id").value="";
document.getElementById("username").value="";
document.getElementById("email").value="";
document.getElementById("recovery_email").value="";
document.getElementById("contact_number").value="";
document.getElementById("department").value="";

// Restore password fields if they were removed
const form = document.getElementById("hodForm");
if (!document.getElementById("passwordBox")) {
const passwordBox = document.createElement("div");
passwordBox.className = "mb-2";
passwordBox.id = "passwordBox";
passwordBox.innerHTML = '<label>Password</label><input type="password" class="form-control" name="password">';
form.insertBefore(passwordBox, document.getElementById("profile_image").parentElement);
}

if (!document.getElementById("confirmBox")) {
const confirmBox = document.createElement("div");
confirmBox.className = "mb-2";
confirmBox.id = "confirmBox";
confirmBox.innerHTML = '<label>Confirm Password</label><input type="password" class="form-control" name="confirm_password">';
form.insertBefore(confirmBox, document.getElementById("profile_image").parentElement);
}
}

</script>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>

