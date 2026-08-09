<?php
require_once __DIR__ . '/../app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: posts.php');
    exit;
}

$title = trim((string) ($_POST['title'] ?? ''));
$author = trim((string) ($_POST['author'] ?? '匿名'));
$content = trim((string) ($_POST['content'] ?? ''));

$length = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
};

$redirectError = static function (string $message): void {
    header('Location: posts.php?' . http_build_query(['error' => $message]));
    exit;
};

if ($title === '' || $length($title) > 200) {
    $redirectError('标题不能为空且不能超过 200 个字符。');
}
if ($author === '' || $length($author) > 64) {
    $redirectError('作者名称不能为空且不能超过 64 个字符。');
}
if ($content === '' || $length($content) > 100000) {
    $redirectError('正文不能为空且不能超过 100000 个字符。');
}

$allowedMedia = [
    'jpg'  => ['type' => 'image', 'mimes' => ['image/jpeg']],
    'jpeg' => ['type' => 'image', 'mimes' => ['image/jpeg']],
    'png'  => ['type' => 'image', 'mimes' => ['image/png']],
    'gif'  => ['type' => 'image', 'mimes' => ['image/gif']],
    'webp' => ['type' => 'image', 'mimes' => ['image/webp']],
    'bmp'  => ['type' => 'image', 'mimes' => ['image/bmp', 'image/x-ms-bmp']],
    'mp4'  => ['type' => 'video', 'mimes' => ['video/mp4']],
    'webm' => ['type' => 'video', 'mimes' => ['video/webm']],
    'mov'  => ['type' => 'video', 'mimes' => ['video/quicktime', 'video/x-quicktime']],
];
$allowedFileExtensions = array_map('strtolower', ALLOWED_EXTENSIONS);
$maxMediaFiles = 8;
$maxMediaSize = getPostMaxFileSize();
$mediaFiles = [];
$input = $_FILES['media'] ?? null;

if (is_array($input) && isset($input['name'])) {
    $names = is_array($input['name']) ? $input['name'] : [$input['name']];
    foreach ($names as $index => $originalName) {
        $error = is_array($input['error']) ? (int) ($input['error'][$index] ?? UPLOAD_ERR_NO_FILE) : (int) $input['error'];
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $redirectError('附件上传失败，请重试。');
        }

        $tmpName = is_array($input['tmp_name']) ? ($input['tmp_name'][$index] ?? '') : $input['tmp_name'];
        $fileSize = is_array($input['size']) ? (int) ($input['size'][$index] ?? 0) : (int) $input['size'];
        $extension = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
        $mimeType = '';
        if (function_exists('finfo_open') && $tmpName !== '') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = (string) finfo_file($finfo, $tmpName);
                finfo_close($finfo);
            }
        }
        if ($mimeType === '' && function_exists('mime_content_type')) {
            $mimeType = (string) mime_content_type($tmpName);
        }

        if (count($mediaFiles) >= $maxMediaFiles) {
            $redirectError('每篇帖子最多上传 8 个附件。');
        }
        if (!in_array($extension, $allowedFileExtensions, true) || $fileSize <= 0 || $fileSize > $maxMediaSize) {
            $redirectError('附件格式不支持，或超过当前发帖上传大小限制。');
        }

        $mediaType = $allowedMedia[$extension]['type'] ?? 'file';
        $normalizedMime = strtolower(trim($mimeType));
        if ($mediaType !== 'file') {
            // Windows/PHP fileinfo 可能把正常的视频容器识别为通用二进制类型，
            // 不能因此误拒绝；明显的文本或其他类型仍然拒绝。
            $allowedMimes = array_map('strtolower', $allowedMedia[$extension]['mimes']);
            $isGenericBinary = in_array($normalizedMime, ['application/octet-stream', 'binary/octet-stream'], true);
            $isVideoMime = $mediaType === 'video' && strpos($normalizedMime, 'video/') === 0;
            if ($normalizedMime !== ''
                && !in_array($normalizedMime, $allowedMimes, true)
                && !$isGenericBinary
                && !$isVideoMime) {
                $redirectError('检测到不匹配的媒体文件类型，请重新选择。');
            }
        }

        // 图片和视频使用扩展名对应的标准 MIME，普通附件保留检测到的 MIME。
        $mediaMimeType = $mediaType === 'file'
            ? ($normalizedMime !== '' ? $normalizedMime : 'application/octet-stream')
            : $allowedMedia[$extension]['mimes'][0];

        $mediaFiles[] = [
            'tmp_name' => $tmpName,
            'original_name' => basename((string) $originalName),
            'extension' => $extension,
            'mime_type' => $mediaMimeType,
            'media_type' => $mediaType,
            'file_size' => $fileSize,
        ];
    }
}

$storedPaths = [];
try {
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $db = getDB();
    $db->beginTransaction();
    $postStmt = $db->prepare(
        'INSERT INTO `posts` (`title`, `author`, `content`)
         VALUES (:title, :author, :content)'
    );
    $postStmt->execute([
        ':title' => $title,
        ':author' => $author,
        ':content' => $content,
    ]);
    $postId = (int) $db->lastInsertId();

    $mediaStmt = $db->prepare(
        'INSERT INTO `post_media`
         (`post_id`, `original_name`, `stored_name`, `media_type`, `mime_type`, `file_size`)
         VALUES (:post_id, :original_name, :stored_name, :media_type, :mime_type, :file_size)'
    );

    foreach ($mediaFiles as $media) {
        $storedName = bin2hex(random_bytes(16)) . '.' . $media['extension'];
        $destination = UPLOAD_DIR . $storedName;
        if (!move_uploaded_file($media['tmp_name'], $destination)) {
            throw new RuntimeException('媒体文件保存失败');
        }
        $storedPaths[] = $destination;
        $mediaStmt->execute([
            ':post_id' => $postId,
            ':original_name' => $media['original_name'],
            ':stored_name' => $storedName,
            ':media_type' => $media['media_type'],
            ':mime_type' => $media['mime_type'],
            ':file_size' => $media['file_size'],
        ]);
    }

    $db->commit();
    header('Location: post.php?id=' . $postId);
    exit;
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    foreach ($storedPaths as $storedPath) {
        if (is_file($storedPath)) {
            unlink($storedPath);
        }
    }
    $redirectError('发布失败，请确认数据库已经初始化并重试。');
}
