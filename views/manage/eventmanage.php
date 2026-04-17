<?php require_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php require_once __DIR__ . '/../../views/includes/sidebar.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<div class="page-bg">
<div class="overlay">
<?php require_once __DIR__ . '/../../views/partials/flash.php'; ?>

<h1 class="mb-4 text-center text-white">
    Welcome, <?= htmlspecialchars($user['username']) ?>
</h1>

<div class="container">
<div class="content-card">

<!-- SEARCH -->
<div class="search-bar">
    <input type="text" id="searchYear" class="form-control"
           placeholder="Search by Year (e.g. 2024)">
    <select id="searchMonth" class="form-control">
        <option value="">All Months</option>
        <option value="01">January</option>
        <option value="02">February</option>
        <option value="03">March</option>
        <option value="04">April</option>
        <option value="05">May</option>
        <option value="06">June</option>
        <option value="07">July</option>
        <option value="08">August</option>
        <option value="09">September</option>
        <option value="10">October</option>
        <option value="11">November</option>
        <option value="12">December</option>
    </select>
</div>

<!-- TABLE -->
<table class="table table-bordered table-striped" id="eventsTable">
<thead class="table-dark">
<tr>
    <th>Event Name</th>
    <th>Coordinator</th>
    <th>Department</th>
    <th>Date</th>
    <th width="180">Actions</th>
</tr>
</thead>

<tbody>
<?php if (!empty($events)): ?>
    <?php foreach ($events as $event): ?>
<tr>
    <td><?= htmlspecialchars($event['programme_name']) ?></td>
    <td><?= htmlspecialchars($event['coordinator_name']) ?></td>
    <td><?= htmlspecialchars($event['department_name']) ?></td>
    <td><?= htmlspecialchars($event['formatted_date']) ?></td>
   <td class="text-center">
<div class="d-flex justify-content-center gap-2">

<a href="<?= Url::to("/documents/view/checklist/" . $event['id']) ?>" 
   class="btn btn-primary btn-sm">
    <i class="bi bi-box-arrow-in-right"></i> View
</a>

<form action="<?= Url::to('/documents/checklist/delete') ?>" 
      method="POST"
      onsubmit="return confirmDelete('<?= htmlspecialchars(addslashes($event['programme_name'])) ?>')">

<input type="hidden" name="checklist_id" value="<?= htmlspecialchars($event['id']) ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<button type="submit" class="btn btn-danger btn-sm">
<i class="bi bi-trash"></i> 
</button>

</form>

</div>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="text-center">No events found</td>
    </tr>
<?php endif; ?>
</tbody>
</table>

</div>
</div>
</div>
</div>

<!-- FILTER SCRIPT -->
<script>
const yearInput  = document.getElementById("searchYear");
const monthSelect = document.getElementById("searchMonth");
const rows       = document.querySelectorAll("#eventsTable tbody tr");

function filterEvents() {
    const year  = yearInput.value.trim();
    const month = monthSelect.value;

    rows.forEach(row => {
        const date     = row.dataset.date;
        if (!date) {
            row.style.display = "none";
            return;
        }

        const rowYear  = date.substring(0, 4);
        const rowMonth = date.substring(5, 7);

        const yearMatch  = year === "" || rowYear.includes(year);
        const monthMatch = month === "" || rowMonth === month;

        row.style.display = (yearMatch && monthMatch) ? "" : "none";
    });
}

yearInput.addEventListener("input", filterEvents);
monthSelect.addEventListener("change", filterEvents);

function confirmDelete(eventName) {
    return confirm(
        "Are you sure you want to delete the checklist for:\n\n" +
        "» " + eventName + "\n\n" +
        "This will also delete:\n" +
        "• All guests & their photos\n" +
        "• Incharges\n" +
        "• Invitation / Notice / Appreciation letters\n" +
        "• Event report & photos\n\n" +
        "This action cannot be undone."
    );
}
</script>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>