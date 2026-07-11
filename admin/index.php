<?php
/**
 * 后台管理 - 系统设置 + 修改密码
 */
require_once __DIR__ . '/../config.php';
$adminUser = adminRequire();

$saved = '';
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $maxSize = (int) ($_POST['max_file_size'] ?? 0);
        if ($maxSize > 0) {
            // 保存时转换为字节
            saveSetting('max_file_size', (string) ($maxSize * 1048576));
            saveSetting('allow_download', isset($_POST['allow_download']) ? '1' : '0');
            saveSetting('site_name', trim($_POST['site_name'] ?? '文件管理系统'));
            $saved = '设置已保存';
        } else {
            $error = '上传大小必须大于 0';
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

// 总览统计
try {
    $db = getDB();
    $totalFiles = $db->query('SELECT COUNT(*) FROM `files`')->fetchColumn();
    $totalSize = $db->query('SELECT COALESCE(SUM(`file_size`), 0) FROM `files`')->fetchColumn();
} catch (\Throwable $e) {
    $totalFiles = 0;
    $totalSize = 0;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - <?php echo $siteName; ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: #f0f2f5; color: #1e293b; min-height: 100vh;
        }
        .header {
            background: #fff; border-bottom: 1px solid #e2e8f0; padding: 0 24px;
            height: 56px; display: flex; align-items: center; justify-content: space-between;
        }
        .header h2 { font-size: 16px; color: #4f46e5; }
        .header-right { display: flex; align-items: center; gap: 16px; font-size: 13px; }
        .header-right a { color: #64748b; text-decoration: none; }
        .header-right a:hover { color: #4f46e5; }
        .header-right .logout { color: #ef4444; }
        .container { max-width: 800px; margin: 24px auto; padding: 0 16px; }
        .card {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
            padding: 24px; margin-bottom: 20px;
        }
        .card h3 { font-size: 15px; margin-bottom: 18px; color: #334155; display: flex; align-items: center; gap: 8px; }
        .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="password"] {
            width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; font-family: inherit; outline: none; transition: border .2s;
        }
        .form-group input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.08); }
        .form-group .hint { font-size: 11px; color: #94a3b8; margin-top: 4px; }
        .form-group input[type="range"] { width: 100%; accent-color: #4f46e5; margin-top: 6px; }
        .toggle-row { display: flex; align-items: center; justify-content: space-between; }
        .toggle-row .label { font-size: 13px; font-weight: 600; color: #475569; }
        .toggle {
            width: 44px; height: 24px; background: #cbd5e1; border-radius: 12px;
            cursor: pointer; position: relative; transition: background .2s; display: inline-block;
        }
        .toggle.on { background: #4f46e5; }
        .toggle::after {
            content: ''; position: absolute; width: 20px; height: 20px; background: #fff;
            border-radius: 50%; top: 2px; left: 2px; transition: left .2s;
        }
        .toggle.on::after { left: 22px; }
        .toggle-row input[type="checkbox"] { display: none; }
        .btn {
            padding: 9px 20px; background: #4f46e5; color: #fff; border: none;
            border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: background .2s;
        }
        .btn:hover { background: #4338ca; }
        .btn-outline {
            padding: 9px 20px; background: #fff; color: #4f46e5; border: 1px solid #4f46e5;
            border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
            font-family: inherit;
        }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .stat-card {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
            padding: 18px; text-align: center;
        }
        .stat-card .num { font-size: 28px; font-weight: 700; color: #4f46e5; }
        .stat-card .lbl { font-size: 12px; color: #94a3b8; margin-top: 4px; }
        @media (max-width: 480px) {
            .header { padding: 0 12px; }
            .container { padding: 0 8px; }
            .card { padding: 16px; }
        }
    </style>
</head>
<body>

<header class="header">
    <h2>⚙️ 后台管理</h2>
    <div class="header-right">
        <span><?php echo htmlspecialchars($adminUser); ?></span>
        <a href="../index.php">← 文件管理</a>
        <a href="logout.php" class="logout">退出登录</a>
    </div>
</header>

<div class="container">

    <?php if ($saved): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($saved); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- 概览 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="num"><?php echo $totalFiles; ?></div>
            <div class="lbl">文件总数</div>
        </div>
        <div class="stat-card">
            <div class="num">
                <?php
                if ($totalSize >= 1073741824) echo round($totalSize / 1073741824, 1) . ' GB';
                elseif ($totalSize >= 1048576) echo round($totalSize / 1048576, 1) . ' MB';
                elseif ($totalSize >= 1024) echo round($totalSize / 1024, 1) . ' KB';
                else echo $totalSize . ' B';
                ?>
            </div>
            <div class="lbl">总占用空间</div>
        </div>
    </div>

    <!-- 系统设置 -->
    <div class="card">
        <h3>📋 系统设置</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            <div class="form-group">
                <label>站点名称</label>
                <input type="text" name="site_name" value="<?php echo $siteName; ?>">
            </div>
            <div class="form-group">
                <label>上传大小限制（MB）</label>
                <input type="range" id="sizeRange" min="1" max="2048" value="<?php echo $maxSizeMB; ?>"
                       oninput="document.getElementById('sizeVal').value = this.value;
                                document.getElementById('sizeLabel').textContent = this.value + ' MB';">
                <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                    <input type="number" id="sizeVal" name="max_file_size"
                           value="<?php echo $maxSizeMB; ?>" min="1" max="2048"
                           style="width:100px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;"
                           oninput="document.getElementById('sizeRange').value = this.value;
                                    document.getElementById('sizeLabel').textContent = this.value + ' MB';">
                    <span id="sizeLabel" style="font-size:13px;color:#64748b;"><?php echo $maxSizeMB; ?> MB</span>
                </div>
            </div>
            <div class="form-group">
                <div class="toggle-row">
                    <span class="label">允许文件下载</span>
                    <label class="toggle <?php echo $allowDownload ? 'on' : ''; ?>" id="toggleDownload">
                        <input type="checkbox" name="allow_download" <?php echo $allowDownload ? 'checked' : ''; ?>
                               onchange="this.parentElement.classList.toggle('on', this.checked);">
                    </label>
                </div>
                <div class="hint">关闭后所有文件将无法下载（管理员不受影响）</div>
            </div>
            <button type="submit" class="btn">💾 保存设置</button>
        </form>
    </div>

    <!-- 修改密码 -->
    <div class="card">
        <h3>🔑 修改密码</h3>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label>原密码</label>
                <input type="password" name="old_password" required>
            </div>
            <div class="form-group">
                <label>新密码</label>
                <input type="password" name="new_password" required minlength="6">
                <div class="hint">至少 6 位</div>
            </div>
            <div class="form-group">
                <label>确认新密码</label>
                <input type="password" name="new_password2" required>
            </div>
            <button type="submit" class="btn">🔒 修改密码</button>
        </form>
    </div>

</div>
</body>
</html>
