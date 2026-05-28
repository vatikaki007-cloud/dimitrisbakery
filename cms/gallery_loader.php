<?php
/**
 * gallery_loader.php
 *
 * Outputs <a><img> tags for all CMS-managed photos for a given $page_slug.
 * Include this file inside the .gallery-grid div on each gallery page.
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
    $stmt = $pdo->prepare(
        'SELECT filename, caption FROM photos WHERE page_slug = ? ORDER BY uploaded_at DESC'
    );
    $stmt->execute([$page_slug]);
    $cms_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cms_photos = [];
}

foreach ($cms_photos as $photo):
    $url     = UPLOADS_URL . '/' . rawurlencode($page_slug) . '/' . rawurlencode($photo['filename']);
    $caption = htmlspecialchars($photo['caption'] ?? $photo['filename'], ENT_QUOTES, 'UTF-8');
?>
<a href="<?= $url ?>" target="_blank">
    <img src="<?= $url ?>" alt="<?= $caption ?>" class="gallery-item" loading="lazy">
</a>
<?php endforeach; ?>
