<?php 
// Support both old session-based flash and new Flash class
$flash = new Flash();

// Check for old session-based errors
if (!empty($_SESSION['errors'])): ?>
    <div class="alert alert-danger" role="alert">
      <ul class="mb-0">
        <?php foreach ($_SESSION['errors'] as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php unset($_SESSION['errors']); ?>
<?php endif; ?>

<?php 
// Check for old session-based success
if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert" style="max-width: 1209px; margin: 0 auto;">
        <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Success:">
            <use xlink:href="#check-circle-fill"/>
        </svg>
        <div style="font-size: 25px !important;">
            <?= htmlspecialchars($_SESSION['success']); ?>
        </div>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php 
// Check for new Flash class messages
$messages = $flash->getMessages();
if (!empty($messages)): ?>
    <?php foreach ($messages as $type => $typeMessages): ?>
        <?php foreach ($typeMessages as $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> d-flex align-items-center" role="alert" style="max-width: 1209px; margin: 0 auto;">
                <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="<?= ucfirst($type) ?>:">
                    <use xlink:href="#<?= $type === 'error' ? 'exclamation-triangle-fill' : ($type === 'success' ? 'check-circle-fill' : 'info-circle-fill') ?>"/>
                </svg>
                <div style="font-size: 25px !important;">
                    <?= htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>
