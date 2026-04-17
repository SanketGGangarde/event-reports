<?php

namespace Validation;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class UserValidation
{
    /* =====================================================
       CREATE USER (Principal / HOD / Coordinator)
    ===================================================== */
    public static function validateCreateUser(array $data, array $files = [])
{
    $errors = [];

    // CSRF
    if (
        empty($data['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $data['csrf_token'])
    ) {
        $errors[] = "Invalid CSRF token";
    }

    // Trim
    $username   = trim($data['username'] ?? '');
    $emailUser  = trim($data['email'] ?? '');
    $recovery   = trim($data['recovery_email'] ?? '');
    $contact    = trim($data['contact_number'] ?? '');
    $password   = $data['password'] ?? '';
    $confirm    = $data['confirm_password'] ?? '';
    $department = $data['department_id'] ?? null;
   
    


    // Required
    if (!$username) $errors[] = "Username required";
    if (!$emailUser) $errors[] = "Email required";
    if (!$recovery) $errors[] = "Recovery email required";
    if (!$contact) $errors[] = "Contact required";
    if (!$password) $errors[] = "Password required";
    if (!$confirm) $errors[] = "Confirm password required";
    
    // Department validation based on role
    // Note: This method is primarily used for principal signup, but we can make it role-aware
    // For now, we'll keep department optional since this is the principal signup method
    // If this method is used for other roles, department should be required


    if (!empty($errors)) {
        return ['status' => false, 'errors' => $errors];
    }

    // Username rule - allow spaces
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_ ]*$/', trim($username))) {
        $errors[] = "Invalid username format";
    }

    // Email (only before @kse.in)
    if (strpos($emailUser, '@') !== false) {
        $errors[] = "Enter only part before @kse.in";
    }

    if (!preg_match('/^[A-Za-z][A-Za-z0-9_ ]*$/', trim($username))) {
        $errors[] = "Invalid username format";
    }

    $email = $emailUser . "@kse.in";

    if (!filter_var($recovery, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid recovery email";
    }

    if (!preg_match('/^[0-9]{10}$/', $contact)) {
        $errors[] = "Contact must be 10 digits";
    }


      if (
                        strlen($password) < 8 ||
                        !preg_match('/[A-Z]/', $password) ||
                        !preg_match('/[a-z]/', $password) ||
                        !preg_match('/[0-9]/', $password)
                    ) {
                        $errors[] = "Password must be at least 8 characters long and include an uppercase letter, lowercase letter, and a number.";
                    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match";
    }

    if (!empty($errors)) {
        return ['status' => false, 'errors' => $errors];
    }

    return [
    'status' => true,
    'data' => [
        'username' => $username,
        'email' => $email,
        'recovery_email' => $recovery,
        'contact_number' => $contact,
        'password' => $password
    ]
];
}




/* =====================================================
       Update User Validation
    ===================================================== */

public static function validateUpdateUser(array $data)
{
    $errors = [];

    $id = $data['id'] ?? $data['hod_id'] ?? $data['coordinator_id'] ?? null;
    if (
        empty($id) ||
        !preg_match('/^[0-9a-f-]{36}$/i', $id)
    ) {
        $errors[] = 'Invalid ID';
    }
    $username   = trim($data['username'] ?? '');
    $emailUser  = trim($data['email'] ?? '');
    $recovery   = trim($data['recovery_email'] ?? '');
    $contact    = trim($data['contact_number'] ?? '');
    $role       = $data['role'] ?? '';
    $department = $data['department_id'] ?? null;
    

    if (!$id) $errors[] = "Invalid ID";
    if (!$username) $errors[] = "Username required";
    if (!$emailUser) $errors[] = "Email required";
    if (!$recovery) $errors[] = "Recovery email required";
    if (!$contact) $errors[] = "Contact required";

    if (in_array($role, ['hod','coordinator']) && !$department) {
        $errors[] = "Department required";
    }

    if (!empty($errors)) {
        return ['status' => false, 'errors' => $errors];
    }

    // Username rule - allow spaces
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_ ]*$/', trim($username))) {
        $errors[] = "Invalid username format";
    }

    // Email (only before @kse.in)
    if (strpos($emailUser, '@') !== false) {
        $errors[] = "Enter only part before @kse.in";
    }

    if (!preg_match('/^[A-Za-z][A-Za-z0-9._]*$/', $emailUser)) {
        $errors[] = "Invalid email username";
    }

    $email = $emailUser . "@kse.in";

    if (!filter_var($recovery, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid recovery email";
    }

    if (!preg_match('/^[0-9]{10}$/', $contact)) {
        $errors[] = "Contact must be 10 digits";
    }

    

    if (!empty($errors)) {
        return ['status' => false, 'errors' => $errors];
    }


    $email = $emailUser . "@kse.in";

    return [
        'status' => true,
        'data' => [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'recovery_email' => $recovery,
            'contact_number' => $contact,
            'role' => $role,
            'department_id' => $department
        ]
    ];
}

 





    /* =====================================================
       LOGIN VALIDATION
    ===================================================== */
 
    public static function validateLogin(array $data)
    {
        $errors = [];

        // ---------------------------
        // 1️⃣ REQUEST METHOD CHECK
        // ---------------------------
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = 'Invalid request method';
        }

        // ---------------------------
        // 2️⃣ SANITIZE INPUT
        // ---------------------------
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // ---------------------------
        // 3️⃣ VALIDATION RULES
        // ---------------------------
        if (empty($email)) {
            $errors[] = 'Email is required';
         }// elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        //     $errors[] = 'Invalid email format';
        // }

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        // ---------------------------
        // 4️⃣ RETURN RESULT
        // ---------------------------
        if (!empty($errors)) {
            return [
                'status' => false,
                'errors' => $errors
            ];
        }

        return [
            'status' => true,
            'data' => [
                'email'    => $email,
                'password' => $password
            ]
        ];
    }



    /* =====================================================
       FORGOT PASSWORD VALIDATION
    ===================================================== */
    public static function validateForgotPassword(array $data)
    {
        try {

            $schema = v::keySet(
                v::key('email',
                    v::stringType()->notEmpty()
                ),
                v::key('recovery_email',
                    v::email()->notEmpty()
                )
            );

            $schema->assert($data);

            return ['status' => true, 'data' => $data];

        } catch (ValidationException $e) {
            return [
                'status' => false,
                'errors' => $e->getMessages()
            ];
        }
    }


    /* =====================================================
       VERIFY OTP
    ===================================================== */
    public static function validateSendResetOtp(array $data)
{
    $errors = [];

    // 1️⃣ Request method check
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $errors[] = 'Invalid request method';
    }

    // 2️⃣ Get and sanitize input
    $email       = trim($data['email'] ?? '');
    $recovery_email = trim($data['recovery_email'] ?? '');

    // 3️⃣ Email validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // 4️⃣ Recovery email validation
    if (empty($recovery_email)) {
        $errors[] = 'Recovery email is required';
    } elseif (!filter_var($recovery_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid recovery email format';
    }

    // 5️⃣ Return result
    if (!empty($errors)) {
        return [
            'status' => false,
            'errors' => $errors
        ];
    }

    return [
        'status' => true,
        'data' => [
            'email'       => $email,
            'recovery_email' => $recovery_email
        ]
    ];
}

/* =====================================================
       This is AFTER user receives OTP and submits it.
    ===================================================== */


public static function validateVerifyOtp(array $data)
{
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $errors[] = 'Invalid request method';
    }

    $otp = trim($data['otp'] ?? '');

    if (empty($otp)) {
        $errors[] = 'OTP is required';
    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {
        $errors[] = 'OTP must be 6 digits';
    }

    if (!empty($errors)) {
        return [
            'status' => false,
            'errors' => $errors
        ];
    }

    return [
        'status' => true,
        'data' => [
            'otp' => $otp
        ]
    ];
}


    /* =====================================================
       RESET PASSWORD
    ===================================================== */
    public static function validateResetPassword(array $data)
{
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $errors[] = 'Invalid request method';
    }

    $password         = $data['password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';

    if (empty($password)) {
        $errors[] = 'Password is required';
    } 

     if (
                        strlen($password) < 8 ||
                        !preg_match('/[A-Z]/', $password) ||
                        !preg_match('/[a-z]/', $password) ||
                        !preg_match('/[0-9]/', $password)
                    ) {
                        $errors[] = "Password must be at least 8 characters long and include an uppercase letter, lowercase letter, and a number.";
                    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    if (!empty($errors)) {
        return [
            'status' => false,
            'errors' => $errors
        ];
    }

    return [
        'status' => true,
        'data' => [
            'password' => $password
        ]
    ];
}
}