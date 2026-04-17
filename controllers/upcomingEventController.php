<?php

/**
 * EventController
 * Handles CRUD operations for events
 * - Principal: sees all events
 * - HOD: sees events in their department
 * - Coordinator/others: sees only their own events
 */
use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../config/cloudinaryHelper.php';
use Cloudinary\Api\Upload\UploadApi;

class UpcomingEventController extends BaseController
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    
    /* =====================================
       HELPERS
    ===================================== */
    private function secureUpload($file, $publicId = null)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        return null;
    }

    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return null;
    }

    try {

        $options = [
            'folder' => 'events',
            'resource_type' => 'image'
        ];

        // overwrite existing image if public id exists
        if ($publicId) {
            $options['public_id'] = $publicId;
            $options['overwrite'] = true;
        }

        $upload = (new UploadApi())->upload(
            $file['tmp_name'],
            $options
        );

        return [
            'url' => $upload['secure_url'],
            'public_id' => $upload['public_id']
        ];

    } catch (Exception $e) {
        error_log("Cloudinary Upload Error: " . $e->getMessage());
        return null;
    }
}

  /* =====================================
   LIST EVENTS (ONLY HOD)
===================================== */
public function index()
{
    if (!isset($_SESSION['user_id'])) {
        $this->redirect('/login');
        return;
    }

    $user_id       = $_SESSION['user_id'];
    $user_role     = $_SESSION['role'] ?? null;
    $department_id = $_SESSION['department_id'] ?? null;

    // Only HOD allowed
    if ($user_role !== 'hod') {
        $_SESSION['errors'][] = "Access denied. Only HOD can view events.";
        $this->redirect('/dashboard');
        return;
    }

    if (!$department_id) {
        $this->redirect('/dashboard?error=no_department');
        return;
    }

    $query = "
        SELECT 
            e.id,
            e.event_name,
            e.start_date,
            e.end_date,
            e.image_path,
            e.image_public_id,
            e.created_by,
            u.username AS created_by_name,
            d.name AS department_name
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE e.department_id = ?
        ORDER BY e.end_date ASC
    ";

    $stmt = $this->pdo->prepare($query);
    $stmt->execute([$department_id]);

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $this->render('manage/manage_events', [
        'events'    => $events,
        'user_role' => $user_role,
        'success'   => $_GET['success'] ?? null,
        'error'     => $_GET['error'] ?? null
    ]);
}
    /* =====================================
       ADD NEW EVENT
    ===================================== */
   public function store()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('/event-reports/manage/events?error=invalid_method');
    }

    if (!isset($_SESSION['user_id'])) {
        $this->redirect('/login');
    }

    $event_name  = trim($_POST['event_name'] ?? '');
    $start_date  = trim($_POST['start_date'] ?? '');
    $end_date    = trim($_POST['end_date'] ?? '');
    $department  = $_POST['department'] ?? $_SESSION['department_id'] ?? null;

    if (empty($event_name) || empty($start_date) || empty($end_date)) {
        $this->redirect('/event-reports/manage/events?error=All fields are required');
    }

    if (strtotime($end_date) < strtotime($start_date)) {
        $this->redirect('/event-reports/manage/events?error=End date must be after start date');
    }

    // Image upload variables
    $image_path = null;
    $image_public_id = null;

    if (!empty($_FILES['event_image']['name'])) {

        $upload = $this->secureUpload($_FILES['event_image']);

        if (!$upload) {
            $this->redirect('/event-reports/manage/events?error=Invalid image file');
        }

        $image_path = $upload['url'];
        $image_public_id = $upload['public_id'];
    }

    // Generate UUID
    $id = Uuid::uuid4()->toString();

    $stmt = $this->pdo->prepare("
        INSERT INTO events 
        (id, event_name, start_date, end_date, image_path, image_public_id, created_by, department_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $id,
        $event_name,
        $start_date,
        $end_date,
        $image_path,
        $image_public_id,
        $_SESSION['user_id'],
        $department
    ]);

    $this->redirect('/event-reports/manage/events?success=Event added successfully');
}

    /* =====================================
       DELETE EVENT
    ===================================== */
    public function destroy()
{
    if (!isset($_GET['delete_event'])) {
        $this->redirect('/event-reports/manage/events?error=no_id');
    }

    $event_id = $_GET['delete_event'] ?? null;

    if (
        empty($event_id) ||
        !preg_match('/^[0-9a-f-]{36}$/i', $event_id)
    ) {
        throw new Exception('Invalid event ID');
    }

    // Fetch event
    $stmt = $this->pdo->prepare("
        SELECT image_path, image_public_id, created_by, department_id 
        FROM events 
        WHERE id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $this->redirect('/event-reports/manage/events?error=event_not_found');
    }

    $user_id   = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? null;

    // Permission check
    if ($user_role !== 'principal' && $event['created_by'] != $user_id) {
        $this->redirect('/event-reports/manage/events?error=permission_denied');
    }

    // Delete image from Cloudinary
    if (!empty($event['image_public_id'])) {
        try {
            (new UploadApi())->destroy($event['image_public_id']);
        } catch (Exception $e) {
            error_log("Cloudinary delete error: " . $e->getMessage());
        }
    }

    // Delete event from database
    $stmt = $this->pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);

    $this->redirect('/event-reports/manage/events?success=Event deleted successfully');
}
}