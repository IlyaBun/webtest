<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <link rel="stylesheet" href="/polessgu_rating/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Боковая панель -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🎓 ПолесГУ</h2>
                <p>Инженерный факультет</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/polessgu_rating/index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span>Главная</span>
                </a>
                
                <a href="/polessgu_rating/modules/students/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'students') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">👨‍🎓</span>
                    <span>Студенты</span>
                </a>
                
                <a href="/polessgu_rating/modules/disciplines/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'disciplines') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">📚</span>
                    <span>Дисциплины</span>
                </a>
                
                <a href="/polessgu_rating/modules/grades/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'grades') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">📝</span>
                    <span>Оценки</span>
                </a>
                
                <a href="/polessgu_rating/modules/analytics/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'analytics') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">📈</span>
                    <span>Аналитика</span>
                </a>
                
                <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
                <a href="/polessgu_rating/modules/users/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span>Пользователи</span>
                </a>
                <?php endif; ?>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?= mb_substr($_SESSION['full_name'], 0, 1) ?></div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                        <div class="user-role"><?= getRoleName($_SESSION['user_role']) ?></div>
                    </div>
                </div>
                <a href="/polessgu_rating/modules/auth/logout.php" class="btn-logout">
                    <span>🚪</span> Выход
                </a>
            </div>
        </aside>
        
        <!-- Основной контент -->
        <main class="main-content">
            <!-- Верхняя панель -->
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                    <h1><?= $pageTitle ?? 'Панель управления' ?></h1>
                </div>
                <div class="header-right">
                    <div class="date-display"><?= date('d.m.Y') ?></div>
                </div>
            </header>
            
            <!-- Содержимое страницы -->
            <div class="content">
