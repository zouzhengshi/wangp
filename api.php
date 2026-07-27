<?php
/**
 * 文件列表 API
 * GET /api.php?action=list&page=1&per_page=20&search=xxx&ext=pdf
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, '仅支持 GET 请求');
}

$action = $_GET['action'] ?? 'list';

try {
    $db = getDB();

    if ($action === 'stats') {
        // 统计信息
        $total = $db->query('SELECT COUNT(*) FROM `files`')->fetchColumn();
        $totalSize = $db->query('SELECT COALESCE(SUM(`file_size`), 0) FROM `files`')->fetchColumn();
        $exts = $db->query('SELECT `file_ext`, COUNT(*) AS cnt FROM `files` GROUP BY `file_ext` ORDER BY cnt DESC')->fetchAll();

        jsonResponse(200, 'ok', [
            'total_files'    => (int) $total,
            'total_size'     => formatSize((int) $totalSize),
            'total_size_b'   => (int) $totalSize,
            'extensions'     => $exts,
            'allow_download' => isDownloadAllowed(),
        ]);
    }

    if ($action === 'folder_ids') {
        $fp = trim($_GET['path'] ?? '');
        $ids = $db->prepare("SELECT `id` FROM `files` WHERE `filepath` LIKE :fp")
                  ->execute([':fp' => $fp . '%'])
                  ->fetchAll(PDO::FETCH_COLUMN);
        jsonResponse(200, 'ok', ['ids' => array_map('intval', $ids)]);
    }

    // 文件列表（默认）
    $page     = max(1, (int) ($_GET['page'] ?? 1));
    $perPage  = min(100, max(10, (int) ($_GET['per_page'] ?? 20)));
    $search   = trim($_GET['search'] ?? '');
    $ext      = trim($_GET['ext'] ?? '');
    $path     = trim($_GET['path'] ?? '');
    $sort     = $_GET['sort'] ?? 'upload_time';
    $order    = strtoupper($_GET['order'] ?? 'DESC');

    // 白名单排序
    $allowedSort = ['id', 'filename', 'file_size', 'file_ext', 'upload_time', 'download_count'];
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'upload_time';
    }
    if (!in_array($order, ['ASC', 'DESC'], true)) {
        $order = 'DESC';
    }

    // 构建查询
    $where = [];
    $params = [];
    $isSearching = $search !== '';

    // 排除占位文件
    $where[] = "`file_ext` != 'folder'";

    // 路径筛选：搜索时搜索子目录，否则精确匹配当前目录
    if ($isSearching) {
        $where[] = '`filepath` LIKE :filepath';
        $params[':filepath'] = $path . '%';
    } else {
        $where[] = '`filepath` = :filepath';
        $params[':filepath'] = $path;
    }

    if ($isSearching) {
        // 搜索：匹配文件名（包含路径的文件名也算）
        $where[] = '`filename` LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }
    if ($ext !== '') {
        $where[] = '`file_ext` = :ext';
        $params[':ext'] = strtolower($ext);
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    // 总数
    $countStmt = $db->prepare("SELECT COUNT(*) FROM `files` $whereClause");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // 分页数据
    $offset = ($page - 1) * $perPage;
    $dataStmt = $db->prepare(
        "SELECT * FROM `files` $whereClause ORDER BY `$sort` $order LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $files = $dataStmt->fetchAll();

    // 格式化
    $files = array_map(function ($f) {
        return [
            'id'             => (int) $f['id'],
            'filename'       => $f['filename'],
            'stored_name'    => $f['stored_name'],
            'file_size'      => (int) $f['file_size'],
            'size_format'    => formatSize((int) $f['file_size']),
            'file_type'      => $f['file_type'],
            'file_ext'       => $f['file_ext'],
            'upload_time'    => $f['upload_time'],
            'download_count' => (int) $f['download_count'],
            'icon'           => getFileIcon($f['file_ext']),
        ];
    }, $files);

    // 查询子文件夹（PHP 提取，兼容性更好）
    $folders = [];
    $subStmt = $db->prepare(
        "SELECT DISTINCT `filepath` FROM `files`
         WHERE `filepath` LIKE :path_like
           AND `filepath` != :path_exact"
    );
    $subStmt->execute([
        ':path_like'  => ($isSearching ? $path : $path) . '%',
        ':path_exact' => $path,
    ]);
    $seen = [];
    while ($row = $subStmt->fetch()) {
        $fp = $row['filepath'];
        if ($fp === '' || $fp === $path) continue;
        $rest = substr($fp, strlen($path));
        $slashPos = strpos($rest, '/');
        if ($slashPos !== false) {
            $name = substr($rest, 0, $slashPos);
        } else {
            $name = rtrim($rest, '/');
        }
        // 搜索时过滤文件夹名
        if ($isSearching && stripos($name, $search) === false && stripos($fp, $search) === false) continue;
        if ($name !== '' && !isset($seen[$name])) {
            $seen[$name] = true;
            $fullpath = $path . $name . '/';
            // 统计该文件夹内文件数量和大小及最新时间
            $statStmt = $db->prepare("SELECT COUNT(*), COALESCE(SUM(`file_size`), 0), MAX(`upload_time`) FROM `files` WHERE `filepath` LIKE :fp_like");
            $statStmt->execute([':fp_like' => $fullpath . '%']);
            $stat = $statStmt->fetch(PDO::FETCH_NUM);
            $folders[] = [
                'name'       => $name,
                'fullpath'   => $fullpath,
                'file_count' => (int) $stat[0],
                'total_size' => formatSize((int) $stat[1]),
                'latest_time'=> $stat[2] ?? '',
            ];
        }
    }

    jsonResponse(200, 'ok', [
        'files'       => $files,
        'folders'     => $folders,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => ceil($total / $perPage),
        'current_path'=> $path,
    ]);

} catch (PDOException $e) {
    jsonResponse(500, '数据库错误: ' . $e->getMessage());
}
