<?php
/**
 * 文件在线预览（图片 / 视频 / 音频 / PDF）
 * GET /view.php?id=1
 * 支持 ETag / 304 缓存 + Range 断点续传
 */
require_once __DIR__ . '/../app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    jsonResponse(405, '仅支持 GET/HEAD 请求');
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

    // 文件信息
    $fileSize = (int) $file['file_size'];
    $fileMtime = filemtime($filePath);

    // ETag: 基于 ID + mtime + size（文件内容变更则 ETag 变化）
    $etag = sprintf('"%s-%d-%d"', md5($file['stored_name']), $fileSize, $fileMtime);

    // 可预览的 MIME 类型映射
    $ext = strtolower($file['file_ext']);
    $previewable = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'bmp' => 'image/bmp', 'webp' => 'image/webp',
        'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg',
        'flac' => 'audio/flac', 'aac' => 'audio/aac', 'm4a' => 'audio/mp4',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain', 'md' => 'text/markdown',
        'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript',
        'json' => 'application/json', 'xml' => 'application/xml',
    ];
    $contentType = $previewable[$ext] ?? $file['file_type'] ?? 'application/octet-stream';

    // === 缓存头 ===
    header('Content-Type: ' . $contentType);
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileMtime) . ' GMT');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Accept-Ranges: bytes');

    // === 304 Not Modified ===
    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($ifNoneMatch === $etag) {
        http_response_code(304);
        exit;
    }

    // === Range 请求（视频拖动 / 断点续传） ===
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

    // === 完整响应 ===
    header('Content-Length: ' . $fileSize);

    // HEAD 请求不输出内容
    if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
        exit;
    }

    // 清理输出缓冲
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
