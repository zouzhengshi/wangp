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
    allowDownload: document.body.dataset.allowDownload === 'true',
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
const fileCache = new Map();
function clearFileCache() { fileCache.clear(); }
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
        per_page: state.viewMode === 'grid' ? 500 : state.perPage,
        sort: state.sort,
        order: state.order,
        search: state.search,
        ext: state.ext,
        path: state.currentPath,
    });
    const cacheKey = params.toString();
    if (fileCache.has(cacheKey)) {
        const c = fileCache.get(cacheKey);
        state.files = c.files; state.folders = c.folders; state.total = c.total;
        state.totalPages = c.totalPages; state.currentPath = c.currentPath;
        renderBreadcrumbs(); renderTable(); renderPagination();
        return;
    }
    try {
        const res = await fetch('api.php?' + params.toString(), { signal: loadFilesAborter.signal });
        const json = await res.json();
        if (json.code === 200) {
            state.files = json.data.files;
            state.folders = json.data.folders || [];
            state.total = json.data.total;
            state.totalPages = json.data.total_pages;
            state.currentPath = json.data.current_path || '';
            fileCache.set(cacheKey, {
                files: json.data.files, folders: json.data.folders || [],
                total: json.data.total, totalPages: json.data.total_pages,
                currentPath: json.data.current_path || '',
            });
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
function deleteFolder(fullpath) {
    showDeleteModal([], [fullpath]);
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
const thumbnailExts = ['jpg','jpeg','png','gif','bmp','webp'];
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
                const imageUrl = thumbnailExts.includes(f.file_ext)
                    ? 'thumb.php?id=' + f.id + '&size=480'
                    : 'view.php?id=' + f.id;
                thumbHtml = '<img src="' + imageUrl + '" alt="" loading="lazy" decoding="async">';
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
    const url = new URL('download.php?id=' + id, location.href).href;
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
    const modal = document.getElementById('deleteModal');
    const closeModal = () => modal.classList.add('is-hidden');
    modal.classList.remove('is-hidden');
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
            clearFileCache();
            loadFiles();
            updateStats();
        } catch (e) {
            toast('删除失败', 'error');
        }
        closeModal();
    };
    document.getElementById('btnCancelDelete').onclick = closeModal;
}

// === 分页 ===
function renderPagination() {
    if (state.viewMode === 'grid') { document.getElementById('pagination').innerHTML = ''; return; }
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
            clearFileCache();
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
    const overlay = document.createElement('div');
    overlay.className = 'preview-overlay';
    overlay.innerHTML = '<div class="modal" style="background:#fff;cursor:default;text-align:left;max-width:340px;" onclick="event.stopPropagation();">' +
        '<h3 style="margin-bottom:12px;">📁 新建文件夹</h3>' +
        '<input id="newFolderName" type="text" placeholder="输入文件夹名称" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;font-family:inherit;" onkeydown="if(event.key===\'Enter\')this.parentElement.querySelector(\'.btn-confirm\').click();">' +
        '<div style="display:flex;gap:8px;margin-top:14px;">' +
            '<button class="btn btn-primary btn-confirm" style="flex:1;justify-content:center;">创建</button>' +
            '<button class="btn" style="flex:1;justify-content:center;" onclick="this.closest(\'.preview-overlay\').remove();">取消</button>' +
        '</div>' +
    '</div>';
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
    setTimeout(() => overlay.querySelector('#newFolderName').focus(), 100);

    overlay.querySelector('.btn-confirm').addEventListener('click', () => {
        const name = overlay.querySelector('#newFolderName').value.trim();
        if (!name) return;
        overlay.remove();
        fetch('mkdir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, path: state.currentPath }),
        }).then(r => r.json()).then(json => {
            if (json.code === 200) {
                toast('文件夹已创建', 'success');
                clearFileCache();
                loadFiles();
                updateStats();
            } else {
                toast(json.msg, 'error');
            }
        }).catch(() => toast('创建失败', 'error'));
    });
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
