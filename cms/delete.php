<?php
/**
 * delete.php — Deletes a photo (POST, returns JSON)
 */
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

// --- CSRF ---
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token.']);
    exit;
}

$photo_id = (int)($_POST['photo_id'] ?? 0);
if ($photo_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid photo ID.']);
    exit;
}

$pdo  = get_db();
$stmt = $pdo->prepare('SELECT * FROM photos WHERE id = ? LIMIT 1');
$stmt->execute([$photo_id]);
$photo = $stmt->fetch();

if (!$photo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Photo not found.']);
    exit;
}

// --- Permission: admin can delete any, user can only delete their own ---
if (!is_admin() && (int)$photo['uploaded_by'] !== current_user_id()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You can only delete your own photos.']);
    exit;
}

// --- Also check page permission for users ---
if (!user_can_access_page($photo['page_slug'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You do not have permission for this page.']);
    exit;
}

// --- Delete file from disk ---
$file_path = UPLOADS_DIR . '/' . $photo['page_slug'] . '/' . $photo['filename'];
if (file_exists($file_path)) {
    @unlink($file_path);
}

// --- Delete from database ---
$del = $pdo->prepare('DELETE FROM photos WHERE id = ?');
$del->execute([$photo_id]);

echo json_encode(['success' => true, 'message' => 'Photo deleted.']);
