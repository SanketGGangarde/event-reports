<?php

namespace Validation;

use PDO;

class DepartmentValidation
{
    public static function validateCreate(array $data, array $files, PDO $pdo)
    {
        $errors = [];

        // ---------------------------
        // 1️⃣ REQUEST METHOD CHECK
        // ---------------------------
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = "Invalid request method";
        }

        // ---------------------------
        // 2️⃣ DEPARTMENT NAME VALIDATION
        // ---------------------------
        $name = trim($data['department_name'] ?? '');

        if (empty($name)) {
            $errors[] = "Department name is required";
        }

        if (strlen($name) < 2) {
            $errors[] = "Department name must be at least 2 characters";
        }

        if (!preg_match('/^[a-zA-Z\s&-]+$/', $name)) {
            $errors[] = "Department name contains invalid characters";
        }

        // ---------------------------
        // 3️⃣ DUPLICATE CHECK (DB)
        // ---------------------------
        if (!empty($name)) {

            $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);

            if ($stmt->fetch()) {
                $errors[] = "Department already exists";
            }
        }

        // ---------------------------
        // 4️⃣ HEADER IMAGE VALIDATION (REQUIRED)
        // ---------------------------
        $headerImage = null;

        if (empty($files['header_image']['name'])) {
            $errors[] = "Header image is required";
        } else {
            $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($files['header_image']['type'], $allowed)) {
                $errors[] = "Header image must be JPG, PNG or WEBP";
            }

            if ($files['header_image']['size'] > $maxSize) {
                $errors[] = "Header image must not exceed 2MB";
            }

            if ($files['header_image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Error uploading header image";
            }

            $headerImage = $files['header_image'];
        }

        // ---------------------------
        // 5️⃣ RETURN ERRORS
        // ---------------------------
        if (!empty($errors)) {
            return [
                'status' => false,
                'errors' => $errors
            ];
        }

        return [
            'status' => true,
            'data'   => [
                'department_name' => $name,
                'header_image'    => $headerImage
            ]
        ];
    
    }


    public static function validateIndexAccess(array $session, array $get){
            $errors = [];

            // ---------------------------
            // 1️⃣ LOGIN CHECK
            // ---------------------------
            if (!isset($session['user_id'])) {
                $errors[] = 'Please login to continue';
            }

            // ---------------------------
            // 2️⃣ ROLE CHECK
            // ---------------------------
            if (
                !isset($session['role']) ||
                $session['role'] !== 'principal'
            ) {
                $errors[] = 'Unauthorized access';
            }

            // ---------------------------
            // 3️⃣ EDIT ID VALIDATION
            // ---------------------------
            $editId = null;

            if (isset($get['edit'])) {
                $editId = $get['edit'] ?? null;
                if (
                    empty($editId) ||
                    !preg_match('/^[0-9a-f-]{36}$/i', $editId)
                ) {
                    $errors[] = 'Invalid department ID';
                }

                if (!$editId) {
                    $errors[] = 'Invalid department ID';
                }
            }

            // ---------------------------
            // 4️⃣ FINAL RESULT
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
                    'editId' => $editId
                ]
            ];
    }





    public static function validateUpdate(array $data)
    {
        $errors = [];

        // 1️⃣ Request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = 'Invalid request method';
        }

        // 2️⃣ ID validation
        $id = $data['department_id'] ?? null;
        if (
            empty($id) ||
            !preg_match('/^[0-9a-f-]{36}$/i', $id)
        ) {
            $errors[] = 'Invalid department ID';
        }
        if (!$id) {
            $errors[] = 'Invalid department ID';
        }

        // 3️⃣ Name validation
        $name = trim($data['department_name'] ?? '');

        if (empty($name)) {
            $errors[] = 'Department name is required';
        } elseif (strlen($name) < 3 || strlen($name) > 100) {
            $errors[] = 'Department name must be between 3 and 100 characters';
        } elseif (!preg_match('/^[A-Za-z][A-Za-z0-9\s]{2,99}$/', $name)) {
            $errors[] = 'Department name must start with a letter and contain only letters, numbers and spaces';
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
                'id'   => $id,
                'name' => $name
            ]
        ];
    }
}

