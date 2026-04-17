<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;

// ------------------------------
// Load Cloudinary environment variables safely
// ------------------------------
// Railway injects env variables directly into the container
$cloudName = getenv('CLOUD_NAME') ?: '';
$cloudApiKey = getenv('CLOUD_API_KEY') ?: '';
$cloudApiSecret = getenv('CLOUD_API_SECRET') ?: '';

// Validate required environment variables
if (empty($cloudName) || empty($cloudApiKey) || empty($cloudApiSecret)) {
    die('Cloudinary environment variables are missing. Please add CLOUD_NAME, CLOUD_API_KEY, and CLOUD_API_SECRET in Railway Variables.');
}

// Configure Cloudinary
Configuration::instance([
    'cloud' => [
        'cloud_name' => $cloudName,
        'api_key'    => $cloudApiKey,
        'api_secret' => $cloudApiSecret,
    ],
    'url' => [
        'secure' => true
    ]
]);

// Optional: debug output in development
$debug = getenv('APP_DEBUG') === 'true';
if ($debug) {
    error_log("Cloudinary configured with cloud: $cloudName");
}