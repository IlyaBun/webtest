<?php
require_once __DIR__ . '/../config/app_config.php';

session_destroy();
header('Location: /modules/auth/login.php');
exit;
?>
