<?php
/**
 * gallery_loader.php
 *
 * Outputs <a><img> tags for all CMS-managed photos for a given $page_slug.
 * Include this file inside the .gallery-grid div on each gallery page.
 *
 * Special logic for weekly_menu:
 * - On Monday, only show images uploaded in the last 3 days
 * - Other days show all images
 *
 * Usage:
 *   $page_slug = 'birthday_cakes';  // Set before including
 *   include '/path/to/cms/gallery_loader.php';
 *
 * The $db_config_path variable must point to the cms/config.php file.
 * Each gallery page sets this before including this file.
 */

if (empty($db_config_path) || !file_exists($db_config_path)) {
    // Silently skip if config is not reachable (static fallback still renders)
    return;
}

require_once $db_config_path;

try {
    $pdo  = get_db();
    
    // For weekly_menu on Monday, add date filter (images from last 3 days only)
    $where_clause = 'page_slug = ?';
    $params = [$page_slug];
    
    if ($page_slug === 'weekly_menu' && date('N') == 1) { // 1 = Monday
        // Only show images from last 3 days on Mondays
        $where_clause .= ' AND uploaded_at > DATE_SUB(NOW(), INTERVAL 3 DAY)';
    }
    
    $stmt = $pdo->prepare(
        "SELECT filename, caption FROM photos WHERE $where_clause ORDER BY uploaded_at DESC"
    );
    $stmt->execute($params);
    $cms_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cms_photos = [];
}

// For weekly_menu on Monday with no recent images, show message
if ($page_slug === 'weekly_menu' && date('N') == 1 && empty($cms_photos)) {
    echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px 20px; color: #666;">';
    echo '<p style="font-size: 18px; font-weight: bold;">Menu Coming Soon</p>';
    echo '<p>We\'re preparing this week\'s menu. Please check back later!</p>';
    echo '</div>';
    return;
}

foreach ($cms_photos as $photo):
    $url     = UPLOADS_URL . '/' . rawurlencode($page_slug) . '/' . rawurlencode($photo['filename']);
    $caption = htmlspecialchars($photo['caption'] ?? $photo['filename'], ENT_QUOTES, 'UTF-8');
?>
<a href="<?= $url ?>" target="_blank">
    <img src="<?= $url ?>" alt="<?= $caption ?>" class="gallery-item" loading="lazy">
</a>
<?php endforeach; ?>
