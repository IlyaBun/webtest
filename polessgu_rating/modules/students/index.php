<?php
/**
 * Страница студентов
 */
require_once '../../config/db_config.php';
require_once '../../config/app_config.php';

checkAuth();

$pageTitle = 'Студенты';
$success = '';
$error = '';

// Обработка удаления
if (isset($_GET['delete']) && $_SESSION['user_role'] === ROLE_ADMIN) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = 'Студент успешно удален';
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении: ' . $e->getMessage();
    }
}

// Получение данных
try {
    $pdo = getDBConnection();
    
    // Все группы для фильтра
    $groupsStmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
    $groups = $groupsStmt->fetchAll();
    
    // Список студентов
    $studentsStmt = $pdo->query("
        SELECT s.*, g.name as group_name, sp.name as specialty_name
        FROM students s
        JOIN groups g ON s.group_id = g.id
        JOIN specialties sp ON g.specialty_id = sp.id
        ORDER BY s.last_name, s.first_name
    ");
    $students = $studentsStmt->fetchAll();
    
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
            <input type="text" id="searchInput" class="form-control" placeholder="ФИО студента..." onkeyup="searchTable('searchInput', 'studentsTable')">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="groupFilter">Группа</label>
            <select id="groupFilter" class="form-control" onchange="filterByGroup('groupFilter', 'studentsTable', 2)">
                <option value="">Все группы</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= htmlspecialchars($group['name']) ?>"><?= htmlspecialchars($group['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: end;">
            <a href="#" class="btn btn-primary" onclick="openModal('addStudentModal')">➕ Добавить студента</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Таблица студентов -->
<div class="table-container">
    <table class="data-table" id="studentsTable">
        <thead>
            <tr>
                <th>№</th>
                <th>ФИО</th>
                <th>Группа</th>
                <th>Специальность</th>
                <th>Курс</th>
                <th>Статус</th>
                <th>Год поступления</th>
                <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
                <th>Действия</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $statusLabels = [
                'active' => '<span class="badge badge-success">Обучается</span>',
                'expelled' => '<span class="badge badge-danger">Отчислен</span>',
                'graduated' => '<span class="badge badge-info">Выпускник</span>',
                'academic_leave' => '<span class="badge badge-warning">Академический отпуск</span>'
            ];
            $index = 1;
            foreach ($students as $student): 
            ?>
            <tr>
                <td><?= $index++ ?></td>
                <td><strong><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name']) ?></strong></td>
                <td><?= htmlspecialchars($student['group_name']) ?></td>
                <td><?= htmlspecialchars($student['specialty_name']) ?></td>
                <td><?= $student['enrollment_year'] - 2020 + 1 ?> курс</td>
                <td><?= $statusLabels[$student['status']] ?? $student['status'] ?></td>
                <td><?= $student['enrollment_year'] ?></td>
                <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
                <td>
                    <div class="btn-group">
                        <a href="#" class="btn btn-sm btn-warning" title="Редактировать">✏️</a>
                        <a href="?delete=<?= $student['id'] ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Вы уверены, что хотите удалить студента?')" title="Удалить">🗑️</a>
                    </div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Модальное окно добавления студента -->
<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ Добавить студента</h3>
            <button class="modal-close" onclick="closeModal('addStudentModal')">&times;</button>
        </div>
        <form method="POST" action="" id="addStudentForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Фамилия *</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Имя *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Отчество</label>
                        <input type="text" name="middle_name" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Группа *</label>
                        <select name="group_id" class="form-control" required>
                            <option value="">Выберите группу</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата рождения</label>
                        <input type="date" name="birth_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Год поступления *</label>
                        <input type="number" name="enrollment_year" class="form-control" value="2024" min="2020" max="2025" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status" class="form-control">
                        <option value="active">Обучается</option>
                        <option value="expelled">Отчислен</option>
                        <option value="graduated">Выпускник</option>
                        <option value="academic_leave">Академический отпуск</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">Отмена</button>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </div>
        </form>
    </div>
</div>

<script>
// Закрытие модального окна по клику вне его
document.getElementById('addStudentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('addStudentModal');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
