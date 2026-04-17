<?php require_once __DIR__ . '/../../views/layouts/header.php'; ?>

<link rel="stylesheet" href="/event-reports/public/css/view.css">

<div class="container">

    <div id="notice-card" class="card mt-5">
        <div class="card-body">

            <!-- ================= HEADER IMAGE ================= -->
            <div class="img-logo text-center">
                <?php if (!empty($header_image)): ?>
                    <img src="<?= htmlspecialchars($header_image) ?>" alt="Department Header Image">
                <?php endif; ?>
            </div>

            <br><br>

            <h1 class="card-title text-center invitation-title" style="text-decoration: underline;">
                NOTICE
            </h1>

            <br><br>

            <!-- ================= DATE ================= -->
            <p class="text-end">
                <strong>DATE:</strong>
                <?= htmlspecialchars(date('j F Y', strtotime($notice_date ?? 'N/A'))) ?>
            </p>

            <!-- ================= PROGRAM NAME ================= -->
            <h3 class="fw-bold text-center">
                <?= htmlspecialchars(strtoupper($programme_name ?? 'N/A')) ?>
            </h3>

            <br><br>

            <!-- ================= CONTENT ================= -->

            <p><strong>Dear Students and Faculty,</strong></p>

            <p><?=$dear ?? '' ?></p>

            <br>

            <h5><strong>Event Highlights:</strong></h5>

            <p><?= $event_highlights ?? '' ?></p>

            <br>

            <h5><strong>Event Details:</strong></h5>

            <p>
                Date:
                <strong><?= htmlspecialchars(preg_replace_callback('/(\d{2})-(\d{2})-(\d{4})/', function($matches) {
                    return date('j-M-Y', strtotime($matches[1] . '-' . $matches[2] . '-' . $matches[3]));
                }, $event_date ?? 'N/A')) ?></strong>
            </p>

            <p>
                Time:
                <strong><?= htmlspecialchars($event_time ?? 'N/A') ?></strong>
            </p>

            <p>
                Venue:
                <strong><?= htmlspecialchars($event_venue ?? 'N/A') ?></strong>
            </p>

            <br><br>

            <!-- ================= SIGNATURES ================= -->

            <div class="signature">

                <!-- Coordinator -->
                <div>
                    <?php if (!empty($coordinator_sign)): ?>
                        <img src="<?= htmlspecialchars($coordinator_sign) ?>" width="150">
                    <?php endif; ?>

                    <strong><?= htmlspecialchars($coordinator_name ?? 'Coordinator') ?></strong>
                    Coordinator
                    Keystone School of Engineering
                </div>

                <!-- HOD - Only show if HOD name is not default 'N/A' (meaning exactly one department exists) -->
                <?php if (!empty($hod_name) && $hod_name !== 'N/A'): ?>
                    <div>
                        <?php if (!empty($hod_sign)): ?>
                            <img src="<?= htmlspecialchars($hod_sign) ?>" width="150">
                        <?php endif; ?>

                        <strong><?= htmlspecialchars($hod_name) ?></strong>
                        HOD
                        Keystone School of Engineering
                    </div>
                <?php endif; ?>

                <!-- Principal -->
                <div>
                    <?php if (!empty($principal_sign)): ?>
                        <img src="<?= htmlspecialchars($principal_sign) ?>" width="150">
                    <?php endif; ?>

                    <strong><?= htmlspecialchars($principal_name ?? 'Principal') ?></strong>
                    Principal
                    Keystone School of Engineering
                </div>

            </div>

            <br>

            <!-- ================= FOOTER IMAGE ================= -->

            <div class="img-logo text-center">
                <img src="/public/images/view_footer.png" alt="Footer Image">
            </div>

            <!-- ================= ACTION BUTTONS ================= -->

            <div id="buttons-container" class="mt-3">

                <a href="<?= Url::to("/documents/notice/$checklist_id") ?>"
                   class="btn btn-secondary">
                    Edit Notice
                </a>

                <button id="download-btn" class="btn btn-success">
                    Download
                </button>

            </div>

        </div>
    </div>

</div>

<!-- ================= JS LIBRARY ================= -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const downloadBtn = document.getElementById("download-btn");
    const card = document.getElementById("notice-card");
    const buttons = document.getElementById("buttons-container");

    if (!downloadBtn || !card || !buttons) return;

    downloadBtn.addEventListener("click", function () {

        // Hide buttons
        buttons.style.display = "none";

        // 🔥 WAIT FOR IMAGES TO LOAD
        const images = card.querySelectorAll("img");
        const promises = [];

        images.forEach(img => {
            if (!img.complete) {
                promises.push(new Promise(resolve => {
                    img.onload = img.onerror = resolve;
                }));
            }
        });

        Promise.all(promises).then(() => {

            html2canvas(card, {
                scale: 2,
                useCORS: true   // 🔥 IMPORTANT
            }).then(function (canvas) {

                const link = document.createElement("a");
                link.href = canvas.toDataURL("image/png");
                link.download = "Notice_<?= htmlspecialchars($checklist_id ?? 'unknown') ?>.png";
                link.click();

                // Show buttons again
                buttons.style.display = "block";

            });

        });

    });

});
</script>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>
