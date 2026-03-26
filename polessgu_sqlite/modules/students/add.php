<?php
require_once __DIR__ . '/../config/app_config.php';
requireLogin();

if (!isAdmin()) {
    header('Location: /index.php');
    exit;
}

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $group_id = (int)$_POST['group_id'];
    $enrollment_year = (int)$_POST['enrollment_year'];
    
    if ($last_name && $first_name && $group_id) {
        $stmt = $db->prepare("INSERT INTO students (last_name, first_name, middle_name, group_id, enrollment_year) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$last_name, $first_name, $middle_name, $group_id, $enrollment_year]);
    }
    
    header('Location: /modules/students/index.php');
    exit;
}
?>
