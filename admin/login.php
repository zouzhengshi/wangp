<?php
/**
 * 管理员登录
 */
require_once __DIR__ . '/../config.php';

// 已登录则跳转
if (adminCheck() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '请输入用户名和密码';
    } else {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM `admin` WHERE `username` = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['admin_user'] = $admin['username'];
            header('Location: index.php');
            exit;
        }
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: #f0f2f5;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,.08);
            padding: 36px; width: 380px; max-width: 92vw;
        }
        .login-card h1 { font-size: 20px; text-align: center; margin-bottom: 4px; color: #1e293b; }
        .login-card .sub { text-align: center; color: #94a3b8; font-size: 13px; margin-bottom: 28px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-group input {
            width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; font-family: inherit; outline: none; transition: border .2s;
        }
        .form-group input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
        .btn {
            width: 100%; padding: 11px; background: #4f46e5; color: #fff; border: none;
            border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: background .2s;
        }
        .btn:hover { background: #4338ca; }
        .error {
            background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
            padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;
        }
        .back-link { display: block; text-align: center; margin-top: 16px; color: #94a3b8; font-size: 13px; text-decoration: none; }
        .back-link:hover { color: #4f46e5; }
    </style>
</head>
<body>
<div class="login-card">
    <h1>🔐 管理员登录</h1>
    <p class="sub">文件管理系统后台</p>

    <?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autofocus required>
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">登 录</button>
    </form>
    <a href="../index.php" class="back-link">← 返回文件管理</a>
</div>
</body>
</html>
