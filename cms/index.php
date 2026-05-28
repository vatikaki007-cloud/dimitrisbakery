<?php
/**
 * cms/index.php — Entry point redirect
 */
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . CMS_ROOT . '/dashboard.php');
} else {
    header('Location: ' . CMS_ROOT . '/login.php');
}
exit;
