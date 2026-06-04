<?php
/**
 * check_specials.php — Check if there are any specials uploaded
 * Called by global_nav.html to determine if "Specials" link should be visible
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM photos WHERE page_slug = ?');
    $stmt->execute(['specials']);
    $result = $stmt->fetch();
    
    $hasSpecials = $result['count'] > 0;
    
    echo json_encode(['hasSpecials' => $hasSpecials]);
} catch (Exception $e) {
    echo json_encode(['hasSpecials' => false, 'error' => $e->getMessage()]);
}
