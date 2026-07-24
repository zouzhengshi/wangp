<?php
/**
 * 文件删除
 * POST /delete.php
 * Body: {"id": 1}  或  {"ids": [1, 2, 3]}
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '仅支持 POST 请求');
}

$input = json_decode(file_get_contents('php://input'), true);

// 删除整个文件夹（通过 filepath 前缀匹配）
if (isset($input['folder_path']) && is_string($input['folder_path']) && $input['folder_path'] !== '') {
    $folderPath = $input['folder_path'];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT `id`, `stored_name` FROM `files` WHERE `filepath` LIKE :fp");
        $stmt->execute([':fp' => $folderPath . '%']);
        $files = $stmt->fetchAll();

        foreach ($files as $file) {
            $fp = UPLOAD_DIR . basename($file['stored_name']);
            if (file_exists($fp)) unlink($fp);
        }
        $db->prepare("DELETE FROM `files` WHERE `filepath` LIKE :fp2")->execute([':fp2' => $folderPath . '%']);

        jsonResponse(200, '文件夹已删除', ['deleted_count' => count($files)]);
    } catch (PDOException $e) {
        jsonResponse(500, '数据库错误');
    }
}

$ids = [];
if (isset($input['ids']) && is_array($input['ids'])) {
    $ids = array_map('intval', $input['ids']);
} elseif (isset($input['id']) && (int) $input['id'] > 0) {
    $ids = [(int) $input['id']];
}

if (empty($ids)) {
    jsonResponse(400, '请提供要删除的文件 ID');
}

try {
    $db = getDB();

    // 查询要删除的文件
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT `id`, `stored_name` FROM `files` WHERE `id` IN ($placeholders)");
    $stmt->execute($ids);
    $files = $stmt->fetchAll();

    if (empty($files)) {
        jsonResponse(404, '没有找到要删除的文件');
    }

    $deleted = [];
    foreach ($files as $file) {
        $filePath = UPLOAD_DIR . basename($file['stored_name']);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $deleted[] = (int) $file['id'];
    }

    // 从数据库删除记录
    $db->prepare("DELETE FROM `files` WHERE `id` IN ($placeholders)")->execute($deleted);

    jsonResponse(200, '删除成功', [
        'deleted_count' => count($deleted),
        'deleted_ids'   => $deleted,
    ]);

} catch (PDOException $e) {
    jsonResponse(500, '数据库错误');
}
