<?php
/**
 * Список дисциплин
 * Система оценки успеваемости ПолесГУ
 */

session_start();
require_once __DIR__ . '/../../config/app_config.php';
requireAuth();

$user = getCurrentUser();

// Получение списка дисциплин с информацией
$disciplines = dbFetchAll("
    SELECT 
        d.id,
        d.name,
        d.semester,
        d.hours_total,
        d.hours_lecture,
        d.hours_practice,
        d.control_type,
        dep.name as department_name,
        CONCAT(u.last_name, ' ', u.first_name, ' ', u.middle_name) as teacher_name
    FROM disciplines d
    LEFT JOIN departments dep ON d.department_id = dep.id
    LEFT JOIN users u ON d.teacher_id = u.id
    ORDER BY d.name
", []);

// Группировка по семестрам
$bySemester = [];
foreach ($disciplines as $disc) {
    $bySemester[$disc['semester']][] = $disc;
}

include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="breadcrumb">
            <h1><i class="fas fa-book"></i> Дисциплины</h1>
        </div>
        <?php if (isAdmin()): ?>
        <div class="actions">
            <a href="discipline_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить дисциплину
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Статистика -->
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-details">
                <h3><?php echo count($disciplines); ?></h3>
                <p>Всего дисциплин</p>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="stat-details">
                <h3><?php echo count(array_unique(array_column($disciplines, 'teacher_name'))); ?></h3>
                <p>Преподавателей</p>
            </div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-details">
                <h3><?php echo array_sum(array_column($disciplines, 'hours_total')); ?></h3>
                <p>Всего часов</p>
            </div>
        </div>
    </div>

    <!-- Дисциплины по семестрам -->
    <?php foreach ($bySemester as $semester => $discs): ?>
    <div class="dashboard-card full-width" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-calendar-alt"></i> <?php echo $semester; ?> семестр</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Название дисциплины</th>
                        <th>Кафедра</th>
                        <th>Преподаватель</th>
                        <th>Всего часов</th>
                        <th>Лекции</th>
                        <th>Практика</th>
                        <th>Контроль</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discs as $disc): ?>
                    <tr>
                        <td><strong><?php echo e($disc['name']); ?></strong></td>
                        <td><?php echo e($disc['department_name'] ?? '-'); ?></td>
                        <td><?php echo e($disc['teacher_name'] ?? 'Не назначен'); ?></td>
                        <td><?php echo $disc['hours_total']; ?></td>
                        <td><?php echo $disc['hours_lecture']; ?></td>
                        <td><?php echo $disc['hours_practice']; ?></td>
                        <td>
                            <?php
                            $controlLabels = [
                                'exam' => ['text' => 'Экзамен', 'class' => 'danger'],
                                'credit' => ['text' => 'Зачет', 'class' => 'info'],
                                'exam_and_credit' => ['text' => 'Экзамен + Зачет', 'class' => 'warning']
                            ];
                            $ctrl = $controlLabels[$disc['control_type']] ?? ['text' => $disc['control_type'], 'class' => 'secondary'];
                            ?>
                            <span class="badge badge-<?php echo $ctrl['class']; ?>"><?php echo $ctrl['text']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
