<?php
/**
 * Страница аналитики
 */
require_once '../../config/db_config.php';
require_once '../../config/app_config.php';

checkAuth();

$pageTitle = 'Аналитика и отчеты';

try {
    $pdo = getDBConnection();
    
    // Успеваемость по группам
    $groupPerformanceStmt = $pdo->query("
        SELECT gr.name as group_name, 
               COUNT(DISTINCT s.id) as student_count,
               ROUND(AVG(g.grade), 2) as avg_grade,
               SUM(CASE WHEN g.grade >= 9 THEN 1 ELSE 0 END) as excellent_count,
               SUM(CASE WHEN g.grade >= 7 AND g.grade < 9 THEN 1 ELSE 0 END) as good_count,
               SUM(CASE WHEN g.grade >= 5 AND g.grade < 7 THEN 1 ELSE 0 END) as satisfactory_count,
               SUM(CASE WHEN g.grade < 5 THEN 1 ELSE 0 END) as unsatisfactory_count
        FROM groups gr
        LEFT JOIN students s ON gr.id = s.group_id AND s.status = 'active'
        LEFT JOIN grades g ON s.id = g.student_id
        GROUP BY gr.id, gr.name
        ORDER BY avg_grade DESC
    ");
    $groupPerformance = $groupPerformanceStmt->fetchAll();
    
    // Рейтинг студентов
    $studentRatingStmt = $pdo->query("
        SELECT s.last_name, s.first_name, s.middle_name, gr.name as group_name,
               ROUND(AVG(g.grade), 2) as avg_grade,
               COUNT(g.id) as grade_count
        FROM students s
        JOIN groups gr ON s.group_id = gr.id
        JOIN grades g ON s.id = g.student_id
        WHERE s.status = 'active'
        GROUP BY s.id, s.last_name, s.first_name, s.middle_name, gr.name
        HAVING COUNT(g.id) >= 3
        ORDER BY avg_grade DESC
        LIMIT 20
    ");
    $studentRating = $studentRatingStmt->fetchAll();
    
    // Динамика успеваемости по месяцам
    $dynamicsStmt = $pdo->query("
        SELECT DATE_FORMAT(grade_date, '%Y-%m') as month,
               ROUND(AVG(grade), 2) as avg_grade,
               COUNT(*) as grade_count
        FROM grades
        GROUP BY DATE_FORMAT(grade_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $dynamics = array_reverse($dynamicsStmt->fetchAll());
    
    // Распределение оценок по дисциплинам
    $disciplineStatsStmt = $pdo->query("
        SELECT d.name as discipline_name,
               ROUND(AVG(g.grade), 2) as avg_grade,
               COUNT(g.id) as grade_count,
               MIN(g.grade) as min_grade,
               MAX(g.grade) as max_grade
        FROM disciplines d
        JOIN grades g ON d.id = g.discipline_id
        GROUP BY d.id, d.name
        ORDER BY avg_grade DESC
    ");
    $disciplineStats = $disciplineStatsStmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Ошибка получения данных: ' . $e->getMessage();
}

include '../../includes/header.php';
?>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Сводная статистика -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?= count($groupPerformance) ?></div>
        <div class="stat-label">Учебных групп</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">🎓</div>
        <div class="stat-value"><?= count($studentRating) ?></div>
        <div class="stat-label">Активных студентов</div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-icon">📚</div>
        <div class="stat-value"><?= count($disciplineStats) ?></div>
        <div class="stat-label">Дисциплин с оценками</div>
    </div>
</div>

<!-- Графики -->
<div class="charts-grid">
    <div class="chart-container">
        <h3>📈 Динамика успеваемости</h3>
        <canvas id="dynamicsChart" data-data='[<?= implode(', ', array_column($dynamics, 'avg_grade')) ?>' ]' height="250"></canvas>
    </div>
    
    <div class="chart-container">
        <h3>🏆 Топ групп</h3>
        <canvas id="topGroupsChart" 
                data-labels='[<?php echo implode('", "', array_slice(array_column($groupPerformance, 'group_name'), 0, 5)) ?>' ]' 
                data-data='[<?php echo implode(', ', array_slice(array_column($groupPerformance, 'avg_grade'), 0, 5)) ?>' ]' 
                height="250"></canvas>
    </div>
</div>

<!-- Успеваемость по группам -->
<div class="table-container">
    <div class="table-header">
        <h2>📊 Успеваемость по группам</h2>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Группа</th>
                <th>Студентов</th>
                <th>Средний балл</th>
                <th>Отлично (9-10)</th>
                <th>Хорошо (7-8)</th>
                <th>Удовл. (5-6)</th>
                <th>Неудовл. (3-4)</th>
                <th>Качество (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groupPerformance as $group): 
                $total = $group['excellent_count'] + $group['good_count'] + $group['satisfactory_count'] + $group['unsatisfactory_count'];
                $quality = $total > 0 ? round(($group['excellent_count'] + $group['good_count']) / $total * 100) : 0;
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($group['group_name']) ?></strong></td>
                <td><?= $group['student_count'] ?? 0 ?></td>
                <td><strong style="color: <?= getGradeColor($group['avg_grade']) ?>"><?= $group['avg_grade'] ?? '-' ?></strong></td>
                <td><span class="badge badge-success"><?= $group['excellent_count'] ?? 0 ?></span></td>
                <td><span class="badge badge-info"><?= $group['good_count'] ?? 0 ?></span></td>
                <td><span class="badge badge-warning"><?= $group['satisfactory_count'] ?? 0 ?></span></td>
                <td><span class="badge badge-danger"><?= $group['unsatisfactory_count'] ?? 0 ?></span></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="flex: 1; background: #e9ecef; border-radius: 10px; height: 8px; overflow: hidden;">
                            <div style="width: <?= $quality ?>%; background: linear-gradient(90deg, #28a745, #17a2b8); height: 100%;"></div>
                        </div>
                        <span><?= $quality ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Рейтинг студентов -->
<div class="table-container">
    <div class="table-header">
        <h2>🌟 Рейтинг студентов (Топ-20)</h2>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Место</th>
                <th>ФИО</th>
                <th>Группа</th>
                <th>Средний балл</th>
                <th>Количество оценок</th>
                <th>Уровень</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $medals = ['🥇', '🥈', '🥉'];
            $index = 0;
            foreach ($studentRating as $student): 
            ?>
            <tr>
                <td><?= $medals[$index] ?? ($index + 1) . ' место' ?></td>
                <td><strong><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name']) ?></strong></td>
                <td><?= htmlspecialchars($student['group_name']) ?></td>
                <td><strong style="color: <?= getGradeColor($student['avg_grade']) ?>"><?= $student['avg_grade'] ?></strong></td>
                <td><?= $student['grade_count'] ?></td>
                <td>
                    <?php if ($student['avg_grade'] >= 9): ?>
                        <span class="badge badge-success">Отличник</span>
                    <?php elseif ($student['avg_grade'] >= 7): ?>
                        <span class="badge badge-info">Хорошист</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Удовл.</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php 
            $index++;
            endforeach; 
            ?>
        </tbody>
    </table>
</div>

<!-- Статистика по дисциплинам -->
<div class="table-container">
    <div class="table-header">
        <h2>📚 Статистика по дисциплинам</h2>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Дисциплина</th>
                <th>Средний балл</th>
                <th>Оценок</th>
                <th>Мин</th>
                <th>Макс</th>
                <th>Разброс</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($disciplineStats as $discipline): 
                $range = $discipline['max_grade'] - $discipline['min_grade'];
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($discipline['discipline_name']) ?></strong></td>
                <td><strong style="color: <?= getGradeColor($discipline['avg_grade']) ?>"><?= $discipline['avg_grade'] ?></strong></td>
                <td><?= $discipline['grade_count'] ?></td>
                <td><span class="badge badge-danger"><?= $discipline['min_grade'] ?></span></td>
                <td><span class="badge badge-success"><?= $discipline['max_grade'] ?></span></td>
                <td>
                    <?php if ($range <= 2): ?>
                        <span class="badge badge-success">Стабильно</span>
                    <?php elseif ($range <= 4): ?>
                        <span class="badge badge-warning">Средне</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Большой</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// График топ групп
const topGroupsCtx = document.getElementById('topGroupsChart');
if (topGroupsCtx) {
    new Chart(topGroupsCtx, {
        type: 'bar',
        data: {
            labels: topGroupsCtx.dataset.labels ? JSON.parse(topGroupsCtx.dataset.labels) : [],
            datasets: [{
                label: 'Средний балл',
                data: topGroupsCtx.dataset.data ? JSON.parse(topGroupsCtx.dataset.data) : [],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(102, 126, 234, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
