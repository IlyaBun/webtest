<?php
/**
 * Страница входа в систему
 * Система оценки успеваемости ПолесГУ
 */

// Старт сессии
session_start();

// Подключение конфигурации
require_once __DIR__ . '/../../config/app_config.php';

// Если уже авторизован - перенаправляем на главную
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        // Поиск пользователя (без хэширования, как требуется)
        $user = dbFetchOne(
            "SELECT id, username, password, full_name, email, role 
             FROM users 
             WHERE username = ? AND password = ?",
            [$username, $password]
        );
        
        if ($user) {
            // Успешный вход
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Генерация CSRF токена
            generateCsrfToken();
            
            header('Location: ' . BASE_URL . '/index.php');
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
    <title>Вход в систему - <?php echo APP_NAME; ?></title>
    
    <!-- Подключение шрифтов -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Иконки Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            padding: 40px 30px;
            text-align: center;
            color: #fff;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1e293b;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
        }
        
        .demo-credentials {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
        }
        
        .demo-credentials h4 {
            font-size: 14px;
            color: #475569;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .demo-credentials ul {
            list-style: none;
            font-size: 13px;
            color: #64748b;
        }
        
        .demo-credentials li {
            padding: 6px 0;
            display: flex;
            gap: 8px;
        }
        
        .demo-credentials code {
            background: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #4f46e5;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #94a3b8;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>ПолесГУ</h1>
            <p>Инженерный факультет</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo e($error); ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Логин</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="Введите логин"
                               value="<?php echo e($_POST['username'] ?? ''); ?>"
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Пароль</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Введите пароль"
                               required>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Войти в систему
                </button>
            </form>
            
            <div class="demo-credentials">
                <h4><i class="fas fa-info-circle"></i> Тестовые учетные записи:</h4>
                <ul>
                    <li><strong>Администратор:</strong> <code>admin</code> / <code>admin123</code></li>
                    <li><strong>Преподаватель:</strong> <code>petrov</code> / <code>petrov123</code></li>
                    <li><strong>Преподаватель:</strong> <code>sidorova</code> / <code>sidorova123</code></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Система оценки успеваемости &copy; <?php echo date('Y'); ?> ПолесГУ</p>
    </div>
</body>
</html>
