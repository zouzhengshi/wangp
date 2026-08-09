<?php
/**
 * 文件管理系统 - 主页面
 */
require_once __DIR__ . '/../config/config.php';

// 检查数据库连接，如果表不存在则提示初始化
$dbReady = true;
$dbError = '';
try {
    $db = getDB();
    $db->query('SELECT 1 FROM `files` LIMIT 1');
} catch (\Throwable $e) {
    $dbReady = false;
    $dbError = $e->getMessage();
}
require_once __DIR__ . '/../views/file-manager.php';
