<?php
/**
 * 管理员登出
 */
require_once __DIR__ . '/../config.php';
session_start();
unset($_SESSION['admin_user']);
session_destroy();
header('Location: login.php');
exit;
