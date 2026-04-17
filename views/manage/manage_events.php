<?php require_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="page-bg">
    <div class="overlay">
        <div class="container">
            <h1 class="mb-4 text-center">Manage Events</h1>

            <div class="content-card">

                <!-- Success / Error Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <!-- Add Event Form -->
                <div class="card mb-4">
                    <div class="card-header"><h5>Add New Event</h5></div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Event Name</label>
                                    <input type="text" name="event_name" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" required>
                                </div>
                            </div>

                            <?php if ($user['role'] === 'principal'): ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Department</label>
                                        <select class="form-select" name="department_id" required>
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= htmlspecialchars($dept['id']) ?>">
                                                    <?= htmlspecialchars($dept['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Event Image</label>
                                    <input type="file" name="event_image" class="form-control" accept="image/*" required> 
                                    <div class="form-text">Optional. Recommended size: 800x400px</div>
                                </div>
                            </div>

                            <button type="submit" name="add_event" class="btn btn-primary">Add Event</button>
                        </form>
                    </div>
                </div>

                <!-- Events List -->
                <div class="card">
                    <div class="card-header"><h5>All Events</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Department</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($events)): ?>
                                        <?php foreach ($events as $event): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($event['event_name'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($event['department_name'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars(date('d-m-Y', strtotime($event['start_date']))) ?></td>
                                                <td><?= htmlspecialchars(date('d-m-Y', strtotime($event['end_date']))) ?></td>
                                                <td><?= htmlspecialchars($event['created_by_name'] ?? '—') ?></td>
                                                <td>
                                                    <a href="/event-reports/manage/events/delete?delete_event=<?= htmlspecialchars($event['id']) ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Delete this event?')">
                                                        Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No events found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>