<?php
/**
 * Выход из системы
 * Система оценки успеваемости ПолесГУ
 */

session_start();
session_destroy();

header('Location: login.php');
exit;
?>
