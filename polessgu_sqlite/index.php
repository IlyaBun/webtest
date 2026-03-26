<?php
require_once __DIR__ . '/../config/app_config.php';
requireLogin();

$pageTitle = 'Главная панель';
include __DIR__ . '/../includes/header.php';

$db = getDB();

// Получение статистики
$stats = [];

// Общее количество студентов
$stats['total_students'] = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Средний балл факультета
$stats['avg_grade'] = $db->query("SELECT ROUND(AVG(grade), 2) FROM grades")->fetchColumn() ?: 0;

// Количество отличников (средний балл >= 9)
$excellent = $db->query("
    SELECT COUNT(DISTINCT student_id) 
    FROM (
        SELECT student_id, AVG(grade) as avg_g 
        FROM grades 
        GROUP BY student_id 
        HAVING avg_g >= 9
    )
")->fetchColumn();
$stats['excellent'] = $excellent;

// Студенты с низкой успеваемостью (средний балл < 5)
$low = $db->query("
    SELECT COUNT(DISTINCT student_id) 
    FROM (
        SELECT student_id, AVG(grade) as avg_g 
        FROM grades 
        GROUP BY student_id 
        HAVING avg_g < 5
    )
")->fetchColumn();
$stats['low_performance'] = $low;

// Количество групп
$stats['total_groups'] = $db->query("SELECT COUNT(*) FROM groups")->fetchColumn();

// Количество дисциплин
$stats['total_disciplines'] = $db->query("SELECT COUNT(*) FROM disciplines")->fetchColumn();

// Распределение оценок
$gradeDistribution = $db->query("
    SELECT grade, COUNT(*) as count 
    FROM grades 
    GROUP BY grade 
    ORDER BY grade
")->fetchAll();

// Последние оценки
$recentGrades = $db->query("
    SELECT g.*, s.last_name, s.first_name, s.middle_name, d.name as discipline_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN disciplines d ON g.discipline_id = d.id
    ORDER BY g.date_recorded DESC, g.id DESC
    LIMIT 10
")->fetchAll();

// Рейтинг групп
$groupRating = $db->query("
    SELECT gr.name as group_name, ROUND(AVG(g.grade), 2) as avg_grade, COUNT(DISTINCT g.student_id) as student_count
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN groups gr ON s.group_id = gr.id
    GROUP BY gr.id, gr.name
    ORDER BY avg_grade DESC
    LIMIT 5
")->fetchAll();

// Рейтинг студентов
$studentRating = $db->query("
    SELECT s.last_name, s.first_name, s.middle_name, gr.name as group_name, ROUND(AVG(g.grade), 2) as avg_grade, COUNT(g.id) as grade_count
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN groups gr ON s.group_id = gr.id
    GROUP BY s.id, s.last_name, s.first_name, s.middle_name, gr.name
    HAVING COUNT(g.id) >= 3
    ORDER BY avg_grade DESC
    LIMIT 10
")->fetchAll();
?>

<div class="dashboard">
    <!-- Карточки статистики -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-content">
                <h3><?= $stats['total_students'] ?></h3>
                <p>Студентов всего</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?= number_format($stats['avg_grade'], 2) ?></h3>
                <p>Средний балл факультета</p>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
                <h3><?= $stats['excellent'] ?></h3>
                <p>Отличников</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <h3><?= $stats['low_performance'] ?></h3>
                <p>Низкая успеваемость</p>
            </div>
        </div>
        
        <div class="stat-card stat-secondary">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?= $stats['total_groups'] ?></h3>
                <p>Учебных групп</p>
            </div>
        </div>
    </div>
    
    <!-- Графики -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Распределение оценок</h3>
            <canvas id="gradeDistributionChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3>Рейтинг групп</h3>
            <canvas id="groupRatingChart"></canvas>
        </div>
    </div>
    
    <!-- Таблицы -->
    <div class="tables-grid">
        <div class="table-card">
            <h3>🏆 Топ студентов</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th>Средний балл</th>
                        <th>Оценок</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentRating as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name']) ?></td>
                        <td><span class="badge badge-group"><?= htmlspecialchars($student['group_name']) ?></span></td>
                        <td><strong><?= number_format($student['avg_grade'], 2) ?></strong></td>
                        <td><?= $student['grade_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="table-card">
            <h3>📈 Рейтинги групп</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Группа</th>
                        <th>Средний балл</th>
                        <th>Студентов</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groupRating as $group): ?>
                    <tr>
                        <td><span class="badge badge-group"><?= htmlspecialchars($group['group_name']) ?></span></td>
                        <td><strong><?= number_format($group['avg_grade'], 2) ?></strong></td>
                        <td><?= $group['student_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Последние оценки -->
    <div class="table-card full-width">
        <h3>📝 Последние оценки</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Студент</th>
                    <th>Дисциплина</th>
                    <th>Оценка</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentGrades as $grade): ?>
                <tr>
                    <td><?= date('d.m.Y', strtotime($grade['date_recorded'])) ?></td>
                    <td><?= htmlspecialchars($grade['last_name'] . ' ' . $grade['first_name']) ?></td>
                    <td><?= htmlspecialchars($grade['discipline_name']) ?></td>
                    <td>
                        <span class="badge badge-grade-<?= $grade['grade'] >= 8 ? 'excellent' : ($grade['grade'] >= 6 ? 'good' : ($grade['grade'] >= 4 ? 'satisfactory' : 'poor')) ?>">
                            <?= $grade['grade'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// График распределения оценок
const gradeCtx = document.getElementById('gradeDistributionChart').getContext('2d');
new Chart(gradeCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($gradeDistribution, 'grade')) ?>,
        datasets: [{
            label: 'Количество оценок',
            data: <?= json_encode(array_column($gradeDistribution, 'count')) ?>,
            backgroundColor: 'rgba(76, 110, 245, 0.8)',
            borderColor: 'rgba(76, 110, 245, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// График рейтинга групп
const groupCtx = document.getElementById('groupRatingChart').getContext('2d');
new Chart(groupCtx, {
    type: 'horizontalBar',
    data: {
        labels: <?= json_encode(array_column($groupRating, 'group_name')) ?>,
        datasets: [{
            label: 'Средний балл',
            data: <?= json_encode(array_column($groupRating, 'avg_grade')) ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderColor: 'rgba(16, 185, 129, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: { beginAtZero: true, max: 10 }
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
