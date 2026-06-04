<?php
session_start();

// Если уже авторизован — отправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Временная простая проверка (без БД)
    if ($login === 'admin' && $password === '123') {
        $_SESSION['user_id'] = 1;
        $_SESSION['login'] = 'admin';
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
        width: 72px;
        height: 72px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 36px;
        }

        .logo-section h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .logo-section p {
            font-size: 14px;
            color: #64748b;
        }

        .login-title {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }

        .error-message {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: #94a3b8;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px 10px 22px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-group input:focus + .input-icon {
            color: #3b82f6;
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .hint {
            text-align: center;
            margin-top: 24px;
            padding: 16px;
            background: #f1f5f9;
            border-radius: 12px;
            font-size: 13px;
            color: #64748b;
        }

        .hint strong {
            color: #3b82f6;
            font-weight: 600;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 36px 24px;
            }

            .logo-icon {
                width: 64px;
                height: 64px;
                font-size: 32px;
            }

            .logo-section h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">📋</div>
                <h1>Журнал педагогов</h1>
                <p>Дворец детского творчества г. Белоярский</p>
            </div>

            <div class="login-title">Вход в систему</div>

            <?php if ($error): ?>
                <div class="error-message">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <div class="input-wrapper">
                        <input type="text" id="login" name="login" placeholder="Введите логин" required autocomplete="username">
                        <span class="input-icon"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Введите пароль" required autocomplete="current-password">
                        <span class="input-icon"></span>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    Войти в систему
                </button>
            </form>

            <div class="hint">
                🔑 Демо-доступ:<br>
                <strong>Логин:</strong> admin | <strong>Пароль:</strong> 123
            </div>
        </div>

        <div class="footer">
            © 2026 Дворец детского (юношеского) творчества г. Белоярский
        </div>
    </div>
</body>
</html>