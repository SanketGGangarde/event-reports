<?php
/**
 * ProfileController
 * Handles user profile management (view and update)
 */
use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../config/cloudinaryHelper.php';
use Cloudinary\Api\Upload\UploadApi;
require_once __DIR__ . '/../core/BaseController.php';

class ProfileController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /* =====================================
       VIEW PROFILE (GET /profile)
    ===================================== */
    public function manageProfile()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Url::to('login') . '?error=unauthorized');
        }

        $userId = $_SESSION['user_id'];

        // Fetch user data
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            session_destroy();
            $this->redirect(Url::to('login') . '?error=user_not_found');
        }

        // Generate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Clear previous session messages
        unset($_SESSION['profile_errors'], $_SESSION['profile_success']);

        // Set global variables for the view
        global $view_user, $csrf_token, $errors, $success;
        $view_user = $user;
        $csrf_token = $_SESSION['csrf_token'];
        $errors = $_SESSION['errors'] ?? [];
        $success = $_SESSION['success'] ?? '';

        // Render the view
        $this->render('manage/manage_profile', [
            'view_user' => $view_user,
            'csrf_token' => $csrf_token,
            'errors' => $errors,
            'success' => $success
        ]);
    }

    /* =====================================
       UPDATE PROFILE (POST /profile)
    ===================================== */
    public function updateProfile()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Url::to('login') . '?error=unauthorized');
        }

        $userId = $_SESSION['user_id'];
        $errors = [];
        $success = "";

        // Clear previous session messages
        unset($_SESSION['profile_errors'], $_SESSION['profile_success']);

        // CSRF validation
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $errors[] = "Invalid request.";
            $_SESSION['errors'] = $errors;
            $this->redirect(Url::to('manage/profile'));
        }

        // Get form data
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $recovery_email = trim($_POST['recovery_email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');

        // Append @kse.in to email if not already present
        if (!empty($email) && substr($email, -8) !== '@kse.in') {
            $email = $email . '@kse.in';
        }
        
        // Debug: Log the email value before validation
        error_log("Email before validation: " . $email);

        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Fetch current user data for comparison
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = "User not found.";
            $_SESSION['errors'] = $errors;
            $this->redirect(Url::to('manage/profile'));
        }

        /* ------------ VALIDATION ------------ */
        
        // Username validation
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        } elseif (strlen($username) > 50) {
            $errors[] = "Username cannot exceed 50 characters.";
        } elseif (preg_match('/^[0-9]/', $username)) {
            $errors[] = "Username cannot start with a number.";
        } elseif (!preg_match('/^[A-Za-z][A-Za-z0-9._ -]*$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, dots, underscores, and hyphens.";
        }

        // Email validation
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } elseif (strlen($email) > 100) {
            $errors[] = "Email cannot exceed 100 characters.";
        } elseif (!str_ends_with($email, '@kse.in')) {
            $errors[] = "Email must end with @kse.in domain.";
        }

        // Recovery email validation
        if (empty($recovery_email)) {
            $errors[] = "Recovery email is required.";
        } elseif (!filter_var($recovery_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid recovery email format.";
        } elseif (strlen($recovery_email) > 100) {
            $errors[] = "Recovery email cannot exceed 100 characters.";
        } elseif (strtolower($recovery_email) === strtolower($email)) {
            $errors[] = "Recovery email must be different from the primary email address.";
        }

        // Contact number validation
        if (empty($contact_number)) {
            $errors[] = "Contact number is required.";
        } elseif (!preg_match('/^[0-9]{10}$/', $contact_number)) {
            $errors[] = "Contact number must be 10 digits and contain only numbers.";
        } elseif (!preg_match('/^[7-9]/', $contact_number)) {
            $errors[] = "Contact number must start with digits 7-9.";
        }

        /* ------------ USERNAME UNIQUE CHECK ------------ */
        $checkUsername = $this->pdo->prepare("SELECT id FROM users WHERE username=? AND id!=?");
        $checkUsername->execute([$username, $userId]);
        if ($checkUsername->fetch()) {
            $errors[] = "Username already exists.";
        }

        /* ------------ UNIQUE CHECK ------------ */
        $check = $this->pdo->prepare("SELECT id FROM users 
            WHERE (email=? OR recovery_email=? OR contact_number=?) 
            AND id!=?");
        $check->execute([$email, $recovery_email, $contact_number, $userId]);
        if ($check->fetch()) {
            $errors[] = "Email / recovery email / contact already exists.";
        }

        /* ================= PASSWORD LOGIC =================
           Trigger ONLY if user typed new password
        =================================================== */
        $passwordChangeRequested = strlen(trim($new_password)) > 0;

        if ($passwordChangeRequested) {

    // Require all fields
    if (empty($current_password)) {
        $errors[] = "Current password is required when changing password.";
    }

    if (empty($new_password)) {
        $errors[] = "New password is required.";
    }

    if (empty($confirm_password)) {
        $errors[] = "Please confirm the new password.";
    }

    // Password strength validation
    if (!empty($new_password)) {

                    if (
                        strlen($new_password) < 8 ||
                        !preg_match('/[A-Z]/', $new_password) ||
                        !preg_match('/[a-z]/', $new_password) ||
                        !preg_match('/[0-9]/', $new_password)
                    ) {
                        $errors[] = "Password must be at least 8 characters long and include an uppercase letter, lowercase letter, and a number.";
                    }
        }

        // Match check
        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        // Verify current password
        if (empty($errors)) {
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = "Current password is incorrect.";
            }
        }
    }
/* ================= IMAGE UPLOAD ================= */

            $profile_image = $user['profile_image'];
            $profile_public_id = $user['profile_public_id'] ?? null;

            if (!empty($_FILES['profile_image']['name'])) {

                $upload = $this->uploadImage(
                    $_FILES['profile_image'],
                    "users/profile",
                    $profile_public_id
                );

                if ($upload) {
                    $profile_image = $upload['url'];
                    $profile_public_id = $upload['public_id'];
                } else {
                    $errors[] = "Invalid profile image.";
                }
            }

            $sign_image = $user['sign_image'];
            $sign_public_id = $user['sign_public_id'] ?? null;

            if (!empty($_FILES['sign_image']['name'])) {

                $upload = $this->uploadImage(
                    $_FILES['sign_image'],
                    "users/signature",
                    $sign_public_id
                );

                if ($upload) {
                    $sign_image = $upload['url'];
                    $sign_public_id = $upload['public_id'];
                } else {
                    $errors[] = "Invalid signature image.";
                }
            }




        /* ================= UPDATE ================= */
        if (empty($errors)) {
            try {
                if ($passwordChangeRequested) {

                    $hash = password_hash($new_password, PASSWORD_DEFAULT);

                    $sql = "UPDATE users SET 
                        username=?, email=?, recovery_email=?, contact_number=?,
                        profile_image=?, profile_public_id=?,
                        sign_image=?, sign_public_id=?,
                        password=?
                        WHERE id=?";

                    $stmtUp = $this->pdo->prepare($sql);
                    $stmtUp->execute([
                        $username,
                        $email,
                        $recovery_email,
                        $contact_number,
                        $profile_image,
                        $profile_public_id,
                        $sign_image,
                        $sign_public_id,
                        $hash,
                        $userId
                    ]);
                
                } else {
                  $sql = "UPDATE users SET 
                    username=?, email=?, recovery_email=?, contact_number=?,
                    profile_image=?, profile_public_id=?,
                    sign_image=?, sign_public_id=?
                    WHERE id=?";

                    $stmtUp = $this->pdo->prepare($sql);
                    $stmtUp->execute([
                        $username,
                        $email,
                        $recovery_email,
                        $contact_number,
                        $profile_image,
                        $profile_public_id,
                        $sign_image,
                        $sign_public_id,
                        $userId
                    ]);
                }

                $success = "Profile updated successfully.";

                // Refresh user data
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id=?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Update session username if changed
                if (isset($user['username'])) {
                    $_SESSION['username'] = $user['username'];
                }

                $_SESSION['success'] = $success;

            } catch (PDOException $e) {
                error_log('Profile update failed: ' . $e->getMessage());
                $errors[] = "Failed to update profile. Please try again.";
                $_SESSION['errors'] = $errors;
            }
        } else {
            $_SESSION['errors'] = $errors;
        }

        $this->redirect(Url::to('manage/profile'));
    }

    /* =====================================
       HELPER: IMAGE UPLOAD
    ===================================== */
    /* =====================================
   HELPER: IMAGE UPLOAD (Cloudinary)
===================================== */
private function uploadImage($file, $folder = "users", $publicId = null)
{
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];

    if (!in_array($file['type'], $allowed)) {
        return false;
    }

    if ($file['size'] > 2097152) {
        return false;
    }

    try {

        $options = [
            'folder' => $folder,
            'resource_type' => 'image'
        ];

        // If public ID exists → overwrite
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
        return false;
    }
}
}