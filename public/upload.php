<?php
/**
 * 文件上传处理
 * POST /upload.php
 * 支持单文件和多文件上传
 */
require_once __DIR__ . '/../app/config/config.php';

// 仅接受 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '仅支持 POST 请求');
}

// 检查是否有文件
if (empty($_FILES)) {
    jsonResponse(400, '没有选择文件');
}

$uploadedFiles = [];
$errors = [];

// 规范化 $_FILES 数组(支持单文件和多文件上传)
function normalizeFiles(): array {
    $result = [];

    // 前端用 FormData append('files[]', f),PHP 收到 $_FILES['files']
    $input = $_FILES['files'] ?? $_FILES;

    // 检查是否是扁平的单文件结构: ['name'=>'x', 'type'=>'y', ...]
    if (isset($input['name']) && !is_array($input['name'])) {
        return [$input];
    }

    // 多文件结构: ['name'=>[0=>'a.jpg',...], 'type'=>[...], ...]
    if (isset($input['name']) && is_array($input['name'])) {
        $count = count($input['name']);
        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'name'     => $input['name'][$i],
                'type'     => $input['type'][$i] ?? '',
                'tmp_name' => $input['tmp_name'][$i],
                'error'    => $input['error'][$i],
                'size'     => $input['size'][$i],
            ];
        }
        return $result;
    }

    return $result;
}

$fileList = normalizeFiles();

// 确保上传目录存在
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$db = getDB();
$paths = $_POST['paths'] ?? [];

foreach ($fileList as $idx => $file) {
    try {
        // 检查上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = '文件超过大小限制';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = '文件不完整';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = '没有选择文件';
                    break;
                default:
                    $errorMsg = '上传失败,错误码: ' . $file['error'];
                    break;
            }
            $errors[] = $file['name'] . ': ' . $errorMsg;
            continue;
        }

        // 检查文件大小
        if ($file['size'] > getMaxFileSize()) {
            $errors[] = $file['name'] . ': 文件过大(最大 ' . formatSize(getMaxFileSize()) . ')';
            continue;
        }

        // 获取扩展名并检查
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === '' || $ext === null) {
            $ext = 'unknown';
        }

        if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
            $errors[] = $file['name'] . ': 不支持的文件格式 .' . $ext;
            continue;
        }

        // 提取路径：从 paths[] 字段获取文件夹结构
        $originalName = $file['name'];
        $filePath = '';
        if (!empty($paths[$idx])) {
            $relPath = $paths[$idx];  // e.g. "PETG多色/img.jpg"
            $parts = explode('/', $relPath);
            $originalName = array_pop($parts);  // 纯文件名
            if (!empty($parts)) {
                $filePath = implode('/', $parts) . '/';  // "PETG多色/"
            }
        }

        // 生成唯一存储名
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = UPLOAD_DIR . $storedName;

        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $errors[] = $file['name'] . ': 文件保存失败';
            continue;
        }

        // 写入数据库
        $stmt = $db->prepare(
            'INSERT INTO `files` (`filename`, `stored_name`, `file_size`, `file_type`, `file_ext`, `filepath`)
             VALUES (:filename, :stored_name, :file_size, :file_type, :file_ext, :filepath)'
        );
        $stmt->execute([
            ':filename'    => $originalName,
            ':stored_name' => $storedName,
            ':file_size'   => $file['size'],
            ':file_type'   => $file['type'] ?: mime_content_type($destPath) ?: 'application/octet-stream',
            ':file_ext'    => $ext,
            ':filepath'    => $filePath,
        ]);

        $uploadedFiles[] = [
            'id'          => (int) $db->lastInsertId(),
            'filename'    => $file['name'],
            'size'        => $file['size'],
            'size_format' => formatSize($file['size']),
            'ext'         => $ext,
        ];

    } catch (PDOException $e) {
        // 数据库错误,删除已存储的文件
        if (isset($destPath) && file_exists($destPath)) {
            unlink($destPath);
        }
        $errors[] = $file['name'] . ': 数据库错误';
    } catch (\Throwable $e) {
        if (isset($destPath) && file_exists($destPath)) {
            unlink($destPath);
        }
        $errors[] = $file['name'] . ': ' . $e->getMessage();
    }
}

// 返回结果
if (empty($uploadedFiles) && !empty($errors)) {
    jsonResponse(400, '所有文件上传失败', ['errors' => $errors]);
}

jsonResponse(200, '上传完成', [
    'uploaded' => $uploadedFiles,
    'errors'   => $errors,
]);
