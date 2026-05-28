<?php
require_once __DIR__ . '/config.php';
unset($_SESSION['portal_customer_id']);
unset($_SESSION['portal_customer_name']);
header("Location: login.php");
exit;
