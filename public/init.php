<?php
/**
 * 数据库初始化入口。初始化完成后请删除此文件。
 */
require_once __DIR__ . '/../app/config/config.php';

$initializationError = null;

try {
    $db = getDB();

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

    try {
        $db->exec("ALTER TABLE `files` ADD COLUMN `filepath` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文件所在目录路径' AFTER `file_ext`");
        $db->exec("ALTER TABLE `files` ADD INDEX `idx_filepath` (`filepath`)");
    } catch (\Throwable $e) {
        // 兼容已经存在 filepath 列或索引的旧表。
    }

    $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(64) NOT NULL UNIQUE COMMENT '设置键',
        `setting_value` TEXT NOT NULL COMMENT '设置值',
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表'");

    $db->exec("CREATE TABLE IF NOT EXISTS `admin` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(64) NOT NULL UNIQUE COMMENT '用户名',
        `password` VARCHAR(255) NOT NULL COMMENT '密码哈希',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表'");

    $db->exec("CREATE TABLE IF NOT EXISTS `posts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(200) NOT NULL COMMENT '帖子标题',
        `author` VARCHAR(64) NOT NULL DEFAULT '匿名' COMMENT '作者名称',
        `content` TEXT NOT NULL COMMENT '帖子正文',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_posts_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='帖子表'");

    $db->exec("CREATE TABLE IF NOT EXISTS `post_media` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT UNSIGNED NOT NULL COMMENT '所属帖子 ID',
        `original_name` VARCHAR(255) NOT NULL COMMENT '原始文件名',
        `stored_name` VARCHAR(80) NOT NULL COMMENT '存储文件名',
        `media_type` VARCHAR(16) NOT NULL COMMENT 'image、video 或 file',
        `mime_type` VARCHAR(100) NOT NULL DEFAULT '',
        `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_post_media_post_id` (`post_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='帖子媒体表'");

    $db->exec("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
        ('max_file_size', '209715200'),
        ('post_max_file_size', '524288000'),
        ('allow_download', '1'),
        ('site_name', '文件管理系统')");

    $stmt = $db->prepare("SELECT COUNT(*) FROM `admin` WHERE `username` = 'admin'");
    $stmt->execute();
    if ((int) $stmt->fetchColumn() === 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $db->prepare("INSERT INTO `admin` (`username`, `password`) VALUES ('admin', :pwd)")
           ->execute([':pwd' => $hash]);
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $adminDir = __DIR__ . '/admin';
    if (!is_dir($adminDir)) {
        mkdir($adminDir, 0755, true);
    }

    $htaccess = UPLOAD_DIR . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "php_flag engine off\n<FilesMatch \"\\.ph.*$\">\n    Deny from all\n</FilesMatch>\n");
    }

    $indexHtml = UPLOAD_DIR . 'index.html';
    if (!file_exists($indexHtml)) {
        file_put_contents($indexHtml, "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1></body></html>");
    }
} catch (PDOException $e) {
    http_response_code(500);
    $initializationError = $e->getMessage();
}

require_once __DIR__ . '/../app/views/init.php';
