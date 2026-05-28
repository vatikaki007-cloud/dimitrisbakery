<?php
/**
 * upload_asset.php — Handles uploading and overwriting static site images.
 */
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

// Only admins or users with site_assets permission can upload site assets
if (empty($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !user_can_access_page('site_assets'))) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Basic validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['photo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

$asset_key = $_POST['asset_key'] ?? '';
if (!array_key_exists($asset_key, SITE_ASSETS)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid asset key']);
    exit;
}

$file = $_FILES['photo'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload failed with error code ' . $file['error']]);
    exit;
}

if ($file['size'] > MAX_UPLOAD_BYTES) {
    http_response_code(400);
    echo json_encode(['error' => 'File is too large (max 10MB)']);
    exit;
}

// Validate MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($file['tmp_name']);

if (!in_array($mime_type, ALLOWED_MIME_TYPES, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file format. Only JPG, PNG, WEBP, and GIF are allowed.']);
    exit;
}

// Validate file extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file extension.']);
    exit;
}

// Determine destination
// The path in SITE_ASSETS is relative to SITE_ROOT (which maps to the web root directory).
// Since this script is in /cms/, the web root is one level up: __DIR__ . '/../'
$asset = SITE_ASSETS[$asset_key];
$target_path = realpath(__DIR__ . '/..') . $asset['path'];

// Note: If the directory exists but the file doesn't, we still want to save it.
// Ensure the directory exists first.
$target_dir = dirname($target_path);
if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create target directory.']);
        exit;
    }
}

// Move the file (overwriting the existing one)
if (move_uploaded_file($file['tmp_name'], $target_path)) {
    echo json_encode([
        'success' => true,
        'path' => $asset['path']
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file. Check server permissions.']);
}
