<style>
/* Sticky Sidebar */
.quick-actions {
  position: sticky;
  top: 90px;   /* distance from top */
  height: fit-content;
  z-index: 10; /* Ensure it stays above other content */
}

/* Card look */
.quick-actions-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 15px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  border: 1px solid #e6e6e6;
}

/* Heading */
.quick-actions-heading {
  font-weight: 600;
  font-size: 16px;
  text-align: center;
  margin-bottom: 15px;
  color: #2c3e50;
}

/* Remove bullets & spacing */
.quick-actions-list {
  padding-left: 0;
  margin: 0;
}

/* Links */
.quick-actions-list .nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 8px;
  color: #34495e;
  font-size: 14px;
  transition: all 0.25s ease;
  text-decoration: none; /* Ensure links are clickable */
}

/* Icons */
.quick-actions-list i {
  font-size: 16px;
  color: #6c63ff;
}

/* Hover effect */
.quick-actions-list .nav-link:hover {
  background: #f0f2ff;
  color: #6c63ff;
  transform: translateX(4px);
}

/* Active page highlight */
.quick-actions-list .nav-link.active {
  background: #6c63ff;
  color: #fff;
}

.quick-actions-list .nav-link.active i {
  color: #fff;
}
</style>

<?php
if (!isset($checklist_id) && isset($_GET['checklist_id'])) {
    $checklist_id = $_GET['checklist_id'];
}
?>

<div class="col-md-2 d-md-block quick-actions">
  <div class="quick-actions-card">
    <h5 class="quick-actions-heading">Checklist Actions</h5>

    <ul class="nav flex-column quick-actions-list">

      <li class="nav-item">
        <a class="nav-link" href="<?= Url::to("/documents/view/checklist/$checklist_id") ?>">
          <i class="bi bi-pencil-square"></i> Edit Checklist
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="<?= Url::to("/documents/invitation/$checklist_id") ?>">
          <i class="bi bi-envelope"></i> Invitations for Guests
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="<?= Url::to("/documents/notice/$checklist_id") ?>">
          <i class="bi bi-bell"></i> Notice
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="<?= Url::to("/documents/appreciation/$checklist_id") ?>">
          <i class="bi bi-heart"></i> Appreciation for Guest
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="<?= Url::to('/documents/event-report/' . $checklist_id) ?>">
          <i class="bi bi-file-earmark-text"></i> Event Report
        </a>
      </li>

    </ul>
  </div>
</div>