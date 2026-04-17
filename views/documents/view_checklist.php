<?php
require_once __DIR__ . '/../layouts/header.php';


if(!isset($_SESSION['user_id'])){
    header("Location: /event-reports/views/auth/login.php");
    exit;
}


require_once __DIR__ . '/../../init/_dbconnect.php';
require_once __DIR__ . '/../../controllers/checklistController.php';

$controller = new ChecklistController($pdo);

$id = $_GET['id'] ?? null;

if(!$id){
    header("Location: /dashboard?error=invalid_id");
    exit;
}

$viewData = $controller->view($id);

$checklist = $viewData['checklist'];
$department = $viewData['department'];
$invitation = $viewData['invitation'];
$guests = $viewData['guests'];
$incharges = $viewData['incharges'];
$departments = $viewData['departments'];

$checklist_id = $checklist['id'];
?>

<div class="container-fluid mt-4">
  <div class="row">
    <?php include __DIR__ . '/../includes/quick_doc_action.php'; ?>

<div class="container mb-5 mt-5">
    <style>
        /* Force all form elements to be clickable */
        .form-control, .form-check-input, textarea, input[type="text"], input[type="email"], input[type="date"], input[type="time"], input[type="file"] {
            cursor: text !important;
            pointer-events: auto !important;
            z-index: 10 !important;
            position: relative !important;
        }
        
        .form-check-input {
            cursor: pointer !important;
            pointer-events: auto !important;
            z-index: 10 !important;
        }
        
        .form-check-label {
            cursor: pointer !important;
            pointer-events: auto !important;
            user-select: none;
        }
        
        /* Make buttons clickable */
        .btn {
            cursor: pointer !important;
            pointer-events: auto !important;
            z-index: 10 !important;
        }
        
        /* Ensure table inputs are clickable */
        .table .form-control {
            cursor: text !important;
            pointer-events: auto !important;
            min-height: 38px;
            padding: 6px 12px;
        }
        
        /* Remove any blocking overlays */
        .container, .card, .card-body, .table, .form-group, .mb-3, .mb-2, .mt-2, .mt-3, .mt-4, .mt-5 {
            pointer-events: auto !important;
            z-index: 1 !important;
        }
        
        /* Ensure the entire form is clickable */
        form {
            pointer-events: auto !important;
        }
    </style>

        <div class="header mb-2" style="margin-top:0px;">
        <h1 style="color:white;"><center>Edit Checklist </center></h1>
        </div>

<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>
<?php require_once __DIR__ . '/../../helpers/csrf_helper.php'; ?>

<form action="<?= Url::to('/documents/checklist/update/'.$checklist_id) ?>"
method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
<input type="hidden" name="id" value="<?= $checklist_id ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

<!-- BASIC -->
<label>Coordinator Name</label>
<input type="text" name="coordinator_name" class="form-control mb-2" readonly
value="<?= $checklist['coordinator_name'] ?>">

<div class="mb-3">
<label>Programme Name</label>
<input type="text" name="programme_name" class="form-control" id="programme_name" value="<?= $checklist['programme_name'] ?>" required>
<div class="invalid-feedback">Programme Name is required.</div>
</div>

<div class="mb-3" id="single_day_div">
<label class="mt-2 mb-2">Programme Date</label>
<input type="date" 
       id="programme_date"
       class="form-control" 
       name="programme_date"
       value="<?= $checklist['programme_date'] ?>">
<div class="invalid-feedback">Programme Date is required.</div>
</div>

<div class="form-check mb-3">
<input type="checkbox" 
       id="multi_day"
       name="multi_day"
       class="form-check-input mt-2 mb-2"
        <?= $checklist['multi_day']?'checked':'' ?>>
<label class="form-check-label" for="multi_day">Is this programme for more than one day?</label>
</div>

<div id="programme_dates" class="mb-3" style="display:none;">
<label>Programme Start Date</label>
<input type="date" 
       class="form-control"
       id="programme_start_date"
       name="programme_start_date"
       value="<?= $checklist['programme_start_date'] ?>">
<div class="invalid-feedback">Start Date is required for multi-day programmes.</div>

<label>Programme End Date</label>
<input type="date" 
       class="form-control"
       id="programme_end_date"
       name="programme_end_date"
       value="<?= $checklist['programme_end_date'] ?>">
<div class="invalid-feedback">End Date is required for multi-day programmes.</div>
</div>

<hr>
<!-- DEPARTMENT -->
<label>Department</label><br>
<?php
foreach($departments as $d){
$chk=in_array($d['id'],$department)?"checked":"";
?>
<div class="form-check">
<input type="checkbox" name="department[]" class="form-check-input" value="<?= $d['id'] ?>" <?= $chk ?> id="dept_<?= $d['id'] ?>">
<label class="form-check-label" for="dept_<?= $d['id'] ?>"><?= $d['name'] ?></label>
</div>
<?php } ?>

<hr>

<!-- INVITATION -->
<label>Invitation to related faculty/students/dept</label><br>
<?php
$years=["F.E","S.E","T.E","B.E"];
foreach($years as $y){
$chk=in_array($y,$invitation)?"checked":"";
?>
<div class="form-check">
<input type="checkbox" name="invitation[]" class="form-check-input" value="<?= $y ?>" <?= $chk ?> id="inv_<?= $y ?>">
<label class="form-check-label" for="inv_<?= $y ?>"><?= $y ?></label>
</div>
<?php } ?>

<br>
<div class="form-check mb-3">
<input type="checkbox" name="communication" class="form-check-input" <?= $checklist['communication']?'checked':'' ?> id="communication">
<label class="form-check-label" for="communication">Communication Pre-Visit</label>
</div>
<textarea name="communication_details" class="form-control mb-3"><?= $checklist['communication_details'] ?? '' ?></textarea>
<hr>
<!-- CHECKLIST ITEMS -->
<?php
$items = [
'transportation','invitation_letter','welcome_banner','gifts','bouquets','shawls',
'cleanliness','water_bottles','snacks','tea_coffee','itinerary','white_board_welcome',
'cleanliness_seminar_hall','mike_speaker','decoration','projector','genset',
'candle_oil_garland_flowers','saraswati_pooja','saraswati_geet','name_plates',
'note_pad','pen','water_bottle_on_dias','itinerary_dias','photo_frame','video_shooting',
'photo_shooting','social_media','impression_book','post_communication','college_database',
'thanks_letter','others'
];

foreach($items as $item){
?>
<div class="form-check mt-2">
<input type="checkbox" name="<?= $item ?>" class="form-check-input" id="<?= $item ?>" <?= $checklist[$item]?'checked':'' ?>>
<label class="form-check-label" for="<?= $item ?>"><?= ucwords(str_replace('_',' ',$item)) ?></label>
</div>
<textarea name="<?= $item ?>_details" class="form-control mb-2"><?= $checklist[$item.'_details'] ?></textarea>
<?php } ?>

<hr>
<h3>Guest Details Entry</h3>

<table border="1" id="guestTable" class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Company</th>
            <th>Designation</th>
            <th>Email</th>
            <th>Bio Image</th>
            <th>Contact</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        <?php if (!empty($guests)) { ?>
            <?php foreach($guests as $g){ ?>
                <tr>
                    <td>
                        <input type="hidden" name="guest_id[]" value="<?= $g['id'] ?>">
                        <input type="text" name="guest_name[]" class="form-control"
                               value="<?= htmlspecialchars($g['guest_name']) ?>" required>
                        <div class="invalid-feedback">Guest name is required.</div>
                    </td>

                    <td>
                        <input type="text" name="company_name[]" class="form-control"
                               value="<?= htmlspecialchars($g['company_name']) ?>" required>
                        <div class="invalid-feedback">Company is required.</div>
                    </td>

                    <td>
                        <input type="text" name="designation[]" class="form-control"
                               value="<?= htmlspecialchars($g['designation']) ?>" required>
                        <div class="invalid-feedback">Designation is required.</div>
                    </td>

                    <td>
                        <input type="email" name="guest_email[]" class="form-control"
                               value="<?= htmlspecialchars($g['guest_email']) ?>">
                    </td>

                    <td>
                        <?php if(!empty($g['bio_image'])){ ?>
                            <a href="<?= $g['bio_image'] ?>" target="_blank">View</a><br>
                        <?php } ?>
                        <input type="file" name="bio_image[]" class="form-control">
                    </td>

                    <td>
                        <input type="text" name="contact_no[]"
                               class="form-control guest-contact"
                               value="<?= htmlspecialchars($g['contact_no']) ?>"
                               maxlength="10" pattern="\d{10}" required>
                        <div class="invalid-feedback">
                            Contact must be exactly 10 digits.
                        </div>
                    </td>

                    <td class="text-center">
                        <button type="button"
                                class="btn btn-danger btn-sm"
                                onclick="deleteGuestRow(this)">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php } ?>
        
           
        <?php } ?>
    </tbody>
</table>
<button type="button" class="btn btn-secondary mb-3" onclick="addGuestRow()">Add Row</button>


<hr>

<h3>Program Incharge</h3>

<table border="1" id="incharge_table" class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Task</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

    <?php if (!empty($incharges)) { 
        foreach ($incharges as $index => $p) { ?>
        
        <tr data-id="<?= $p['id']; ?>">
            <!-- Index Column -->
            <td><?= $index + 1; ?></td>

            <!-- Hidden ID -->
            <input type="hidden" name="incharge_id[]" value="<?= $p['id']; ?>">

            <td>
                <input type="text" 
                       name="incharge_name[]" 
                       class="form-control"
                       value="<?= htmlspecialchars($p['incharge_name']); ?>">
            </td>

            <td>
                <textarea name="task[]" 
                          class="form-control"><?= htmlspecialchars($p['task']); ?></textarea>
            </td>

            <td>
                <button type="button"
                        class="btn btn-danger"
                        onclick="deleteInchargeRow(this)">
                    Delete
                </button>
            </td>
        </tr>

    <?php } } ?>

    </tbody>
</table>
<button type="button" class="btn btn-secondary mb-3" onclick="addInchargeRow()">Add Incharge</button>
<hr>
<!-- APPLICATION LETTER -->
<?php if($checklist['application_letter']){ ?>
Current File :
<a href="<?= $checklist['application_letter'] ?>" target="_blank">View</a>
<?php } ?>

<div class="mb-3">
<label>Upload New Application Letter</label>
<input type="file" name="application_letter" class="form-control">
</div>

<button type="submit" class="btn btn-primary mb-5">Update Checklist</button>



</form>
</div>
</div>
</div>

<script>
// Bootstrap validation
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function (event) {
            const multiDay = document.getElementById('multi_day').checked;

            // Multi-day validation
            if (multiDay) {
                const start = document.getElementById('programme_start_date');
                const end = document.getElementById('programme_end_date');
                if (!start.value) start.classList.add('is-invalid');
                if (!end.value) end.classList.add('is-invalid');
            }

            // Guest contact validation
            const contacts = document.querySelectorAll('.guest-contact');
            contacts.forEach(input => {
                const val = input.value;
                if(!/^\d{10}$/.test(val)){
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }
            });

            // Guest email validation
            const emails = document.querySelectorAll('input[name="guest_email[]"]');
            emails.forEach(input => {
                const val = input.value;
                if(val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)){
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!form.checkValidity() || (multiDay && (!document.getElementById('programme_start_date').value || !document.getElementById('programme_end_date').value))) {
                event.preventDefault()
                event.stopPropagation()
            }

            form.classList.add('was-validated')
        }, false)
    })
})();
</script>




<script src="/public/js/checklistValidation.js"></script>
<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>
