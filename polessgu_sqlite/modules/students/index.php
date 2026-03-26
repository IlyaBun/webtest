<?php
require_once __DIR__ . '/../config/app_config.php';
requireLogin();

$pageTitle = 'Студенты';
include __DIR__ . '/../includes/header.php';

$db = getDB();

// Обработка удаления
if (isset($_POST['delete']) && isAdmin()) {
    $id = (int)$_POST['delete'];
    $db->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
    header('Location: /modules/students/index.php');
    exit;
}

// Поиск и фильтрация
$search = $_GET['search'] ?? '';
$groupFilter = $_GET['group'] ?? '';

$query = "SELECT s.*, g.name as group_name, g.course 
          FROM students s 
          JOIN groups g ON s.group_id = g.id 
          WHERE 1=1";

$params = [];

if ($search) {
    $query .= " AND (s.last_name LIKE ? OR s.first_name LIKE ? OR s.middle_name LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

if ($groupFilter) {
    $query .= " AND g.id = ?";
    $params[] = $groupFilter;
}

$query .= " ORDER BY s.last_name, s.first_name";

$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Список групп для фильтра
$groups = $db->query("SELECT id, name FROM groups ORDER BY name")->fetchAll();
?>

<div class="module-container">
    <div class="module-header">
        <h2>👨‍🎓 Студенты</h2>
        <?php if (isAdmin()): ?>
        <button class="btn btn-primary" onclick="showModal('addStudentModal')">+ Добавить студента</button>
        <?php endif; ?>
    </div>
    
    <!-- Фильтры -->
    <div class="filters-bar">
        <form method="GET" class="filters-form">
            <input type="text" name="search" placeholder="Поиск по ФИО..." 
                   value="<?= htmlspecialchars($search) ?>" class="form-control">
            
            <select name="group" class="form-control">
                <option value="">Все группы</option>
                <?php foreach ($groups as $grp): ?>
                <option value="<?= $grp['id'] ?>" <?= $groupFilter == $grp['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($grp['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-secondary">Применить</button>
            <a href="/modules/students/index.php" class="btn btn-outline">Сбросить</a>
        </form>
        
        <div class="results-count">
            Найдено: <strong><?= count($students) ?></strong> студентов
        </div>
    </div>
    
    <!-- Таблица студентов -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Группа</th>
                    <th>Курс</th>
                    <th>Год поступления</th>
                    <th>Средний балл</th>
                    <?php if (isAdmin()): ?>
                    <th>Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): 
                    // Получаем средний балл студента
                    $avgGrade = $db->prepare("SELECT ROUND(AVG(grade), 2) FROM grades WHERE student_id = ?");
                    $avgGrade->execute([$student['id']]);
                    $avg = $avgGrade->fetchColumn() ?: '-';
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) ?></strong><br>
                        <small><?= htmlspecialchars($student['middle_name']) ?></small>
                    </td>
                    <td><span class="badge badge-group"><?= htmlspecialchars($student['group_name']) ?></span></td>
                    <td><?= $student['course'] ?></td>
                    <td><?= $student['enrollment_year'] ?></td>
                    <td>
                        <?php if ($avg !== '-'): ?>
                        <span class="badge badge-grade-<?= $avg >= 8 ? 'excellent' : ($avg >= 6 ? 'good' : ($avg >= 4 ? 'satisfactory' : 'poor')) ?>">
                            <?= $avg ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">Нет оценок</span>
                        <?php endif; ?>
                    </td>
                    <?php if (isAdmin()): ?>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-outline" onclick="editStudent(<?= $student['id'] ?>)">✏️</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить студента?')">
                                <input type="hidden" name="delete" value="<?= $student['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Модальное окно добавления -->
<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Добавить студента</h3>
            <button class="close-btn" onclick="closeModal('addStudentModal')">&times;</button>
        </div>
        <form method="POST" action="/modules/students/add.php">
            <div class="form-group">
                <label>Фамилия *</label>
                <input type="text" name="last_name" required class="form-control">
            </div>
            <div class="form-group">
                <label>Имя *</label>
                <input type="text" name="first_name" required class="form-control">
            </div>
            <div class="form-group">
                <label>Отчество</label>
                <input type="text" name="middle_name" class="form-control">
            </div>
            <div class="form-group">
                <label>Группа *</label>
                <select name="group_id" required class="form-control">
                    <?php foreach ($groups as $grp): ?>
                    <option value="<?= $grp['id'] ?>"><?= htmlspecialchars($grp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Год поступления *</label>
                <input type="number" name="enrollment_year" required min="2020" max="2025" 
                       value="<?= date('Y') ?>" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addStudentModal')">Отмена</button>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </div>
        </form>
    </div>
</div>

<script>
function editStudent(id) {
    alert('Функция редактирования будет реализована в следующей версии');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
