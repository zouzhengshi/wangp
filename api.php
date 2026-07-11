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

    if ($search !== '') {
        $where[] = '`filename` LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }
    if ($ext !== '') {
        $where[] = '`file_ext` = :ext';
        $params[':ext'] = strtolower($ext);
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

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

    jsonResponse(200, 'ok', [
        'files'       => $files,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => ceil($total / $perPage),
    ]);

} catch (PDOException $e) {
    jsonResponse(500, '数据库错误: ' . $e->getMessage());
}
