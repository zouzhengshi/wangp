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
    <title>文件管理系统</title>
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
            th:nth-child(6), td:nth-child(6),
            th:nth-child(7), td:nth-child(7) { display: none; }
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

            /* 表格转卡片 */
            .file-table-wrap { background: transparent; box-shadow: none; border-radius: 0; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tbody tr {
                background: var(--card-bg);
                border-radius: var(--radius);
                box-shadow: var(--shadow);
                margin-bottom: 10px;
                padding: 12px;
                position: relative;
                border: 1px solid var(--border);
            }
            tbody tr.selected {
                border-color: var(--primary);
                box-shadow: 0 0 0 2px rgba(79,70,229,.15);
            }
            .check-col {
                position: absolute;
                top: 12px;
                left: 12px;
                width: auto;
                z-index: 1;
            }
            .check-col input { width: 18px; height: 18px; }
            td {
                padding: 4px 0;
                border-bottom: none;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            td:first-of-type { display: none; } /* 隐藏多余的 checkbox 列 td */
            td:nth-of-type(2) { /* 图标 + 文件名放一起 */ }
            .file-icon { width: 28px; height: 28px; font-size: 16px; margin-right: 4px; }
            .file-name { max-width: none; white-space: normal; word-break: break-all; font-size: 14px; }
            .file-meta { font-size: 11px; }
            .file-ext { font-size: 10px; padding: 1px 6px; }

            /* 卡片内操作按钮 */
            .actions { margin-top: 6px; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 8px; }
            .actions .btn { padding: 6px 14px; font-size: 13px; }

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
    </style>
</head>
<body>

<!-- 顶部 -->
<header class="header">
    <a href="index.php" class="header-brand">
        <i class="fa-solid fa-cloud-arrow-up"></i> 文件管理系统
    </a>
    <div class="header-stats">
        <span><i class="fa-regular fa-file"></i> 文件数: <strong id="statFiles">--</strong></span>
        <span><i class="fa-solid fa-hard-drive"></i> 总大小: <strong id="statSize">--</strong></span>
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
        <p>支持图片、文档、压缩包、音视频、代码等上百种文件格式 · 单文件最大 200MB</p>
        <input type="file" id="fileInput" multiple>
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
        <button class="btn btn-danger" id="btnDeleteSelected" style="display:none;">
            <i class="fa-solid fa-trash"></i> 删除选中
        </button>
        <button class="btn" id="btnRefresh">
            <i class="fa-solid fa-rotate"></i> 刷新
        </button>
    </div>

    <!-- 格式筛选 -->
    <div class="ext-filter" id="extFilter">
        <span class="ext-tag active" data-ext="">全部</span>
        <!-- 动态生成 -->
    </div>

    <!-- 文件列表 -->
    <div class="file-table-wrap">
        <table>
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
async function loadFiles() {
    const params = new URLSearchParams({
        action: 'list',
        page: state.page,
        per_page: state.perPage,
        sort: state.sort,
        order: state.order,
        search: state.search,
        ext: state.ext,
    });
    try {
        const res = await fetch('api.php?' + params.toString());
        const json = await res.json();
        if (json.code === 200) {
            state.files = json.data.files;
            state.total = json.data.total;
            state.totalPages = json.data.total_pages;
            renderTable();
            renderPagination();
            updateStats();
        }
    } catch (e) {
        toast('加载文件列表失败', 'error');
    }
}

// === 加载统计 ===
async function updateStats() {
    try {
        const res = await fetch('api.php?action=stats');
        const json = await res.json();
        if (json.code === 200) {
            document.getElementById('statFiles').textContent = json.data.total_files;
            document.getElementById('statSize').textContent = json.data.total_size;
            renderExtFilter(json.data.extensions || []);
        }
    } catch (e) {}
}

// === 渲染格式筛选 ===
function renderExtFilter(exts) {
    const container = document.getElementById('extFilter');
    let html = '<span class="ext-tag active" data-ext="">全部</span>';
    exts.slice(0, 20).forEach(e => {
        html += `<span class="ext-tag" data-ext="${e.file_ext}">${e.file_ext} (${e.cnt})</span>`;
    });
    container.innerHTML = html;
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

// === 渲染表格 ===
function renderTable() {
    if (state.files.length === 0) {
        fileTableBody.innerHTML = `<tr><td colspan="8">
            <div class="empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <h3>暂无文件</h3>
                <p>上传文件开始使用吧</p>
            </div>
        </td></tr>`;
        return;
    }
    fileTableBody.innerHTML = state.files.map(f => `
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
                    <a href="download.php?id=${f.id}" class="btn" title="下载"><i class="fa-solid fa-download"></i></a>
                    <button class="btn btn-danger" onclick="deleteFile(${f.id})" title="删除"><i class="fa-solid fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
    bindRowEvents();
    updateSelectAll();
    updateDeleteBtn();
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
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
    });
}

// === 全选 ===
selectAll.addEventListener('change', () => {
    if (selectAll.checked) {
        state.files.forEach(f => state.selectedIds.add(f.id));
    } else {
        state.selectedIds.clear();
    }
    renderTable();
});

function updateSelectAll() {
    if (state.files.length === 0) { selectAll.checked = false; return; }
    selectAll.checked = state.files.length > 0 && state.selectedIds.size === state.files.length;
}

function updateDeleteBtn() {
    btnDeleteSelected.style.display = state.selectedIds.size > 0 ? '' : 'none';
    btnDeleteSelected.innerHTML = `<i class="fa-solid fa-trash"></i> 删除选中 (${state.selectedIds.size})`;
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
    showDeleteModal([...state.selectedIds]);
});

function showDeleteModal(ids) {
    document.getElementById('deleteModal').style.display = '';
    document.getElementById('deleteModalText').textContent = `确定要删除 ${ids.length} 个文件吗？此操作不可恢复。`;
    document.getElementById('btnConfirmDelete').onclick = async () => {
        try {
            const res = await fetch('delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids }),
            });
            const json = await res.json();
            if (json.code === 200) {
                toast(`已删除 ${json.data.deleted_count} 个文件`, 'success');
                ids.forEach(id => state.selectedIds.delete(id));
                loadFiles();
            } else {
                toast(json.msg, 'error');
            }
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
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener('change', () => {
    handleFiles(fileInput.files);
    fileInput.value = '';
});

// uploadZone 点击由内部 file input 直接处理，无需额外 click 事件

async function handleFiles(files) {
    if (!files || files.length === 0) return;
    const formData = new FormData();
    for (const f of files) { formData.append('files[]', f); }

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
        } else {
            toast(result.msg || '上传失败', 'error');
            (result.data?.errors || []).forEach(e => toast(e, 'error'));
        }
    } catch (e) {
        toast('上传失败: ' + e.message, 'error');
    }

    progressBar.classList.remove('active');
}

// === 刷新 ===
document.getElementById('btnRefresh').addEventListener('click', () => loadFiles());

// === 关闭右键菜单 ===
document.addEventListener('click', () => hideContextMenu());

// === 开始加载 ===
loadFiles();
</script>
</body>
</html>
