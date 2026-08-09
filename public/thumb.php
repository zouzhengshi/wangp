<?php
/**
 * 图片缩略图接口。
 * 网格列表使用缩略图，原图仍由 view.php 提供预览。
 */
require_once __DIR__ . '/../app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    jsonResponse(405, '仅支持 GET/HEAD 请求');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$size = min(800, max(160, (int) ($_GET['size'] ?? 480)));
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

    $filePath = UPLOAD_DIR . basename($file['stored_name']);
    if (!file_exists($filePath)) {
        jsonResponse(404, '文件物理文件不存在');
    }

    $ext = strtolower($file['file_ext']);
    $supported = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    if (!in_array($ext, $supported, true) || !function_exists('imagecreatetruecolor')) {
        header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Content-Length: ' . filesize($filePath));
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            readfile($filePath);
        }
        exit;
    }

    $mtime = (int) filemtime($filePath);
    $etag = sprintf('"thumb-%s-%d-%d-%d"', md5($file['stored_name']), filesize($filePath), $mtime, $size);
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Vary: Accept-Encoding');

    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        exit;
    }

    $loaders = [
        'jpg'  => 'imagecreatefromjpeg',
        'jpeg' => 'imagecreatefromjpeg',
        'png'  => 'imagecreatefrompng',
        'gif'  => 'imagecreatefromgif',
        'bmp'  => 'imagecreatefrombmp',
        'webp' => 'imagecreatefromwebp',
    ];
    $loader = $loaders[$ext];
    if (!function_exists($loader)) {
        header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($filePath));
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            readfile($filePath);
        }
        exit;
    }

    $source = @$loader($filePath);
    if (!$source) {
        header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($filePath));
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            readfile($filePath);
        }
        exit;
    }

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $scale = min(1, $size / max($sourceWidth, $sourceHeight));
    $targetWidth = max(1, (int) round($sourceWidth * $scale));
    $targetHeight = max(1, (int) round($sourceHeight * $scale));

    $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
    $background = imagecolorallocate($thumbnail, 255, 255, 255);
    imagefill($thumbnail, 0, 0, $background);
    imagecopyresampled(
        $thumbnail,
        $source,
        0,
        0,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $sourceWidth,
        $sourceHeight
    );

    ob_start();
    imagejpeg($thumbnail, null, 82);
    $output = ob_get_clean();
    imagedestroy($thumbnail);
    imagedestroy($source);

    header('Content-Type: image/jpeg');
    header('Content-Length: ' . strlen($output));
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        echo $output;
    }
} catch (PDOException $e) {
    jsonResponse(500, '数据库错误');
}
