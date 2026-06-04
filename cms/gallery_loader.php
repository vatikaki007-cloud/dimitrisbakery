<?php
/**
 * gallery_loader.php
 *
 * Outputs <a><img> tags for all CMS-managed photos for a given $page_slug.
 * Include this file inside the .gallery-grid div on each gallery page.
 *
 * Special logic for weekly_menu:
 * - Monday-Friday: Show menu only if uploaded after last Saturday
 * - Saturday-Sunday: Show menu only if uploaded in last 2 days
 * - If no valid images, show a random default image
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
    
    $where_clause = 'page_slug = ?';
    $params = [$page_slug];
    
    if ($page_slug === 'weekly_menu') {
        $current_day = date('N'); // 1=Monday, 6=Saturday, 7=Sunday
        
        if ($current_day >= 1 && $current_day <= 5) {
            // Monday-Friday: Show menu if uploaded after last Saturday
            // Last Saturday is 2 days ago on Mon, 3 days ago on Tue, 4 on Wed, 5 on Thurs, 6 on Fri
            $days_since_last_saturday = $current_day + 1; // +1 because we want AFTER Saturday
            $where_clause .= ' AND uploaded_at > DATE_SUB(NOW(), INTERVAL ' . $days_since_last_saturday . ' DAY)';
        } else {
            // Saturday-Sunday: Show menu only if uploaded in last 2 days (today or yesterday)
            $where_clause .= ' AND uploaded_at > DATE_SUB(NOW(), INTERVAL 2 DAY)';
        }
    }
    
    $stmt = $pdo->prepare(
        "SELECT filename, caption FROM photos WHERE $where_clause ORDER BY uploaded_at DESC"
    );
    $stmt->execute($params);
    $cms_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cms_photos = [];
}

// If no valid images, show random default image
if ($page_slug === 'weekly_menu' && empty($cms_photos)) {
    // Get default images from weekley_menu/images/ folder
    $default_images_dir = __DIR__ . '/../weekley_menu/images/';
    
    if (is_dir($default_images_dir)) {
        $files = array_filter(scandir($default_images_dir), function($file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        });
        
        // Remove . and .. from array and exclude hero/logo/menu files
        $files = array_values(array_filter($files, function($file) {
            if ($file === '.' || $file === '..') return false;
            if (strpos($file, 'hero') !== false) return false;
            if (strpos($file, 'logo') !== false) return false;
            if (strpos($file, 'menu.jpg') !== false) return false;
            return true;
        }));
        
        if (!empty($files)) {
            // Pick a random default image
            $random_image = $files[array_rand($files)];
            // Use relative path from site root (/weekley_menu/images/...) for better local/server compatibility
            $default_url = '/weekley_menu/images/' . rawurlencode($random_image);
            $caption = htmlspecialchars(pathinfo($random_image, PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8');
            
            echo '<a href="' . htmlspecialchars($default_url, ENT_QUOTES, 'UTF-8') . '" target="_blank">';
            echo '<img src="' . htmlspecialchars($default_url, ENT_QUOTES, 'UTF-8') . '" alt="' . $caption . '" class="gallery-item" loading="lazy">';
            echo '</a>';
            return;
        }
    }
}

foreach ($cms_photos as $photo):
    $url     = UPLOADS_URL . '/' . rawurlencode($page_slug) . '/' . rawurlencode($photo['filename']);
    $caption = htmlspecialchars($photo['caption'] ?? $photo['filename'], ENT_QUOTES, 'UTF-8');
?>
<a href="<?= $url ?>" target="_blank">
    <img src="<?= $url ?>" alt="<?= $caption ?>" class="gallery-item" loading="lazy">
</a>
<?php endforeach; ?>
