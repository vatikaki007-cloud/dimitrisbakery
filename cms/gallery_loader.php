<?php
/**
 * gallery_loader.php
 *
 * Outputs <a><img> tags for all CMS-managed photos for a given $page_slug.
 * Include this file inside the .gallery-grid div on each gallery page.
 *
 * Special logic for weekly_menu:
 * - On Monday, only show images uploaded in the last 3 days
 * - If no valid images on Monday, show a random default image from weekley_menu/images/
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

// For weekly_menu, if no images, show random default image
if ($page_slug === 'weekly_menu' && empty($cms_photos)) {
    // Get default images from weekley_menu/images/ folder
    $default_images_dir = __DIR__ . '/../weekley_menu/images/';
    
    if (is_dir($default_images_dir)) {
        $files = array_filter(scandir($default_images_dir), function($file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        });
        
        // Remove . and .. from array
        $files = array_values(array_filter($files, function($file) {
            return $file !== '.' && $file !== '..';
        }));
        
        if (!empty($files)) {
            // Pick a random default image
            $random_image = $files[array_rand($files)];
            $default_url = SITE_ROOT . '/weekley_menu/images/' . rawurlencode($random_image);
            $caption = htmlspecialchars(pathinfo($random_image, PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8');
            
            echo '<a href="' . $default_url . '" target="_blank">';
            echo '<img src="' . $default_url . '" alt="' . $caption . '" class="gallery-item" loading="lazy">';
            echo '</a>';
            return;
        }
    }
    
    // No default images found, show message
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
