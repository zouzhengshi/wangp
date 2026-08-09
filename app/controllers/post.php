<?php
require_once __DIR__ . '/../config/config.php';

$siteName = htmlspecialchars(getSetting('site_name', '文件管理系统'));
$post = null;
$media = [];
$postError = '';
$adminUser = adminCheck();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT `id`, `title`, `author`, `content`, `created_at`
             FROM `posts` WHERE `id` = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch();
        if ($post) {
            $mediaStmt = $db->prepare(
                'SELECT `id`, `original_name`, `media_type`, `mime_type`, `file_size`
                 FROM `post_media` WHERE `post_id` = :post_id ORDER BY `id` ASC'
            );
            $mediaStmt->execute([':post_id' => $id]);
            $media = $mediaStmt->fetchAll();
        }
    } catch (PDOException $e) {
        $postError = '帖子功能尚未初始化，请先访问初始化页面。';
    }
}

if (!$post && $postError === '') {
    http_response_code(404);
    $postError = '帖子不存在或已被删除。';
}

require_once __DIR__ . '/../views/forum/detail.php';
