<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title><?php echo $initializationError === null ? '初始化成功' : '数据库连接失败'; ?></title>
    <link rel="stylesheet" href="assets/css/init.css">
</head>
<body>
<main class="result-card <?php echo $initializationError === null ? 'is-success' : 'is-error'; ?>">
    <?php if ($initializationError === null): ?>
        <h1>✅ 数据库初始化成功</h1>
        <p>文件上传系统已就绪</p>
        <p class="credentials">🔑 默认管理员账号: admin / admin123</p>
        <p class="warning">⚠ 请立即删除 <code>init.php</code> 并修改默认密码</p>
        <p><a href="drive.php">前往文件管理 →</a></p>
    <?php else: ?>
        <h1>数据库连接失败</h1>
        <p><?php echo htmlspecialchars($initializationError); ?></p>
        <p>请检查 config.php 中的数据库配置。</p>
    <?php endif; ?>
</main>
</body>
</html>
