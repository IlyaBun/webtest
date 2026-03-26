<?php
/**
 * Страница аналитики и отчетов
 * Система оценки успеваемости ПолесГУ
 */

session_start();
require_once __DIR__ . '/../../config/app_config.php';
requireAuth();

$user = getCurrentUser();

// Общая статистика факультета
$facultyStats = dbFetchOne("SELECT * FROM v_faculty_summary");

// Статистика по группам
$groupStats = dbFetchAll("SELECT * FROM v_group_statistics ORDER BY group_average DESC", []);

// Рейтинг студентов
$studentRating = dbFetchAll("
    SELECT * FROM v_student_average 
    WHERE total_grades > 0
    ORDER BY average_grade DESC, total_grades DESC
    LIMIT 20
", []);

// Распределение оценок по дисциплинам
$disciplineStats = dbFetchAll("
    SELECT 
        d.name as discipline_name,
        COUNT(gr.id) as total_grades,
        ROUND(AVG(gr.grade), 2) as average_grade,
        SUM(CASE WHEN gr.grade >= 9 THEN 1 ELSE 0 END) as excellent_count,
        SUM(CASE WHEN gr.grade >= 7 AND gr.grade < 9 THEN 1 ELSE 0 END) as good_count,
        SUM(CASE WHEN gr.grade >= 4 AND gr.grade < 7 THEN 1 ELSE 0 END) as satisfactory_count,
        SUM(CASE WHEN gr.grade < 4 THEN 1 ELSE 0 END) as poor_count
    FROM disciplines d
    LEFT JOIN grades gr ON d.id = gr.discipline_id
    GROUP BY d.id, d.name
    HAVING total_grades > 0
    ORDER BY average_grade DESC
", []);

// Динамика успеваемости по семестрам
$semesterStats = dbFetchAll("
    SELECT 
        academic_year,
        semester,
        ROUND(AVG(grade), 2) as average_grade,
        COUNT(*) as total_grades,
        SUM(CASE WHEN grade >= 9 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as excellent_percent,
        SUM(CASE WHEN grade >= 4 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as success_percent
    FROM grades
    GROUP BY academic_year, semester
    ORDER BY academic_year DESC, semester DESC
", []);

// Статистика по курсам
$courseStats = dbFetchAll("
    SELECT 
        g.course,
        COUNT(DISTINCT s.id) as student_count,
        ROUND(AVG(gr.grade), 2) as average_grade,
        SUM(CASE WHEN gr.grade >= 4 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(gr.grade), 0) as success_rate
    FROM groups g
    LEFT JOIN students s ON g.id = s.group_id AND s.status = 'active'
    LEFT JOIN grades gr ON s.id = gr.student_id
    GROUP BY g.course
    ORDER BY g.course
", []);

include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="breadcrumb">
            <h1><i class="fas fa-chart-bar"></i> Аналитика и отчеты</h1>
        </div>
    </div>

    <!-- Ключевые показатели -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-details">
                <h3><?php echo $facultyStats['total_students'] ?? 0; ?></h3>
                <p>Всего студентов</p>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-details">
                <h3><?php echo $facultyStats['faculty_average'] ?? '0.00'; ?></h3>
                <p>Средний балл</p>
            </div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-medal"></i></div>
            <div class="stat-details">
                <h3><?php echo $facultyStats['excellent_count'] ?? 0; ?></h3>
                <p>Отличников</p>
            </div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-details">
                <h3><?php echo $facultyStats['overall_success_rate'] ?? '0.00'; ?>%</h3>
                <p>Успеваемость</p>
            </div>
        </div>
    </div>

    <!-- Графики -->
    <div class="dashboard-grid">
        <!-- Распределение по уровням -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Распределение студентов</h3>
            </div>
            <div class="card-body">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>

        <!-- Успеваемость по курсам -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> Успеваемость по курсам</h3>
            </div>
            <div class="card-body">
                <canvas id="courseChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Рейтинг групп -->
    <div class="dashboard-card full-width" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-trophy"></i> Рейтинг групп</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Группа</th>
                        <th>Курс</th>
                        <th>Специальность</th>
                        <th>Студентов</th>
                        <th>Отличников</th>
                        <th>Хорошистов</th>
                        <th>Удовл.</th>
                        <th>Неуд.</th>
                        <th>Ср. балл</th>
                        <th>Успеваемость</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($groupStats as $group): ?>
                    <tr>
                        <td><strong><?php echo $rank++; ?></strong></td>
                        <td><span class="badge badge-primary"><?php echo e($group['group_name']); ?></span></td>
                        <td><?php echo $group['course']; ?></td>
                        <td><?php echo e($group['specialty']); ?></td>
                        <td><?php echo $group['total_students']; ?></td>
                        <td><?php echo $group['excellent_students'] ?? 0; ?></td>
                        <td><?php echo $group['good_students'] ?? 0; ?></td>
                        <td><?php echo $group['satisfactory_students'] ?? 0; ?></td>
                        <td><?php echo $group['poor_students'] ?? 0; ?></td>
                        <td>
                            <strong><?php echo $group['group_average']; ?></strong>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; background: #e2e8f0; border-radius: 10px; height: 8px; overflow: hidden;">
                                    <div style="width: <?php echo $group['success_rate'] ?? 0; ?>%; background: <?php echo ($group['success_rate'] ?? 0) >= 80 ? '#28a745' : (($group['success_rate'] ?? 0) >= 60 ? '#ffc107' : '#dc3545'); ?>; height: 100%;"></div>
                                </div>
                                <span style="font-size: 12px;"><?php echo number_format($group['success_rate'] ?? 0, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Топ студентов -->
    <div class="dashboard-card full-width" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-star"></i> Топ-20 студентов по успеваемости</h3>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ФИО студента</th>
                        <th>Группа</th>
                        <th>Специальность</th>
                        <th>Оценок</th>
                        <th>Средний балл</th>
                        <th>Уровень</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($studentRating as $student): 
                        $level = getKnowledgeLevel($student['average_grade']);
                    ?>
                    <tr>
                        <td>
                            <?php if ($rank <= 3): ?>
                            <i class="fas fa-trophy" style="color: <?php echo $rank === 1 ? '#ffd700' : ($rank === 2 ? '#c0c0c0' : '#cd7f32'); ?>;"></i>
                            <?php endif; ?>
                            <strong><?php echo $rank++; ?></strong>
                        </td>
                        <td><strong><?php echo e($student['full_name']); ?></strong></td>
                        <td><?php echo e($student['group_name']); ?></td>
                        <td><?php echo e($student['specialty']); ?></td>
                        <td><?php echo $student['total_grades']; ?></td>
                        <td>
                            <strong style="color: <?php echo getGradeColor($student['average_grade']); ?>;">
                                <?php echo $student['average_grade']; ?>
                            </strong>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $level['class']; ?>"><?php echo $level['level']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Статистика по дисциплинам -->
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-book"></i> Успеваемость по дисциплинам</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Дисциплина</th>
                        <th>Всего оценок</th>
                        <th>Средний балл</th>
                        <th>Отлично</th>
                        <th>Хорошо</th>
                        <th>Удовл.</th>
                        <th>Неуд.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($disciplineStats as $discipline): ?>
                    <tr>
                        <td><strong><?php echo e($discipline['discipline_name']); ?></strong></td>
                        <td><?php echo $discipline['total_grades']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $discipline['average_grade'] >= 7 ? 'success' : ($discipline['average_grade'] >= 5 ? 'warning' : 'danger'); ?>">
                                <?php echo $discipline['average_grade']; ?>
                            </span>
                        </td>
                        <td><span class="badge badge-success"><?php echo $discipline['excellent_count']; ?></span></td>
                        <td><span class="badge badge-info"><?php echo $discipline['good_count']; ?></span></td>
                        <td><span class="badge badge-warning"><?php echo $discipline['satisfactory_count']; ?></span></td>
                        <td><span class="badge badge-danger"><?php echo $discipline['poor_count']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js для графиков -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Распределение студентов
const distCtx = document.getElementById('distributionChart').getContext('2d');
new Chart(distCtx, {
    type: 'doughnut',
    data: {
        labels: ['Отличники', 'Хорошисты', 'Удовл.', 'Неуд.'],
        datasets: [{
            data: [
                <?php echo $facultyStats['excellent_count'] ?? 0; ?>,
                <?php echo $facultyStats['good_count'] ?? 0; ?>,
                <?php echo $facultyStats['satisfactory_count'] ?? 0; ?>,
                <?php echo $facultyStats['poor_count'] ?? 0; ?>
            ],
            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Успеваемость по курсам
const courseCtx = document.getElementById('courseChart').getContext('2d');
new Chart(courseCtx, {
    type: 'bar',
    data: {
        labels: [<?php foreach ($courseStats as $cs) echo "'{$cs['course']} курс',"; ?>],
        datasets: [{
            label: 'Средний балл',
            data: [<?php foreach ($courseStats as $cs) echo "{$cs['average_grade']},"; ?>],
            backgroundColor: '#4f46e5',
            borderRadius: 8
        }, {
            label: 'Успеваемость %',
            data: [<?php foreach ($courseStats as $cs) echo "{$cs['success_rate']},"; ?>],
            backgroundColor: '#28a745',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, max: 100 }
        },
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
