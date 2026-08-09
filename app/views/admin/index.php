<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - <?php echo $siteName; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<header class="header">
    <h2>⚙️ 后台管理</h2>
    <div class="header-right">
        <span><?php echo htmlspecialchars($adminUser); ?></span>
        <a href="../drive.php">← 文件管理</a>
        <a href="../posts.php">帖子管理</a>
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
                <input type="range" id="sizeRange" min="1" max="10240" value="<?php echo $maxSizeMB; ?>"
                       oninput="document.getElementById('sizeVal').value = this.value;
                                document.getElementById('sizeLabel').textContent = this.value + ' MB';">
                <div class="size-control">
                    <input type="number" id="sizeVal" name="max_file_size" class="size-input"
                           value="<?php echo $maxSizeMB; ?>" min="1" max="10240"
                           oninput="document.getElementById('sizeRange').value = this.value;
                                    document.getElementById('sizeLabel').textContent = this.value + ' MB';">
                    <span id="sizeLabel" class="size-label"><?php echo $maxSizeMB; ?> MB</span>
                </div>
            </div>
            <div class="form-group">
                <label>发帖媒体上传大小限制（MB）</label>
                <input type="range" id="postSizeRange" min="1" max="10240" value="<?php echo $postMaxSizeMB; ?>"
                       oninput="document.getElementById('postSizeVal').value = this.value;
                                document.getElementById('postSizeLabel').textContent = this.value + ' MB';">
                <div class="size-control">
                    <input type="number" id="postSizeVal" name="post_max_file_size" class="size-input"
                           value="<?php echo $postMaxSizeMB; ?>" min="1" max="10240"
                           oninput="document.getElementById('postSizeRange').value = this.value;
                                    document.getElementById('postSizeLabel').textContent = this.value + ' MB';">
                    <span id="postSizeLabel" class="size-label"><?php echo $postMaxSizeMB; ?> MB</span>
                </div>
                <div class="hint">发帖图片、视频和附件的单个文件限制，最大 10GB</div>
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
