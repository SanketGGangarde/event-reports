<?php
require_once __DIR__."/../../views/layouts/header.php";



// CSRF token: generate if it does not exist
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<div class="container-fluid mt-3">

<!-- ================= DEFAULT HEADER ================= -->

<div class="row mb-4">
<div class="col-md-5">

<div class="card border-primary shadow">
<div class="card-header bg-primary text-white">
<i class="fas fa-image"></i> College Default Header
</div>

<div class="card-body">

<?php if(!empty($defaultHeader['image'])): ?>

<button class="btn btn-success w-100 mb-2"
onclick="togglePreview(this)">
View Current Header
</button>

<div id="previewBox" class="d-none text-center mb-3">
<img src="<?= htmlspecialchars($defaultHeader['image']) ?>"
class="img-fluid rounded"
style="max-height:200px;">
</div>

<?php endif; ?>

<form method="POST"
      enctype="multipart/form-data"
      class="needs-validation"
      novalidate>

<input type="hidden"
name="csrf_token"
value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<div class="mb-3">
<input type="file"
       name="default_header_image"
       class="form-control"
       accept="image/jpeg,image/png"
       required>

<div class="invalid-feedback">
Please upload a valid JPG or PNG image.
</div>
</div>

<button name="save_default_header"
class="btn btn-primary w-100">
<?= $defaultHeader?'Update':'Upload' ?>
</button>

</form>

</div>
</div>

</div>
</div>

<!-- ================= MAIN ROW ================= -->

<div class="row">

<!-- FORM -->

<div class="col-md-5">

<div class="card shadow">
<div class="card-header">
<?= $editDepartment?'Update Department':'Add Department' ?>
</div>
<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

<div class="card-body">

<form method="POST"
      enctype="multipart/form-data"
      class="needs-validation"
      novalidate>

<input type="hidden"
name="csrf_token"
value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<input type="hidden"
name="department_id"
value="<?= htmlspecialchars($editDepartment['id'] ?? '') ?>">

<input type="hidden"
name="old_image"
value="<?= htmlspecialchars($editDepartment['header_image'] ?? '') ?>">

<div class="mb-3">
<label class="form-label">Department Name</label>
<input type="text"
       name="department_name"
       class="form-control"
       value="<?= htmlspecialchars($editDepartment['name'] ?? '') ?>"
       required
       minlength="3"
       maxlength="100"
       pattern="^[A-Za-z][A-Za-z0-9\s]{2,99}$">

<div class="invalid-feedback">
Department name must start with a letter and be 3–100 characters.
</div>
</div>

<div class="mb-3">
<label class="form-label">Header Image</label>

<input type="file"
       name="header_image"
       class="form-control"
       accept="image/jpeg,image/png"
       <?= !$editDepartment ? 'required' : '' ?>>

<?php if(!$editDepartment): ?>
<div class="invalid-feedback">
Header image is required (JPG or PNG only).
</div>
<?php else: ?>
<div class="form-text">
Optional. Leave empty to keep existing image.
</div>
<?php endif; ?>

</div>

<?php if(!empty($editDepartment['header_image'])): ?>
<img src="<?= htmlspecialchars($editDepartment['header_image']) ?>"
class="img-fluid mb-2 rounded"
style="max-height:120px;">
<?php endif; ?>

<button
name="<?= $editDepartment?'update_department':'create_department' ?>"
class="btn btn-primary w-100">

<?= $editDepartment?'Update Department':'Create Department' ?>

</button>

</form>

</div>
</div>

</div>

<!-- TABLE -->

<div class="col-md-6">

<div class="card shadow">
<div class="card-header">Departments</div>

<div class="card-body">

<table class="table table-bordered table-hover text-center">

<thead class="table-light">
<tr>
<th>Name</th>
<th>Users</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php foreach($departments as $d): ?>

<tr>
<td><?= htmlspecialchars($d['name']) ?></td>

<td>
<span class="badge bg-info">
<?= $d['user_count'] ?>
</span>
</td>

<td>
<a href="?edit=<?= $d['id'] ?>"
class="btn btn-warning btn-sm">
Edit
</a>
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

<!-- ================= SCRIPTS ================= -->

<script>
// Bootstrap Validation
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

// Toggle Header Preview
function togglePreview(btn){
let box=document.getElementById("previewBox");

if(box.classList.contains("d-none")){
box.classList.remove("d-none");
btn.innerText="Hide Header";
}else{
box.classList.add("d-none");
btn.innerText="View Current Header";
}
}
</script>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>