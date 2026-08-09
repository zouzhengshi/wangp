<?php
/**
 * 帖子媒体读取接口，支持图片/视频预览、附件下载、缓存和 Range 请求。
 */
require_once __DIR__ . '/../app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    jsonResponse(405, '仅支持 GET/HEAD 请求');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(400, '请提供有效的媒体 ID');
}

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM `post_media` WHERE `id` = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $media = $stmt->fetch();
    if (!$media) {
        jsonResponse(404, '媒体文件不存在');
    }

    $filePath = UPLOAD_DIR . basename($media['stored_name']);
    if (!is_file($filePath)) {
        jsonResponse(404, '媒体文件物理文件不存在');
    }

    $fileSize = (int) filesize($filePath);
    $fileMtime = (int) filemtime($filePath);
    $etag = sprintf('"post-media-%s-%d-%d"', md5($media['stored_name']), $fileSize, $fileMtime);
    header('Content-Type: ' . ($media['mime_type'] ?: 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff');
    if ($media['media_type'] === 'file' || isset($_GET['download'])) {
        $downloadName = basename((string) $media['original_name']);
        $downloadName = str_replace(["\\", "/", "\r", "\n", '"'], '', $downloadName);
        header("Content-Disposition: attachment; filename=download; filename*=UTF-8''" . rawurlencode($downloadName));
    }
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileMtime) . ' GMT');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Accept-Ranges: bytes');

    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        exit;
    }

    $rangeHeader = $_SERVER['HTTP_RANGE'] ?? '';
    if ($rangeHeader !== '' && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
        $start = $matches[1] !== '' ? (int) $matches[1] : 0;
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
        if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
            header('Content-Range: bytes */' . $fileSize);
            http_response_code(416);
            exit;
        }

        $length = $end - $start + 1;
        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
        header('Content-Length: ' . $length);
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            exit;
        }

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
    readfile($filePath);
    exit;
} catch (PDOException $e) {
    jsonResponse(500, '数据库错误');
}
