<?php
/**
 * check_weekly_menu.php — Check if there are any weekly menu images uploaded
 * Called by weekly menu pages to show menu availability
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM photos WHERE page_slug = ?');
    $stmt->execute(['weekly_menu']);
    $result = $stmt->fetch();
    
    $hasMenu = $result['count'] > 0;
    
    echo json_encode(['hasMenu' => $hasMenu]);
} catch (Exception $e) {
    echo json_encode(['hasMenu' => false, 'error' => $e->getMessage()]);
}
