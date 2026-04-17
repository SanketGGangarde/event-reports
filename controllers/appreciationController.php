<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../core/BaseController.php';

class AppreciationController extends BaseController
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /* =====================================
       MANAGE APPRECIATION FORM (GET + POST)
       - Shows form per guest with pagination
       - Creates/updates appreciation
    ===================================== */
    public function manage()
    {
        // Auth check
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $checklist_id = $_GET['checklist_id'] ?? $_POST['checklist_id'] ?? null;
        if (
            empty($checklist_id) ||
            !preg_match('/^[0-9a-f-]{36}$/i', $checklist_id)
        ) {
            $this->redirect('/event-reports/dashboard?error=checklist_not_found');
            exit;
        }

        $page = $_GET['page'] ?? 1;
        if (
            empty($page) ||
            !is_numeric($page) ||
            $page < 1
        ) {
            $page = 1;
        }
        if ($page < 1) $page = 1;

        // Fetch checklist + coordinator
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.username as coordinator_name
            FROM checklists c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$checklist_id]);
        $checklist = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$checklist) {
            $this->redirect('/dashboard?error=checklist_not_found');
        }

        // Fetch all guests
        $stmtGuests = $this->pdo->prepare("
            SELECT id, guest_name, company_name, designation
            FROM checklist_guests 
            WHERE checklist_id = ?
            ORDER BY id ASC
        ");
        $stmtGuests->execute([$checklist_id]);
        $guests = $stmtGuests->fetchAll(PDO::FETCH_ASSOC);

        $totalGuests = count($guests);

         if ($totalGuests == 0) {
            

         $_SESSION['errors'] = ["No guests found for this checklist. Please add guests first."];


    $this->redirect("/event-reports/documents/view/checklist/$checklist_id");
    return;
}

       if ($page > $totalGuests) {
            $page = $totalGuests;
        }
        
        if (!isset($guests[$page - 1])) {
            die("Guest not found for page: " . $page);
        }
        $guest = $guests[$page - 1];
        $guest_id = $guest['id'];
        $guestName = htmlspecialchars($guest['guest_name'] ?? '');
        $companyName = htmlspecialchars($guest['company_name'] ?? 'N/A');
        $companyDesignation = htmlspecialchars($guest['designation'] ?? 'N/A');

        $coordinator_name = htmlspecialchars($checklist['coordinator_name'] ?? 'N/A');

        // Fetch HOD - only if exactly one department exists
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

        // Check existing appreciation
        $stmtAppreciation = $this->pdo->prepare("
            SELECT * FROM appreciation 
            WHERE checklist_id = ? AND guest_id = ?
        ");
        $stmtAppreciation->execute([$checklist_id, $guest_id]);
        $existingAppreciation = $stmtAppreciation->fetch(PDO::FETCH_ASSOC);

        // Prefill
        $date = $existingAppreciation['appreciation_date'] ?? '';
        $subject = $existingAppreciation['subject'] ?? '';
        $respected = $existingAppreciation['respected'] ?? '';
        $body = $existingAppreciation['body'] ?? '';
        $recipient = "$guestName - $companyName - $companyDesignation";

        $errors = [];
        $success = $_GET['success'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date       = trim($_POST['date'] ?? '');
            $recipient  = trim($_POST['recipient'] ?? '');
            $subject    = trim($_POST['subject'] ?? '');
            $respected  = trim($_POST['respected'] ?? '');
            $body = $_POST['body'] ?? '';

            if (empty($date) || empty($subject) || empty($respected) || empty($body)) {
                $errors[] = "All fields are required!";
            } else {
                try {
                    if ($existingAppreciation) {
                        $stmtUpdate = $this->pdo->prepare("
                            UPDATE appreciation 
                            SET appreciation_date = ?, recipient = ?, subject = ?, respected = ?, body = ?
                            WHERE checklist_id = ? AND guest_id = ?
                        ");
                        $stmtUpdate->execute([$date, $recipient, $subject, $respected, $body, $checklist_id, $guest_id]);
                        $success = "Appreciation updated successfully!";
                    } else {
                        $id = Uuid::uuid4()->toString();
                        $stmtInsert = $this->pdo->prepare("
                            INSERT INTO appreciation 
                            (id,checklist_id, guest_id, appreciation_date, recipient, subject, respected, body)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        try {
                            $stmtInsert->execute([$id, $checklist_id, $guest_id, $date, $recipient, $subject, $respected, $body]);
                            $success = "Appreciation saved successfully!";
                        } catch (PDOException $e) {
                            error_log("Appreciation Insert Error: " . $e->getMessage());
                            $error = "Failed to save appreciation.";
                        }
                    }

                    // Refresh data (like invitation system - no redirect)
                    $stmtAppreciation->execute([$checklist_id, $guest_id]);
                    $existingAppreciation = $stmtAppreciation->fetch(PDO::FETCH_ASSOC);

                    $date = $existingAppreciation['appreciation_date'] ?? '';
                    $subject = $existingAppreciation['subject'] ?? '';
                    $respected = $existingAppreciation['respected'] ?? '';
                    $body = $existingAppreciation['body'] ?? '';
                } catch (PDOException $e) {
                    error_log("Appreciation save failed: " . $e->getMessage());
                    $errors[] = "Failed to save appreciation. Please try again.";
                }
            }
        }

        $this->render('documents/appreciation', [
            'checklist_id'       => $checklist_id,
            'page'               => $page,
            'totalGuests'        => $totalGuests,
            'guest'              => $guest,
            'guestName'          => $guestName,
            'companyName'        => $companyName,
            'companyDesignation' => $companyDesignation,
            'coordinator_name'   => $coordinator_name,
            'hod_name'           => $hod_name,
            'existingAppreciation' => $existingAppreciation,
            'date'               => $date,
            'subject'            => $subject,
            'respected'          => $respected,
            'body'               => $body,
            'recipient'          => $recipient,
            'success'            => $success,
            'errors'             => $errors
        ]);
    }

    /* =====================================
       VIEW FINAL APPRECIATION (after submit)
    ===================================== */
    public function view($checklist_id = null)
{
    // Auth check
    if (!isset($_SESSION['user_id'])) {
        $this->redirect('/login');
    }

    // Get checklist_id
    if ($checklist_id === null) {
        $checklist_id = $_GET['checklist_id'] ?? null;
    }

    if (!$checklist_id) {
        $this->redirect('/dashboard?error=checklist_id_missing');
    }

    /* ==============================
       ✅ PAGINATION (FIX)
    ============================== */
    $page = $_GET['page'] ?? 1;
    $page = (is_numeric($page) && $page > 0) ? (int)$page : 1;

    // Fetch guests
    $stmtGuests = $this->pdo->prepare("
        SELECT id, guest_name, company_name, designation
        FROM checklist_guests
        WHERE checklist_id = ?
        ORDER BY id ASC
    ");
    $stmtGuests->execute([$checklist_id]);
    $guests = $stmtGuests->fetchAll(PDO::FETCH_ASSOC);

    $totalGuests = count($guests);

    if ($totalGuests == 0) {
        $this->redirect('/dashboard?error=no_guests_found');
    }

    if ($page > $totalGuests) {
        $page = $totalGuests;
    }

    $guest = $guests[$page - 1];
    $guest_id = $guest['id'];

    /* ==============================
       ✅ FETCH APPRECIATION PER GUEST
    ============================== */
   $stmt = $this->pdo->prepare("
        SELECT a.*, ch.department
        FROM appreciation a
        JOIN checklists ch 
            ON BINARY a.checklist_id = BINARY ch.id
        WHERE BINARY a.checklist_id = ?
        AND BINARY a.guest_id = ?
        LIMIT 1
    ");
    $stmt->execute([$checklist_id, $guest_id]);
    $appreciation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appreciation) {
        $this->redirect('/documents/appreciation/' . $checklist_id . '?page=' . $page . '&error=no_appreciation');
    }

    /* ==============================
       HEADER IMAGE
    ============================== */
    $deptArray = json_decode($appreciation['department'], true) ?? [];

    // Default header
    $stmtDefault = $this->pdo->query("SELECT image FROM default_header LIMIT 1");
    $defaultRow = $stmtDefault->fetch(PDO::FETCH_ASSOC);
    $header_image = $defaultRow['image'] ?? '';

    // Department header if single dept
    if (is_array($deptArray) && count($deptArray) === 1 && !empty($deptArray[0])) {
        $stmtDept = $this->pdo->prepare("SELECT header_image FROM departments WHERE id = ?");
        $stmtDept->execute([$deptArray[0]]);
        $deptRow = $stmtDept->fetch(PDO::FETCH_ASSOC);

        if (!empty($deptRow['header_image'])) {
            $header_image = $deptRow['header_image'];
        }
    }

    /* ==============================
       DATE FORMAT
    ============================== */
    $date = !empty($appreciation['appreciation_date'])
        ? date('d-m-Y', strtotime($appreciation['appreciation_date']))
        : 'N/A';

    /* ==============================
       GUEST INFO
    ============================== */
    $guestName = $guest['guest_name'] ?? '';
    $companyName = $guest['company_name'] ?? 'N/A';
    $companyDesignation = $guest['designation'] ?? 'N/A';

    /* ==============================
       COORDINATOR
    ============================== */
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

    /* ==============================
       HOD (ONLY IF 1 DEPT)
    ============================== */
    $hod_name = 'N/A';
    $hod_sign = '';

    if (is_array($deptArray) && count($deptArray) === 1) {
        $stmtHod = $this->pdo->prepare("
            SELECT username AS name, sign_image 
            FROM users 
            WHERE role = 'hod' AND department_id = ? 
            LIMIT 1
        ");
        $stmtHod->execute([$deptArray[0]]);
        $hod = $stmtHod->fetch(PDO::FETCH_ASSOC);

        $hod_name = htmlspecialchars($hod['name'] ?? 'N/A');
        $hod_sign = $hod['sign_image'] ?? '';
    }

    /* ==============================
       PRINCIPAL
    ============================== */
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

    /* ==============================
       FINAL RENDER
    ============================== */
    $this->render('documents/view_appreciation', [
        'header_image'       => $header_image,
        'date'               => $date,
        'guestName'          => $guestName,
        'companyName'        => $companyName,
        'companyDesignation' => $companyDesignation,
        'subject'            => htmlspecialchars($appreciation['subject'] ?? ''),
        'respected'          => htmlspecialchars($appreciation['respected'] ?? ''),
        'body' => $appreciation['body'] ?? '',
        'coordinator_name'   => $coordinator_name,
        'coordinator_sign'   => $coordinator_sign,
        'hod_name'           => $hod_name,
        'hod_sign'           => $hod_sign,
        'principal_name'     => $principal_name,
        'principal_sign'     => $principal_sign,
        'checklist_id'       => $checklist_id,

        // ✅ FIX (IMPORTANT)
        'page'               => $page,
        'totalGuests'        => $totalGuests
    ]);
}
    
}
