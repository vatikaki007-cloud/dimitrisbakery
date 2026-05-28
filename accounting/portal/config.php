<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

function require_portal_login() {
    if (!isset($_SESSION['portal_customer_id'])) {
        header("Location: login.php");
        exit;
    }
}
