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

    // 路径筛选：精确匹配当前目录
    $where[] = '`filepath` = :filepath';
    $params[':filepath'] = $path;

    if ($search !== '') {
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

    // 查询子文件夹
    $folders = [];
    $folderStmt = $db->prepare(
        "SELECT DISTINCT
            SUBSTRING_INDEX(SUBSTR(`filepath`, :len), '/', 1) AS `name`,
            CONCAT(:path_prefix, SUBSTRING_INDEX(SUBSTR(`filepath`, :len2), '/', 1), '/') AS `fullpath`
         FROM `files`
         WHERE `filepath` LIKE :path_like
           AND `filepath` != :path_exact
           AND LENGTH(`filepath`) > :minlen"
    );
    $pathLen = strlen($path) + 1;
    $folderStmt->execute([
        ':len'        => $pathLen,
        ':len2'       => $pathLen,
        ':path_prefix'=> $path,
        ':path_like'  => $path . '%',
        ':path_exact' => $path,
        ':minlen'     => strlen($path),
    ]);
    while ($row = $folderStmt->fetch()) {
        if ($row['name'] !== '') {
            $folders[] = [
                'name'     => $row['name'],
                'fullpath' => $row['fullpath'],
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
