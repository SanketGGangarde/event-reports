<?php require_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">

            <?php include __DIR__ . '/../includes/quick_doc_action.php'; ?>

       <div class="container mt-4">
    <div class="header mt-4">
    <h2 class="text-center">EVENT REPORT</h2>
</div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?>
                            <p class="mb-1"><?= htmlspecialchars($err) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

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

                <form action="<?= Url::to('/documents/event-report/' . $checklist_id) ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="checklist_id" value="<?= $checklist_id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <!-- EVENT BASIC INFO -->
                    <div class="mb-4">
                        <label class="mt-2 mb-1 fw-bold">Event Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($programme['programme_name'] ?? '—') ?>" readonly>

                        <label class="mt-3 mb-1 fw-bold">Date</label>
                        <input type="date" class="form-control" value="<?= htmlspecialchars($programme['programme_date'] ?? '') ?>" readonly>

                        <?php if (!empty($programme['multi_day'])): ?>
                            <label class="mt-3 mb-1 fw-bold">Start Date</label>
                            <input type="date" class="form-control" value="<?= htmlspecialchars($programme['programme_start_date'] ?? '') ?>" readonly>

                            <label class="mt-3 mb-1 fw-bold">End Date</label>
                            <input type="date" class="form-control" value="<?= htmlspecialchars($programme['programme_end_date'] ?? '') ?>" readonly>
                        <?php endif; ?>

                        <label class="mt-3 mb-1 fw-bold">Time</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($event_time) ?>" readonly>

                        <label class="mt-3 mb-1 fw-bold">Venue</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($event_venue) ?>" readonly>
                    </div>

                    <hr class="my-4">

                    <?php if (!empty($guests)): ?> 

                    <!-- GUEST DETAILS (styled like checklist.php tables) -->
                    <h3 class="mt-4 mb-3">Guest Details</h3>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover">
                            <thead style="background:#0d6efd; color:white;">
                                <tr>
                                    <th>Name</th>
                                    <th>Company / Organization</th>
                                    <th>Designation</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                               
                                    <?php foreach ($guests as $g): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($g['guest_name'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($g['company_name'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($g['designation'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($g['guest_email'] ?? '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                
                                
                            </tbody>
                        </table>
                    </div>

                    <?php endif; ?>

                    <hr class="my-4">

                    <!-- REPORT CONTENT -->
                   

                    <label class="mt-2 mb-1 fw-bold">Description</label>
                    <textarea class="form-control mb-3 ckeditor-field" name="description" rows="5"  ><?= $form_data['description'] ?></textarea>

                    <label class="mt-2 mb-1 fw-bold">Activities and Highlights</label>
                    <textarea class="form-control mb-3 ckeditor-field" name="activities" rows="5" ><?= $form_data['activities'] ?></textarea>

                    <label class="mt-2 mb-1 fw-bold">Significance</label>
                    <textarea class="form-control mb-3 ckeditor-field" name="significance" rows="5" ><?= $form_data['significance'] ?></textarea>

                    <label class="mt-2 mb-1 fw-bold">Conclusion</label>
                    <textarea class="form-control mb-3 ckeditor-field" name="conclusion" rows="5" ><?= $form_data['conclusion'] ?></textarea>

                    <label class="mt-2 mb-1 fw-bold">Faculties' Responses & Participation</label>
                    <textarea class="form-control mb-4 ckeditor-field" name="faculties_participation" rows="5" ><?= $form_data['faculties_participation'] ?></textarea>

                    <hr class="my-4">
<!-- PHOTOS -->
<h3 class="mt-4 mb-3">Event Photos</h3>

<table id="photoTable" class="table table-bordered mb-4">
    <thead style="background:#0dcaf0; color:white;">
        <tr>
            <th style="width:35%">Preview / Upload</th>
            <th style="width:45%">Caption</th>
            <th style="width:20%">Action</th>
        </tr>
    </thead>
    <tbody id="photoTableBody">

        <!-- Existing saved photos (now from Cloudinary) -->
        <?php foreach ($form_data['photos'] ?? [] as $idx => $photo_url): ?>
        <tr class="photo-row existing-photo" data-type="existing">
            <td>
                <?php if ($photo_url): ?>
                <img src="<?= htmlspecialchars($photo_url) ?>"
                     class="img-thumbnail mb-2"
                     alt="Event photo <?= $idx + 1 ?>"
                     style="max-width:220px; max-height:140px; object-fit:cover; border-radius:6px;">
                <?php else: ?>
                <div class="text-muted">No preview available</div>
                <?php endif; ?>

                <!-- Preserve this photo & public_id unless user deletes the row -->
                <input type="hidden" name="kept_photos[]"     value="<?= htmlspecialchars($photo_url) ?>">
                <input type="hidden" name="kept_public_ids[]" value="<?= htmlspecialchars($form_data['public_ids'][$idx] ?? '') ?>">
                <input type="hidden" name="kept_captions[]"   value="<?= htmlspecialchars($form_data['captions'][$idx] ?? '') ?>">
            </td>
            <td>
                <input type="text" name="kept_captions_display[]"
                       class="form-control"
                       value="<?= htmlspecialchars($form_data['captions'][$idx] ?? '') ?>"
                       placeholder="Enter caption for this photo">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm delete-photo-btn">Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>

        <!-- If no photos exist yet, show one empty new upload row -->
        <?php if (empty($form_data['photos'])): ?>
        <tr class="photo-row new-photo" data-type="new">
            <td>
                <input type="file" name="new_photos[]" class="form-control photo-input" accept="image/jpeg,image/png,image/gif">
                <small class="text-muted d-block mt-1">Max 5MB • JPEG, PNG, GIF</small>
            </td>
            <td>
                <input type="text" name="new_captions[]" class="form-control" placeholder="Enter caption">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm delete-photo-btn">Delete</button>
            </td>
        </tr>
        <?php endif; ?>

    </tbody>
</table>

<button type="button" class="btn btn-success mb-4" onclick="addNewPhotoRow()">
    <i class="bi bi-plus-lg"></i> Add More Photo
</button>
                    <!-- SIGNATORIES -->
                    <div class="mb-5">
                        <div class="row g-4">
                            <?php 
                            // Check if HOD name should be shown (only if it's not the default 'Not assigned' from multiple departments)
                            $show_hod = (!empty($hod_name) && $hod_name !== 'Not assigned');
                            ?>
                            
                            <?php if ($show_hod): ?>
                                <div class="col-md-6">
                                    <label class="mb-1 fw-bold">HOD Name</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($hod_name) ?>" readonly>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-md-6">
                                <label class="mb-1 fw-bold">Coordinator Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($coordinator_name) ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- ACTION BUTTONS -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <?= $is_update ? 'Update Event Report' : 'Create Event Report' ?>
                        </button>

                        <div>
                            <?php if ($is_update): ?>
                                <a href="<?= Url::to("/documents/view/event-report/$checklist_id") ?>" 
                                   class="btn btn-info btn-lg px-4 me-3" 
                                   style="text-decoration:none;">
                                    View Report
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </form>

            </div> <!-- end container mb-5 -->
        </div> <!-- end col-lg-10 -->

    </div> <!-- end row -->
</div> <!-- end container-fluid -->
<script>
/* ================================
   PHOTO ROW MANAGEMENT
================================ */
function addNewPhotoRow() {
    const tbody = document.getElementById('photoTableBody');
    const row = document.createElement('tr');
    row.className = 'photo-row new-photo';
    row.dataset.type = 'new';

    row.innerHTML = `
        <td>
            <input type="file" name="new_photos[]" class="form-control photo-input" accept="image/jpeg,image/png,image/gif">
            <small class="text-muted d-block mt-1">Max 5MB • JPEG, PNG, GIF</small>
        </td>
        <td>
            <input type="text" name="new_captions[]" class="form-control" placeholder="Enter caption">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm delete-photo-btn">Delete</button>
        </td>
    `;

    tbody.appendChild(row);
    // No need to disable anymore
}

function updateDeleteButtons() {
    // We no longer disable the last delete button
    // Optional: you can add visual hint if you want
    document.querySelectorAll('#photoTableBody .delete-photo-btn').forEach(btn => {
        btn.title = "Remove this photo";
        btn.disabled = false;
    });
}

// Delete row handler
document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('delete-photo-btn')) return;

    const row = e.target.closest('tr');
    if (!row) return;

    row.remove();

    // If no rows left → automatically add one empty row (optional but nice UX)
    const remainingRows = document.querySelectorAll('#photoTableBody .photo-row');
    if (remainingRows.length === 0) {
        addNewPhotoRow();
    }

    updateDeleteButtons();
});

// Live preview for newly selected files
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('photo-input')) return;

    const file = e.target.files[0];
    if (!file) return;

    const td = e.target.closest('td');
    let img = td.querySelector('img.preview-img');

    if (!img) {
        img = document.createElement('img');
        img.className = 'preview-img img-thumbnail mb-2';
        img.style.maxWidth = '220px';
        img.style.maxHeight = '140px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '6px';
        td.insertBefore(img, e.target);
    }

    const reader = new FileReader();
    reader.onload = function(ev) {
        img.src = ev.target.result;
        img.alt = file.name;
    };
    reader.readAsDataURL(file);
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateDeleteButtons();

    document.querySelectorAll('.existing-photo img').forEach(img => {
        if (!img.alt) img.alt = "Existing event photo";
    });
});
</script>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>