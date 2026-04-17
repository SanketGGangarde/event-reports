<?php

/**
 * NoticeController
 * Handles notice creation/update form + final read-only view of saved notice
 */
use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../core/BaseController.php';

class NoticeController extends BaseController
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /* =====================================
       MANAGE NOTICE FORM (GET + POST)
    ===================================== */
    public function manage($checklist_id = null)
    {
        // Auth check
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        // Get checklist_id from parameter or GET
        if ($checklist_id === null) {
            $checklist_id = $_GET['checklist_id'] ?? null;
        }
        
        if (
            empty($checklist_id) ||
            !preg_match('/^[0-9a-f-]{36}$/i', $checklist_id)
        ) {
            throw new Exception('Invalid checklist ID');
        }
        if (!$checklist_id) {
            $this->redirect('/dashboard?error=checklist_id_missing');
        }

        $errors = [];
        $success = $_GET['success'] ?? '';
        $form_data = [];

        // Fetch programme details
        $stmtProgramme = $this->pdo->prepare("
            SELECT programme_name, programme_date, multi_day, 
                   programme_start_date, programme_end_date
            FROM checklists
            WHERE id = ?
        ");
        $stmtProgramme->execute([$checklist_id]);
        $programme = $stmtProgramme->fetch(PDO::FETCH_ASSOC);

        if (!$programme) {
            $this->redirect('/dashboard?error=checklist_not_found');
        }

        // Fetch coordinator name
        $stmtCoord = $this->pdo->prepare("
            SELECT u.username AS coordinator_name
            FROM checklists c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $stmtCoord->execute([$checklist_id]);
        $coord = $stmtCoord->fetch(PDO::FETCH_ASSOC);
        $coordinator_name = htmlspecialchars($coord['coordinator_name'] ?? 'N/A');

        // Fetch HOD name - only if exactly one department exists
        $hod_name = 'N/A';
        
        // Get department data to check count
        $stmtDept = $this->pdo->prepare("SELECT department FROM checklists WHERE id = ?");
        $stmtDept->execute([$checklist_id]);
        $deptRow = $stmtDept->fetch(PDO::FETCH_ASSOC);
        
        $deptArray = json_decode($deptRow['department'] ?? '[]', true);
        
        // Only show HOD if exactly one department exists
        if (is_array($deptArray) && count($deptArray) === 1) {
            $dept_id = $deptArray[0]; // Keep as UUID string
            $stmtHod = $this->pdo->prepare("
                SELECT u.username AS hod_name
                FROM users u
                WHERE u.role = 'hod' AND u.department_id = ?
                LIMIT 1
            ");
            $stmtHod->execute([$dept_id]);
            $hodRow = $stmtHod->fetch(PDO::FETCH_ASSOC);
            $hod_name = htmlspecialchars($hodRow['hod_name'] ?? 'N/A');
        }

        // Check existing notice
        $stmtNotice = $this->pdo->prepare("
            SELECT notice_date, dear, event_highlights, event_time, event_venue
            FROM notice
            WHERE checklist_id = ?
        ");
        $stmtNotice->execute([$checklist_id]);
        $existingNotice = $stmtNotice->fetch(PDO::FETCH_ASSOC);

        $is_update = !empty($existingNotice);

        // Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           

            $date             = trim($_POST['date'] ?? '');
            $dear             = trim($_POST['dear'] ?? '');
            $event_highlights = trim($_POST['event_highlights'] ?? '');
            $event_time       = trim($_POST['event_time'] ?? '');
            $event_venue      = trim($_POST['event_venue'] ?? '');

            error_log("DEBUG: Parsed values - date: $date, dear: $dear, highlights: $event_highlights, time: $event_time, venue: $event_venue");

            // CSRF validation
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $this->redirect('/documents/notice?checklist_id=' . $checklist_id . '&error=csrf_invalid');
                exit;
            }

            if (empty($date) || empty($dear) || empty($event_highlights) ||
                empty($event_time) || empty($event_venue)) {
                $errors[] = "All fields are required!";
                error_log("DEBUG: Validation failed - empty fields");
            } else {
                try {
                    // Get HOD ID for the checklist - use the userdept_id from checklists table
                    $stmtHodId = $this->pdo->prepare("
                        SELECT u.id
                        FROM users u
                        WHERE u.department_id = (
                            SELECT userdept_id FROM checklists WHERE id = ?
                        ) AND u.role = 'hod'
                        LIMIT 1
                    ");
                    $stmtHodId->execute([$checklist_id]);
                    $hodData = $stmtHodId->fetch(PDO::FETCH_ASSOC);
                    $hod_id = $hodData['id'] ?? null;
                    
                    error_log("DEBUG: HOD ID retrieved: " . ($hod_id ?? 'null'));

                    if ($is_update) {
                        $stmtUpdate = $this->pdo->prepare("
                            UPDATE notice 
                            SET notice_date = ?, dear = ?, event_highlights = ?, 
                                event_time = ?, event_venue = ?
                            WHERE checklist_id = ?
                        ");
                        $result = $stmtUpdate->execute([
                            $date, $dear, $event_highlights, 
                            $event_time, $event_venue, $checklist_id
                        ]);
                        
                        error_log("DEBUG: Update query result: " . ($result ? 'success' : 'failed'));
                        error_log("DEBUG: Update query row count: " . $stmtUpdate->rowCount());
                        
                        if ($result && $stmtUpdate->rowCount() > 0) {
                            $success = "Notice updated successfully!";
                        } else {
                            $errors[] = "Notice update failed or no changes made.";
                        }
                    } else {
                        $id = Uuid::uuid4()->toString();
                        $stmtInsert = $this->pdo->prepare("
                            INSERT INTO notice 
                            (id, checklist_id, notice_date, dear, event_highlights, event_time, event_venue)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $result = $stmtInsert->execute([
                            $id, $checklist_id, $date, $dear, 
                            $event_highlights, $event_time, $event_venue
                        ]);
                        
                        error_log("DEBUG: Insert query result: " . ($result ? 'success' : 'failed'));
                        error_log("DEBUG: Insert query row count: " . $stmtInsert->rowCount());
                        
                        if ($result) {
                            $success = "Notice saved successfully!";
                        } else {
                            $errors[] = "Notice save failed.";
                        }
                    }

                    $this->redirect("/event-reports/documents/notice/$checklist_id?success=" . urlencode($success));
                } catch (PDOException $e) {
                    error_log("Notice save failed: " . $e->getMessage());
                    $errors[] = "Database error: Failed to save notice.";
                }
            }

            $form_data = [
                'date'             => $date,
                'dear'             => $dear,
                'event_highlights' => $event_highlights,
                'event_time'       => $event_time,
                'event_venue'      => $event_venue
            ];
        } else {
            // GET - load existing if any
            if ($existingNotice) {
                $form_data = [
                    'date'             => $existingNotice['notice_date'] ?? '',
                    'dear'             => $existingNotice['dear'] ?? '',
                    'event_highlights' => $existingNotice['event_highlights'] ?? '',
                    'event_time'       => $existingNotice['event_time'] ?? '',
                    'event_venue'      => $existingNotice['event_venue'] ?? ''
                ];
            }
        }

        $this->render('documents/notice', [
            'checklist_id'     => $checklist_id,
            'programme_name'   => htmlspecialchars($programme['programme_name'] ?? ''),
            'programme_date'   => htmlspecialchars($programme['programme_date'] ?? ''),
            'multi_day'        => $programme['multi_day'] ?? 0,
            'programme_start_date' => htmlspecialchars($programme['programme_start_date'] ?? ''),
            'programme_end_date'   => htmlspecialchars($programme['programme_end_date'] ?? ''),
            'coordinator_name' => $coordinator_name,
            'hod_name'         => $hod_name,
            'form_data'        => $form_data,
            'is_update'        => $is_update,
            'success'          => $success,
            'errors'           => $errors
        ]);
    }

    /* =====================================
       VIEW SAVED NOTICE (final read-only display)
    ===================================== */
    public function view($checklist_id = null)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        // Get checklist_id from router parameter or GET parameter
        $checklist_id = $checklist_id ?? $_GET['checklist_id'] ?? null;
        if (
            empty($checklist_id) ||
            !preg_match('/^[0-9a-f-]{36}$/i', $checklist_id)
        ) {
            throw new Exception('Invalid checklist ID');
        }
        if (!$checklist_id) {
            $this->redirect('/dashboard?error=checklist_id_missing');
        }

        // Fetch notice
        $stmtNotice = $this->pdo->prepare("
            SELECT * FROM notice WHERE checklist_id = ?
        ");
        $stmtNotice->execute([$checklist_id]);
        $notice = $stmtNotice->fetch(PDO::FETCH_ASSOC);

        if (!$notice) {
            $this->redirect("/event-reports/documents/notice?checklist_id=$checklist_id&error=no_notice_found");
        }

        // Fetch programme details
        $stmtProgramme = $this->pdo->prepare("
            SELECT programme_name, programme_date, multi_day,
                   programme_start_date, programme_end_date, department
            FROM checklists
            WHERE id = ?
        ");
        $stmtProgramme->execute([$checklist_id]);
        $checklist = $stmtProgramme->fetch(PDO::FETCH_ASSOC);

        if (!$checklist) {
            $this->redirect('/dashboard&error=checklist_not_found');
        }

        // Header image logic
        $deptArray = json_decode($checklist['department'], true) ?? [];
        $header_image = '';

        // Default header
        $stmtDefault = $this->pdo->query("SELECT image FROM default_header LIMIT 1");
        $defaultRow = $stmtDefault->fetch(PDO::FETCH_ASSOC);
        $header_image = $defaultRow['image'] ?? '';

        // Single department override
        if (is_array($deptArray) && count($deptArray) === 1 && !empty($deptArray[0])) {
            $dept_id = $deptArray[0]; // Keep as UUID string
            $stmtDept = $this->pdo->prepare("SELECT header_image FROM departments WHERE id = ?");
            $stmtDept->execute([$dept_id]);
            $deptRow = $stmtDept->fetch(PDO::FETCH_ASSOC);
            if (!empty($deptRow['header_image'])) {
                $header_image = $deptRow['header_image'];
            }
        }

        // Format dates
        $notice_date = !empty($notice['notice_date'])
            ? date('d-m-Y', strtotime($notice['notice_date']))
            : 'N/A';

        $programme_name = htmlspecialchars($checklist['programme_name'] ?? '');

        if ($checklist['multi_day']) {
            $event_date = date('d-m-Y', strtotime($checklist['programme_start_date'])) .
                          " to " .
                          date('d-m-Y', strtotime($checklist['programme_end_date']));
        } else {
            $event_date = date('d-m-Y', strtotime($checklist['programme_date']));
        }

        // Coordinator + sign
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

        // HOD + sign - only if exactly one department exists
        $hod_name = 'N/A';
        $hod_sign = '';
        
        // Check if exactly one department exists (same logic as manage() method)
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

        // Principal + sign
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

        // Prepare data for view
        $this->render('documents/view_notice', [
            'header_image'       => $header_image,
            'notice_date'        => $notice_date,
            'programme_name'     => $programme_name,
            'event_date'         => $event_date, 
            'dear'               => htmlspecialchars_decode($notice['dear'] ?? ''),
            'event_highlights'   => htmlspecialchars_decode($notice['event_highlights'] ?? ''),
            'event_time'         => !empty($notice['event_time'])
                                    ? date('h:i A', strtotime($notice['event_time']))
                                    : 'N/A',
            'event_venue'        => htmlspecialchars($notice['event_venue'] ?? ''),
            'coordinator_name'   => $coordinator_name,
            'coordinator_sign'   => $coordinator_sign,
            'hod_name'           => $hod_name,
            'hod_sign'           => $hod_sign,
            'principal_name'     => $principal_name,
            'principal_sign'     => $principal_sign,
            'checklist_id'       => $checklist_id
        ]);
    }
}