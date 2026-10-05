<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id, login, password FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    
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
            background: #fafafa;
            padding: 20px;
        }
        
        .login-container { width: 100%; max-width: 420px; }
        
        .login-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.06);
            border: 1px solid #f1f5f9;
        }
        
        /* Логотип */
        .logo-section { text-align: center; margin-bottom: 32px; }
        
        .logo-img {
            width: 72px;
            height: 72px;
            display: block;
            margin: 0 auto 20px auto;
        }
        
        .logo-section h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        
        .logo-section p {
            font-size: 13px;
            color: #94a3b8;
        }
        
        .color-strip {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin: 24px 0;
        }
        
        .color-strip span {
            width: 32px;
            height: 3px;
            border-radius: 3px;
        }
        
        .color-strip .c-blue   { background: #3b82f6; }
        .color-strip .c-green  { background: #10b981; }
        .color-strip .c-red    { background: #ef4444; }
        .color-strip .c-yellow { background: #f59e0b; }
        
            .login-title {
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            color: #64748b;
            margin-bottom: 28px;
        }
        
        .error-message {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        

        .form-group { margin-bottom: 18px; }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
            background: #ffffff;
            color: #1e293b;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.08);
        }
        
        .form-group input::placeholder {
            color: #cbd5e1;
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: #1e293b;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            margin-top: 8px;
        }
        
        .login-button:hover {
            background: #0f172a;
        }
        
        .login-button:active {
            transform: scale(0.99);
        }
        
        .hint {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.6;
        }
        
        .hint strong {
            color: #475569;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            
            <div class="logo-section">
                <img src="images/logo.png" alt="Логотип ДЮЦ" class="logo-img">
                <h1>Журнал педагогов</h1>
                <p>Дворец детского творчества г. Белоярский</p>
            </div>
            
            <div class="color-strip">
                <span class="c-blue"></span>
                <span class="c-green"></span>
                <span class="c-red"></span>
                <span class="c-yellow"></span>
            </div>
            
            <div class="login-title">Вход в систему</div>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" placeholder="Введите логин" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" placeholder="Введите пароль" required>
                </div>
                <button type="submit" class="login-button">Войти</button>
            </form>
            
            <div class="hint">
                Демо-доступ: <strong>admin</strong> / <strong>123</strong>
            </div>
            
        </div>
    </div>
</body>
</html>
