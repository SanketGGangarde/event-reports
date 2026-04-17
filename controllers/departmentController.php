<?php

/**
 * DepartmentController
 * Handles CRUD for departments + Default Header Logo upload (Principal only)
 */
use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../config/cloudinaryHelper.php';
use Cloudinary\Api\Upload\UploadApi;
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../validation/departmentValidation.php';
require_once __DIR__ . '/../core/Flash.php';

use Validation\DepartmentValidation;

class DepartmentController extends BaseController
{
    protected $flash;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->flash = new Flash();
    }

    /* =====================================
       HELPERS (file upload - shared)
    ===================================== */
    private function uploadToCloudinary($file, $folder, $publicId = null)
{
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        return null;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }

    try {

        $options = [
            'folder' => $folder,
            'resource_type' => 'image'
        ];

        // 🔥 If public_id exists → overwrite image
       if ($publicId) {
            $options['public_id'] = $publicId;
            $options['overwrite'] = true;
            $options['invalidate'] = true; // 🔥 ADD THIS
        }

        $upload = (new UploadApi())->upload(
            $file['tmp_name'],
            $options
        );

        return [
            'url' => $upload['secure_url'],
            'public_id' => $upload['public_id']
        ];

    } catch (\Exception $e) {
        error_log("Cloudinary Department Upload Error: " . $e->getMessage());
        return null;
    }
}

    /* =====================================
       SHOW DEPARTMENTS + DEFAULT HEADER FORM
    ===================================== */
    public function index(){
        
    $validation = DepartmentValidation::validateIndexAccess(
        $_SESSION,
        $_GET
    );

    if (!$validation['status']) {
        return $this->redirect('/login?error=unauthorized');
    }

    $editId = $validation['data']['editId'];

    // ---------------------------
    // Fetch departments with user count
    // ---------------------------
    $stmt = $this->pdo->query("
        SELECT d.*, COUNT(u.id) AS user_count
        FROM departments d
        LEFT JOIN users u ON d.id = u.department_id
        GROUP BY d.id
        ORDER BY d.name ASC
    ");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------
    // Fetch default header
    // ---------------------------
    $stmtDefault = $this->pdo->query("
        SELECT id, image, public_id FROM default_header LIMIT 1
    ");
    $defaultHeader = $stmtDefault->fetch(PDO::FETCH_ASSOC);

    // ---------------------------
    // Fetch edit department if exists
    // ---------------------------
    $editDepartment = null;

    if ($editId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM departments WHERE id = ?
        ");
        $stmt->execute([$editId]);
        $editDepartment = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $this->render('manage/manage_department', [
        'departments'     => $departments,
        'editDepartment'  => $editDepartment,
        'defaultHeader'   => $defaultHeader,
        'success'         => $_GET['success'] ?? null,
        'error'           => $_GET['error'] ?? null,
        'success_default' => $_GET['success_default'] ?? null,
        'error_default'   => $_GET['error_default'] ?? null
    ]);
}

    /* =====================================
       CREATE NEW DEPARTMENT
    ===================================== */
    

public function store()
{
    if (isset($_POST['update_department'])) {
        $this->update();
        return;
    }

    if (isset($_POST['save_default_header'])) {
        $this->updateDefaultHeader();
        return;
    }

    //  Call validation with PDO
    $validation = DepartmentValidation::validateCreate(
        $_POST,
        $_FILES,
        $this->pdo
    );

    if (!$validation['status']) {
        return $this->redirectWithErrors(
            '/event-reports/manage/departments',
            $validation['errors'],
            $_POST
        );
    }

    $data = $validation['data'];
    $name = $data['department_name'];
   $headerImage = null;
    $headerPublicId = null;

    if (!empty($_FILES['header_image']['name'])) {

        $upload = $this->uploadToCloudinary(
            $_FILES['header_image'],
            'departments/header'
        );

        if ($upload) {
            $headerImage = $upload['url'];
            $headerPublicId = $upload['public_id'];
        }
    }

    $id = Uuid::uuid4()->toString();

     $sql = "INSERT INTO departments (id, name, header_image, header_public_id)
            VALUES (?, ?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);

    $stmt->execute([
        $id,
        $name,
        $headerImage,
        $headerPublicId
    ]);

    $_SESSION['success'] = "Department created successfully";

    $this->redirect('/event-reports/manage/departments');
}

    /* =====================================
       UPDATE DEPARTMENT
    ===================================== */


public function update()
{
    // 🔐 1️⃣ Must be logged in
    if (!isset($_SESSION['user_id'])) {
        return $this->redirect('/login?error=Please login first');
    }

    // 🔐 2️⃣ Only principal allowed
    if ($_SESSION['role'] !== 'principal') {
        return $this->redirect('/dashboard?error=Unauthorized access');
    }

    // 3️⃣ Validate input
    $validation = DepartmentValidation::validateUpdate($_POST);

    if (!$validation['status']) {
        return $this->redirectWithErrors(
            "/event-reports/manage/departments?edit=" . ($_POST['department_id'] ?? ''),
            $validation['errors']
        );
    }

    $id   = $validation['data']['id'];
    $name = $validation['data']['name'];

    try {

        // 4️⃣ Check duplicate department name (excluding current ID)
        $stmt = $this->pdo->prepare("
            SELECT id FROM departments
            WHERE name = ? AND id != ?
            LIMIT 1
        ");
        $stmt->execute([$name, $id]);

        if ($stmt->fetch()) {
            $this->flash->error("Department name already exists.");
        $this->redirect('/event-reports/manage/departments/?edit=' . $id);
        return;
        }

        // 5️⃣ Get current image
        $stmt = $this->pdo->prepare("
            SELECT header_image, header_public_id FROM departments WHERE id = ?
        ");
        $stmt->execute([$id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            return $this->redirect('/event-reports/manage/departments?error=Department not found');
        }

        $headerImage = $current['header_image'];
        $headerPublicId = $current['header_public_id'];

        // 6️⃣ Handle new image upload
        if (!empty($_FILES['header_image']['name'])) {

        $upload = $this->uploadToCloudinary(
            $_FILES['header_image'],
            'departments/header',
            $headerPublicId // 🔥 overwrite if exists
        );

        if ($upload) {
            $headerImage = $upload['url'];
            $headerPublicId = $upload['public_id'];
        }
    }

        // 7️⃣ Update department
        $stmt = $this->pdo->prepare("
            UPDATE departments
            SET name = ?, header_image = ?, header_public_id = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $headerImage,
            $headerPublicId,
            $id
        ]);

        $_SESSION['success'] = "Department updated successfully";

        // Redirect to the manage departments page after successful update
        $this->redirect('/event-reports/manage/departments');

    } catch (\Exception $e) {

        error_log("Department Update Error: " . $e->getMessage());

        $_SESSION['error'] = "Failed to update department";
    }
}
    /* =====================================
       SAVE / UPDATE DEFAULT HEADER LOGO
    ===================================== */
    public function updateDefaultHeader()
{
    // 1️⃣ Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_default_header'])) {
        return $this->redirect('/event-reports/manage/departments?error=invalid_request');
    }

    // 2️⃣ Validate file exists
    if (empty($_FILES['default_header_image']['name']) || 
        $_FILES['default_header_image']['error'] !== UPLOAD_ERR_OK) {
        return $this->redirect('/event-reports/manage/departments?error_default=Please select an image');
    }

    $file = $_FILES['default_header_image'];

    // 3️⃣ Validate MIME type (secure way)
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $mime = mime_content_type($file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        return $this->redirect('/event-reports/manage/departments?error_default=Only JPG, PNG, WEBP allowed');
    }

    // 4️⃣ Validate size (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return $this->redirect('/event-reports/manage/departments?error_default=Image must be less than 2MB');
    }

    try {

        // 5️⃣ Fetch existing default header (if any)
        $stmt = $this->pdo->query("SELECT id, image, public_id FROM default_header LIMIT 1");
        $defaultHeader = $stmt->fetch(PDO::FETCH_ASSOC);

        $existingPublicId = $defaultHeader['public_id'] ?? null;

        // 6️⃣ Prepare Cloudinary upload options
        $options = [
            'folder' => 'departments/default_header',
            'resource_type' => 'image'
        ];

        // 🔥 Overwrite if already exists
       if ($existingPublicId) {
            $options['public_id'] = $existingPublicId;
            $options['overwrite'] = true;
            $options['invalidate'] = true; // 🔥 ADD THIS
        }

        // 7️⃣ Upload to Cloudinary
        $upload = (new \Cloudinary\Api\Upload\UploadApi())
            ->upload($file['tmp_name'], $options);

        $imageUrl = $upload['secure_url'];
        $publicId = $upload['public_id'];

        // 8️⃣ Update or Insert in DB
        if ($defaultHeader) {

            $updateStmt = $this->pdo->prepare("
                UPDATE default_header 
                SET image = ?, public_id = ?
                WHERE id = ?
            ");
            $updateStmt->execute([
                $imageUrl,
                $publicId,
                $defaultHeader['id']
            ]);

        } else {
            $id = Uuid::uuid4()->toString();
            $insertStmt = $this->pdo->prepare("
                INSERT INTO default_header (id, image, public_id)
                VALUES (?, ?, ?)
            ");
            $insertStmt->execute([
                $id,
                $imageUrl,
                $publicId
            ]);
        }

        return $this->redirect(
            '/event-reports/manage/departments?success_default=Default header saved successfully'
        );

    } catch (\Exception $e) {

        error_log("Default Header Upload Error: " . $e->getMessage());

        return $this->redirect(
            '/event-reports/manage/departments?error_default=Upload failed'
        );
    }
}
}
