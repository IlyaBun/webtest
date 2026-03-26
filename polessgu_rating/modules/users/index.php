<?php
/**
 * Страница управления пользователями (только для администраторов)
 */
require_once '../../config/db_config.php';
require_once '../../config/app_config.php';

checkAdmin();

$pageTitle = 'Пользователи системы';
$success = '';
$error = '';

// Обработка добавления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, role, email)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['username'],
            $_POST['password'],
            $_POST['full_name'],
            $_POST['role'],
            $_POST['email'] ?? null
        ]);
        $success = 'Пользователь успешно добавлен';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = 'Пользователь с таким логином уже существует';
        } else {
            $error = 'Ошибка при добавлении: ' . $e->getMessage();
        }
    }
}

// Обработка удаления
if (isset($_GET['delete']) && $_SESSION['user_role'] === ROLE_ADMIN) {
    try {
        $pdo = getDBConnection();
        // Нельзя удалить самого себя
        if ($_GET['delete'] != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            $success = 'Пользователь успешно удален';
        } else {
            $error = 'Нельзя удалить свою собственную учетную запись';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении: ' . $e->getMessage();
    }
}

// Получение данных
try {
    $pdo = getDBConnection();
    $usersStmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $usersStmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Ошибка получения данных: ' . $e->getMessage();
}

include '../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Фильтры -->
<div class="filters">
    <div class="filter-row">
        <div class="form-group" style="margin-bottom: 0; flex: 1;">
            <label for="searchInput">🔍 Поиск</label>
            <input type="text" id="searchInput" class="form-control" placeholder="Поиск по имени или логину..." onkeyup="searchTable('searchInput', 'usersTable')">
        </div>
        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: end;">
            <a href="#" class="btn btn-primary" onclick="openModal('addUserModal')">➕ Добавить пользователя</a>
        </div>
    </div>
</div>

<!-- Таблица пользователей -->
<div class="table-container">
    <table class="data-table" id="usersTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Логин</th>
                <th>ФИО</th>
                <th>Роль</th>
                <th>Email</th>
                <th>Статус</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                <td><?= htmlspecialchars($user['full_name']) ?></td>
                <td>
                    <?php if ($user['role'] === ROLE_ADMIN): ?>
                        <span class="badge badge-danger">Администратор</span>
                    <?php else: ?>
                        <span class="badge badge-info">Преподаватель</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                <td>
                    <?php if ($user['is_active']): ?>
                        <span class="badge badge-success">Активен</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Заблокирован</span>
                    <?php endif; ?>
                </td>
                <td><?= formatDate($user['created_at']) ?></td>
                <td>
                    <div class="btn-group">
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirm('Вы уверены, что хотите удалить пользователя?')" title="Удалить">🗑️</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Модальное окно добавления пользователя -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ Добавить пользователя</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST" action="" id="addUserForm">
            <div class="modal-body">
                <input type="hidden" name="add_user" value="1">
                
                <div class="form-group">
                    <label>Логин *</label>
                    <input type="text" name="username" class="form-control" required placeholder="Уникальный логин">
                </div>
                
                <div class="form-group">
                    <label>Пароль *</label>
                    <input type="password" name="password" class="form-control" required minlength="6" placeholder="Минимум 6 символов">
                </div>
                
                <div class="form-group">
                    <label>ФИО *</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="Фамилия Имя Отчество">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com">
                </div>
                
                <div class="form-group">
                    <label>Роль *</label>
                    <select name="role" class="form-control" required>
                        <option value="teacher">Преподаватель</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Отмена</button>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('addUserModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('addUserModal');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
