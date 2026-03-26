<?php
/**
 * Главная страница (Dashboard)
 */
require_once 'config/db_config.php';
require_once 'config/app_config.php';

checkAuth();

$pageTitle = 'Главная панель';

// Получение статистики
try {
    $pdo = getDBConnection();
    
    // Общее количество студентов
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
    $totalStudents = $stmt->fetch()['total'];
    
    // Средний балл факультета
    $stmt = $pdo->query("SELECT ROUND(AVG(grade), 2) as avg_grade FROM grades");
    $avgGrade = $stmt->fetch()['avg_grade'] ?? 0;
    
    // Количество отличников (средний балл >= 9)
    $stmt = $pdo->query("
        SELECT COUNT(*) as excellent FROM (
            SELECT student_id, AVG(grade) as student_avg 
            FROM grades 
            GROUP BY student_id 
            HAVING student_avg >= 9
        ) as excellent_students
    ");
    $excellentStudents = $stmt->fetch()['excellent'] ?? 0;
    
    // Студенты с низкой успеваемостью (средний балл < 5)
    $stmt = $pdo->query("
        SELECT COUNT(*) as low FROM (
            SELECT student_id, AVG(grade) as student_avg 
            FROM grades 
            GROUP BY student_id 
            HAVING student_avg < 5
        ) as low_students
    ");
    $lowPerformanceStudents = $stmt->fetch()['low'] ?? 0;
    
    // Всего оценок
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM grades");
    $totalGrades = $stmt->fetch()['total'];
    
    // Распределение оценок
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN grade >= 9 THEN 1 ELSE 0 END) as excellent,
            SUM(CASE WHEN grade >= 7 AND grade < 9 THEN 1 ELSE 0 END) as good,
            SUM(CASE WHEN grade >= 5 AND grade < 7 THEN 1 ELSE 0 END) as satisfactory,
            SUM(CASE WHEN grade < 5 THEN 1 ELSE 0 END) as unsatisfactory
        FROM grades
    ");
    $gradeDistribution = $stmt->fetch();
    
    // Последние оценки
    $stmt = $pdo->query("
        SELECT g.*, s.last_name, s.first_name, s.middle_name, d.name as discipline_name
        FROM grades g
        JOIN students s ON g.student_id = s.id
        JOIN disciplines d ON g.discipline_id = d.id
        ORDER BY g.grade_date DESC, g.created_at DESC
        LIMIT 10
    ");
    $recentGrades = $stmt->fetchAll();
    
    // Рейтинг групп
    $stmt = $pdo->query("
        SELECT gr.name as group_name, ROUND(AVG(g.grade), 2) as avg_grade
        FROM grades g
        JOIN students s ON g.student_id = s.id
        JOIN groups gr ON s.group_id = gr.id
        GROUP BY gr.id, gr.name
        ORDER BY avg_grade DESC
        LIMIT 5
    ");
    $groupRating = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Ошибка получения данных: ' . $e->getMessage();
}

include 'includes/header.php';
?>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Карточки статистики -->
<div class="stats-grid">
    <div class="stat-card info">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-label">Студентов обучается</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?= number_format($avgGrade, 2) ?></div>
        <div class="stat-label">Средний балл факультета</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">⭐</div>
        <div class="stat-value"><?= $excellentStudents ?></div>
        <div class="stat-label">Отличников</div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value"><?= $lowPerformanceStudents ?></div>
        <div class="stat-label">Низкая успеваемость</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-value"><?= $totalGrades ?></div>
        <div class="stat-label">Всего оценок</div>
    </div>
</div>

<!-- Графики -->
<div class="charts-grid">
    <div class="chart-container">
        <h3>📊 Распределение оценок</h3>
        <canvas id="gradeDistributionChart" data-data='[<?= $gradeDistribution['excellent'] ?? 0 ?>, <?= $gradeDistribution['good'] ?? 0 ?>, <?= $gradeDistribution['satisfactory'] ?? 0 ?>, <?= $gradeDistribution['unsatisfactory'] ?? 0 ?>]' height="250"></canvas>
    </div>
    
    <div class="chart-container">
        <h3>🏆 Рейтинг групп</h3>
        <canvas id="groupPerformanceChart" data-labels='[<?php echo implode('", "', array_column($groupRating, 'group_name')) ?>' ]' data-data='[<?php echo implode(', ', array_column($groupRating, 'avg_grade')) ?>' ]' height="250"></canvas>
    </div>
</div>

<!-- Последние оценки -->
<div class="table-container">
    <div class="table-header">
        <h2>📋 Последние оценки</h2>
        <a href="/polessgu_rating/modules/grades/index.php" class="btn btn-primary btn-sm">Все оценки</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Студент</th>
                <th>Дисциплина</th>
                <th>Оценка</th>
                <th>Тип</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentGrades as $grade): ?>
            <tr>
                <td><?= formatDate($grade['grade_date']) ?></td>
                <td><?= htmlspecialchars($grade['last_name'] . ' ' . mb_substr($grade['first_name'], 0, 1) . '.' . mb_substr($grade['middle_name'], 0, 1) . '.') ?></td>
                <td><?= htmlspecialchars($grade['discipline_name']) ?></td>
                <td>
                    <span class="grade-badge" style="background: <?= getGradeColor($grade['grade']) ?>">
                        <?= $grade['grade'] ?>
                    </span>
                </td>
                <td>
                    <?php
                    $typeLabels = ['exam' => 'Экзамен', 'credit' => 'Зачет', 'current' => 'Текущий', 'reexam' => 'Пересдача'];
                    echo $typeLabels[$grade['control_type']] ?? $grade['control_type'];
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Рейтинг групп -->
<div class="table-container">
    <div class="table-header">
        <h2>🏅 Топ-5 групп по успеваемости</h2>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Место</th>
                <th>Группа</th>
                <th>Средний балл</th>
                <th>Уровень</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
            $index = 0;
            foreach ($groupRating as $group): 
            ?>
            <tr>
                <td><?= $medals[$index] ?? ($index + 1) ?></td>
                <td><strong><?= htmlspecialchars($group['group_name']) ?></strong></td>
                <td><strong><?= number_format($group['avg_grade'], 2) ?></strong></td>
                <td>
                    <?php if ($group['avg_grade'] >= 9): ?>
                        <span class="badge badge-success">Отлично</span>
                    <?php elseif ($group['avg_grade'] >= 7): ?>
                        <span class="badge badge-info">Хорошо</span>
                    <?php elseif ($group['avg_grade'] >= 5): ?>
                        <span class="badge badge-warning">Удовл.</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Низкий</span>
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

<?php include 'includes/footer.php'; ?>
