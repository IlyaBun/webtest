<?php
/**
 * Журнал оценок
 * Система оценки успеваемости ПолесГУ
 */

session_start();
require_once __DIR__ . '/../../config/app_config.php';
requireAuth();

$user = getCurrentUser();

// Получение списка студентов, групп и дисциплин
$students = dbFetchAll("
    SELECT s.id, CONCAT(s.last_name, ' ', s.first_name, ' ', s.middle_name) as full_name, g.name as group_name
    FROM students s
    JOIN groups g ON s.group_id = g.id
    WHERE s.status = 'active'
    ORDER BY g.name, s.last_name
", []);

$groups = dbFetchAll("SELECT id, name FROM groups ORDER BY name", []);
$disciplines = dbFetchAll("SELECT id, name FROM disciplines ORDER BY name", []);

// Фильтры
$groupFilter = $_GET['group'] ?? '';
$studentFilter = $_GET['student'] ?? '';
$disciplineFilter = $_GET['discipline'] ?? '';

// Построение запроса для оценок
$where = [];
$params = [];

if ($groupFilter) {
    $where[] = "s.group_id = ?";
    $params[] = (int)$groupFilter;
}

if ($studentFilter) {
    $where[] = "s.id = ?";
    $params[] = (int)$studentFilter;
}

if ($disciplineFilter) {
    $where[] = "g.discipline_id = ?";
    $params[] = (int)$disciplineFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Получение оценок
$grades = dbFetchAll("
    SELECT 
        gr.id,
        gr.grade,
        gr.grade_date,
        gr.semester,
        gr.academic_year,
        CONCAT(s.last_name, ' ', s.first_name, ' ', s.middle_name) as student_name,
        g.name as group_name,
        d.name as discipline_name
    FROM grades gr
    JOIN students s ON gr.student_id = s.id
    JOIN groups g ON s.group_id = g.id
    JOIN disciplines d ON gr.discipline_id = d.id
    $whereClause
    ORDER BY gr.grade_date DESC, gr.created_at DESC
    LIMIT 100
", $params);

include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="breadcrumb">
            <h1><i class="fas fa-star"></i> Оценки</h1>
        </div>
        <div class="actions">
            <?php if (isAdmin() || hasRole('teacher')): ?>
            <a href="grade_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить оценку
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="dashboard-card" style="margin-bottom: 24px;">
        <div class="card-body">
            <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                    <label>Группа</label>
                    <select name="group" class="form-control">
                        <option value="">Все группы</option>
                        <?php foreach ($groups as $grp): ?>
                        <option value="<?php echo $grp['id']; ?>" <?php echo $groupFilter == $grp['id'] ? 'selected' : ''; ?>>
                            <?php echo e($grp['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                    <label>Студент</label>
                    <select name="student" class="form-control">
                        <option value="">Все студенты</option>
                        <?php foreach ($students as $std): ?>
                        <option value="<?php echo $std['id']; ?>" <?php echo $studentFilter == $std['id'] ? 'selected' : ''; ?>>
                            <?php echo e($std['full_name']); ?> (<?php echo e($std['group_name']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                    <label>Дисциплина</label>
                    <select name="discipline" class="form-control">
                        <option value="">Все дисциплины</option>
                        <?php foreach ($disciplines as $disc): ?>
                        <option value="<?php echo $disc['id']; ?>" <?php echo $disciplineFilter == $disc['id'] ? 'selected' : ''; ?>>
                            <?php echo e($disc['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Найти
                </button>
                
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-redo"></i> Сбросить
                </a>
            </form>
        </div>
    </div>

    <!-- Таблица оценок -->
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Журнал оценок (<?php echo count($grades); ?>)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Студент</th>
                        <th>Группа</th>
                        <th>Дисциплина</th>
                        <th>Семестр</th>
                        <th>Учебный год</th>
                        <th>Оценка</th>
                        <?php if (isAdmin()): ?>
                        <th style="width: 100px;">Действия</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grades)): ?>
                    <tr>
                        <td colspan="<?php echo isAdmin() ? '8' : '7'; ?>" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <p style="color: #64748b;">Оценки не найдены</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><?php echo formatDate($grade['grade_date']); ?></td>
                        <td><strong><?php echo e($grade['student_name']); ?></strong></td>
                        <td><span class="badge badge-primary"><?php echo e($grade['group_name']); ?></span></td>
                        <td><?php echo e($grade['discipline_name']); ?></td>
                        <td><?php echo $grade['semester']; ?></td>
                        <td><?php echo e($grade['academic_year']); ?></td>
                        <td>
                            <span class="grade-badge" style="background-color: <?php echo getGradeColor($grade['grade']); ?>;">
                                <?php echo $grade['grade']; ?>
                            </span>
                            <small style="display: block; font-size: 11px; color: #64748b; margin-top: 4px;">
                                <?php echo getGradeText($grade['grade']); ?>
                            </small>
                        </td>
                        <?php if (isAdmin()): ?>
                        <td>
                            <a href="grade_edit.php?id=<?php echo $grade['id']; ?>" class="btn btn-sm btn-outline" data-tooltip="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
