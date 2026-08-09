<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSetting('site_name', '文件管理系统')); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/file-manager.css">
</head>
<body data-allow-download="<?php echo isDownloadAllowed() ? 'true' : 'false'; ?>">

<!-- 顶部 -->
<header class="header">
    <a href="drive.php" class="header-brand">
        <i class="fa-solid fa-cloud-arrow-up"></i> <?php echo htmlspecialchars(getSetting('site_name', '文件管理系统')); ?>
    </a>
    <div class="header-actions">
        <div class="header-stats">
            <span><i class="fa-regular fa-file"></i> 文件数: <strong id="statFiles">--</strong></span>
            <span><i class="fa-solid fa-hard-drive"></i> 总大小: <strong id="statSize">--</strong></span>
        </div>
        <a href="index.php" class="btn" title="返回首页"><i class="fa-solid fa-house"></i></a>
        <a href="admin/index.php" class="btn" title="后台管理"><i class="fa-solid fa-gear"></i></a>
    </div>
</header>

<div class="main-container">

    <?php if (!$dbReady): ?>
    <!-- 数据库未初始化提示 -->
    <div class="database-error">
        <i class="fa-solid fa-triangle-exclamation database-error-icon"></i>
        <h3 class="database-error-title">数据库未就绪</h3>
        <p class="database-error-message"><?php echo htmlspecialchars($dbError); ?></p>
        <a href="init.php" class="btn btn-primary">前往初始化 →</a>
    </div>
    <?php else: ?>

    <!-- 上传区域 -->
    <div class="upload-zone" id="uploadZone">
        <i class="fa-solid fa-cloud-arrow-up"></i>
        <h3>点击上传或拖拽文件到此处</h3>
        <p>支持图片、文档、压缩包、音视频、代码等上百种文件格式 · 单文件最大 <?php echo formatSize(getMaxFileSize()); ?></p>
        <input type="file" id="fileInput" multiple>
        <input type="file" id="folderInput" webkitdirectory class="is-hidden">
    </div>

    <!-- 进度条 -->
    <div class="progress-bar-wrap" id="progressBar">
        <div class="progress-header">
            <span id="progressText">正在上传...</span>
            <span id="progressPercent">0%</span>
        </div>
        <div class="progress-track">
            <div class="progress-fill" id="progressFill"></div>
        </div>
    </div>

    <!-- 工具栏 -->
    <div class="toolbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="搜索文件名...">
        </div>
        <button class="btn btn-primary" id="btnUpload">
            <i class="fa-solid fa-plus"></i> 上传文件
        </button>
        <button class="btn" id="btnUploadFolder">
            <i class="fa-solid fa-folder-open"></i> 上传文件夹
        </button>
        <button class="btn btn-danger is-hidden" id="btnDeleteSelected">
            <i class="fa-solid fa-trash"></i> 删除选中
        </button>
        <button class="btn" id="btnRefresh">
            <i class="fa-solid fa-rotate"></i> 刷新
        </button>
        <button class="btn" id="btnNewFolder" title="新建文件夹">
            <i class="fa-solid fa-folder-plus"></i>
        </button>
        <span class="view-toggle" id="viewToggle">
            <span class="view-btn active" data-view="list" title="列表视图"><i class="fa-solid fa-list"></i></span>
            <span class="view-btn" data-view="grid" title="网格视图"><i class="fa-solid fa-table-cells"></i></span>
        </span>
    </div>

    <!-- 格式筛选 -->
    <div class="ext-filter" id="extFilter">
        <span class="ext-tag active" data-ext="">全部</span>
        <!-- 动态生成 -->
    </div>

    <!-- 面包屑导航 -->
    <div class="breadcrumbs breadcrumb-container" id="breadcrumbs"></div>

    <!-- 文件列表 -->
    <div class="file-table-wrap">
        <div id="mobileSelectBar" class="is-hidden"></div>
        <table id="listTable">
            <thead>
                <tr>
                    <th class="check-col"><input type="checkbox" id="selectAll" title="全选"></th>
                    <th class="select-column"></th>
                    <th data-sort="filename">文件名 <i class="fa-solid fa-sort"></i></th>
                    <th data-sort="file_ext">格式 <i class="fa-solid fa-sort"></i></th>
                    <th data-sort="file_size">大小 <i class="fa-solid fa-sort"></i></th>
                    <th data-sort="upload_time">上传时间 <i class="fa-solid fa-sort"></i></th>
                    <th data-sort="download_count">下载 <i class="fa-solid fa-sort"></i></th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="fileTableBody">
                <tr><td colspan="8"><div class="empty-state"><div class="loading-spinner"></div><h3 class="loading-title">加载中...</h3></div></td></tr>
            </tbody>
        </table>
                <div id="gridSelectBar" class="is-hidden"></div>
        <div class="grid-view" id="fileGridView"></div>
    </div>

    <!-- 分页 -->
    <div class="pagination-container" id="pagination"></div>

    <?php endif; ?>

</div>

<!-- Toast 容器 -->
<div class="toast-container" id="toastContainer"></div>

<!-- 右键菜单 -->
<div class="context-menu" id="contextMenu">
    <!-- JS 动态填充 -->
</div>

<!-- 删除确认模态框 -->
<div class="modal-overlay is-hidden" id="deleteModal">
    <div class="modal">
        <i class="fa-solid fa-triangle-exclamation modal-warning-icon"></i>
        <h3>确认删除</h3>
        <p id="deleteModalText">确定要删除选中的文件吗？此操作不可恢复。</p>
        <div class="btn-group">
            <button class="btn" id="btnCancelDelete">取消</button>
            <button class="btn btn-danger" id="btnConfirmDelete">确认删除</button>
        </div>
    </div>
</div>

    <script src="assets/js/file-manager.js" defer></script>
</body>
</html>
