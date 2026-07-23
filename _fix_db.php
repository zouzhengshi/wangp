<?php
/**
 * 临时修复：添加 filepath 列
 * 访问此页面后请删除
 */
require_once __DIR__ . '/config.php';
try {
    $db = getDB();
    $db->exec("ALTER TABLE `files` ADD COLUMN `filepath` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文件所在目录路径' AFTER `file_ext`");
    echo 'OK: filepath column added<br>';
} catch (\Throwable $e) {
    echo 'Info (may already exist): ' . htmlspecialchars($e->getMessage()) . '<br>';
}
try {
    $db = getDB();
    $db->exec("ALTER TABLE `files` ADD INDEX `idx_filepath` (`filepath`)");
    echo 'OK: index added<br>';
} catch (\Throwable $e) {
    echo 'Info: ' . htmlspecialchars($e->getMessage()) . '<br>';
}
echo '<br><a href="index.php">返回首页</a>';
