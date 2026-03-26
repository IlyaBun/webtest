<?php
/**
 * Конфигурация приложения
 * Система оценки успеваемости студентов инженерного факультета ПолесГУ
 */

// Настройки приложения
define('APP_NAME', 'Система оценки успеваемости ПолесГУ');
define('APP_VERSION', '1.0.0');
define('APP_FACULTY', 'Инженерный факультет');

// Пути
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/polessgu_rating');

// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Установить 1 при использовании HTTPS

// Таймаут сессии (30 минут)
define('SESSION_TIMEOUT', 1800);

// Настройки отображения
define('DEFAULT_PER_PAGE', 20);
define('MIN_PASSING_GRADE', 4); // Минимальный проходной балл
define('EXCELLENT_GRADE', 9);   // Балл для отличника

// Включение отображения ошибок (отключить в production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Часовой пояс
date_default_timezone_set('Europe/Minsk');

// Подключение конфигурации БД
require_once BASE_PATH . '/config/db_config.php';

/**
 * Проверка авторизации пользователя
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Проверка таймаута сессии
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Получение текущего пользователя
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Проверка роли пользователя
 */
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Проверка прав администратора
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Перенаправление на страницу входа если не авторизован
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
}

/**
 * Перенаправление если нет прав администратора
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/index.php?error=access_denied');
        exit;
    }
}

/**
 * Безопасный вывод данных
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Форматирование даты
 */
function formatDate($date, $format = 'd.m.Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Получение текстового значения оценки
 */
function getGradeText($grade) {
    if ($grade >= 9) return 'Отлично';
    if ($grade >= 7) return 'Хорошо';
    if ($grade >= 4) return 'Удовлетворительно';
    return 'Неудовлетворительно';
}

/**
 * Получение цвета для оценки
 */
function getGradeColor($grade) {
    if ($grade >= 9) return '#28a745';
    if ($grade >= 7) return '#17a2b8';
    if ($grade >= 4) return '#ffc107';
    return '#dc3545';
}

/**
 * Расчет уровня знаний
 */
function getKnowledgeLevel($average) {
    if ($average >= 9) return ['level' => 'Высокий', 'class' => 'excellent'];
    if ($average >= 7) return ['level' => 'Средний', 'class' => 'good'];
    if ($average >= 4) return ['level' => 'Базовый', 'class' => 'satisfactory'];
    return ['level' => 'Низкий', 'class' => 'poor'];
}

/**
 * Генерация CSRF токена
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Проверка CSRF токена
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Установка flash сообщения
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Получение и очистка flash сообщения
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Пагинация
 */
function paginate($totalCount, $perPage = DEFAULT_PER_PAGE, $currentPage = 1) {
    $totalPages = ceil($totalCount / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'total_count' => $totalCount,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

?>
