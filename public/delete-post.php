<?php
require_once __DIR__ . '/../app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: posts.php');
    exit;
}

if (adminCheck() === null) {
    header('Location: admin/login.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    header('Location: posts.php?error=' . rawurlencode('无效的帖子 ID。'));
    exit;
}

$mediaFiles = [];
try {
    $db = getDB();
    $mediaStmt = $db->prepare('SELECT `stored_name` FROM `post_media` WHERE `post_id` = :post_id');
    $mediaStmt->execute([':post_id' => $id]);
    $mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_COLUMN);

    $db->beginTransaction();
    $db->prepare('DELETE FROM `post_media` WHERE `post_id` = :post_id')->execute([':post_id' => $id]);
    $deleteStmt = $db->prepare('DELETE FROM `posts` WHERE `id` = :id');
    $deleteStmt->execute([':id' => $id]);
    if ($deleteStmt->rowCount() === 0) {
        throw new RuntimeException('帖子不存在');
    }
    $db->commit();

    foreach ($mediaFiles as $storedName) {
        $path = UPLOAD_DIR . basename($storedName);
        if (is_file($path)) {
            unlink($path);
        }
    }
    header('Location: posts.php?deleted=1');
    exit;
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    header('Location: posts.php?error=' . rawurlencode('删除帖子失败，请稍后重试。'));
    exit;
}
