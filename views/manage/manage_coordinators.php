<?php
require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="container-fluid mt-3">
<div class="row">

<!-- ================= FORM ================= -->

<div class="col-md-6">
<div class="card shadow-sm">

<div class="card-header bg-primary text-white">
<h5 id="formTitle"><i class="fas fa-plus-circle me-2"></i>Add New Coordinator</h5>
</div>

<div class="card-body">

<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

<form method="POST"
      enctype="multipart/form-data"
      id="coordinatorForm"
      class="needs-validation"
      novalidate
      action="<?= Url::to('/manage/coordinators') ?>">

<input type="hidden" name="coordinator_id" id="coordinator_id">
<input type="hidden" name="csrf_token"
value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

<!-- NAME -->
<div class="mb-3">
<label class="form-label">Name</label>
<input type="text"
       name="username"
       id="username"
       class="form-control"
       minlength="3"
       required>
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
Enter valid username only.
</div>
</div>
</div>

<!-- RECOVERY EMAIL -->
<div class="mb-3">
<label class="form-label">Recovery Email</label>
<input type="email"
       name="recovery_email"
       id="recovery_email"
       class="form-control"
       required>
<div class="invalid-feedback">
Enter valid recovery email.
</div>
</div>

<!-- CONTACT -->
<div class="mb-3">
<label class="form-label">Contact Number</label>
<input type="tel"
       name="contact_number"
       id="contact_number"
       pattern="[0-9]{10}"
       maxlength="10"
       class="form-control"
       required>
<div class="invalid-feedback">
Enter valid 10-digit number.
</div>
</div>

<!-- PASSWORD -->
<div class="mb-3" id="passwordBox">
<label>Password</label>
<input type="password"
       name="password"
       class="form-control"
       minlength="8"
       required>
<div class="invalid-feedback">
Minimum 8 characters required.
</div>
</div>

<!-- CONFIRM -->
<div class="mb-3" id="confirmBox">
<label>Confirm Password</label>
<input type="password"
       name="confirm_password"
       id="confirm_password"
       class="form-control"
       required>
<div class="invalid-feedback">
Passwords must match.
</div>
</div>

<!-- PROFILE -->
<div class="mb-3">
<label class="form-label">Profile Image</label>
<input type="file"
       name="profile_image"
       id="profile_image"
       accept="image/png, image/jpeg"
       class="form-control">
<div id="profilePreviewContainer" class="mt-2"></div>
</div>

<!-- SIGN -->
<div class="mb-3">
<label class="form-label">Signature Image</label>
<input type="file"
       name="sign_image"
       id="sign_image"
       accept="image/png, image/jpeg"
       class="form-control">
<div id="signPreviewContainer" class="mt-2"></div>
</div>

<button class="btn btn-primary w-100"
        id="submitBtn"
        name="create_coordinator"
        type="submit">
Create Coordinator
</button>

</form>
</div>
</div>
</div>

<!-- ================= LIST ================= -->

<div class="col-md-6">
<div class="card shadow-sm">

<div class="card-header bg-dark text-white">
<h5>Coordinators List</h5>
</div>

<div class="card-body">

<table class="table table-bordered table-hover">
<thead class="table-light">
<tr>
<th>Name</th>
<th>Email</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach($coordinators as $c): ?>
<tr>
<td><?= htmlspecialchars($c['username']) ?></td>
<td><?= htmlspecialchars($c['email']) ?></td>
<td><?= date("d-m-Y",strtotime($c['created_at'])) ?></td>
<td>
<button type="button"
class="btn btn-sm btn-info editBtn"

data-id="<?= $c['id'] ?>"
data-name="<?= htmlspecialchars($c['username']) ?>"
data-email="<?= htmlspecialchars($c['email']) ?>"
data-recovery="<?= htmlspecialchars($c['recovery_email']) ?>"
data-contact="<?= htmlspecialchars($c['contact_number']) ?>"
data-profile="<?= htmlspecialchars($c['profile_image']) ?>"
data-sign="<?= htmlspecialchars($c['sign_image']) ?>"
>
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
document.addEventListener('DOMContentLoaded', function () {

const form = document.getElementById('coordinatorForm');
const submitBtn = document.getElementById('submitBtn');
const profileContainer = document.getElementById("profilePreviewContainer");
const signContainer = document.getElementById("signPreviewContainer");

/* ================= BOOTSTRAP VALIDATION ================= */

form.addEventListener('submit', function (e) {

    if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    }

    const password = form.querySelector('input[name="password"]');
    const confirm = document.getElementById('confirm_password');

    if (password && confirm && password.value !== confirm.value) {
        e.preventDefault();
        confirm.classList.add('is-invalid');
        alert('Passwords do not match');
    }

    form.classList.add('was-validated');

}, false);


/* ================= LIVE IMAGE PREVIEW ================= */

document.getElementById("profile_image").addEventListener("change", function(e){
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(ev){
            profileContainer.innerHTML = `
                <img src="${ev.target.result}" 
                     style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:2px solid #28a745;">
            `;
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById("sign_image").addEventListener("change", function(e){
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(ev){
            signContainer.innerHTML = `
                <img src="${ev.target.result}" 
                     style="width:120px;height:60px;border:2px solid #28a745;">
            `;
        };
        reader.readAsDataURL(file);
    }
});


/* ================= EDIT BUTTON ================= */

document.querySelectorAll(".editBtn").forEach(btn => {

    btn.onclick = function(){

        document.getElementById("formTitle").innerText="Update Coordinator";
        submitBtn.innerText="Update Coordinator";
        submitBtn.name="update_coordinator";
        submitBtn.classList.replace("btn-primary","btn-success");

        form.action="<?= Url::to('/manage/coordinators/update') ?>";

        document.getElementById("coordinator_id").value=this.dataset.id;
        document.getElementById("username").value=this.dataset.name;
        document.getElementById("email").value=this.dataset.email.replace("@kse.in","");
        document.getElementById("recovery_email").value=this.dataset.recovery;
        document.getElementById("contact_number").value=this.dataset.contact;

        /* Hide Password Fields */
        const passBox = document.getElementById("passwordBox");
        const confirmBox = document.getElementById("confirmBox");
        if(passBox) passBox.remove();
        if(confirmBox) confirmBox.remove();

        /* Show Current Profile Image */
        profileContainer.innerHTML = "";
        if(this.dataset.profile){
            profileContainer.innerHTML = `
                <img src="${this.dataset.profile}" 
                     style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:2px solid #007bff;">
            `;
        }

        /* Show Current Signature Image */
        signContainer.innerHTML = "";
        if(this.dataset.sign){
            signContainer.innerHTML = `
                <img src="${this.dataset.sign}" 
                     style="width:120px;height:60px;border:2px solid #007bff;">
            `;
        }

    };

});

});
</script>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>