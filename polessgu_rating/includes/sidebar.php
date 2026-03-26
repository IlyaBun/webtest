        <!-- Боковая панель -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>ПолесГУ</span>
                </div>
                <p class="subtitle"><?php echo APP_FACULTY; ?></p>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i>
                            <span>Главная</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/modules/students/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'students') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-user-graduate"></i>
                            <span>Студенты</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/modules/disciplines/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'disciplines') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-book"></i>
                            <span>Дисциплины</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/modules/grades/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'grades') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i>
                            <span>Оценки</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/modules/analytics/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'analytics') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span>Аналитика</span>
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/modules/users/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-users-cog"></i>
                            <span>Пользователи</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <div class="user-mini">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <span class="name"><?php echo e(getCurrentUser()['full_name']); ?></span>
                        <span class="role"><?php echo getCurrentUser()['role'] === 'admin' ? 'Администратор' : 'Преподаватель'; ?></span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Основной контент -->
        <main class="main-wrapper">
