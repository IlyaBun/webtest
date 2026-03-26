<?php
require_once __DIR__ . '/../config/app_config.php';

// Проверка авторизации
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: /index.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo-large">
                    <span class="logo-icon">🎓</span>
                    <h1>ПолесГУ</h1>
                </div>
                <p class="subtitle">Система оценки успеваемости студентов<br>инженерного факультета</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Логин</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Введите логин" value="<?= htmlspecialchars($username ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Введите пароль">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Войти</button>
            </form>
            
            <div class="login-info">
                <p><strong>Тестовые учетные записи:</strong></p>
                <ul>
                    <li>Администратор: <code>admin</code> / <code>admin123</code></li>
                    <li>Преподаватель: <code>petrov</code> / <code>petrov123</code></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
