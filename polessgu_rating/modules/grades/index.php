<?php
/**
 * Страница оценок (Журнал)
 */
require_once '../../config/db_config.php';
require_once '../../config/app_config.php';

checkAuth();

$pageTitle = 'Оценки';
$success = '';
$error = '';

// Обработка добавления оценки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO grades (student_id, discipline_id, grade, grade_date, semester, academic_year, control_type, teacher_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['student_id'],
            $_POST['discipline_id'],
            $_POST['grade'],
            $_POST['grade_date'],
            $_POST['semester'],
            $_POST['academic_year'],
            $_POST['control_type'],
            $_SESSION['user_id']
        ]);
        $success = 'Оценка успешно добавлена';
    } catch (PDOException $e) {
        $error = 'Ошибка при добавлении: ' . $e->getMessage();
    }
}

// Обработка удаления
if (isset($_GET['delete']) && $_SESSION['user_role'] === ROLE_ADMIN) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = 'Оценка успешно удалена';
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении: ' . $e->getMessage();
    }
}

// Получение данных
try {
    $pdo = getDBConnection();
    
    // Все студенты
    $studentsStmt = $pdo->query("
        SELECT s.id, s.last_name, s.first_name, s.middle_name, g.name as group_name
        FROM students s
        JOIN groups g ON s.group_id = g.id
        WHERE s.status = 'active'
        ORDER BY g.name, s.last_name
    ");
    $students = $studentsStmt->fetchAll();
    
    // Все дисциплины
    $disciplinesStmt = $pdo->query("SELECT id, name, code FROM disciplines ORDER BY name");
    $disciplines = $disciplinesStmt->fetchAll();
    
    // Все группы
    $groupsStmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
    $groups = $groupsStmt->fetchAll();
    
    // Список оценок с фильтрацией
    $filterGroup = $_GET['group'] ?? '';
    $filterStudent = $_GET['student'] ?? '';
    
    $where = [];
    $params = [];
    
    if ($filterGroup) {
        $where[] = "s.group_id = ?";
        $params[] = $filterGroup;
    }
    
    if ($filterStudent) {
        $where[] = "g.student_id = ?";
        $params[] = $filterStudent;
    }
    
    $sql = "
        SELECT g.*, 
               s.last_name, s.first_name, s.middle_name, 
               gr.name as group_name,
               d.name as discipline_name,
               t.last_name as teacher_last_name
        FROM grades g
        JOIN students s ON g.student_id = s.id
        JOIN groups gr ON s.group_id = gr.id
        JOIN disciplines d ON g.discipline_id = d.id
        LEFT JOIN teachers t ON g.teacher_id = t.id
    ";
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    
    $sql .= " ORDER BY g.grade_date DESC, g.created_at DESC LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $grades = $stmt->fetchAll();
    
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
        <div class="form-group" style="margin-bottom: 0;">
            <label for="groupFilter">Группа</label>
            <select id="groupFilter" class="form-control" onchange="location.href='?group='+this.value">
                <option value="">Все группы</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= $group['id'] ?>" <?= $filterGroup == $group['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($group['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="studentFilter">Студент</label>
            <select id="studentFilter" class="form-control" onchange="location.href='?student='+this.value">
                <option value="">Все студенты</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= $student['id'] ?>" <?= $filterStudent == $student['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) ?> (<?= $student['group_name'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: end;">
            <a href="#" class="btn btn-primary" onclick="openModal('addGradeModal')">➕ Добавить оценку</a>
        </div>
    </div>
</div>

<!-- Таблица оценок -->
<div class="table-container">
    <div class="table-header">
        <h2>📋 Журнал оценок</h2>
        <button class="btn btn-sm btn-success" onclick="exportTableToCSV('gradesTable', 'grades_export.csv')">📥 Экспорт в CSV</button>
    </div>
    <table class="data-table" id="gradesTable">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Студент</th>
                <th>Группа</th>
                <th>Дисциплина</th>
                <th>Оценка</th>
                <th>Тип</th>
                <th>Семестр</th>
                <th>Учебный год</th>
                <th>Преподаватель</th>
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
                'current' => 'Текущий',
                'reexam' => 'Пересдача'
            ];
            foreach ($grades as $grade): 
            ?>
            <tr>
                <td><?= formatDate($grade['grade_date']) ?></td>
                <td><strong><?= htmlspecialchars($grade['last_name'] . ' ' . mb_substr($grade['first_name'], 0, 1) . '.' . mb_substr($grade['middle_name'], 0, 1) . '.') ?></strong></td>
                <td><?= htmlspecialchars($grade['group_name']) ?></td>
                <td><?= htmlspecialchars($grade['discipline_name']) ?></td>
                <td>
                    <span class="grade-badge" style="background: <?= getGradeColor($grade['grade']) ?>">
                        <?= $grade['grade'] ?>
                    </span>
                </td>
                <td><?= $controlTypes[$grade['control_type']] ?? $grade['control_type'] ?></td>
                <td><?= $grade['semester'] ?></td>
                <td><?= htmlspecialchars($grade['academic_year']) ?></td>
                <td><?= htmlspecialchars($grade['teacher_last_name'] ?? '-') ?></td>
                <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
                <td>
                    <a href="?delete=<?= $grade['id'] ?>&group=<?= $filterGroup ?>&student=<?= $filterStudent ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Вы уверены, что хотите удалить оценку?')" title="Удалить">🗑️</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Модальное окно добавления оценки -->
<div id="addGradeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ Добавить оценку</h3>
            <button class="modal-close" onclick="closeModal('addGradeModal')">&times;</button>
        </div>
        <form method="POST" action="" id="addGradeForm">
            <div class="modal-body">
                <input type="hidden" name="add_grade" value="1">
                
                <div class="form-group">
                    <label>Студент *</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">Выберите студента</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>">
                                <?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name']) ?> 
                                (<?= $student['group_name'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Дисциплина *</label>
                    <select name="discipline_id" class="form-control" required>
                        <option value="">Выберите дисциплину</option>
                        <?php foreach ($disciplines as $discipline): ?>
                            <option value="<?= $discipline['id'] ?>">
                                <?= htmlspecialchars($discipline['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Оценка (1-10) *</label>
                        <input type="number" name="grade" class="form-control" min="1" max="10" value="7" required>
                    </div>
                    <div class="form-group">
                        <label>Дата *</label>
                        <input type="date" name="grade_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Семестр *</label>
                        <select name="semester" class="form-control" required>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == 1 ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Учебный год *</label>
                        <input type="text" name="academic_year" class="form-control" value="2024/2025" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Тип контроля *</label>
                    <select name="control_type" class="form-control" required>
                        <option value="exam">Экзамен</option>
                        <option value="credit">Зачет</option>
                        <option value="current">Текущий контроль</option>
                        <option value="reexam">Пересдача</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addGradeModal')">Отмена</button>
                <button type="submit" class="btn btn-primary">Добавить оценку</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('addGradeModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('addGradeModal');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
