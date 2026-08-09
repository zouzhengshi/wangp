<?php
/**
 * 管理员登录
 */
require_once __DIR__ . '/../config/config.php';

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
require_once __DIR__ . '/../views/admin/login.php';
