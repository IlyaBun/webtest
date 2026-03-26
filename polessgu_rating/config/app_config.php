<?php
/**
 * Основные настройки приложения
 */

// Название системы
define('APP_NAME', 'Система оценки успеваемости ПолесГУ');
define('APP_SUBTITLE', 'Инженерный факультет');

// Настройки pagination
define('ITEMS_PER_PAGE', 20);

// Роли пользователей
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher');

// Шкала оценок (10-балльная система Беларуси)
$gradeScale = [
    'excellent' => ['min' => 9, 'max' => 10, 'label' => 'Отлично', 'color' => '#28a745'],
    'good' => ['min' => 7, 'max' => 8, 'label' => 'Хорошо', 'color' => '#17a2b8'],
    'satisfactory' => ['min' => 5, 'max' => 6, 'label' => 'Удовлетворительно', 'color' => '#ffc107'],
    'unsatisfactory' => ['min' => 3, 'max' => 4, 'label' => 'Неудовлетворительно', 'color' => '#dc3545']
];

// Статусы студентов
$statuses = [
    'active' => 'Обучается',
    'expelled' => 'Отчислен',
    'graduated' => 'Выпускник',
    'academic_leave' => 'Академический отпуск'
];

// Функция проверки авторизации
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /polessgu_rating/modules/auth/login.php');
        exit;
    }
}

// Функция проверки прав администратора
function checkAdmin() {
    checkAuth();
    if ($_SESSION['user_role'] !== ROLE_ADMIN) {
        $_SESSION['error'] = 'Доступ запрещен. Требуются права администратора.';
        header('Location: /polessgu_rating/index.php');
        exit;
    }
}

// Функция получения названия роли
function getRoleName($role) {
    return $role === ROLE_ADMIN ? 'Администратор' : 'Преподаватель';
}

// Функция форматирования даты
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

// Функция получения цветовой метки для оценки
function getGradeColor($grade) {
    global $gradeScale;
    foreach ($gradeScale as $level) {
        if ($grade >= $level['min'] && $grade <= $level['max']) {
            return $level['color'];
        }
    }
    return '#6c757d';
}

// Функция получения текстовой оценки
function getGradeLabel($grade) {
    global $gradeScale;
    foreach ($gradeScale as $level) {
        if ($grade >= $level['min'] && $grade <= $level['max']) {
            return $level['label'];
        }
    }
    return 'Нет данных';
}

// Функция генерации CSRF токена
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Функция проверки CSRF токена
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
