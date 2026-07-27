<?php
/**
 * 创建文件夹
 * POST /mkdir.php
 * Body: {"name": "新文件夹"}
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '仅支持 POST 请求');
}

$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');
$path = trim($input['path'] ?? '');

if ($name === '') {
    jsonResponse(400, '请输入文件夹名称');
}

// 净化文件夹名
$name = preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);

try {
    $db = getDB();

    // 检查是否已存在
    $stmt = $db->prepare("SELECT COUNT(*) FROM `files` WHERE `filepath` = :fp");
    $stmt->execute([':fp' => $path . $name . '/']);
    if ((int) $stmt->fetchColumn() > 0) {
        jsonResponse(400, '文件夹已存在');
    }

    // 创建占位文件
    $storedName = bin2hex(random_bytes(16)) . '.folder';
    $placeholderPath = UPLOAD_DIR . $storedName;
    file_put_contents($placeholderPath, '');

    // 写入数据库
    $db->prepare("INSERT INTO `files` (`filename`, `stored_name`, `file_size`, `file_type`, `file_ext`, `filepath`)
                  VALUES (:fn, :sn, 0, 'application/x-folder', 'folder', :fp)")
       ->execute([
           ':fn' => '.placeholder',
           ':sn' => $storedName,
           ':fp' => $path . $name . '/',
       ]);

    jsonResponse(200, '文件夹创建成功', [
        'name'     => $name,
        'fullpath' => $path . $name . '/',
    ]);

} catch (PDOException $e) {
    jsonResponse(500, '数据库错误');
}
