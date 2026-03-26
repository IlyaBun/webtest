<?php
/**
 * Управление пользователями (Админ-панель)
 * Система оценки успеваемости ПолесГУ
 */

session_start();
require_once __DIR__ . '/../../config/app_config.php';
requireAdmin(); // Только для администраторов

$user = getCurrentUser();

// Получение списка пользователей
$users = dbFetchAll("
    SELECT 
        id,
        username,
        full_name,
        email,
        role,
        created_at,
        updated_at
    FROM users
    ORDER BY 
        CASE WHEN role = 'admin' THEN 0 ELSE 1 END,
        last_name, first_name
", []);

include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="breadcrumb">
            <h1><i class="fas fa-users-cog"></i> Пользователи системы</h1>
        </div>
        <div class="actions">
            <a href="user_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить пользователя
            </a>
        </div>
    </div>

    <!-- Статистика -->
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-details">
                <h3><?php echo count($users); ?></h3>
                <p>Всего пользователей</p>
            </div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-details">
                <h3><?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?></h3>
                <p>Администраторов</p>
            </div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="stat-details">
                <h3><?php echo count(array_filter($users, fn($u) => $u['role'] === 'teacher')); ?></h3>
                <p>Преподавателей</p>
            </div>
        </div>
    </div>

    <!-- Таблица пользователей -->
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Список пользователей</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Дата регистрации</th>
                        <th>Последнее обновление</th>
                        <th style="width: 150px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $usr): ?>
                    <tr>
                        <td><strong>#<?php echo $usr['id']; ?></strong></td>
                        <td>
                            <code><?php echo e($usr['username']); ?></code>
                        </td>
                        <td><strong><?php echo e($usr['full_name']); ?></strong></td>
                        <td><?php echo e($usr['email'] ?? '-'); ?></td>
                        <td>
                            <?php if ($usr['role'] === 'admin'): ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-user-shield"></i> Администратор
                            </span>
                            <?php else: ?>
                            <span class="badge badge-info">
                                <i class="fas fa-chalkboard-teacher"></i> Преподаватель
                            </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($usr['created_at']); ?></td>
                        <td><?php echo formatDate($usr['updated_at']); ?></td>
                        <td>
                            <a href="user_edit.php?id=<?php echo $usr['id']; ?>" 
                               class="btn btn-sm btn-outline" 
                               data-tooltip="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($usr['id'] != $user['id']): ?>
                            <a href="user_delete.php?id=<?php echo $usr['id']; ?>" 
                               class="btn btn-sm btn-outline" 
                               data-confirm="Вы уверены, что хотите удалить пользователя?"
                               data-tooltip="Удалить"
                               style="color: #dc3545; border-color: #dc3545;">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Информация о безопасности -->
    <div class="dashboard-card full-width" style="margin-top: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-shield-alt"></i> Безопасность</h3>
        </div>
        <div class="card-body">
            <div style="background: #fff3cd; padding: 20px; border-radius: 12px; border-left: 4px solid #ffc107;">
                <h4 style="margin-bottom: 10px; color: #856404;">
                    <i class="fas fa-exclamation-triangle"></i> Важно!
                </h4>
                <p style="color: #856404; margin-bottom: 0;">
                    В текущей версии пароли хранятся в открытом виде без хэширования (учебный проект). 
                    В production-среде обязательно используйте <code>password_hash()</code> для безопасного хранения паролей.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
