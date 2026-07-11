<?php
/**
 * 文件下载
 * GET /download.php?id=1
 * 支持 ETag / 304 / Range 断点续传
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    jsonResponse(405, '仅支持 GET/HEAD 请求');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(400, '请提供有效的文件 ID');
}

// 检查是否允许下载
if (!isDownloadAllowed()) {
    jsonResponse(403, '下载功能已被管理员关闭');
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

    $fileSize = (int) $file['file_size'];
    $fileMtime = filemtime($filePath);
    $etag = sprintf('"%s-%d-%d"', md5($file['stored_name']), $fileSize, $fileMtime);

    // 更新下载次数
    $db->prepare('UPDATE `files` SET `download_count` = `download_count` + 1 WHERE `id` = :id')
       ->execute([':id' => $id]);

    // 响应头
    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . rawurlencode($file['filename']) . '"');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileMtime) . ' GMT');
    header('Accept-Ranges: bytes');

    // 304
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        exit;
    }

    // Range 请求
    $rangeHeader = $_SERVER['HTTP_RANGE'] ?? '';
    if ($rangeHeader !== '' && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
        $start = $matches[1] !== '' ? (int) $matches[1] : 0;
        $end   = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
        if ($start >= $fileSize || $end >= $fileSize) {
            header('Content-Range: bytes */' . $fileSize);
            http_response_code(416);
            exit;
        }
        $length = $end - $start + 1;
        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
        header('Content-Length: ' . $length);
        $handle = fopen($filePath, 'rb');
        fseek($handle, $start);
        $remaining = $length;
        while ($remaining > 0 && !feof($handle)) {
            $chunk = fread($handle, min(8192, $remaining));
            echo $chunk;
            $remaining -= strlen($chunk);
            flush();
        }
        fclose($handle);
        exit;
    }

    header('Content-Length: ' . $fileSize);

    if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
        exit;
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

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
