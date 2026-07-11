<?php
/**
 * 文件在线预览（图片 / 视频 / 音频 / PDF）
 * GET /view.php?id=1
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, '仅支持 GET 请求');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(400, '请提供有效的文件 ID');
}

// 检查是否允许下载（预览即文件访问，同样受控）
if (!isDownloadAllowed()) {
    jsonResponse(403, '文件访问已被管理员关闭');
}

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM `files` WHERE `id` = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $file = $stmt->fetch();

    if (!$file) {
        jsonResponse(404, '文件不存在或已被删除');
    }

    $filePath = UPLOAD_DIR . basename($file['stored_name']);

    if (!file_exists($filePath)) {
        jsonResponse(404, '文件物理文件不存在');
    }

    // 可预览的 MIME 类型映射
    $ext = strtolower($file['file_ext']);
    $previewable = [
        // 图片
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'bmp' => 'image/bmp', 'webp' => 'image/webp',
        'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        // 视频
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg',
        // 音频
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg',
        'flac' => 'audio/flac', 'aac' => 'audio/aac', 'm4a' => 'audio/mp4',
        // PDF
        'pdf' => 'application/pdf',
        // 文本
        'txt' => 'text/plain', 'md' => 'text/markdown',
        'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript',
        'json' => 'application/json', 'xml' => 'application/xml',
    ];

    $contentType = $previewable[$ext] ?? $file['file_type'] ?? 'application/octet-stream';

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . $file['file_size']);
    header('Cache-Control: public, max-age=86400');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

    // 不设置 Content-Disposition: attachment，浏览器会尝试预览

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
