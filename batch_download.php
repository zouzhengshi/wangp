<?php
/**
 * 批量打包下载
 * POST /batch_download.php
 * Body: {"ids": [1, 2, 3]}
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '仅支持 POST 请求');
}

if (!isDownloadAllowed()) {
    jsonResponse(403, '下载功能已被管理员关闭');
}

// 支持 JSON POST 和 form POST
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['ids'])) {
    $ids = array_map('intval', $input['ids']);
} else {
    $rawIds = $_POST['ids'] ?? '[]';
    $ids = array_map('intval', json_decode($rawIds, true) ?: []);
}

if (empty($ids)) {
    jsonResponse(400, '请选择要下载的文件');
}

try {
    $db = getDB();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM `files` WHERE `id` IN ($placeholders)");
    $stmt->execute($ids);
    $files = $stmt->fetchAll();

    if (empty($files)) {
        jsonResponse(404, '没有找到文件');
    }

    // 创建临时 ZIP
    $zipName = 'files_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . uniqid('batch_') . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        jsonResponse(500, '无法创建压缩包');
    }

    foreach ($files as $file) {
        $filePath = UPLOAD_DIR . basename($file['stored_name']);
        if (file_exists($filePath)) {
            // 用 filepath + filename 作为 ZIP 内的路径保留目录结构
            $innerName = $file['filepath'] . $file['filename'];
            $zip->addFile($filePath, $innerName);
        }
    }
    $zip->close();

    // 输出 ZIP
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . rawurlencode($zipName) . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache');

    readfile($zipPath);
    unlink($zipPath);
    exit;

} catch (PDOException $e) {
    jsonResponse(500, '数据库错误');
}
