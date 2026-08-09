<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $siteName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>
<main class="home-shell">
    <header class="home-header">
        <div class="home-logo"><i class="fa-solid fa-cloud-arrow-up"></i></div>
        <div>
            <p class="eyebrow">WELCOME</p>
            <h1><?php echo $siteName; ?></h1>
        </div>
    </header>

    <section class="home-intro">
        <p class="home-kicker">选择你要进入的空间</p>
        <h2>文件与内容，都在这里</h2>
        <p class="home-description">管理你的文件，或者发布一篇帖子与大家分享。</p>
    </section>

    <section class="entry-grid" aria-label="功能入口">
        <a class="entry-card entry-drive" href="drive.php">
            <span class="entry-icon"><i class="fa-solid fa-hard-drive"></i></span>
            <span class="entry-content">
                <strong>进入网盘</strong>
                <small>上传、预览、搜索和管理文件</small>
            </span>
            <i class="fa-solid fa-arrow-right entry-arrow"></i>
        </a>
        <a class="entry-card entry-posts" href="posts.php">
            <span class="entry-icon"><i class="fa-solid fa-comments"></i></span>
            <span class="entry-content">
                <strong>进入发帖</strong>
                <small>浏览内容，发布你的新帖子</small>
            </span>
            <i class="fa-solid fa-arrow-right entry-arrow"></i>
        </a>
        <a class="entry-card entry-admin" href="admin/index.php">
            <span class="entry-icon"><i class="fa-solid fa-gear"></i></span>
            <span class="entry-content">
                <strong>进入后台</strong>
                <small>管理上传限制、下载权限和管理员密码</small>
            </span>
            <i class="fa-solid fa-arrow-right entry-arrow"></i>
        </a>
    </section>
</main>
</body>
</html>
