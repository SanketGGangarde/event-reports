<?php

/**
 * EventReportController
 * Handles event report form (create/update) + final read-only view
 */
use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../config/cloudinaryHelper.php';
use Cloudinary\Api\Upload\UploadApi;
require_once __DIR__ . '/../core/BaseController.php';

class EventReportController extends BaseController
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    private function uploadPhotoToCloudinary($index, $existingPublicId = null)
{
    if (
        !isset($_FILES['new_photos']['name'][$index]) ||
        $_FILES['new_photos']['error'][$index] !== UPLOAD_ERR_OK
    ) {
        return null;
    }
    $tmp = $_FILES['new_photos']['tmp_name'][$index];
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    $type = $_FILES['new_photos']['type'][$index];
    if (!in_array($type, $allowed)) {
        return null;
    }
    $max_size = 5 * 1024 * 1024;
    if ($_FILES['new_photos']['size'][$index] > $max_size) {
        return null;
    }
    try {
        $options = [
            'folder' => 'event_reports/photos',
            'resource_type' => 'image'
        ];
        if ($existingPublicId) {
            $options['public_id'] = $existingPublicId;
            $options['overwrite'] = true;
            $options['invalidate'] = true;
        }
        $upload = (new UploadApi())->upload($tmp, $options);
        return [
            'url' => $upload['secure_url'],
            'public_id' => $upload['public_id']
        ];
    } catch (Exception $e) {
        error_log("Photo Upload Error: " . $e->getMessage());
        return null;
    }
}

private function deleteFromCloudinary($public_id)
{
    if (empty($public_id)) return;
    try {
        (new UploadApi())->destroy($public_id, [
            'resource_type' => 'image',
            'invalidate' => true
        ]);
    } catch (Exception $e) {
        error_log("Cloudinary Delete Error: " . $e->getMessage());
    }
}
    /**
     * Form: Show (GET) + Save/Update (POST)
     */
    public function manage()
{
    // Auth & role check (coordinator or HOD only)
    if (!isset($_SESSION['user_id'])) {
        $this->redirect('/login');
    }
    $role = $_SESSION['role'] ?? '';
    // if ($role !== 'coordinator' && $role !== 'hod') {
    // $this->redirect('/dashboard?error=access_denied');
    // }
    
    // Get checklist_id from POST (for form submissions) or GET (for URL parameter)
    $checklist_id = $_POST['checklist_id'] ?? $_GET['checklist_id'] ?? null;
    
    if (
        empty($checklist_id) ||
        !preg_match('/^[0-9a-f-]{36}$/i', $checklist_id)
    ) {
        throw new Exception('Invalid checklist ID');
    }
    $errors = [];
    $success = $_GET['success'] ?? '';
    $form_data = [];
    $is_update = false;
    try {
        // Fetch programme
        $stmtProgramme = $this->pdo->prepare("SELECT * FROM checklists WHERE id = ?");
        $stmtProgramme->execute([$checklist_id]);
        $programme = $stmtProgramme->fetch(PDO::FETCH_ASSOC);
        if (!$programme) {
            throw new Exception("Checklist not found");
        }
        // Coordinator name (current user)
        $stmtUser = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmtUser->execute([$_SESSION['user_id']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $coordinator_name = htmlspecialchars($user['username'] ?? 'Unknown');
        // HOD name - only if exactly one department
        $hod_name = 'Not assigned';
        $deptArray = json_decode($programme['department'] ?? '[]', true);
        if (is_array($deptArray) && count($deptArray) === 1) {
            $dept_id = $deptArray[0]; // Keep as UUID string
            $stmtHod = $this->pdo->prepare("
                SELECT u.username
                FROM users u
                WHERE u.role = 'hod' AND u.department_id = ?
                LIMIT 1
            ");
            $stmtHod->execute([$dept_id]);
            $hod = $stmtHod->fetch(PDO::FETCH_ASSOC);
            $hod_name = htmlspecialchars($hod['username'] ?? 'Not assigned');
        }
        // Existing report
        $stmtReport = $this->pdo->prepare("SELECT * FROM event_report WHERE checklist_id = ?");
        $stmtReport->execute([$checklist_id]);
        $report = $stmtReport->fetch(PDO::FETCH_ASSOC);
        $is_update = !empty($report);
        // Form defaults / prefill
        $existing_photos = !empty($report['photos']) ? json_decode($report['photos'], true) : [];
        $existing_public_ids = !empty($report['photos_public_ids']) ? json_decode($report['photos_public_ids'], true) : [];
        $existing_captions = !empty($report['captions']) ? json_decode($report['captions'], true) : [];
        $form_data = [
            'description' => $report['description'] ?? '',
            'activities' => $report['activities'] ?? '',
            'significance' => $report['significance'] ?? '',
            'conclusion' => $report['conclusion'] ?? '',
            'faculties_participation' => $report['faculties_participation'] ?? '',
            'photos' => $existing_photos,
            'public_ids' => $existing_public_ids,  // New: Pass to view for hidden inputs
            'captions' => $existing_captions,
        ];
        // Guests
        $stmtGuests = $this->pdo->prepare("
            SELECT guest_name, company_name, contact_no ,guest_email,designation
            FROM checklist_guests
            WHERE checklist_id = ?
        ");
        $stmtGuests->execute([$checklist_id]);
        $guests = $stmtGuests->fetchAll(PDO::FETCH_ASSOC);
        // Time & Venue from notice
        $event_time = '--:-- --';
        $event_venue = 'Not specified';
        $stmtNotice = $this->pdo->prepare("
            SELECT event_time, event_venue
            FROM notice
            WHERE checklist_id = ?
            LIMIT 1
        ");
        $stmtNotice->execute([$checklist_id]);
        $notice = $stmtNotice->fetch(PDO::FETCH_ASSOC);
        if ($notice) {
            $event_time = $notice['event_time']
                ? date('h:i A', strtotime($notice['event_time']))
                : '--:-- --';
            $event_venue = $notice['event_venue'] ?: 'Not specified';
        }
        // POST handling
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            $token = $_POST['csrf_token'] ?? '';
            if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                $errors[] = "Invalid CSRF token. Please try again.";
            }
            
            $description = trim($_POST['description'] ?? '');
            $activities = trim($_POST['activities'] ?? '');
            $significance = trim($_POST['significance'] ?? '');
            $conclusion = trim($_POST['conclusion'] ?? '');
            $faculties_participation = trim($_POST['faculties_participation'] ?? '');
            // Enhanced validation
            if (empty($description)) {
                $errors[] = "Description is required!";
            }
            if (empty($activities)) {
                $errors[] = "Activities and Highlights is required!";
            }
            if (empty($significance)) {
                $errors[] = "Significance is required!";
            }
            if (empty($conclusion)) {
                $errors[] = "Conclusion is required!";
            }
            if (empty($faculties_participation)) {
                $errors[] = "Faculties' Responses & Participation is required!";
            }
            // Photo handling
            $photos_array = [];
            $public_ids_array = [];
            $captions_array = [];
            // Kept photos
            $kept_photos = $_POST['kept_photos'] ?? [];
            $kept_public_ids = $_POST['kept_public_ids'] ?? [];
            $kept_captions = $_POST['kept_captions'] ?? [];
            foreach ($kept_photos as $i => $path) {
                if (!empty($path)) {
                    $photos_array[] = $path;
                    $public_ids_array[] = $kept_public_ids[$i] ?? null;
                    $captions_array[] = trim($_POST['kept_captions_display'][$i] ?? $kept_captions[$i] ?? '');
                }
            }
            // Detect and delete removed photos from Cloudinary (if update)
            if ($is_update) {
                $existing_map = array_combine($existing_photos, $existing_public_ids) ?? [];
                $kept_urls = $kept_photos;
                $removed_urls = array_diff($existing_photos, $kept_urls);
                foreach ($removed_urls as $removed_url) {
                    $public_id = $existing_map[$removed_url] ?? null;
                    if ($public_id) {
                        $this->deleteFromCloudinary($public_id);
                    }
                }
            }
            // New uploads to Cloudinary
            if (!empty($_FILES['new_photos']['name'][0])) {
                foreach ($_FILES['new_photos']['name'] as $i => $name) {
                    if ($_FILES['new_photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $upload = $this->uploadPhotoToCloudinary($i);
                    if ($upload) {
                        $photos_array[] = $upload['url'];
                        $public_ids_array[] = $upload['public_id'];
                        $captions_array[] = trim($_POST['new_captions'][$i] ?? '');
                    } else {
                        $errors[] = "Failed to upload photo '$name'";
                    }
                }
            }
            // if (empty($photos_array)) {
            // $errors[] = "At least one photo is required.";
            // }
            if (empty($errors)) {
                $photos_json = json_encode($photos_array);
                $public_ids_json = json_encode($public_ids_array);
                $captions_json = json_encode($captions_array);
                // Debug logging
                error_log("Event Report Save Attempt - Checklist ID: $checklist_id");
                error_log("Photos JSON: " . $photos_json);
                error_log("Public IDs JSON: " . $public_ids_json);
                error_log("Captions JSON: " . $captions_json);
                error_log("Is Update: " . ($is_update ? 'YES' : 'NO'));
                try {
                    if ($is_update) {
                        $stmt = $this->pdo->prepare("
                            UPDATE event_report
                            SET description = ?, activities = ?, significance = ?,
                                conclusion = ?, faculties_participation = ?,
                                photos = ?, captions = ?, photos_public_ids = ?
                            WHERE checklist_id = ?
                        ");
                        $result = $stmt->execute([
                            $description, $activities, $significance,
                            $conclusion, $faculties_participation,
                            $photos_json, $captions_json, $public_ids_json, $checklist_id
                        ]);
                       
                        if ($result) {
                            $success = "Event report updated successfully!";
                            $this->redirect(Url::to('/documents/event-report/' . $checklist_id . '?success=' . urlencode($success)));
                            // $this->redirect(Url::to('/documents/event-report', ['checklist_id' => $checklist_id, 'success' => $success]));
                        } else {
                            $errors[] = "Failed to update event report. Please try again.";
                            error_log("Event report update failed for checklist_id: $checklist_id");
                        }
                    } else {
                        $id = Uuid::uuid4()->toString();
                        $stmt = $this->pdo->prepare("
                            INSERT INTO event_report
                            (id, checklist_id, description, activities, significance, conclusion,
                             faculties_participation, photos, captions, photos_public_ids)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $result = $stmt->execute([
                            $id, $checklist_id, $description, $activities, $significance,
                            $conclusion, $faculties_participation,
                            $photos_json, $captions_json, $public_ids_json
                        ]);
                       
                        if ($result) {
                            $success = "Event report created successfully!";
                           $this->redirect(Url::to("/documents/event-report/$checklist_id?success=" . urlencode($success)));
                        } else {
                            $errors[] = "Failed to create event report. Please try again.";
                            error_log("Event report insert failed for checklist_id: $checklist_id");
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Event report save failed: " . $e->getMessage());
                    error_log("SQL Error Code: " . $e->getCode());
                    error_log("Checklist ID: " . $checklist_id);
                    $errors[] = "Database error: Could not save report. Please check the data and try again.";
                }
            }
            // Repopulate on error
            $form_data = [
                'description' => $description,
                'activities' => $activities,
                'significance' => $significance,
                'conclusion' => $conclusion,
                'faculties_participation' => $faculties_participation,
                'photos' => $photos_array,  // Repopulate for error display
                'public_ids' => $public_ids_array,  // New
                'captions' => $captions_array
            ];
        }
        // Render form view
        $this->render('documents/event_report', [
            'checklist_id' => $checklist_id,
            'programme' => $programme,
            'event_time' => $event_time,
            'event_venue' => $event_venue,
            'guests' => $guests,
            'coordinator_name' => $coordinator_name,
            'hod_name' => $hod_name,
            'form_data' => $form_data,
            'is_update' => $is_update,
            'success' => $success,
            'errors' => $errors
        ]);
    } catch (Exception $e) {
        $errors[] = "Error: " . $e->getMessage();
        $this->render('/event-reports/documents/event_report', [
            'checklist_id' => $checklist_id,
            'errors' => $errors
        ]);
    }
}

    /**
     * View saved event report (final display)
     */
    public function view()
    {
        // Public access - no authentication required for viewing
        $checklist_id = $_GET['checklist_id'] ?? null;
        if (
            empty($checklist_id) ||
            !preg_match('/^[0-9a-f-]{36}$/i', $checklist_id)
        ) {
            throw new Exception('Invalid checklist ID');
        }
        if (!$checklist_id) {
            $this->redirect('/dashboard?error=checklist_id_missing');
        }

        try {
            // Fetch event report
            $stmtReport = $this->pdo->prepare("SELECT * FROM event_report WHERE checklist_id = ?");
            $stmtReport->execute([$checklist_id]);
            $event = $stmtReport->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                $this->redirect("/documents/event-report?checklist_id=$checklist_id&error=no_report_found");
            }

            // Fetch checklist
            $stmtProgramme = $this->pdo->prepare("
                SELECT programme_name, programme_date, multi_day,
                       programme_start_date, programme_end_date, department
                FROM checklists
                WHERE id = ?
            ");
            $stmtProgramme->execute([$checklist_id]);
            $checklist = $stmtProgramme->fetch(PDO::FETCH_ASSOC);

            if (!$checklist) {
                $this->redirect("/documents/event-report?checklist_id=$checklist_id&error=checklist_not_found");
            }

            // Fetch guests
            $stmtGuests = $this->pdo->prepare("
                SELECT guest_name, company_name, contact_no,guest_email,designation 
                FROM checklist_guests 
                WHERE checklist_id = ?
            ");
            $stmtGuests->execute([$checklist_id]);
            $guests = $stmtGuests->fetchAll(PDO::FETCH_ASSOC);

            // ------------------------------------------------------------------------------

            // Fetch notice for time/venue
            $stmtNotice = $this->pdo->prepare("
                SELECT event_time, event_venue 
                FROM notice 
                WHERE checklist_id = ?
            ");
            $stmtNotice->execute([$checklist_id]);
            $notice = $stmtNotice->fetch(PDO::FETCH_ASSOC);

            // Header image
            $deptArray = json_decode($checklist['department'] ?? '[]', true);
            $header_image = '';

            $stmtDefault = $this->pdo->query("SELECT image FROM default_header LIMIT 1");
            $defaultRow = $stmtDefault->fetch(PDO::FETCH_ASSOC);
            $header_image = $defaultRow['image'] ?? '';

            if (is_array($deptArray) && count($deptArray) === 1 && !empty($deptArray[0])) {
                $dept_id = $deptArray[0]; // Keep as UUID string
                $stmtDept = $this->pdo->prepare("SELECT header_image FROM departments WHERE id = ?");
                $stmtDept->execute([$dept_id]);
                $deptRow = $stmtDept->fetch(PDO::FETCH_ASSOC);
                if (!empty($deptRow['header_image'])) {
                    $header_image = $deptRow['header_image'];
                }
            }

            // Format data
            // Format data
            $programme_name = htmlspecialchars($checklist['programme_name'] ?? '');

            if ($checklist['multi_day']) {

                $start = date('l jS F Y', strtotime($checklist['programme_start_date']));
                $end   = date('l jS F Y', strtotime($checklist['programme_end_date']));

                $event_date = $start . " to " . $end;

            } else {

                $event_date = date('l jS F Y', strtotime($checklist['programme_date']));
            }

            $event_time = !empty($notice['event_time'])
                ? date('h:i A', strtotime($notice['event_time']))
                : 'N/A';

            $event_venue = htmlspecialchars($notice['event_venue'] ?? 'N/A');

            $photos   = json_decode($event['photos'] ?? '[]', true);
            $captions = json_decode($event['captions'] ?? '[]', true);

            // Coordinator
            $stmtCoord = $this->pdo->prepare("
                SELECT u.username AS name, u.sign_image
                FROM checklists c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?
            ");
            $stmtCoord->execute([$checklist_id]);
            $coord = $stmtCoord->fetch(PDO::FETCH_ASSOC);
            $coordinator_name = htmlspecialchars($coord['name'] ?? 'Coordinator');
            $coordinator_sign = $coord['sign_image'] ?? '';

            // HOD - only if exactly one department
            $hod_name = 'N/A';
            $hod_sign = '';
            $deptArray = json_decode($checklist['department'] ?? '[]', true);
            if (is_array($deptArray) && count($deptArray) === 1) {
                $dept_id = $deptArray[0]; // Keep as UUID string
                $stmtHod = $this->pdo->prepare("
                    SELECT username AS name, sign_image
                    FROM users
                    WHERE role = 'hod' AND department_id = ?
                    LIMIT 1
                ");
                $stmtHod->execute([$dept_id]);
                $hod = $stmtHod->fetch(PDO::FETCH_ASSOC);
                $hod_name = htmlspecialchars($hod['name'] ?? 'N/A');
                $hod_sign = $hod['sign_image'] ?? '';
            }

            // Principal
            $stmtPrincipal = $this->pdo->prepare("
                SELECT username AS name, sign_image
                FROM users
                WHERE role = 'principal'
                LIMIT 1
            ");
            $stmtPrincipal->execute();
            $principal = $stmtPrincipal->fetch(PDO::FETCH_ASSOC);
            $principal_name = htmlspecialchars($principal['name'] ?? 'Principal');
            $principal_sign = $principal['sign_image'] ?? '';

            // -------------------------------------------------
// Button access control
// -------------------------------------------------
$canAccessButtons = false;

if (isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? '';
    $user_department = $_SESSION['department_id'] ?? null;

    // Get creator id
    $stmtCreator = $this->pdo->prepare("SELECT created_by FROM checklists WHERE id=?");
    $stmtCreator->execute([$checklist_id]);
    $creator = $stmtCreator->fetchColumn();

    // Coordinator
    if ($creator === $user_id) {
        $canAccessButtons = true;
    }

    // Principal
    elseif ($user_role === 'principal') {
        $canAccessButtons = true;
    }

    // HOD of same department
    elseif ($user_role === 'hod' && in_array($user_department, $deptArray)) {
        $canAccessButtons = true;
    }
}

            // Render final view
            $this->render('documents/view_eventreport', [
    'header_image'       => $header_image,
    'programme_name'     => $programme_name,
    'event_date'         => $event_date,
    'event_time'         => $event_time,
    'event_venue'        => $event_venue,
    'guests'             => $guests,

    'description'        => htmlspecialchars_decode($event['description'] ?? ''),
    'activities'         => htmlspecialchars_decode($event['activities'] ?? ''),
    'significance'       => htmlspecialchars_decode($event['significance'] ?? ''),
    'conclusion'         => htmlspecialchars_decode($event['conclusion'] ?? ''),
    'faculties_participation' => htmlspecialchars_decode($event['faculties_participation'] ?? ''),
    'photos'             => $photos,
    'captions'           => $captions,

    'coordinator_name'   => $coordinator_name,
    'coordinator_sign'   => $coordinator_sign,
    'hod_name'           => $hod_name,
    'hod_sign'           => $hod_sign,
    'principal_name'     => $principal_name,
    'principal_sign'     => $principal_sign,

    'checklist_id'       => $checklist_id,

    'canAccessButtons'   => $canAccessButtons
]);

        } catch (Exception $e) {
            $this->redirect("/documents/event-report?checklist_id=$checklist_id&error=" . urlencode($e->getMessage()));
        }
    }
}