<?php
// Include the main CMS config for database credentials
require_once __DIR__ . '/../cms/config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Add accounting specific constants here
define('ACCOUNTING_ROOT', SITE_ROOT . '/accounting');

// Helper function for DB connection (reuses the one from cms if possible, or creates a new one)
// We already have get_db() from cms/config.php
