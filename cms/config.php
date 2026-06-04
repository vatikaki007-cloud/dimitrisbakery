<?php
/**
 * config.php — Database connection (PDO)
 *
 * IMPORTANT: Fill in your actual cPanel database credentials below.
 * Never commit this file to a public repository.
 */

define('DB_HOST', 'localhost');          // Almost always 'localhost' on cPanel
define('DB_NAME', 'dimitdkc_bakery');       // e.g. dimitris_cms  — from cPanel > MySQL Databases
define('DB_USER', 'dimitdkc_vatikaki');       // e.g. dimitris_admin
define('DB_PASS', '3rLeA*n,H,tW^,&g');   // The password you set in cPanel

define('DB_CHARSET', 'utf8mb4');

// --- Base URL paths ---
define('SITE_ROOT', 'https://dimitrisbakery.co.za');          // No trailing slash
define('CMS_ROOT', SITE_ROOT . '/cms');                       // CMS base URL
define('UPLOADS_DIR', __DIR__ . '/uploads');                   // Absolute server path
define('UPLOADS_URL', SITE_ROOT . '/cms/uploads');            // Public URL to uploads

// --- Upload limits ---
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024);  // 10 MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// --- Page slugs (must match folder names inside uploads/) ---
define('GALLERY_PAGES', [
    'birthday_cakes' => 'Birthday Cakes',
    'wedding_cakes' => 'Wedding Cakes',
    'sweet_treats' => 'Sweet Treats',
    'any_occasion' => 'Any Occasion Cakes',
    'catering' => 'Catering',
    'specials' => 'Specials',
]);

// --- Site Assets (Static Images Manager) ---
// These define the exact files that can be overwritten via the CMS Site Assets manager
define('SITE_ASSETS', [
    'logo' => [
        'name' => 'Main Logo & Backgrounds',
        'desc' => 'The main logo used in the navbar, footer, and page backgrounds (like the bread man).',
        'path' => '/index_images/logo.png',
    ],
    'index1' => [
        'name' => 'Home - Top Hero Image',
        'desc' => 'The large background image at the very top of the home page.',
        'path' => '/index_images/index1.png',
    ],
    'index2' => [
        'name' => 'Home - Quote Banner',
        'desc' => 'The background image for the happy clients quote section.',
        'path' => '/index_images/index2.png',
    ],
    'index3' => [
        'name' => 'Home - Home Cooked Meals',
        'desc' => 'The background image for the weekly meals section.',
        'path' => '/index_images/index3.png',
    ],
    'weekly_menu' => [
        'name' => 'Weekly Menu Image',
        'desc' => 'The actual menu image displayed on the Weekly Menus page.',
        'path' => '/weekley_menu/images/menu.jpg',
    ],
    'hero_weekly' => [
        'name' => 'Hero Banner - Weekly Menu',
        'desc' => 'The large background image at the top of the Weekly Menus page.',
        'path' => '/weekley_menu/images/weekly_menu_hero.jpg',
    ],
    'hero_confectioner' => [
        'name' => 'Hero Banner - Confectioner Main',
        'desc' => 'The large background image at the top of the main Confectioner page.',
        'path' => '/confectioner_images/conf1.jpg',
    ],
    'hero_birthday' => [
        'name' => 'Hero Banner - Birthday Cakes',
        'desc' => 'The large background image at the top of the Birthday Cakes page.',
        'path' => '/confectioner/birthday_cakes/images/bc_hero.jpg',
    ],
    'hero_wedding' => [
        'name' => 'Hero Banner - Wedding Cakes',
        'desc' => 'The large background image at the top of the Wedding Cakes page.',
        'path' => '/confectioner/wedding_cakes/images/wc_hero.jpg',
    ],
    'hero_sweets' => [
        'name' => 'Hero Banner - Sweet Treats',
        'desc' => 'The large background image at the top of the Sweet Treats page.',
        'path' => '/confectioner/sweet_treats/images/st_hero.jpg',
    ],
    'hero_occasion' => [
        'name' => 'Hero Banner - Any Occasion',
        'desc' => 'The large background image at the top of the Any Occasion Cakes page.',
        'path' => '/confectioner/any_occasion/images/ao_hero.jpg',
    ],
    'hero_catering' => [
        'name' => 'Hero Banner - Catering',
        'desc' => 'The large background image at the top of the Catering page.',
        'path' => '/catering/images/catering_hero.jpg',
    ],
    'cat_wedding' => [
        'name' => 'Category Image - Wedding',
        'desc' => 'Thumbnail for wedding cakes category.',
        'path' => '/confectioner_images/weddingcakes.jpg',
    ],
    'cat_birthday' => [
        'name' => 'Category Image - Birthday',
        'desc' => 'Thumbnail for birthday cakes category.',
        'path' => '/confectioner_images/birthdaycakes.jpg',
    ],
    'cat_occasion' => [
        'name' => 'Category Image - Occasion',
        'desc' => 'Thumbnail for any occasion cakes category.',
        'path' => '/confectioner_images/anyoccasioncakes.jpg',
    ],
    'cat_sweets' => [
        'name' => 'Category Image - Sweets',
        'desc' => 'Thumbnail for sweet treats category.',
        'path' => '/confectioner_images/sweettreats.jpg',
    ]
]);

// ---------------------------------------------------------------------------
// PDO connection — called as needed
// ---------------------------------------------------------------------------
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Don't expose credentials in error messages on live server
            die('<p style="color:red;font-family:sans-serif;">Database connection failed. Please contact the administrator.</p>');
        }
    }
    return $pdo;
}
