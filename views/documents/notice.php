<?php require_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../../views/includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <?php include __DIR__ . '/../includes/quick_doc_action.php'; ?>
        
        <div class="container mt-4">
            <div class="header mt-4">
                <h2 class="text-center">NOTICE</h2>
            </div>

            <!-- Display Messages (from controller/query params) -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <!-- Display Controller Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
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

            <form method="POST" action="<?= Url::to("/documents/notice/$checklist_id") ?>" novalidate>

                <input type="hidden" name="checklist_id" value="<?= htmlspecialchars($checklist_id) ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <!-- Programme (readonly) -->
                <div class="mb-3">
                    <label class="form-label">Programme Name</label>
                    <input type="text" class="form-control" name="programme_name"
                           value="<?= htmlspecialchars($programme_name ?? '') ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Programme Date</label>
                    <input type="date" class="form-control" name="programme_date" readonly
                           value="<?= htmlspecialchars($programme_date ?? '') ?>"
                           <?= ($multi_day ?? 0) ? 'readonly' : '' ?>>
                </div>

                <div class="mb-3">
                    <input type="checkbox" name="multi_day" <?= ($multi_day ?? 0) ? 'checked' : '' ?> readonly>
                    <label class="form-label">Is this programme for more than one day?</label>
                </div>

                <?php if ($multi_day ?? 0): ?>
                    <div class="mb-3">
                        <label class="form-label">Programme Start Date</label>
                        <input type="date" class="form-control" name="programme_start_date" readonly
                               value="<?= htmlspecialchars($programme_start_date ?? '') ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Programme End Date</label>
                        <input type="date" class="form-control" name="programme_end_date"  readonly
                               value="<?= htmlspecialchars($programme_end_date ?? '') ?>" readonly>
                    </div>
                <?php endif; ?>

                <hr>

                <!-- Notice Fields -->
                <div class="mb-3">
                    <label class="form-label">Notice Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control"
                           value="<?= htmlspecialchars($form_data['date'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Dear Students and Faculty <span class="text-danger">*</span></label>
                    <textarea   name="dear" class="form-control ckeditor-field" rows="4" required><?= htmlspecialchars($form_data['dear'] ?? '') ?></textarea>
                    <div id="dear-error" class="text-danger" style="display: none;">Please enter Dear Students and Faculty content.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Event Highlights <span class="text-danger">*</span></label>
                    <textarea  name="event_highlights" class="form-control ckeditor-field" rows="6" required><?= htmlspecialchars($form_data['event_highlights'] ?? '') ?></textarea>
                    <div id="highlights-error" class="text-danger" style="display: none;">Please enter Event Highlights.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Event Time <span class="text-danger">*</span></label>
                    <input type="time" name="event_time" class="form-control"
                           value="<?= htmlspecialchars($form_data['event_time'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Event Venue <span class="text-danger">*</span></label>
                    <input type="text" name="event_venue" class="form-control"
                           value="<?= htmlspecialchars($form_data['event_venue'] ?? '') ?>" required>
                </div>

                <?php 
                // Check if HOD name should be shown (only if it's not the default 'N/A' from multiple departments)
                $show_hod = (!empty($hod_name) && $hod_name !== 'N/A');
                ?>
                
                <?php if ($show_hod): ?>
                    <div class="mb-3">
                        <label class="form-label">HOD</label>
                        <input type="text" class="form-control"
                               value="<?= htmlspecialchars($hod_name) ?>" readonly>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Coordinator</label>
                    <input type="text" class="form-control"
                           value="<?= htmlspecialchars($coordinator_name ?? 'N/A') ?>" readonly>
                </div>

                <div class="d-flex gap-2">
                    <?php if ($is_update): ?>
                        <button type="submit" class="btn btn-warning" id="updateNoticeBtn">Update Notice</button>
                        <a href="<?= Url::to("/documents/view/notice/$checklist_id") ?>" 
                           class="btn btn-info">View Notice</a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary" id="saveNoticeBtn">Save Notice</button>
                    <?php endif; ?>
                    <a href="<?= Url::to('/dashboard') ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation for notice form
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const saveBtn = document.getElementById('saveNoticeBtn');
    const updateBtn = document.getElementById('updateNoticeBtn');
    
    console.log('Notice form script loaded');
    console.log('Form element:', form);
    console.log('Save button:', saveBtn);
    console.log('Update button:', updateBtn);
    
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            
            // Basic validation
            const date = document.querySelector('input[name="date"]');
            const dear = document.querySelector('textarea[name="dear"]');
            const eventHighlights = document.querySelector('textarea[name="event_highlights"]');
            const eventTime = document.querySelector('input[name="event_time"]');
            const eventVenue = document.querySelector('input[name="event_venue"]');
            
            // Get CKEditor content if available
            let dearContent = dear ? dear.value.trim() : '';
            let highlightsContent = eventHighlights ? eventHighlights.value.trim() : '';
            
            // Try to get content from CKEditor instances
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances) {
                const dearInstance = CKEDITOR.instances[dear ? dear.name : 'dear'];
                const highlightsInstance = CKEDITOR.instances[eventHighlights ? eventHighlights.name : 'event_highlights'];
                
                if (dearInstance) {
                    dearContent = dearInstance.getData().trim();
                    console.log('CKEditor dear content:', dearContent);
                }
                
                if (highlightsInstance) {
                    highlightsContent = highlightsInstance.getData().trim();
                    console.log('CKEditor highlights content:', highlightsContent);
                }
            }
            
            console.log('Form fields:', { date, dear, eventHighlights, eventTime, eventVenue });
            console.log('Content values:', { dearContent, highlightsContent });
            
            let isValid = true;
            let errorMessage = '';
            
            if (!date || !date.value) {
                isValid = false;
                errorMessage += 'Please select a Notice Date.\n';
            }
            
            if (!dearContent) {
                isValid = false;
                errorMessage += 'Please enter Dear Students and Faculty content.\n';
                // Show custom error message
                const dearError = document.getElementById('dear-error');
                if (dearError) dearError.style.display = 'block';
            } else {
                // Hide error message if content exists
                const dearError = document.getElementById('dear-error');
                if (dearError) dearError.style.display = 'none';
            }
            
            if (!highlightsContent) {
                isValid = false;
                errorMessage += 'Please enter Event Highlights.\n';
                // Show custom error message
                const highlightsError = document.getElementById('highlights-error');
                if (highlightsError) highlightsError.style.display = 'block';
            } else {
                // Hide error message if content exists
                const highlightsError = document.getElementById('highlights-error');
                if (highlightsError) highlightsError.style.display = 'none';
            }
            
            if (!eventTime || !eventTime.value) {
                isValid = false;
                errorMessage += 'Please select Event Time.\n';
            }
            
            if (!eventVenue || !eventVenue.value.trim()) {
                isValid = false;
                errorMessage += 'Please enter Event Venue.\n';
            }
            
            console.log('Validation result:', { isValid, errorMessage });
            
            if (!isValid) {
                e.preventDefault();
                alert(errorMessage);
                return false;
            }
            
            // If validation passes, allow form submission
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            } else if (updateBtn) {
                updateBtn.disabled = true;
                updateBtn.textContent = 'Updating...';
            }
            
            console.log('Form validation passed, allowing submission');
            return true;
        });
    }
    
    // Add click event listeners to buttons for debugging
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            console.log('Save Notice button clicked');
        });
    }
    
    if (updateBtn) {
        updateBtn.addEventListener('click', function() {
            console.log('Update Notice button clicked');
        });
    }
});
</script>



<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>
