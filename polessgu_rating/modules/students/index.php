<?php
/**
 * Список студентов
 * Система оценки успеваемости ПолесГУ
 */

session_start();
require_once __DIR__ . '/../../config/app_config.php';
requireAuth();

$user = getCurrentUser();

// Получение списка групп для фильтра
$groups = dbFetchAll("SELECT id, name, course FROM groups ORDER BY name", []);

// Фильтры
$search = trim($_GET['search'] ?? '');
$groupFilter = $_GET['group'] ?? '';
$statusFilter = $_GET['status'] ?? 'active';

// Построение запроса
$where = ["s.status = ?"];
$params = [$statusFilter];
$types = 's';

if ($search) {
    $where[] = "(s.last_name LIKE ? OR s.first_name LIKE ? OR s.middle_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= 'sss';
}

if ($groupFilter) {
    $where[] = "s.group_id = ?";
    $params[] = (int)$groupFilter;
    $types .= 'i';
}

$whereClause = implode(' AND ', $where);

// Пагинация
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = DEFAULT_PER_PAGE;

// Получение общего количества
$countSql = "SELECT COUNT(*) as total FROM students s WHERE $whereClause";
$countResult = dbFetchOne($countSql, $params);
$totalCount = $countResult['total'];

$pagination = paginate($totalCount, $perPage, $page);

// Получение студентов
$sql = "
    SELECT 
        s.id,
        s.last_name,
        s.first_name,
        s.middle_name,
        s.date_of_birth,
        s.enrollment_year,
        s.status,
        g.name as group_name,
        g.course,
        sp.name as specialty,
        ROUND(AVG(gr.grade), 2) as average_grade,
        COUNT(gr.id) as grades_count
    FROM students s
    JOIN groups g ON s.group_id = g.id
    LEFT JOIN specialties sp ON g.specialty_id = sp.id
    LEFT JOIN grades gr ON s.id = gr.student_id
    WHERE $whereClause
    GROUP BY s.id, g.name, g.course, sp.name
    ORDER BY s.last_name, s.first_name
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
";

$students = dbFetchAll($sql, $params);

include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="breadcrumb">
            <h1><i class="fas fa-user-graduate"></i> Студенты</h1>
        </div>
        <div class="actions">
            <?php if (isAdmin()): ?>
            <a href="student_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить студента
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="dashboard-card" style="margin-bottom: 24px;">
        <div class="card-body">
            <form method="GET" action="" class="filters-form" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                    <label>Поиск по ФИО</label>
                    <input type="text" name="search" class="form-control" placeholder="Фамилия или имя" value="<?php echo e($search); ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Группа</label>
                    <select name="group" class="form-control">
                        <option value="">Все группы</option>
                        <?php foreach ($groups as $grp): ?>
                        <option value="<?php echo $grp['id']; ?>" <?php echo $groupFilter == $grp['id'] ? 'selected' : ''; ?>>
                            <?php echo e($grp['name']); ?> (<?php echo $grp['course']; ?> курс)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Статус</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Активные</option>
                        <option value="graduated" <?php echo $statusFilter === 'graduated' ? 'selected' : ''; ?>>Выпускники</option>
                        <option value="expelled" <?php echo $statusFilter === 'expelled' ? 'selected' : ''; ?>>Отчисленные</option>
                        <option value="academic_leave" <?php echo $statusFilter === 'academic_leave' ? 'selected' : ''; ?>>Академ</option>
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

    <!-- Таблица студентов -->
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h3>
                <i class="fas fa-list"></i> 
                Список студентов (<?php echo $totalCount; ?>)
            </h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table table-striped table-hover" id="studentsTable">
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th>Курс</th>
                        <th>Специальность</th>
                        <th>Год поступления</th>
                        <th>Ср. балл</th>
                        <th>Статус</th>
                        <?php if (isAdmin()): ?>
                        <th style="width: 120px;">Действия</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="<?php echo isAdmin() ? '8' : '7'; ?>" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <p style="color: #64748b;">Студенты не найдены</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name']); ?></strong>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo e($student['group_name']); ?></span>
                        </td>
                        <td><?php echo $student['course']; ?></td>
                        <td><?php echo e($student['specialty']); ?></td>
                        <td><?php echo $student['enrollment_year']; ?></td>
                        <td>
                            <?php if ($student['average_grade']): ?>
                            <span class="badge badge-<?php echo $student['average_grade'] >= 9 ? 'success' : ($student['average_grade'] >= 7 ? 'info' : ($student['average_grade'] >= 4 ? 'warning' : 'danger')); ?>">
                                <?php echo $student['average_grade']; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusLabels = [
                                'active' => ['text' => 'Активен', 'class' => 'success'],
                                'graduated' => ['text' => 'Выпускник', 'class' => 'info'],
                                'expelled' => ['text' => 'Отчислен', 'class' => 'danger'],
                                'academic_leave' => ['text' => 'Академ', 'class' => 'warning']
                            ];
                            $status = $statusLabels[$student['status']] ?? ['text' => $student['status'], 'class' => 'secondary'];
                            ?>
                            <span class="badge badge-<?php echo $status['class']; ?>"><?php echo $status['text']; ?></span>
                        </td>
                        <?php if (isAdmin()): ?>
                        <td>
                            <a href="student_edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline" data-tooltip="Редактировать">
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

    <!-- Пагинация -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
        <div style="color: #64748b;">
            Страница <?php echo $pagination['current_page']; ?> из <?php echo $pagination['total_pages']; ?>
        </div>
        <div style="display: flex; gap: 8px;">
            <?php if ($pagination['has_prev']): ?>
            <a href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>&group=<?php echo urlencode($groupFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn btn-outline btn-sm">
                <i class="fas fa-chevron-left"></i> Назад
            </a>
            <?php endif; ?>
            
            <?php if ($pagination['has_next']): ?>
            <a href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>&group=<?php echo urlencode($groupFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn btn-outline btn-sm">
                Вперед <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
