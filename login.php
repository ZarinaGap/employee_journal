<?php
session_start();

// Если уже авторизован — отправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Ищем пользователя в БД
    $stmt = $pdo->prepare("SELECT id, login, password FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    
    // Проверяем пароль через bcrypt
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - Журнал педагогов ДЮЦ</title>
    <link rel="icon" type="image/png" href="images/Russia.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .login-container { width: 100%; max-width: 420px; }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .logo-section { text-align: center; margin-bottom: 32px; }
        .logo-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; font-size: 36px;
        }
        .logo-section h1 { font-size: 26px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
        .logo-section p { font-size: 14px; color: #64748b; }
        .login-title {
            text-align: center; font-size: 18px; font-weight: 600;
            color: #475569; margin-bottom: 32px;
            padding-bottom: 20px; border-bottom: 2px solid #f1f5f9;
        }
        .error-message {
            background: #fef2f2; border-left: 4px solid #ef4444;
            color: #dc2626; padding: 12px 16px; border-radius: 8px;
            margin-bottom: 24px; font-size: 14px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 13px; font-weight: 600;
            color: #475569; margin-bottom: 8px; text-transform: uppercase;
        }
        .form-group input {
            width: 100%; padding: 12px 14px;
            border: 1px solid #e2e8f0; border-radius: 12px;
            font-size: 15px; font-family: inherit;
            transition: all 0.3s; background: #f8fafc;
        }
        .form-group input:focus {
            outline: none; border-color: #3b82f6;
            background: white; box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        }
        .login-button {
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            transition: all 0.3s;
        }
        .login-button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59,130,246,0.4); }
        .hint {
            text-align: center; margin-top: 24px; padding: 16px;
            background: #f1f5f9; border-radius: 12px;
            font-size: 13px; color: #64748b;
        }
        .hint strong { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
    <img src="images/logo.png" alt="Логотип ДЮЦ" style="width:70px; height:70px; border-radius:10%; display:block; margin:0 auto 25px auto;">
    <h1>Журнал педагогов</h1>
    <p>Дворец детского творчества г. Белоярский</p>
</div>
            <div class="login-title">Вход в систему</div>
            <?php if ($error): ?>
                <div class="error-message">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="login">👤 Логин</label>
                    <input type="text" id="login" name="login" placeholder="Введите логин" required>
                </div>
                <div class="form-group">
                    <label for="password">🔒 Пароль</label>
                    <input type="password" id="password" name="password" placeholder="Введите пароль" required>
                </div>
                <button type="submit" class="login-button">Войти в систему</button>
            </form>
            <div class="hint">
                🔑 Демо-доступ:<br>
                <strong>Логин:</strong> admin | <strong>Пароль:</strong> 123
            </div>
        </div>
    </div>
</body>
</html>