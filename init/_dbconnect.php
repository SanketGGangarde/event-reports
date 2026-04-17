<?php

require_once __DIR__ . '/../vendor/autoload.php';

/* Fetch environment variables */
$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT') ?: 3306;
$dbname   = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');

/* Validate environment variables */
if (empty($host) || empty($username) || empty($dbname) || empty($password)) {
    error_log("❌ Database environment variables are missing.");
    throw new Exception("Database configuration error.");
}

/* Try DB Connection */
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Store globally (your existing approach)
    $GLOBALS['pdo'] = $pdo;

} catch (PDOException $e) {
    // Log actual error (safe)
    error_log("Database Connection Failed: " . $e->getMessage());

    // Throw generic exception (handled in index.php)
    throw new Exception("Database connection failed.");
}

/* CSRF token generation */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
