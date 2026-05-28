<?php
/**
 * auth.php — Session & permission helpers
 * Include this at the top of every CMS page.
 */

require_once __DIR__ . '/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------------
// Guard functions
// ---------------------------------------------------------------------------

/** Redirect to login if the visitor is not authenticated. */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . CMS_ROOT . '/login.php');
        exit;
    }
}

/** Redirect to dashboard if the user is not an admin. */
function require_admin(): void {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . CMS_ROOT . '/dashboard.php?error=access_denied');
        exit;
    }
}

// ---------------------------------------------------------------------------
// Current user helpers
// ---------------------------------------------------------------------------

function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_role(): string {
    return $_SESSION['role'] ?? '';
}

function is_admin(): bool {
    return current_role() === 'admin';
}

function current_username(): string {
    return $_SESSION['username'] ?? '';
}

function current_full_name(): string {
    return $_SESSION['full_name'] ?? current_username();
}

// ---------------------------------------------------------------------------
// Permission helpers
// ---------------------------------------------------------------------------

/**
 * Returns true if the current user may manage a given gallery page.
 * Admins always return true. Regular users check the page_permissions table.
 */
function user_can_access_page(string $page_slug): bool {
    if (is_admin()) {
        return true;
    }
    $pdo  = get_db();
    $stmt = $pdo->prepare('SELECT 1 FROM page_permissions WHERE user_id = ? AND page_slug = ? LIMIT 1');
    $stmt->execute([current_user_id(), $page_slug]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Returns array of page_slugs the current user is allowed to manage.
 */
function get_user_pages(): array {
    if (is_admin()) {
        return array_keys(GALLERY_PAGES);
    }
    $pdo  = get_db();
    $stmt = $pdo->prepare('SELECT page_slug FROM page_permissions WHERE user_id = ?');
    $stmt->execute([current_user_id()]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ---------------------------------------------------------------------------
// CSRF helpers
// ---------------------------------------------------------------------------

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
