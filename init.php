<?php
/**
 * 数据库初始化 - 创建所有表
 * 访问一次即可初始化，初始化后建议删除此文件
 */
require_once __DIR__ . '/config.php';

try {
    $db = getDB();

    // 文件表
    $db->exec("CREATE TABLE IF NOT EXISTS `files` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `filename` VARCHAR(500) NOT NULL COMMENT '原始文件名',
        `stored_name` VARCHAR(64) NOT NULL COMMENT '存储文件名(UUID)',
        `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
        `file_type` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'MIME类型',
        `file_ext` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '文件扩展名(小写)',
        `filepath` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文件所在目录路径(相对于根)',
        `upload_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
        `download_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载次数',
        INDEX `idx_ext` (`file_ext`),
        INDEX `idx_filepath` (`filepath`),
        INDEX `idx_upload_time` (`upload_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件管理表'");

    // 兼容旧表：如果 filepath 列不存在则添加
    try {
        $db->exec("ALTER TABLE `files` ADD COLUMN `filepath` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文件所在目录路径' AFTER `file_ext`");
        $db->exec("ALTER TABLE `files` ADD INDEX `idx_filepath` (`filepath`)");
    } catch (\Throwable $e) {
        // 列或索引已存在则忽略
    }

    // 系统设置表
    $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(64) NOT NULL UNIQUE COMMENT '设置键',
        `setting_value` TEXT NOT NULL COMMENT '设置值',
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表'");

    // 管理员表
    $db->exec("CREATE TABLE IF NOT EXISTS `admin` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(64) NOT NULL UNIQUE COMMENT '用户名',
        `password` VARCHAR(255) NOT NULL COMMENT '密码哈希',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表'");

    // 初始化默认设置
    $db->exec("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
        ('max_file_size', '209715200'),
        ('allow_download', '1'),
        ('site_name', '文件管理系统')");

    // 初始化默认管理员 admin / admin123
    $stmt = $db->prepare("SELECT COUNT(*) FROM `admin` WHERE `username` = 'admin'");
    $stmt->execute();
    if ((int) $stmt->fetchColumn() === 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $db->prepare("INSERT INTO `admin` (`username`, `password`) VALUES ('admin', :pwd)")
           ->execute([':pwd' => $hash]);
    }

    // 创建上传目录
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // 创建 admin 目录
    $adminDir = __DIR__ . '/admin';
    if (!is_dir($adminDir)) {
        mkdir($adminDir, 0755, true);
    }

    // 创建 .htaccess 防止执行上传的 PHP 文件
    $htaccess = UPLOAD_DIR . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "php_flag engine off\n<FilesMatch \"\\.ph.*$\">\n    Deny from all\n</FilesMatch>\n");
    }

    // 创建 index.html 防止目录列表
    $indexHtml = UPLOAD_DIR . 'index.html';
    if (!file_exists($indexHtml)) {
        file_put_contents($indexHtml, "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1></body></html>");
    }

    echo "<!DOCTYPE html><html lang=\"zh-CN\"><head><meta charset=\"utf-8\"><title>初始化成功</title>";
    echo "<style>body{font-family:'Microsoft YaHei',sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f0f9eb}";
    echo ".box{text-align:center;padding:40px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08)}";
    echo "h1{color:#22c55e}span{color:#666}</style></head><body><div class='box'>";
    echo "<h1>✅ 数据库初始化成功</h1>";
    echo "<p>文件上传系统已就绪</p>";
    echo "<p style='color:#f59e0b;font-weight:600'>🔑 默认管理员账号: admin / admin123</p>";
    echo "<p><span style='color:#ef4444'>⚠ 请立即删除 <code>init.php</code> 并修改默认密码</span></p>";
    echo "<p><a href='index.php' style='color:#3b82f6'>前往文件管理 →</a></p>";
    echo "</div></body></html>";

} catch (PDOException $e) {
    http_response_code(500);
    echo "<h1>数据库连接失败</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>请检查 config.php 中的数据库配置</p>";
}
