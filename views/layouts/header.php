<?php
require_once __DIR__ . '/../../init/session.php';
require_once __DIR__ . '/../../init/_dbconnect.php';
require_once __DIR__ . '/../../core/Url.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Event Documentation Portal</title>

  <link rel="icon" type="image/png" sizes="32x32" href="/public/images/keystone_logo.jpeg">
<link rel="icon" type="image/png" sizes="16x16" href="/public/images/keystone_logo.jpeg">
<link rel="apple-touch-icon" href="/public/images/keystone_logo.jpeg">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons ✅ -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- Font Awesome ✅ FIXED VERSION -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="/public/css/navbar.css">
  <link rel="stylesheet" href="/public/css/footer.css">
  <link rel="stylesheet" href="/public/css/managehod.css">
  <link rel="stylesheet" href="/public/css/checklist.css">
  <link rel="stylesheet" href="/public/css/view.css">
  <link rel="stylesheet" href="/public/css/profile.css">
  <link rel="stylesheet" href="/public/css/event_report.css">

  <style>
    body {
      min-height: 100vh;
    }
  </style>
</head>

<body class="d-flex flex-column min-vh-100">

<?php
// Navbar
require_once __DIR__ . '/../includes/navbar.php';

// Sidebar (only if logged in)
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../includes/sidebar.php';
}
?>
