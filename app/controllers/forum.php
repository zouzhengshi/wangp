<?php
require_once __DIR__ . '/../config/config.php';

$siteName = htmlspecialchars(getSetting('site_name', '文件管理系统'));
$errorMessage = trim((string) ($_GET['error'] ?? ''));
$successMessage = isset($_GET['deleted']) ? '帖子已删除。' : '';
$postMaxFileSize = getPostMaxFileSize();
$adminUser = adminCheck();
$posts = [];

try {
    $db = getDB();
    $stmt = $db->query(
        'SELECT `id`, `title`, `author`, `content`, `created_at`
         FROM `posts`
         ORDER BY `created_at` DESC, `id` DESC
         LIMIT 100'
    );
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = '帖子功能尚未初始化，请先访问初始化页面。';
}

require_once __DIR__ . '/../views/forum/index.php';
