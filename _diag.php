<?php
require_once __DIR__ . '/config.php';
echo '<h3>🔍 数据库诊断</h3><pre>';

// 1. 检查 filepath 列是否存在
try {
    $db = getDB();
    $cols = $db->query("DESCRIBE `files`")->fetchAll(PDO::FETCH_ASSOC);
    $hasFilepath = false;
    foreach ($cols as $col) {
        if ($col['Field'] === 'filepath') { $hasFilepath = true; break; }
    }
    echo "filepath 列存在: " . ($hasFilepath ? "✅ 是" : "❌ 否，需要运行 _fix_db.php") . "\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit;
}

// 2. 检查文件总数
$total = $db->query("SELECT COUNT(*) FROM `files`")->fetchColumn();
echo "文件总数: $total\n\n";

// 3. 检查有 filepath 的文件
if ($total > 0) {
    $withPath = $db->query("SELECT COUNT(*) FROM `files` WHERE `filepath` != ''")->fetchColumn();
    echo "有路径(filepath不为空)的文件: $withPath\n\n";

    // 4. 列出所有不同的 filepath
    $paths = $db->query("SELECT DISTINCT `filepath`, COUNT(*) as cnt FROM `files` WHERE `filepath` != '' GROUP BY `filepath`")->fetchAll();
    echo "存在的文件夹路径:\n";
    foreach ($paths as $p) {
        echo "  [{$p['filepath']}] {$p['cnt']} 个文件\n";
    }
    if (empty($paths)) echo "  (无)\n";

    // 5. 最近上传的文件
    echo "\n最近5个文件:\n";
    $recent = $db->query("SELECT id, filename, filepath, file_ext FROM `files` ORDER BY upload_time DESC LIMIT 5")->fetchAll();
    foreach ($recent as $r) {
        echo "  #{$r['id']} filepath=[{$r['filepath']}] filename={$r['filename']} .{$r['file_ext']}\n";
    }
}
echo '</pre><br><a href="index.php">←返回</a>';
