<?php
/**
 * 文件下载
 * GET /download.php?id=1
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, '仅支持 GET 请求');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(400, '请提供有效的文件 ID');
}

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM `files` WHERE `id` = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $file = $stmt->fetch();

    if (!$file) {
        jsonResponse(404, '文件不存在或已被删除');
    }

    $filePath = UPLOAD_DIR . $file['stored_name'];

    if (!file_exists($filePath)) {
        jsonResponse(404, '文件物理文件不存在');
    }

    // 更新下载次数
    $db->prepare('UPDATE `files` SET `download_count` = `download_count` + 1 WHERE `id` = :id')
       ->execute([':id' => $id]);

    // 输出文件
    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . rawurlencode($file['filename']) . '"');
    header('Content-Length: ' . $file['file_size']);
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // 大文件分块读取
    $handle = fopen($filePath, 'rb');
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
    exit;

} catch (PDOException $e) {
    jsonResponse(500, '数据库错误');
}
