<?php
/**
 * upload.php — Handles photo uploads via POST (AJAX or form)
 * Returns JSON response.
 */
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

// --- Validate CSRF ---
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh the page.']);
    exit;
}

// --- Validate page slug ---
$page_slug = $_POST['page_slug'] ?? '';
if (!array_key_exists($page_slug, GALLERY_PAGES)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid gallery page.']);
    exit;
}

// --- Permission check ---
if (!user_can_access_page($page_slug)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You do not have permission to upload to this page.']);
    exit;
}

// --- Check file was uploaded ---
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'File is too large (server limit).',
        UPLOAD_ERR_FORM_SIZE  => 'File is too large (form limit).',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension.',
    ];
    $err_code = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(['success' => false, 'error' => $upload_errors[$err_code] ?? 'Unknown upload error.']);
    exit;
}

$tmp_path  = $_FILES['photo']['tmp_name'];
$orig_name = $_FILES['photo']['name'];
$file_size = $_FILES['photo']['size'];

// --- Size check ---
if ($file_size > MAX_UPLOAD_BYTES) {
    echo json_encode(['success' => false, 'error' => 'File exceeds the 10 MB limit.']);
    exit;
}

// --- MIME type check (use finfo for real MIME, not client-provided) ---
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mime     = $finfo->file($tmp_path);
if (!in_array($mime, ALLOWED_MIME_TYPES, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WebP, and GIF are allowed.']);
    exit;
}

// --- Extension check ---
$ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file extension.']);
    exit;
}

// --- Build unique filename ---
$unique_name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

// --- Ensure upload directory exists ---
$target_dir = UPLOADS_DIR . '/' . $page_slug;
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$target_path = $target_dir . '/' . $unique_name;

if (!move_uploaded_file($tmp_path, $target_path)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file. Check server folder permissions.']);
    exit;
}

// --- Caption (optional) ---
$caption = trim($_POST['caption'] ?? '');
$caption = $caption !== '' ? $caption : null;

// --- Insert into database ---
try {
    $pdo  = get_db();
    $stmt = $pdo->prepare(
        'INSERT INTO photos (page_slug, filename, caption, uploaded_by) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$page_slug, $unique_name, $caption, current_user_id()]);
    $photo_id = (int)$pdo->lastInsertId();

    echo json_encode([
        'success'  => true,
        'photo_id' => $photo_id,
        'filename' => $unique_name,
        'url'      => UPLOADS_URL . '/' . $page_slug . '/' . $unique_name,
        'message'  => 'Photo uploaded successfully.',
    ]);
} catch (PDOException $e) {
    // Clean up the file if DB insert fails
    @unlink($target_path);
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
}
