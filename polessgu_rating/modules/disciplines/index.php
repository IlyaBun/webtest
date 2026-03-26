<?php
/**
 * Страница дисциплин
 */
require_once '../../config/db_config.php';
require_once '../../config/app_config.php';

checkAuth();

$pageTitle = 'Дисциплины';
$success = '';
$error = '';

// Обработка удаления
if (isset($_GET['delete']) && $_SESSION['user_role'] === ROLE_ADMIN) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM disciplines WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = 'Дисциплина успешно удалена';
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении: ' . $e->getMessage();
    }
}

// Получение данных
try {
    $pdo = getDBConnection();
    
    // Все кафедры для фильтра
    $departmentsStmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $departmentsStmt->fetchAll();
    
    // Список дисциплин
    $disciplinesStmt = $pdo->query("
        SELECT d.*, dep.name as department_name
        FROM disciplines d
        LEFT JOIN departments dep ON d.department_id = dep.id
        ORDER BY d.name
    ");
    $disciplines = $disciplinesStmt->fetchAll();
    
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
            <input type="text" id="searchInput" class="form-control" placeholder="Название дисциплины..." onkeyup="searchTable('searchInput', 'disciplinesTable')">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="deptFilter">Кафедра</label>
            <select id="deptFilter" class="form-control" onchange="filterByGroup('deptFilter', 'disciplinesTable', 3)">
                <option value="">Все кафедры</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept['name']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: end;">
            <a href="#" class="btn btn-primary" onclick="openModal('addDisciplineModal')">➕ Добавить дисциплину</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Таблица дисциплин -->
<div class="table-container">
    <table class="data-table" id="disciplinesTable">
        <thead>
            <tr>
                <th>№</th>
                <th>Название</th>
                <th>Код</th>
                <th>Кафедра</th>
                <th>Кредиты</th>
                <th>Семестр</th>
                <th>Тип контроля</th>
                <th>Часы (всего)</th>
                <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
                <th>Действия</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $controlTypes = [
                'exam' => 'Экзамен',
                'credit' => 'Зачет',
                'exam_credit' => 'Экзамен + Зачет'
            ];
            $index = 1;
            foreach ($disciplines as $discipline): 
            ?>
            <tr>
                <td><?= $index++ ?></td>
                <td><strong><?= htmlspecialchars($discipline['name']) ?></strong></td>
                <td><?= htmlspecialchars($discipline['code'] ?? '-') ?></td>
                <td><?= htmlspecialchars($discipline['department_name'] ?? 'Не указана') ?></td>
                <td><?= $discipline['credits'] ?></td>
                <td><?= $discipline['semester'] ?></td>
                <td><?= $controlTypes[$discipline['control_type']] ?? $discipline['control_type'] ?></td>
                <td><?= $discipline['hours_total'] ?></td>
                <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
                <td>
                    <div class="btn-group">
                        <a href="#" class="btn btn-sm btn-warning" title="Редактировать">✏️</a>
                        <a href="?delete=<?= $discipline['id'] ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Вы уверены, что хотите удалить дисциплину?')" title="Удалить">🗑️</a>
                    </div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Модальное окно добавления дисциплины -->
<div id="addDisciplineModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ Добавить дисциплину</h3>
            <button class="modal-close" onclick="closeModal('addDisciplineModal')">&times;</button>
        </div>
        <form method="POST" action="" id="addDisciplineForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Название дисциплины *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Код</label>
                        <input type="text" name="code" class="form-control" placeholder="Например: ВМ-01">
                    </div>
                    <div class="form-group">
                        <label>Кафедра</label>
                        <select name="department_id" class="form-control">
                            <option value="">Не указана</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Кредиты ECTS</label>
                        <input type="number" name="credits" class="form-control" value="4" min="1" max="10">
                    </div>
                    <div class="form-group">
                        <label>Семестр</label>
                        <input type="number" name="semester" class="form-control" value="1" min="1" max="8">
                    </div>
                    <div class="form-group">
                        <label>Тип контроля</label>
                        <select name="control_type" class="form-control">
                            <option value="exam">Экзамен</option>
                            <option value="credit">Зачет</option>
                            <option value="exam_credit">Экзамен + Зачет</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Всего часов</label>
                        <input type="number" name="hours_total" class="form-control" value="108" min="18">
                    </div>
                    <div class="form-group">
                        <label>Лекции</label>
                        <input type="number" name="hours_lecture" class="form-control" value="36" min="0">
                    </div>
                    <div class="form-group">
                        <label>Практические</label>
                        <input type="number" name="hours_practice" class="form-control" value="36" min="0">
                    </div>
                    <div class="form-group">
                        <label>Лабораторные</label>
                        <input type="number" name="hours_lab" class="form-control" value="36" min="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addDisciplineModal')">Отмена</button>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('addDisciplineModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('addDisciplineModal');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
