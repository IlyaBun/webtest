<?php
/**
 * Система оценки успеваемости студентов инженерного факультета ПолесГУ
 * 
 * Дипломный проект
 * Веб-приложение для оценки количественных и качественных показателей успеваемости
 */

// Старт сессии
session_start();

// Подключение конфигурации
require_once __DIR__ . '/config/app_config.php';

// Проверка авторизации
requireAuth();

$user = getCurrentUser();

// Получение общей статистики факультета
$facultyStats = dbFetchOne("SELECT * FROM v_faculty_summary");

// Получение последних оценок
$recentGrades = dbFetchAll("
    SELECT 
        g.id,
        g.grade,
        g.grade_date,
        CONCAT(s.last_name, ' ', s.first_name, ' ', s.middle_name) as student_name,
        d.name as discipline_name,
        gr.name as group_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN disciplines d ON g.discipline_id = d.id
    JOIN groups gr ON s.group_id = gr.id
    ORDER BY g.created_at DESC
    LIMIT 10
", []);

// Получение рейтинга групп
$groupRating = dbFetchAll("
    SELECT 
        g.name as group_name,
        g.course,
        sp.name as specialty,
        COUNT(DISTINCT s.id) as student_count,
        ROUND(AVG(gr.grade), 2) as average_grade
    FROM groups g
    JOIN specialties sp ON g.specialty_id = sp.id
    LEFT JOIN students s ON g.id = s.group_id AND s.status = 'active'
    LEFT JOIN grades gr ON s.id = gr.student_id
    GROUP BY g.id, g.name, g.course, sp.name
    HAVING average_grade IS NOT NULL
    ORDER BY average_grade DESC
    LIMIT 5
", []);

// Получение лучших студентов
$topStudents = dbFetchAll("
    SELECT 
        v.full_name,
        v.group_name,
        v.average_grade,
        v.total_grades
    FROM v_student_average v
    WHERE v.average_grade >= 9
    ORDER BY v.average_grade DESC, v.total_grades DESC
    LIMIT 5
", []);

// Получение студентов с низкой успеваемостью
$lowPerformanceStudents = dbFetchAll("
    SELECT 
        v.full_name,
        v.group_name,
        v.average_grade,
        v.min_grade
    FROM v_student_average v
    WHERE v.average_grade < 6
    ORDER BY v.average_grade ASC
    LIMIT 5
", []);

// Включение шаблона
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/sidebar.php';
?>

<!-- Основной контент -->
<div class="main-content">
    <!-- Верхняя панель -->
    <div class="top-bar">
        <div class="breadcrumb">
            <h1><i class="fas fa-home"></i> Главная</h1>
        </div>
        <div class="user-info">
            <span class="welcome-text">Добро пожаловать, <?php echo e($user['full_name']); ?>!</span>
            <a href="<?php echo BASE_URL; ?>/modules/auth/logout.php" class="btn btn-outline btn-sm">
                <i class="fas fa-sign-out-alt"></i> Выход
            </a>
        </div>
    </div>

    <!-- Карточки статистики -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $facultyStats['total_students'] ?? 0; ?></h3>
                <p>Всего студентов</p>
            </div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $facultyStats['faculty_average'] ?? '0.00'; ?></h3>
                <p>Средний балл факультета</p>
            </div>
        </div>

        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="fas fa-medal"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $facultyStats['excellent_count'] ?? 0; ?></h3>
                <p>Отличников</p>
            </div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo ($facultyStats['poor_count'] ?? 0) + ($facultyStats['satisfactory_count'] ?? 0); ?></h3>
                <p>Низкая успеваемость</p>
            </div>
        </div>

        <div class="stat-card stat-info">
            <div class="stat-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $facultyStats['overall_success_rate'] ?? '0.00'; ?>%</h3>
                <p>Успеваемость</p>
            </div>
        </div>
    </div>

    <!-- Графики и диаграммы -->
    <div class="dashboard-grid">
        <!-- Распределение оценок -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Распределение оценок</h3>
            </div>
            <div class="card-body">
                <canvas id="gradesChart"></canvas>
            </div>
        </div>

        <!-- Рейтинг групп -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-trophy"></i> Топ-5 групп</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Группа</th>
                            <th>Курс</th>
                            <th>Студентов</th>
                            <th>Ср. балл</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupRating as $group): ?>
                        <tr>
                            <td><strong><?php echo e($group['group_name']); ?></strong></td>
                            <td><?php echo $group['course']; ?></td>
                            <td><?php echo $group['student_count']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $group['average_grade'] >= 8 ? 'success' : ($group['average_grade'] >= 6 ? 'warning' : 'danger'); ?>">
                                    <?php echo $group['average_grade']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Лучшие студенты -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-star"></i> Лучшие студенты</h3>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($topStudents as $student): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo e($student['full_name']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo e($student['group_name']); ?></small>
                        </div>
                        <span class="badge badge-success"><?php echo $student['average_grade']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Студенты с низкой успеваемостью -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-heartbeat"></i> Требуется внимание</h3>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($lowPerformanceStudents as $student): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo e($student['full_name']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo e($student['group_name']); ?></small>
                        </div>
                        <span class="badge badge-danger"><?php echo $student['average_grade']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Последние оценки -->
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Последние добавленные оценки</h3>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Студент</th>
                        <th>Группа</th>
                        <th>Дисциплина</th>
                        <th>Оценка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentGrades as $grade): ?>
                    <tr>
                        <td><?php echo formatDate($grade['grade_date']); ?></td>
                        <td><?php echo e($grade['student_name']); ?></td>
                        <td><?php echo e($grade['group_name']); ?></td>
                        <td><?php echo e($grade['discipline_name']); ?></td>
                        <td>
                            <span class="grade-badge" style="background-color: <?php echo getGradeColor($grade['grade']); ?>;">
                                <?php echo $grade['grade']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Подключение скриптов для графиков -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Данные для диаграммы распределения оценок
const gradesData = {
    excellent: <?php echo $facultyStats['excellent_count'] ?? 0; ?>,
    good: <?php echo $facultyStats['good_count'] ?? 0; ?>,
    satisfactory: <?php echo $facultyStats['satisfactory_count'] ?? 0; ?>,
    poor: <?php echo $facultyStats['poor_count'] ?? 0; ?>
};

const ctx = document.getElementById('gradesChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Отлично (9-10)', 'Хорошо (7-8)', 'Удовл. (4-6)', 'Неуд. (1-3)'],
        datasets: [{
            data: [gradesData.excellent, gradesData.good, gradesData.satisfactory, gradesData.poor],
            backgroundColor: [
                '#28a745',
                '#17a2b8',
                '#ffc107',
                '#dc3545'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
