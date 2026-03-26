<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h1><?= htmlspecialchars($pageTitle ?? 'Главная') ?></h1>
                </div>
                <div class="header-right">
                    <span class="user-info">
                        <span class="user-name"><?= htmlspecialchars(getCurrentUser()['full_name']) ?></span>
                        <span class="user-role badge badge-<?= getCurrentUser()['role'] ?>">
                            <?= getCurrentUser()['role'] === 'admin' ? 'Администратор' : 'Преподаватель' ?>
                        </span>
                    </span>
                    <a href="/modules/auth/logout.php" class="btn btn-outline btn-sm">Выход</a>
                </div>
            </header>
            
            <div class="content-wrapper">
