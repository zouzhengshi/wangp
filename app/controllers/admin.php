<?php
/**
 * 后台管理 - 系统设置 + 修改密码
 */
require_once __DIR__ . '/../config/config.php';
$adminUser = adminRequire();

$saved = '';
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $maxSize = (int) ($_POST['max_file_size'] ?? 0);
        $postMaxSize = (int) ($_POST['post_max_file_size'] ?? 0);
        if ($maxSize > 0 && $maxSize <= 10240 && $postMaxSize > 0 && $postMaxSize <= 10240) {
            // 保存时转换为字节
            saveSetting('max_file_size', (string) ($maxSize * 1048576));
            saveSetting('post_max_file_size', (string) ($postMaxSize * 1048576));
            saveSetting('allow_download', isset($_POST['allow_download']) ? '1' : '0');
            saveSetting('site_name', trim($_POST['site_name'] ?? '文件管理系统'));
            $saved = '设置已保存';
        } else {
            $error = '网盘和发帖上传大小必须设置为 1–10240MB';
        }

    } elseif ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPassword2 = $_POST['new_password2'] ?? '';

        if ($newPassword !== $newPassword2) {
            $error = '两次新密码不一致';
        } elseif (strlen($newPassword) < 6) {
            $error = '新密码至少 6 位';
        } else {
            $db = getDB();
            $stmt = $db->prepare('SELECT `password` FROM `admin` WHERE `username` = :u LIMIT 1');
            $stmt->execute([':u' => $adminUser]);
            $admin = $stmt->fetch();

            if (!$admin || !password_verify($oldPassword, $admin['password'])) {
                $error = '原密码错误';
            } else {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $db->prepare('UPDATE `admin` SET `password` = :p WHERE `username` = :u')
                   ->execute([':p' => $hash, ':u' => $adminUser]);
                $saved = '密码修改成功，下次登录请使用新密码';
            }
        }
    }
}

// 当前设置
$maxFileSize = getMaxFileSize();
// 兼容旧数据：如果值太小（< 1MB），视为异常，回退默认值
if ($maxFileSize < 1048576) {
    $maxFileSize = DEFAULT_MAX_FILE_SIZE;
}
$allowDownload = isDownloadAllowed();
$siteName = htmlspecialchars(getSetting('site_name', '文件管理系统'));
$maxSizeMB = round($maxFileSize / 1048576, 0);
$postMaxFileSize = getPostMaxFileSize();
$postMaxSizeMB = round($postMaxFileSize / 1048576, 0);

// 总览统计
try {
    $db = getDB();
    $totalFiles = $db->query('SELECT COUNT(*) FROM `files`')->fetchColumn();
    $totalSize = $db->query('SELECT COALESCE(SUM(`file_size`), 0) FROM `files`')->fetchColumn();
} catch (\Throwable $e) {
    $totalFiles = 0;
    $totalSize = 0;
}
require_once __DIR__ . '/../views/admin/index.php';
