<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <span class="logo-icon">🎓</span>
            <span class="logo-text">ПолесГУ</span>
        </div>
        <p class="subtitle">Система оценки</p>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span>Главная</span>
                </a>
            </li>
            <li>
                <a href="/modules/students/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'students') ? 'active' : '' ?>">
                    <span class="nav-icon">👨‍🎓</span>
                    <span>Студенты</span>
                </a>
            </li>
            <li>
                <a href="/modules/disciplines/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'disciplines') ? 'active' : '' ?>">
                    <span class="nav-icon">📚</span>
                    <span>Дисциплины</span>
                </a>
            </li>
            <li>
                <a href="/modules/grades/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'grades') ? 'active' : '' ?>">
                    <span class="nav-icon">📝</span>
                    <span>Оценки</span>
                </a>
            </li>
            <li>
                <a href="/modules/analytics/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'analytics') ? 'active' : '' ?>">
                    <span class="nav-icon">📈</span>
                    <span>Аналитика</span>
                </a>
            </li>
            <?php if (isAdmin()): ?>
            <li>
                <a href="/modules/users/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'users') ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span>Пользователи</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <p class="version">v<?= APP_VERSION ?></p>
        <p class="copyright">Инженерный факультет<br>Полесский государственный университет</p>
    </div>
</aside>
