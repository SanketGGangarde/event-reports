<?php require_once __DIR__ . '/../../views/layouts/header.php'; ?>

<link rel="stylesheet" href="/event-reports/public/css/view.css">

<div class="container">
    <div id="invitation-card" class="card mt-5">
        <div class="card-body">
            <!-- Department Header Image -->
            <div class="img-logo text-center">
                <?php if (!empty($header_image)): ?>
                    <img src="<?= htmlspecialchars($header_image) ?>" alt="Department Header Image">
                <?php else: ?>
                    <p class="text-muted">No header image available</p>
                <?php endif; ?>
            </div>
            <br><br>

            <h1 class="card-title text-center invitation-title" style="font-size: 2.5rem; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;">
                LETTER OF INVITATION
            </h1>
            <br><br>

            <p style="text-align: right;"><strong>DATE: </strong><?= htmlspecialchars($date ?? 'N/A') ?></p><br>

            <p>
                <strong>To:</strong><br>
                <strong><?= htmlspecialchars($guestName ?? 'Guest Name') ?></strong><br>
                <?= htmlspecialchars($companyDesignation ?? 'Designation') ?><br>
                <?= htmlspecialchars($companyName ?? 'Company Name') ?>
            </p><br>

            <p style="font-weight: bold;"><strong>Subject: </strong><?= htmlspecialchars($subject ?? 'N/A') ?></p><br>
            <p>Respected <?= htmlspecialchars($respected ?? '') ?>,</p>
           
        <div>
    <?= $body ?>
</div>
            <!-- Signature Section -->
            <div class="signature">
                <!-- Coordinator -->
                <div>
                    <?php if (!empty($coordinator_sign)): ?>
                        <img src="<?= htmlspecialchars($coordinator_sign) ?>" style="width:150px; height:auto;">
                    <?php endif; ?>
                    <strong><?= htmlspecialchars($coordinator_name ?? 'Programme Coordinator') ?></strong>
                    <strong>Coordinator</strong>
                    <strong>Keystone School of Engineering</strong>
                </div>

                <!-- HOD - Only show if HOD name is not default 'N/A' (meaning exactly one department exists) -->
                <?php if (!empty($hod_name) && $hod_name !== 'N/A'): ?>
                    <div>
                        <?php if (!empty($hod_sign)): ?>
                            <img src="<?= htmlspecialchars($hod_sign) ?>" style="width:150px; height:auto;">
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($hod_name) ?></strong>
                        <strong>HOD</strong>
                        <strong>Keystone School of Engineering</strong>
                    </div>
                <?php endif; ?>

                <!-- Principal -->
                <div>
                    <?php if (!empty($principal_sign)): ?>
                        <img src="<?= htmlspecialchars($principal_sign) ?>" style="width:150px; height:auto;">
                    <?php endif; ?>
                    <strong><?= htmlspecialchars($principal_name ?? 'Principal') ?></strong>
                    <strong>Principal</strong>
                    <strong>Keystone School of Engineering</strong>
                </div>
            </div>
            <br>

            <!-- Footer Image -->
            <div class="img-logo text-center">
                <img src="/public/images/view_footer.png" alt="Footer Image">
            </div>

            <!-- Buttons -->
            <div id="buttons-container" class="mt-4 text-center">
                <a href="<?= Url::to("/documents/invitation/$checklist_id?page=" . ($page ?? 1)) ?>" 
                   class="btn btn-secondary">
                    Edit Invitation
                </a>
                <button id="download-btn" class="btn btn-success">
                    Download
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.getElementById("download-btn").addEventListener("click", function() {

    const element = document.getElementById("invitation-card");
    const buttons = document.getElementById("buttons-container");

    // Hide buttons before capturing
    buttons.style.display = "none";

    // 🔥 WAIT FOR ALL IMAGES TO LOAD
    const images = element.querySelectorAll("img");
    const promises = [];

    images.forEach(img => {
        if (!img.complete) {
            promises.push(new Promise(resolve => {
                img.onload = img.onerror = resolve;
            }));
        }
    });

    Promise.all(promises).then(() => {

        html2canvas(element, {
            scale: 2,
            useCORS: true   // 🔥 IMPORTANT FIX
        }).then(canvas => {

            let link = document.createElement("a");
            link.href = canvas.toDataURL("image/png");
            link.download = "Invitation_<?= htmlspecialchars($checklist_id ?? 'unknown') ?>.png";
            link.click();

            // Show buttons again
            buttons.style.display = "block";
        });

    });

});
</script>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>
