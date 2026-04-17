<?php

/**
 * NewUserController
 * Manages creation and updating of Coordinators (by HOD) and HODs (by Principal)
 */
use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../config/cloudinaryHelper.php';
use Cloudinary\Api\Upload\UploadApi;
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../core/Flash.php';

class usersController extends BaseController
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->flash = new Flash();
    }

    /* =====================================
       SHARED HELPERS
    ===================================== */
   private function uploadImage($file, $folder = 'users', $publicId = null)
{
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];

    if (!in_array($file['type'], $allowed)) {
        return null;
    }

    if ($file['size'] > 2097152) {
        return null;
    }

    try {

        $options = [
            'folder' => $folder,
            'resource_type' => 'image'
        ];

        // 🔥 If public ID exists → overwrite
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
    protected function getCurrentUser()
    {
        if (!isset($_SESSION['user_id'])) { 
            $this->redirect('/login');
        }

        $stmt = $this->pdo->prepare("SELECT id, username, role, department_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            session_destroy();
            $this->redirect('/login?error=user_not_found');
        }

        return $user;
    }

    
/* =====================================
    CREATE USER 
    ===================================== */

   public function createUser()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $this->redirect('/dashboard?error=invalid_method');
    }

    $currentUser = $this->getCurrentUser();

    /*
    =====================================================
    🔐 DETERMINE ROLE FROM CURRENT USER
    =====================================================
    */
    if (isset($currentUser['role']) && $currentUser['role'] === 'principal') {
        $expectedRole = 'hod';
    } 
    elseif (isset($currentUser['role']) && $currentUser['role'] === 'hod') {
        $expectedRole = 'coordinator';
    } 
    else {
        return $this->redirect('/dashboard?error=Access denied');
    }

    /*
    =====================================================
    ✅ VALIDATION
    =====================================================
    */
    $validation = \Validation\UserValidation::validateCreateUser($_POST, $_FILES);

    if (!$validation['status']) {
        return $this->redirectWithErrors($_SERVER['HTTP_REFERER'], $validation['errors']);
    }

    $data = $validation['data'];

   /*
=====================================================
🏢 DEPARTMENT LOGIC
=====================================================
*/

$departmentId = null;

/*
-----------------------------------------------------
1️⃣ Principal → Creating HODif (
        empty($department) ||
        !preg_match('/^[0-9a-f-]{36}$/i', $department)
    ) {
        $errors[] = 'Invalid department ID';
    }
    $department = $data['department_id'] ?? null;
    if (
        empty($department) ||
        !preg_match('/^[0-9a-f-]{36}$/i', $department)
    ) {
        $errors[] = 'Invalid department ID';
    }
-----------------------------------------------------
*/
if ($expectedRole === 'hod') {

    $departmentId = $_POST['department_id'] ?? null;

    if (
        empty($departmentId) ||
        !preg_match(
            '/^[0-9a-f-]{36}$/i',
            $departmentId
        )
    ) {
        return $this->redirect($_SERVER['HTTP_REFERER'] . '?error=Invalid Department');
    }

    // 🚨 Ensure only one HOD per department
    $stmt = $this->pdo->prepare("
        SELECT id FROM users 
        WHERE role = 'hod' 
        AND department_id = ?
    ");

    $stmt->execute([$departmentId]);

    if ($stmt->fetch()) {
        $this->flash->error("HOD of that department already exists");
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }
}

/*
-----------------------------------------------------
2️⃣ HOD → Creating Coordinator
-----------------------------------------------------
*/
elseif ($expectedRole === 'coordinator') {

    // Force department from logged-in HOD
    $departmentId = $currentUser['department_id'] ?? null;

    if (
        empty($departmentId) ||
        !preg_match(
            '/^[0-9a-f-]{36}$/i',
            $departmentId
        )
    ) {
        return $this->redirect('/dashboard?error=HOD department not assigned');
    }
}
    /*
    =====================================================
    🔎 UNIQUE CHECK
    =====================================================
    */
    $stmt = $this->pdo->prepare("
        SELECT id FROM users 
        WHERE username = ? 
        OR email = ? 
        OR recovery_email = ? 
        OR contact_number = ?
    ");

    $stmt->execute([
        $data['username'],
        $data['email'],
        $data['recovery_email'],
        $data['contact_number']
    ]);

    if ($stmt->fetch()) {
        $this->flash->error("User already exists");
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }
    /*
    =====================================================
    🔐 HASH PASSWORD
    =====================================================
    */
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    /*
    =====================================================
    🖼 IMAGE UPLOAD
    =====================================================
    */
    $profile = null;
$profilePublicId = null;

$sign = null;
$signPublicId = null;

if (!empty($_FILES['profile_image']['name'])) {

    $upload = $this->uploadImage(
        $_FILES['profile_image'],
        'users/profile'
    );

    if ($upload) {
        $profile = $upload['url'];
        $profilePublicId = $upload['public_id'];
    }
}

if (!empty($_FILES['sign_image']['name'])) {

    $upload = $this->uploadImage(
        $_FILES['sign_image'],
        'users/signature'
    );

    if ($upload) {
        $sign = $upload['url'];
        $signPublicId = $upload['public_id'];
    }
}

    /*
    =====================================================
    💾 INSERT
    =====================================================
    */
    $id = Uuid::uuid4()->toString();
   $stmt = $this->pdo->prepare("
    INSERT INTO users
    (id, username, email, recovery_email, password, contact_number,
    department_id,
    profile_image, profile_public_id,
    sign_image, sign_public_id,
    role)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $id,
        $data['username'],
        $data['email'],
        $data['recovery_email'],
        $hashedPassword,
        $data['contact_number'],
        $departmentId, // 🔥 MISSING BEFORE
        $profile,
        $profilePublicId,
        $sign,
        $signPublicId,
        $expectedRole
    ]);
    $this->flash->success("User created successfully!");
    return $this->redirect($_SERVER['HTTP_REFERER']);
}

/* =====================================
       UPDATE USER
    ===================================== */
   public function updateUser()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $this->redirect('/dashboard?error=invalid_method');
    }

    $currentUser = $this->getCurrentUser();

    if (!$currentUser) {
        return $this->redirect('/login');
    }

    /*
    =====================================================
    ✅ VALIDATION
    =====================================================
    */
    $validation = \Validation\UserValidation::validateUpdateUser($_POST, $_FILES);

    if (!$validation['status']) {
        return $this->redirectWithErrors($_SERVER['HTTP_REFERER'], $validation['errors']);
    }

    $data = $validation['data'];

    /*
    =====================================================
    🔎 FETCH TARGET USER
    =====================================================
    */
    $stmt = $this->pdo->prepare("
        SELECT id, role, department_id 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$data['id']]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        return $this->redirect($_SERVER['HTTP_REFERER'] . '?error=User not found');
    }

    /*
    =====================================================
    🔐 DETERMINE EXPECTED ROLE (DO NOT TRUST FORM ROLE)
    =====================================================
    */
    if ($currentUser['role'] === 'principal') {

        // Principal can update HOD 
        if ($targetUser['role'] === 'hod') {
            $expectedRole = 'hod';
        }else {
            return $this->redirect('/dashboard?error=Access denied');
        }

    } 
    elseif ($currentUser['role'] === 'hod') {

        // HOD can update ONLY coordinator
        if ($targetUser['role'] !== 'coordinator') {
            return $this->redirect('/dashboard?error=Access denied');
        }

        // Must be same department
        if ($targetUser['department_id'] != $currentUser['department_id']) {
            return $this->redirect('/dashboard?error=Unauthorized department access');
        }

        $expectedRole = 'coordinator';

    } 
    else {
        return $this->redirect('/dashboard?error=Access denied');
    }

    /*
    =====================================================
    🏢 DEPARTMENT LOGIC
    =====================================================
    */

    if ($expectedRole === 'hod') {

        $departmentId = $_POST['department_id'] ?? null;

        if (
            empty($departmentId) ||
            !preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $departmentId
            )
        ) {
            return $this->redirect($_SERVER['HTTP_REFERER'] . '?error=Invalid Department');
        }
    }

    // 🚨 Ensure only one HOD per department (exclude current user)
if ($expectedRole === 'hod') {

    $stmt = $this->pdo->prepare("
        SELECT id FROM users
        WHERE role = 'hod'
        AND department_id = ?
        AND id != ?
    ");

    $stmt->execute([$departmentId, $data['id']]);

    if ($stmt->fetch()) {
        $this->flash->error("Hod of that department already exists");
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }
}

    if ($expectedRole === 'coordinator') {
        // Coordinator must belong to HOD's department
        $departmentId = ($currentUser['role'] === 'hod')
            ? $currentUser['department_id']
            : $targetUser['department_id'];
    }

    /*
    =====================================================
    🔎 UNIQUE CHECK (EXCLUDE CURRENT USER)
    =====================================================
    */
    $stmt = $this->pdo->prepare("
        SELECT id FROM users
        WHERE (username = ? OR email = ? OR recovery_email = ? OR contact_number = ?)
        AND id != ?
    ");

    $stmt->execute([
        $data['username'],
        $data['email'],
        $data['recovery_email'],
        $data['contact_number'],
        $data['id']
    ]);

    if ($stmt->fetch()) {
        $this->flash->error("Duplicate data found");
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /*
    =====================================================
    🖼 OPTIONAL IMAGE UPDATE
    =====================================================
    */
    // Only update images if new files are uploaded, otherwise keep existing
  $stmt = $this->pdo->prepare("SELECT profile_image, profile_public_id, sign_image, sign_public_id FROM users WHERE id = ?");
$stmt->execute([$data['id']]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

$profile = $existing['profile_image'];
$profilePublicId = $existing['profile_public_id'];

$sign = $existing['sign_image'];
$signPublicId = $existing['sign_public_id'];

if (!empty($_FILES['profile_image']['name'])) {

    $upload = $this->uploadImage(
        $_FILES['profile_image'],
        'users/profile',
        $profilePublicId
    );

    if ($upload) {
        $profile = $upload['url'];
        $profilePublicId = $upload['public_id'];
    }
}

if (!empty($_FILES['sign_image']['name'])) {

    $upload = $this->uploadImage(
        $_FILES['sign_image'],
        'users/signature',
        $signPublicId
    );

    if ($upload) {
        $sign = $upload['url'];
        $signPublicId = $upload['public_id'];
    }
}

    /*
    =====================================================
    💾 UPDATE QUERY
    =====================================================
    */
    $stmt = $this->pdo->prepare("
    UPDATE users SET
        username = ?,
        email = ?,
        recovery_email = ?,
        contact_number = ?,
        role = ?,
        department_id = ?,
        profile_image = ?,
        profile_public_id = ?,
        sign_image = ?,
        sign_public_id = ?
    WHERE id = ?
");

    $stmt->execute([
    $data['username'],
    $data['email'],
    $data['recovery_email'],
    $data['contact_number'],
    $expectedRole,
    $departmentId,
    $profile,
    $profilePublicId,
    $sign,
    $signPublicId,
    $data['id']
]);
    
    $this->flash->success("Account updated successfully!");
    return $this->redirect($_SERVER['HTTP_REFERER']);
}



/* =====================================
       HODS - List (Principal view)
    ===================================== */
    public function hodIndex()
    {
        $user = $this->getCurrentUser();
        if ($user['role'] !== 'principal') {
            $this->redirect('/dashboard?error=access_denied');
        }

        $stmt = $this->pdo->prepare("
            SELECT u.*, d.name AS department_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.role = 'hod'
            ORDER BY u.username ASC
        ");
        $stmt->execute();
        $hods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtDepts = $this->pdo->query("SELECT id, name FROM departments ORDER BY name ASC");
        $departments = $stmtDepts->fetchAll(PDO::FETCH_ASSOC);

        $this->render('manage/manage_hod', [
            'hods'         => $hods,
            'departments'  => $departments,
            'success'      => $_GET['success'] ?? null,
            'error'        => $_GET['error'] ?? null
        ]);
    }

    public function coordinatorsIndex()
    {
        $user = $this->getCurrentUser();

        if (!in_array($user['role'], ['hod'])) {
            $this->redirect('/dashboard?error=access_denied');
        }

        $query = "
            SELECT id, username, email, recovery_email, contact_number, created_at,
                   profile_image, sign_image
            FROM users 
            WHERE role = 'coordinator'
        ";

        $params = [];
        if ($user['role'] === 'hod' && $user['department_id']) {
            $query .= " AND department_id = ?";
            $params[] = $user['department_id'];
        }

        $query .= " ORDER BY username ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $coordinators = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('manage/manage_coordinators', [
            'coordinators' => $coordinators,
            'user'         => $user,
            'success'      => $_GET['success'] ?? null,
            'error'        => $_GET['error'] ?? null
        ]);
    }

    
}


