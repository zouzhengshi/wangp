<?php
/**
 * 文件管理系统 - 主页面
 */
require_once __DIR__ . '/config.php';

// 检查数据库连接，如果表不存在则提示初始化
$dbReady = true;
$dbError = '';
try {
    $db = getDB();
    $db->query('SELECT 1 FROM `files` LIMIT 1');
} catch (\Throwable $e) {
    $dbReady = false;
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSetting('site_name', '文件管理系统')); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f5f6fa;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --danger-light: #fef2f2;
            --success: #22c55e;
            --success-light: #f0fdf4;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-lg: 0 10px 40px rgba(0,0,0,.08);
            --transition: 0.2s ease;
            --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* 顶部导航 */
        .header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }
        .header-brand {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .header-brand i { font-size: 22px; }
        .header-stats {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .header-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .header-stats strong { color: var(--text); }

        /* 主布局 */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px 24px;
        }

        /* 上传区域 */
        .upload-zone {
            background: var(--card-bg);
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition);
            position: relative;
            margin-bottom: 20px;
        }
        .upload-zone:hover, .upload-zone.drag-over {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .upload-zone.drag-over {
            transform: scale(1.01);
        }
        .upload-zone i {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 12px;
            display: block;
        }
        .upload-zone h3 {
            font-size: 16px;
            margin-bottom: 4px;
            color: var(--text);
        }
        .upload-zone p {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        /* 进度条 */
        .progress-bar-wrap {
            display: none;
            margin-bottom: 16px;
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 16px 20px;
            box-shadow: var(--shadow);
        }
        .progress-bar-wrap.active { display: block; }
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .progress-track {
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 3px;
        }

        /* 工具栏 */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: var(--font);
            background: var(--card-bg);
            outline: none;
            transition: border var(--transition);
        }
        .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 14px;
        }
        .btn {
            padding: 9px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: var(--font);
            cursor: pointer;
            background: var(--card-bg);
            color: var(--text);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all var(--transition);
            white-space: nowrap;
            font-weight: 500;
        }
        .btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: var(--danger); color: #fff; border-color: var(--danger); }
        .btn-danger:hover { background: var(--danger-hover); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn.active { background: var(--primary-light); color: var(--primary); border-color: var(--primary); }

        /* 视图切换按钮 */
        .view-btn {
            padding: 5px 10px; border-radius: 6px; cursor: pointer;
            font-size: 13px; color: #94a3b8; transition: all 0.15s;
        }
        .view-btn:hover { color: #475569; }
        .view-btn.active { background: #fff; color: var(--primary); box-shadow: 0 1px 2px rgba(0,0,0,.06); }

        /* 网格视图 */
        .grid-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; padding: 4px; }
        .grid-view .grid-card {
            background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border);
            overflow: hidden; transition: all 0.2s; position: relative;
            display: flex; flex-direction: column;
        }
        .grid-view .grid-card:hover { border-color: var(--primary); box-shadow: 0 8px 24px rgba(0,0,0,.1); transform: translateY(-4px); }
        .grid-view .grid-thumb {
            width: 100%; height: 150px; background: #f8fafc;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
            position: relative; flex-shrink: 0;
        }
        .grid-view .grid-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .grid-view .grid-thumb video { width: 100%; height: 100%; object-fit: cover; }
        .grid-view .grid-thumb .thumb-icon { font-size: 52px; color: #cbd5e1; }
        .grid-view .grid-thumb .thumb-overlay {
            position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.3), transparent 60%);
            display: flex; align-items: flex-end; justify-content: flex-end; padding: 8px;
            opacity: 0; transition: opacity 0.2s;
        }
        .grid-view .grid-card:hover .thumb-overlay { opacity: 1; }
        .grid-view .grid-info { padding: 10px 12px; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .grid-view .grid-name {
            font-size: 13px; font-weight: 500; overflow:hidden; text-overflow:ellipsis;
            white-space:nowrap; line-height: 1.4; color: var(--text);
        }
        .grid-view .grid-meta {
            font-size: 11px; color: var(--text-secondary); margin-top:3px;
            display: flex; align-items: center; gap: 6px;
        }
        .grid-view .grid-check {
            position: absolute; top: 8px; left: 8px; z-index: 2;
            background: rgba(255,255,255,.9); border-radius: 4px; padding: 3px 4px;
        }
        .grid-view .grid-check input { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; }
        .grid-view .folder-card .grid-thumb { background: #fffbeb; }
        .grid-view .folder-card .grid-thumb .thumb-icon { color: #f59e0b; }
        .grid-view .grid-card.selected { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(79,70,229,.25); }
        .grid-view .grid-badge {
            position: absolute; top: 8px; right: 8px; z-index: 2;
            font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 4px;
            background: rgba(255,255,255,.85); color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .grid-view { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
            .grid-view .grid-thumb { height: 120px; }
        }
        @media (max-width: 480px) {
            .grid-view { grid-template-columns: repeat(3, 1fr); gap: 6px; }
            .grid-view .grid-thumb { height: 100px; }
            .grid-view .grid-name { font-size: 11px; }
            .grid-view .grid-info { padding: 6px 8px; }
        }

        .ext-filter {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .ext-tag {
            padding: 4px 10px;
            font-size: 12px;
            border-radius: 20px;
            border: 1px solid var(--border);
            cursor: pointer;
            background: var(--card-bg);
            color: var(--text-secondary);
            transition: all var(--transition);
            font-family: var(--font);
        }
        .ext-tag:hover { border-color: var(--primary); color: var(--primary); }
        .ext-tag.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* 文件表格 */
        .file-table-wrap {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        thead { background: #f8fafc; }
        th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
        }
        th:hover { color: var(--primary); }
        th i { margin-left: 4px; font-size: 10px; }
        td { padding: 12px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr { transition: background var(--transition); }
        tbody tr:hover { background: #f8fafc; }
        tbody tr.selected { background: var(--primary-light); }
        .file-icon { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--primary); }
        .file-name {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 500;
        }
        .file-meta { font-size: 12px; color: var(--text-secondary); white-space: nowrap; }
        .file-ext {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            border-radius: 4px;
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            font-family: 'SF Mono', 'Fira Code', monospace;
        }
        .actions { display: flex; gap: 6px; }
        .actions .btn { padding: 6px 10px; font-size: 12px; }

        .check-col { width: 40px; text-align: center; }
        .check-col input { width: 16px; height: 16px; cursor: pointer; accent-color: var(--primary); }

        /* 空状态 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        .empty-state i { font-size: 56px; display: block; margin-bottom: 16px; opacity: 0.3; }
        .empty-state h3 { font-size: 18px; color: var(--text); margin-bottom: 4px; }

        /* Toast */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .toast {
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            color: #fff;
            font-size: 14px;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            max-width: 360px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }
        .toast.info { background: var(--primary); }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ===== 响应式 ===== */

        /* 平板及以下 (≤1024px) */
        @media (max-width: 1024px) {
            .main-container { padding: 14px; }
            .file-name { max-width: 180px; }
        }

        /* 小屏平板 (≤768px) */
        @media (max-width: 768px) {
            .header { padding: 0 14px; height: 52px; }
            .header-brand { font-size: 16px; }
            .header-stats { display: none; }
            .main-container { padding: 10px; }
            .upload-zone { padding: 28px 16px; }
            .upload-zone i { font-size: 36px; }
            .upload-zone h3 { font-size: 14px; }
            .upload-zone p { font-size: 12px; }

            .toolbar { gap: 8px; }
            .search-box { min-width: 140px; }
            .btn { padding: 8px 12px; font-size: 12px; }
            .ext-filter { gap: 3px; }
            .ext-tag { padding: 3px 8px; font-size: 11px; }

            /* 移动端全选栏 */
            #mobileSelectBar {
                display: flex; align-items: center; gap: 8px;
                padding: 6px 0; margin-bottom: 8px; font-size: 13px; color: #64748b;
            }

            /* 表格转卡片 */
            .file-table-wrap { background: transparent; box-shadow: none; border-radius: 0; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tbody tr {
                background: var(--card-bg);
                border-radius: var(--radius);
                box-shadow: var(--shadow);
                margin-bottom: 10px;
                padding: 12px 42px 12px 12px; /* 右侧留空给checkbox */
                position: relative;
                border: 1px solid var(--border);
            }
            tbody tr.selected {
                border-color: var(--primary);
                box-shadow: 0 0 0 2px rgba(79,70,229,.15);
            }
            .check-col {
                position: absolute;
                top: 14px;
                right: 10px;
                left: auto;
                width: auto;
                z-index: 2;
                display: block !important;
            }
            .check-col input { width: 18px; height: 18px; }
            td {
                padding: 4px 0;
                border-bottom: none;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            tbody tr td:first-of-type { display: none; } /* 隐藏所有行的第一个td，改为绝对定位 */
            .file-icon { width: 28px; height: 28px; font-size: 16px; margin-right: 4px; }
            .file-name { max-width: none; white-space: normal; word-break: break-all; font-size: 14px; }
            .file-meta { font-size: 11px; display: inline-block !important; width: auto; margin-right: 10px; }
            .file-ext { font-size: 10px; padding: 1px 6px; }
            tbody tr.folder-row .file-name { margin-left: 0; }

            /* 卡片内操作按钮 */
            .actions {
                position: absolute; bottom: 10px; right: 10px;
                display: flex; gap: 4px; z-index: 2;
                margin: 0; border: none; padding: 0;
            }
            .actions .btn { padding: 4px 10px; font-size: 12px; }

            /* Toast */
            .toast-container { top: 60px; right: 8px; left: 8px; }
            .toast { max-width: none; font-size: 13px; padding: 10px 14px; }

            /* 分页 */
            #pagination { margin-top: 12px; }

            /* 模态框 */
            .modal { width: 92%; padding: 20px; }
        }

        /* 手机 (≤480px) */
        @media (max-width: 480px) {
            .header { padding: 0 10px; height: 48px; }
            .header-brand { font-size: 15px; gap: 6px; }
            .header-brand i { font-size: 18px; }
            .upload-zone { padding: 22px 10px; border-radius: var(--radius-sm); }
            .upload-zone i { font-size: 30px; }
            .upload-zone h3 { font-size: 13px; }
            .upload-zone p { font-size: 11px; }

            .toolbar { gap: 6px; flex-wrap: wrap; }
            .search-box { flex: 1 1 100%; min-width: 0; order: 1; }
            #btnUpload { order: 2; }
            #btnRefresh { order: 3; }
            #btnDeleteSelected { order: 4; }
            .search-box input { font-size: 16px; padding: 10px 10px 10px 34px; } /* 防 iOS 缩放 */

            .ext-filter { gap: 2px; margin-bottom: 12px; }
            .ext-tag { padding: 3px 7px; font-size: 10px; }

            tbody tr { padding: 10px; margin-bottom: 8px; }
            .file-name { font-size: 13px; }

            .modal { width: 94%; padding: 18px; border-radius: var(--radius-sm); }
            .modal h3 { font-size: 16px; }
            .btn { font-size: 12px; padding: 7px 10px; }
        }

        /* 右键菜单 */
        .context-menu {
            position: fixed;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            z-index: 999;
            min-width: 160px;
            padding: 4px;
            display: none;
        }
        .context-menu .menu-item {
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background var(--transition);
            font-family: var(--font);
        }
        .context-menu .menu-item:hover { background: var(--primary-light); color: var(--primary); }
        .context-menu .menu-item.danger:hover { background: var(--danger-light); color: var(--danger); }

        /* 模态框 */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.4);
            z-index: 998;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 24px;
            width: 90%;
            max-width: 420px;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }
        .modal h3 { margin-bottom: 8px; }
        .modal p { color: var(--text-secondary); font-size: 14px; margin-bottom: 20px; }
        .modal .btn-group { display: flex; gap: 10px; justify-content: center; }

        .loading-spinner {
            display: inline-block;
            width: 20px; height: 20px;
            border: 2px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* 预览弹窗 */
        .preview-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.85);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .preview-overlay img {
            max-width: 95vw;
            max-height: 95vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,.5);
        }
        .preview-overlay video {
            max-width: 95vw;
            max-height: 95vh;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,.5);
            outline: none;
        }
        .preview-overlay audio {
            width: 400px;
            max-width: 90vw;
        }
        .preview-close {
            position: absolute;
            top: 16px;
            right: 20px;
            color: #fff;
            font-size: 32px;
            cursor: pointer;
            z-index: 1;
            opacity: 0.8;
            transition: opacity 0.2s;
            background: none;
            border: none;
        }
        .preview-close:hover { opacity: 1; }
        .preview-info {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-size: 13px;
            opacity: 0.7;
            background: rgba(0,0,0,.5);
            padding: 6px 16px;
            border-radius: 20px;
        }
        @media (max-width: 768px) {
            .preview-overlay img, .preview-overlay video {
                max-width: 100vw;
                max-height: 90vh;
                border-radius: 0;
            }
            .preview-close { top: 8px; right: 12px; font-size: 28px; }
        }
    </style>
</head>
<body>

<!-- 顶部 -->
<header class="header">
    <a href="index.php" class="header-brand">
        <i class="fa-solid fa-cloud-arrow-up"></i> <?php echo htmlspecialchars(getSetting('site_name', '文件管理系统')); ?>
    </a>
    <div style="display:flex;align-items:center;gap:16px;">
        <div class="header-stats">
            <span><i class="fa-regular fa-file"></i> 文件数: <strong id="statFiles">--</strong></span>
            <span><i class="fa-solid fa-hard-drive"></i> 总大小: <strong id="statSize">--</strong></span>
        </div>
        <a href="admin/" class="btn" title="后台管理"><i class="fa-solid fa-gear"></i></a>
    </div>
</header>

<div class="main-container">

    <?php if (!$dbReady): ?>
    <!-- 数据库未初始化提示 -->
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:24px;margin-bottom:20px;text-align:center;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:40px;color:#ef4444;display:block;margin-bottom:12px;"></i>
        <h3 style="color:#991b1b;margin-bottom:8px;">数据库未就绪</h3>
        <p style="color:#b91c1c;font-size:14px;margin-bottom:16px;"><?php echo htmlspecialchars($dbError); ?></p>
        <a href="init.php" class="btn btn-primary">前往初始化 →</a>
    </div>
    <?php else: ?>

    <!-- 上传区域 -->
    <div class="upload-zone" id="uploadZone">
        <i class="fa-solid fa-cloud-arrow-up"></i>
        <h3>点击上传或拖拽文件到此处</h3>
        <p>支持图片、文档、压缩包、音视频、代码等上百种文件格式 · 单文件最大 <?php echo formatSize(getMaxFileSize()); ?></p>
        <input type="file" id="fileInput" multiple>
        <input type="file" id="folderInput" webkitdirectory style="display:none;">
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
        <button class="btn btn-danger" id="btnDeleteSelected" style="display:none;">
            <i class="fa-solid fa-trash"></i> 删除选中
        </button>
        <button class="btn" id="btnRefresh">
            <i class="fa-solid fa-rotate"></i> 刷新
        </button>
        <button class="btn" id="btnNewFolder" title="新建文件夹">
            <i class="fa-solid fa-folder-plus"></i>
        </button>
        <span style="margin-left:auto;display:flex;gap:2px;background:#f1f5f9;border-radius:8px;padding:2px;" id="viewToggle">
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
    <div class="breadcrumbs" id="breadcrumbs" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:12px;font-size:13px;"></div>

    <!-- 文件列表 -->
    <div class="file-table-wrap">
        <div id="mobileSelectBar" style="display:none;"></div>
        <table id="listTable">
            <thead>
                <tr>
                    <th class="check-col"><input type="checkbox" id="selectAll" title="全选"></th>
                    <th style="width:50px;"></th>
                    <th data-sort="filename">文件名 <i class="fa-solid fa-sort"></i></th>
                    <th data-sort="file_ext">格式 <i class="fa-solid fa-sort"></i></th>
                    <th data-sort="file_size">大小 <i class="fa-solid fa-sort"></i></th>
                    <th data-sort="upload_time">上传时间 <i class="fa-solid fa-sort"></i></th>
                    <th data-sort="download_count">下载 <i class="fa-solid fa-sort"></i></th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="fileTableBody">
                <tr><td colspan="8"><div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:16px;">加载中...</h3></div></td></tr>
            </tbody>
        </table>
                <div id="gridSelectBar" style="display:none;"></div>
        <div class="grid-view" id="fileGridView" style="display:none;"></div>
    </div>

    <!-- 分页 -->
    <div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:16px;" id="pagination"></div>

    <?php endif; ?>

</div>

<!-- Toast 容器 -->
<div class="toast-container" id="toastContainer"></div>

<!-- 右键菜单 -->
<div class="context-menu" id="contextMenu">
    <!-- JS 动态填充 -->
</div>

<!-- 删除确认模态框 -->
<div class="modal-overlay" id="deleteModal" style="display:none;">
    <div class="modal">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:40px;color:#ef4444;display:block;margin-bottom:12px;"></i>
        <h3>确认删除</h3>
        <p id="deleteModalText">确定要删除选中的文件吗？此操作不可恢复。</p>
        <div class="btn-group">
            <button class="btn" id="btnCancelDelete">取消</button>
            <button class="btn btn-danger" id="btnConfirmDelete">确认删除</button>
        </div>
    </div>
</div>

<script>
// 全局状态
let state = {
    files: [],
    page: 1,
    perPage: 20,
    total: 0,
    totalPages: 1,
    sort: 'upload_time',
    order: 'DESC',
    search: '',
    ext: '',
    selectedIds: new Set(),
    selectedFolders: new Set(),
    allowDownload: <?php echo isDownloadAllowed() ? 'true' : 'false'; ?>,
    currentPath: '',
    viewMode: localStorage.getItem('viewMode') || 'list',
};

const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');
const fileTableBody = document.getElementById('fileTableBody');
const searchInput = document.getElementById('searchInput');
const selectAll = document.getElementById('selectAll');
const btnDeleteSelected = document.getElementById('btnDeleteSelected');
const contextMenu = document.getElementById('contextMenu');
const toastContainer = document.getElementById('toastContainer');

// === Toast ===
function toast(msg, type = 'info') {
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
    el.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i> ${msg}`;
    toastContainer.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.3s'; setTimeout(() => el.remove(), 300); }, 3000);
}

// === 加载文件列表 ===
let loadFilesAborter = null;
async function loadFiles() {
    // 取消上一次未完成的请求
    if (loadFilesAborter) loadFilesAborter.abort();
    loadFilesAborter = new AbortController();

    // 立即显示加载态
    fileTableBody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:16px;">加载中...</h3></div></td></tr>`;
    document.getElementById('pagination').innerHTML = '';

    const params = new URLSearchParams({
        action: 'list',
        page: state.page,
        per_page: state.perPage,
        sort: state.sort,
        order: state.order,
        search: state.search,
        ext: state.ext,
        path: state.currentPath,
    });
    try {
        const res = await fetch('api.php?' + params.toString(), { signal: loadFilesAborter.signal });
        const json = await res.json();
        if (json.code === 200) {
            state.files = json.data.files;
            state.folders = json.data.folders || [];
            state.total = json.data.total;
            state.totalPages = json.data.total_pages;
            state.currentPath = json.data.current_path || '';
            renderBreadcrumbs();
            renderTable();
            renderPagination();
        }
    } catch (e) {
        if (e.name !== 'AbortError') {
            toast('加载文件列表失败', 'error');
        }
    }
}

// === 加载统计（仅页面初始化 + 上传/删除后调用，筛选/翻页不触发） ===
async function updateStats() {
    try {
        const res = await fetch('api.php?action=stats');
        const json = await res.json();
        if (json.code === 200) {
            document.getElementById('statFiles').textContent = json.data.total_files;
            document.getElementById('statSize').textContent = json.data.total_size;
            state.allowDownload = json.data.allow_download !== false;
            renderExtFilter(json.data.extensions || []);
        }
    } catch (e) {}
}

// === 渲染格式筛选 ===
function renderExtFilter(exts) {
    const container = document.getElementById('extFilter');
    let html = '<span class="ext-tag" data-ext="">全部</span>';
    exts.slice(0, 20).forEach(e => {
        html += `<span class="ext-tag" data-ext="${e.file_ext}">${e.file_ext} (${e.cnt})</span>`;
    });
    container.innerHTML = html;

    // 恢复之前选中的筛选状态
    const activeTag = container.querySelector(`.ext-tag[data-ext="${state.ext}"]`);
    if (activeTag) activeTag.classList.add('active');
    else container.querySelector('.ext-tag[data-ext=""]').classList.add('active');

    container.querySelectorAll('.ext-tag').forEach(tag => {
        tag.addEventListener('click', () => {
            container.querySelectorAll('.ext-tag').forEach(t => t.classList.remove('active'));
            tag.classList.add('active');
            state.ext = tag.dataset.ext;
            state.page = 1;
            loadFiles();
        });
    });
}

// === 面包屑导航 ===
function renderBreadcrumbs() {
    const container = document.getElementById('breadcrumbs');
    if (state.currentPath === '') {
        container.innerHTML = '';
        return;
    }
    let html = '<span class="btn" onclick="navigateTo(\'\')" style="cursor:pointer;"><i class="fa-solid fa-home"></i> 根目录</span>';
    const parts = state.currentPath.replace(/\/$/, '').split('/');
    let accumulated = '';
        parts.forEach((part, i) => {
            accumulated += part + '/';
            html += '<i class="fa-solid fa-chevron-right" style="color:#94a3b8;font-size:10px;"></i>';
            if (i === parts.length - 1) {
                html += '<span class="btn active" style="cursor:default;">' + escapeHtml(part) + '</span>';
            } else {
                html += '<span class="btn" onclick="navigateTo(\'' + escapeAttr(accumulated) + '\')" style="cursor:pointer;">' + escapeHtml(part) + '</span>';
            }
        });
    container.innerHTML = html;
}
function navigateTo(path) {
    state.currentPath = path;
    state.page = 1;
    state.selectedIds.clear();
    state.selectedFolders.clear();
    state.ext = '';
    loadFiles();
    updateStats();
}
function goUp() {
    if (state.currentPath === '') return;
    const parts = state.currentPath.replace(/\/$/, '').split('/');
    parts.pop();
    navigateTo(parts.length > 0 ? parts.join('/') + '/' : '');
}

// === 视图切换 ===
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        state.viewMode = btn.dataset.view;
        localStorage.setItem('viewMode', state.viewMode);
        renderTable();
    });
});

// === 渲染表格 ===
function renderTable() {
    if (state.viewMode === 'grid') {
        document.getElementById('mobileSelectBar').style.display = 'none';
        document.getElementById('listTable').style.display = 'none';
        document.getElementById('gridSelectBar').style.display = '';
        document.getElementById('fileGridView').style.display = '';
        renderGridView();
        return;
    }
    document.getElementById('listTable').style.display = '';
    document.getElementById('gridSelectBar').style.display = 'none';
    document.getElementById('fileGridView').style.display = 'none';

    const hasFolders = state.folders && state.folders.length > 0;
    const hasFiles = state.files.length > 0;

    if (!hasFiles && !hasFolders) {
        fileTableBody.innerHTML = `<tr><td colspan="8">
            <div class="empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <h3>此目录为空</h3>
                <p>上传文件或文件夹开始使用吧</p>
            </div>
        </td></tr>`;
        return;
    }

    let rows = '';

    // 渲染子文件夹
    if (hasFolders) {
        state.folders.forEach(fd => {
            rows += `<tr class="folder-row" data-folder="${escapeAttr(fd.fullpath)}">
                <td class="check-col"><input type="checkbox" class="row-checkbox folder-check" data-folder="${escapeAttr(fd.fullpath)}" ${state.selectedFolders && state.selectedFolders.has(fd.fullpath) ? 'checked' : ''}></td>
                <td><div class="file-icon"><i class="fa-solid fa-folder" style="color:#f59e0b;"></i></div></td>
                <td>
                    <div class="file-name" style="cursor:pointer;color:#4f46e5;" onclick="navigateTo('${escapeAttr(fd.fullpath)}')" title="${escapeHtml(fd.name)}">
                        📁 ${escapeHtml(fd.name)}
                    </div>
                </td>
                <td><span class="file-ext" style="background:#fef3c7;color:#92400e;">文件夹</span></td>
                <td class="file-meta">${fd.total_size || '--'}</td>
                <td class="file-meta">${fd.latest_time || '--'}</td>
                <td class="file-meta">${fd.file_count || 0} 文件</td>
                <td>
                    <div class="actions">
                        <button class="btn" onclick="downloadFolder('${escapeAttr(fd.fullpath)}')" title="下载文件夹"><i class="fa-solid fa-download"></i></button>
                        <button class="btn btn-danger" onclick="deleteFolder('${escapeAttr(fd.fullpath)}')" title="删除文件夹"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        });
    }

    // 渲染文件
    if (hasFiles) {
        rows += state.files.map(f => `
            <tr data-id="${f.id}" class="${state.selectedIds.has(f.id) ? 'selected' : ''}">
                <td class="check-col"><input type="checkbox" class="row-checkbox" data-id="${f.id}" ${state.selectedIds.has(f.id) ? 'checked' : ''}></td>
                <td><div class="file-icon"><i class="fa-regular ${f.icon}"></i></div></td>
                <td><div class="file-name" title="${escapeHtml(f.filename)}">${escapeHtml(f.filename)}</div></td>
                <td><span class="file-ext">${f.file_ext}</span></td>
                <td class="file-meta">${f.size_format}</td>
                <td class="file-meta">${f.upload_time}</td>
                <td class="file-meta">${f.download_count}</td>
                <td>
                    <div class="actions">
                        ${isPreviewable(f.file_ext) ? `<button class="btn btn-preview" data-id="${f.id}" data-filename="${escapeAttr(f.filename)}" data-ext="${f.file_ext}" title="预览"><i class="fa-solid fa-eye"></i></button>` : ''}
                        <button class="btn btn-download-row" data-id="${f.id}" title="下载"><i class="fa-solid fa-download"></i></button>
                    <button class="btn btn-danger btn-delete-row" data-id="${f.id}" title="删除"><i class="fa-solid fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
    }
    // 更新移动端全选栏
    const totalItems = (state.files.length || 0) + ((state.folders || []).length || 0);
    const totalSel = state.selectedIds.size + state.selectedFolders.size;
    const mb = document.getElementById('mobileSelectBar');
    if (totalItems > 0 && window.innerWidth <= 768) {
        mb.style.display = '';
        const allSel = totalSel === totalItems;
        mb.innerHTML = '<input type="checkbox" ' + (allSel ? 'checked' : '') + ' onchange="mobileSelectAll(this.checked)" style="accent-color:var(--primary);width:16px;height:16px;">' +
            '<span>全选</span>' +
            (totalSel > 0 ? '<span style="margin-left:auto;color:var(--primary);font-weight:600;">已选 ' + totalSel + ' 个</span>' : '');
    } else {
        mb.style.display = 'none';
    }

    fileTableBody.innerHTML = rows;
    bindRowEvents();
    updateSelectAll();
    updateDeleteBtn();
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// === 绑定行事件 ===
function bindRowEvents() {
    fileTableBody.querySelectorAll('tr[data-id]').forEach(row => {
        const id = parseInt(row.dataset.id);
        row.addEventListener('contextmenu', e => {
            e.preventDefault();
            showContextMenu(e.clientX, e.clientY, id);
        });
        row.querySelector('.row-checkbox')?.addEventListener('change', e => {
            if (e.target.checked) state.selectedIds.add(id);
            else state.selectedIds.delete(id);
            row.classList.toggle('selected', e.target.checked);
            updateSelectAll();
            updateDeleteBtn();
        });

        // 预览按钮 — 事件委托
        row.querySelector('.btn-preview')?.addEventListener('click', e => {
            e.preventDefault();
            const btn = e.currentTarget;
            const fid = parseInt(btn.dataset.id);
            const fname = btn.dataset.filename;
            const fext = btn.dataset.ext;
            const file = state.files.find(f => f.id === fid);
            previewFile(fid, file ? file.filename : fname, fext);
        });

        // 下载按钮 — 事件委托
        row.querySelector('.btn-download-row')?.addEventListener('click', e => {
            e.preventDefault();
            downloadFile(parseInt(e.currentTarget.dataset.id));
        });

        // 删除按钮 — 事件委托
        row.querySelector('.btn-delete-row')?.addEventListener('click', e => {
            e.preventDefault();
            deleteFile(parseInt(e.currentTarget.dataset.id));
        });

        // 文件夹勾选
        row.querySelector('.folder-check')?.addEventListener('change', e => {
            const fp = e.target.dataset.folder;
            if (e.target.checked) state.selectedFolders.add(fp);
            else state.selectedFolders.delete(fp);
            updateDeleteBtn();
        });
    });
}

// === 删除文件夹 ===
async function deleteFolder(fullpath) {
    if (!confirm('确定删除文件夹 "' + fullpath + '" 及其所有文件？此操作不可恢复。')) return;
    try {
        const res = await fetch('delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ folder_path: fullpath }),
        });
        const json = await res.json();
        if (json.code === 200) {
            toast('文件夹已删除', 'success');
            loadFiles();
            updateStats();
        } else {
            toast(json.msg, 'error');
        }
    } catch (e) {
        toast('删除失败', 'error');
    }
}

// === 下载文件夹 ===
function downloadFolder(fullpath) {
    const name = fullpath.replace(/\/$/, '').split('/').pop() || 'download';
    const overlay = document.createElement('div');
    overlay.className = 'preview-overlay';
    overlay.innerHTML = '<div class="modal" style="background:#fff;cursor:default;text-align:left;max-width:360px;" onclick="event.stopPropagation();">' +
        '<h3 style="margin-bottom:6px;">📦 下载文件夹</h3>' +
        '<p style="color:#64748b;font-size:13px;margin-bottom:16px;">' + escapeHtml(name) + '</p>' +
        '<div style="display:flex;gap:8px;">' +
            '<button class="btn btn-primary" style="flex:1;justify-content:center;padding:12px;" onclick="doDownloadFolder(\'' + escapeAttr(fullpath) + '\', \'deflate\');this.parentElement.parentElement.parentElement.remove();">' +
                '<i class="fa-solid fa-file-zipper"></i> ZIP 压缩</button>' +
            '<button class="btn" style="flex:1;justify-content:center;padding:12px;" onclick="doDownloadFolder(\'' + escapeAttr(fullpath) + '\', \'store\');this.parentElement.parentElement.parentElement.remove();">' +
                '<i class="fa-solid fa-folder-open"></i> 解压即用</button>' +
        '</div>' +
        '<button class="btn" style="width:100%;margin-top:12px;justify-content:center;" onclick="this.parentElement.parentElement.remove();">取消</button>' +
    '</div>';
    overlay.addEventListener('click', () => overlay.remove());
    document.body.appendChild(overlay);
}

function doDownloadFolder(fullpath, mode) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'batch_download.php';
    form.target = '_blank';
    ['folder_path', 'mode'].forEach(name => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = name === 'folder_path' ? fullpath : mode;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// === 全选 ===
selectAll.addEventListener('change', () => {
    if (selectAll.checked) {
        state.files.forEach(f => state.selectedIds.add(f.id));
        state.folders.forEach(fd => state.selectedFolders.add(fd.fullpath));
    } else {
        state.selectedIds.clear();
        state.selectedFolders.clear();
    }
    renderTable();
});

function updateSelectAll() {
    const totalFiles = state.files.length;
    const totalFolders = (state.folders || []).length;
    if (totalFiles === 0 && totalFolders === 0) { selectAll.checked = false; return; }
    const allFilesChecked = totalFiles === 0 || state.selectedIds.size === totalFiles;
    const allFoldersChecked = totalFolders === 0 || state.selectedFolders.size === totalFolders;
    selectAll.checked = allFilesChecked && allFoldersChecked;
}

function updateDeleteBtn() {
    const total = state.selectedIds.size + state.selectedFolders.size;
    btnDeleteSelected.style.display = total > 0 ? '' : 'none';
    btnDeleteSelected.innerHTML = `<i class="fa-solid fa-trash"></i> 删除选中 (${total})`;
}

// === 网格视图 ===
const imgExts = ['jpg','jpeg','png','gif','bmp','webp','svg','ico'];
const vidExts = ['mp4','webm'];
function renderGridView() {
    const grid = document.getElementById('fileGridView');
    const hasFolders = state.folders && state.folders.length > 0;
    const hasFiles = state.files.length > 0;

    if (!hasFiles && !hasFolders) {
        grid.innerHTML = '<div class="empty-state"><i class="fa-solid fa-folder-open"></i><h3>此目录为空</h3><p>上传文件或文件夹开始使用吧</p></div>';
        return;
    }

    let html = '';

    // 全选栏
    let selectBarHtml = '';
    if (hasFiles || hasFolders) {
        const allFilesSel = hasFiles ? state.files.every(f => state.selectedIds.has(f.id)) : true;
        const allFoldersSel = hasFolders ? state.folders.every(fd => state.selectedFolders.has(fd.fullpath)) : true;
        const allSel = allFilesSel && allFoldersSel;
        const totalSel = state.selectedIds.size + state.selectedFolders.size;
        selectBarHtml = '<div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px;color:#64748b;">' +
            '<input type="checkbox" ' + (allSel ? 'checked' : '') + ' onchange="toggleGridSelectAll(this.checked)" style="accent-color:var(--primary);width:16px;height:16px;">' +
            '<span>全选</span>' +
            (totalSel > 0 ? '<span style="margin-left:auto;color:var(--primary);font-weight:600;">已选 ' + totalSel + ' 个</span>' +
                '<button class="btn btn-primary" onclick="batchDownload()" style="padding:4px 12px;font-size:12px;margin-left:8px;"><i class="fa-solid fa-download"></i> 打包下载</button>' : '') +
            '</div>';
    }
    document.getElementById('gridSelectBar').innerHTML = selectBarHtml;

    // 文件夹卡片
    if (hasFolders) {
        state.folders.forEach(fd => {
            const sel = state.selectedFolders.has(fd.fullpath);
            html += '<div class="grid-card folder-card' + (sel ? ' selected' : '') + '" data-folder="' + escapeAttr(fd.fullpath) + '">' +
                '<div class="grid-thumb" onclick="navigateTo(\'' + escapeAttr(fd.fullpath) + '\')">' +
                    '<div class="grid-check" onclick="event.stopPropagation();">' +
                        '<input type="checkbox" ' + (sel ? 'checked' : '') + ' onchange="toggleGridFolderSelect(\'' + escapeAttr(fd.fullpath) + '\', this.checked)">' +
                    '</div>' +
                    '<i class="fa-solid fa-folder thumb-icon" style="color:#f59e0b;"></i>' +
                '</div>' +
                '<div class="grid-info" onclick="navigateTo(\'' + escapeAttr(fd.fullpath) + '\')">' +
                    '<div class="grid-name">📁 ' + escapeHtml(fd.name) + '</div>' +
                    '<div class="grid-meta">' + (fd.file_count || 0) + ' 个文件</div>' +
                '</div>' +
            '</div>';
        });
    }

    // 文件卡片
    if (hasFiles) {
        state.files.forEach(f => {
            const isImg = imgExts.includes(f.file_ext);
            const isVid = vidExts.includes(f.file_ext);
            const sel = state.selectedIds.has(f.id);
            const selClass = sel ? ' selected' : '';
            let badgeHtml = '', thumbHtml = '';
            if (isImg) {
                badgeHtml = '<div class="grid-badge">图片</div>';
                thumbHtml = '<img src="view.php?id=' + f.id + '" alt="" loading="lazy">';
            } else if (isVid) {
                badgeHtml = '<div class="grid-badge">视频</div>';
                thumbHtml = '<video src="view.php?id=' + f.id + '" preload="metadata" style="width:100%;height:100%;object-fit:cover;"></video>' +
                    '<i class="fa-solid fa-play" style="position:absolute;font-size:30px;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.4);z-index:1;pointer-events:none;"></i>';
            } else {
                badgeHtml = '<div class="grid-badge">' + f.file_ext + '</div>';
                thumbHtml = '<i class="fa-regular ' + f.icon + ' thumb-icon"></i>';
            }
            html += '<div class="grid-card' + selClass + '" data-id="' + f.id + '">' +
                '<div class="grid-thumb">' +
                    '<div class="grid-check" onclick="event.stopPropagation();">' +
                        '<input type="checkbox" ' + (sel ? 'checked' : '') + ' onchange="toggleGridSelect(' + f.id + ', this.checked)">' +
                    '</div>' +
                    badgeHtml +
                    '<div class="thumb-overlay">' +
                        '<button class="btn" onclick="event.stopPropagation();downloadFile(' + f.id + ')" title="下载" style="padding:3px 8px;font-size:11px;"><i class="fa-solid fa-download"></i></button>' +
                        '<button class="btn btn-danger" onclick="event.stopPropagation();deleteFile(' + f.id + ')" title="删除" style="padding:3px 8px;font-size:11px;"><i class="fa-solid fa-trash"></i></button>' +
                    '</div>' +
                    thumbHtml +
                '</div>' +
                '<div class="grid-info" onclick="gridCardClick(' + f.id + ', \'' + f.file_ext + '\')">' +
                    '<div class="grid-name" title="' + escapeHtml(f.filename) + '">' + escapeHtml(f.filename) + '</div>' +
                    '<div class="grid-meta">' + f.size_format + '</div>' +
                '</div>' +
            '</div>';
        });
    }
    grid.innerHTML = html;
}

function gridCardClick(id, ext) {
    if (imgExts.includes(ext) || vidExts.includes(ext) || ext === 'pdf') {
        const file = state.files.find(f => f.id === id);
        if (file) previewFile(id, file.filename, ext);
    } else {
        downloadFile(id);
    }
}

function toggleGridSelect(id, checked) {
    if (checked) state.selectedIds.add(id);
    else state.selectedIds.delete(id);
    updateDeleteBtn();
    renderGridView();
}

function toggleGridSelectAll(checked) {
    if (checked) {
        state.files.forEach(f => state.selectedIds.add(f.id));
        (state.folders || []).forEach(fd => state.selectedFolders.add(fd.fullpath));
    } else {
        state.files.forEach(f => state.selectedIds.delete(f.id));
        state.selectedFolders.clear();
    }
    updateDeleteBtn();
    renderGridView();
}

function toggleGridFolderSelect(fullpath, checked) {
    if (checked) state.selectedFolders.add(fullpath);
    else state.selectedFolders.delete(fullpath);
    updateDeleteBtn();
    renderGridView();
}

function mobileSelectAll(checked) {
    if (checked) {
        state.files.forEach(f => state.selectedIds.add(f.id));
        (state.folders || []).forEach(fd => state.selectedFolders.add(fd.fullpath));
    } else {
        state.selectedIds.clear();
        state.selectedFolders.clear();
    }
    updateDeleteBtn();
    renderTable();
}

function batchDownload() {
    if (state.selectedIds.size === 0) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'batch_download.php';
    form.target = '_blank';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ids';
    input.value = JSON.stringify([...state.selectedIds]);
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// === 右键菜单 ===
function showContextMenu(x, y, id) {
    contextMenu.innerHTML = `
        <div class="menu-item" onclick="downloadFile(${id});hideContextMenu();">
            <i class="fa-solid fa-download"></i> 下载
        </div>
        <div class="menu-item" onclick="copyLink(${id});hideContextMenu();">
            <i class="fa-solid fa-link"></i> 复制下载链接
        </div>
        <div class="menu-item danger" onclick="deleteFile(${id});hideContextMenu();">
            <i class="fa-solid fa-trash"></i> 删除
        </div>
    `;
    contextMenu.style.display = 'block';
    contextMenu.style.left = Math.min(x, window.innerWidth - 180) + 'px';
    contextMenu.style.top = Math.min(y, window.innerHeight - 120) + 'px';
}

function hideContextMenu() { contextMenu.style.display = 'none'; }
document.addEventListener('click', e => { if (!contextMenu.contains(e.target)) hideContextMenu(); });

function downloadFile(id) {
    if (!state.allowDownload) {
        toast('下载功能已被管理员关闭', 'error');
        return;
    }
    window.open('download.php?id=' + id, '_blank');
}

function copyLink(id) {
    const url = location.origin + location.pathname.replace('index.php', '') + 'download.php?id=' + id;
    navigator.clipboard.writeText(url).then(() => toast('下载链接已复制', 'success'));
}

// === 删除文件 ===
function deleteFile(id) {
    showDeleteModal([id]);
}

btnDeleteSelected.addEventListener('click', () => {
    showDeleteModal([...state.selectedIds], [...state.selectedFolders]);
});

function showDeleteModal(ids, folders) {
    folders = folders || [];
    const total = ids.length + folders.length;
    document.getElementById('deleteModal').style.display = '';
    document.getElementById('deleteModalText').textContent = `确定要删除 ${total} 个项目吗？此操作不可恢复。`;
    document.getElementById('btnConfirmDelete').onclick = async () => {
        try {
            // 先删除文件夹
            for (const fp of folders) {
                await fetch('delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ folder_path: fp }),
                });
            }
            // 再删除文件
            if (ids.length > 0) {
                await fetch('delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids }),
                });
            }
            toast(`已删除 ${total} 个项目`, 'success');
            state.selectedIds.clear();
            state.selectedFolders.clear();
            loadFiles();
            updateStats();
        } catch (e) {
            toast('删除失败', 'error');
        }
        document.getElementById('deleteModal').style.display = 'none';
    };
    document.getElementById('btnCancelDelete').onclick = () => {
        document.getElementById('deleteModal').style.display = 'none';
    };
}

// === 分页 ===
function renderPagination() {
    const container = document.getElementById('pagination');
    if (state.totalPages <= 1) { container.innerHTML = ''; return; }
    let html = '';
    html += `<button class="btn" onclick="goPage(${state.page - 1})" ${state.page <= 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
    html += `<span style="font-size:13px;color:var(--text-secondary);padding:0 12px;">${state.page} / ${state.totalPages}</span>`;
    html += `<button class="btn" onclick="goPage(${state.page + 1})" ${state.page >= state.totalPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
    container.innerHTML = html;
}

function goPage(p) {
    if (p < 1 || p > state.totalPages) return;
    state.page = p;
    loadFiles();
}

// === 排序 ===
document.querySelectorAll('th[data-sort]').forEach(th => {
    th.addEventListener('click', () => {
        const sort = th.dataset.sort;
        if (state.sort === sort) {
            state.order = state.order === 'ASC' ? 'DESC' : 'ASC';
        } else {
            state.sort = sort;
            state.order = 'DESC';
        }
        state.page = 1;
        loadFiles();
    });
});

// === 搜索 ===
let searchTimer;
searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        state.search = searchInput.value.trim();
        state.page = 1;
        loadFiles();
    }, 300);
});

// === 上传 ===
document.getElementById('btnUpload').addEventListener('click', () => fileInput.click());

// 拖拽上传
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
uploadZone.addEventListener('drop', async e => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const items = e.dataTransfer.items;
    if (items && items.length > 0 && items[0].webkitGetAsEntry) {
        // 支持文件夹拖拽
        const allFiles = [];
        async function traverse(entry, path) {
            if (entry.isFile) {
                const file = await new Promise(resolve => entry.file(resolve));
                file._relativePath = path + file.name;
                allFiles.push(file);
            } else if (entry.isDirectory) {
                const reader = entry.createReader();
                const entries = await new Promise(resolve => reader.readEntries(resolve));
                for (const child of entries) {
                    await traverse(child, path + entry.name + '/');
                }
            }
        }
        for (const item of items) {
            const entry = item.webkitGetAsEntry();
            if (entry) await traverse(entry, '');
        }
        handleFiles(allFiles);
    } else {
        handleFiles(e.dataTransfer.files);
    }
});

fileInput.addEventListener('change', () => {
    handleFiles(fileInput.files);
    fileInput.value = '';
});

// 文件夹上传
const folderInput = document.getElementById('folderInput');
document.getElementById('btnUploadFolder').addEventListener('click', () => folderInput.click());
folderInput.addEventListener('change', () => {
    handleFiles(folderInput.files);
    folderInput.value = '';
});

// uploadZone 点击由内部 file input 直接处理，无需额外 click 事件

async function handleFiles(files) {
    if (!files || files.length === 0) return;
    const formData = new FormData();
    for (const f of files) {
        formData.append('files[]', f);
        // 文件夹上传用单独字段传路径（拖拽: _relativePath, 选择器: webkitRelativePath）
        formData.append('paths[]', f._relativePath || f.webkitRelativePath || '');
    }

    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const progressPercent = document.getElementById('progressPercent');

    progressBar.classList.add('active');
    progressFill.style.width = '0%';
    progressText.textContent = `正在上传 ${files.length} 个文件...`;
    progressPercent.textContent = '0%';

    try {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload.php');
        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = pct + '%';
                progressPercent.textContent = pct + '%';
            }
        };
        const result = await new Promise((resolve, reject) => {
            xhr.onload = () => {
                try { resolve(JSON.parse(xhr.responseText)); } catch { reject(new Error('解析响应失败')); }
            };
            xhr.onerror = () => reject(new Error('网络错误'));
            xhr.send(formData);
        });

        if (result.code === 200) {
            const uploaded = result.data.uploaded || [];
            const errors = result.data.errors || [];
            if (uploaded.length > 0) toast(`成功上传 ${uploaded.length} 个文件`, 'success');
            if (errors.length > 0) errors.forEach(e => toast(e, 'error'));
            loadFiles();
            updateStats();
        } else {
            toast(result.msg || '上传失败', 'error');
            (result.data?.errors || []).forEach(e => toast(e, 'error'));
        }
    } catch (e) {
        toast('上传失败: ' + e.message, 'error');
    }

    progressBar.classList.remove('active');
}

// === 预览 ===
const previewableExts = ['jpg','jpeg','png','gif','bmp','webp','svg','ico','mp4','webm','mp3','wav','ogg','flac','aac','m4a','pdf'];
function isPreviewable(ext) { return previewableExts.includes(ext); }

function previewFile(id, filename, ext) {
    const url = 'view.php?id=' + id;
    const overlay = document.createElement('div');
    overlay.className = 'preview-overlay';

    let mediaHtml = '';
    if (['jpg','jpeg','png','gif','bmp','webp','svg','ico'].includes(ext)) {
        mediaHtml = `<img src="${url}" alt="${escapeHtml(filename)}">`;
    } else if (['mp4','webm'].includes(ext)) {
        mediaHtml = `<video src="${url}" controls autoplay></video>`;
    } else if (['mp3','wav','ogg','flac','aac','m4a'].includes(ext)) {
        mediaHtml = `<audio src="${url}" controls autoplay></audio>`;
    } else if (ext === 'pdf') {
        mediaHtml = `<iframe src="${url}" style="width:90vw;height:90vh;border:none;border-radius:8px;"></iframe>`;
    } else {
        mediaHtml = `<iframe src="${url}" style="width:90vw;height:90vh;border:none;border-radius:8px;"></iframe>`;
    }

    overlay.innerHTML = `
        <button class="preview-close" onclick="this.parentElement.remove()">&times;</button>
        ${mediaHtml}
        <div class="preview-info">${escapeHtml(filename)}</div>
    `;
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.remove();
    });
    document.addEventListener('keydown', function escClose(e) {
        if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', escClose); }
    });
    document.body.appendChild(overlay);
}

// === 刷新 ===
document.getElementById('btnRefresh').addEventListener('click', () => loadFiles());

// === 新建文件夹 ===
document.getElementById('btnNewFolder').addEventListener('click', () => {
    const name = prompt('请输入文件夹名称:');
    if (!name || !name.trim()) return;
    fetch('mkdir.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name.trim(), path: state.currentPath }),
    }).then(r => r.json()).then(json => {
        if (json.code === 200) {
            toast('文件夹已创建', 'success');
            loadFiles();
            updateStats();
        } else {
            toast(json.msg, 'error');
        }
    }).catch(() => toast('创建失败', 'error'));
});

// === 关闭右键菜单 ===
document.addEventListener('click', () => hideContextMenu());

// === 开始加载 ===
// 恢复视图偏好
if (state.viewMode === 'grid') {
    document.querySelectorAll('.view-btn').forEach(b => { b.classList.toggle('active', b.dataset.view === 'grid'); });
}
updateStats();
loadFiles();
</script>
</body>
</html>
