<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发帖 - <?php echo $siteName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/forum.css">
</head>
<body>
<main class="forum-shell">
    <header class="forum-header">
        <div>
            <a class="back-home" href="index.php"><i class="fa-solid fa-arrow-left"></i> 返回入口</a>
            <h1><i class="fa-solid fa-comments"></i> 发帖</h1>
            <p>分享你的想法、经验和问题。</p>
        </div>
        <div class="forum-header-actions">
            <a class="header-drive-link" href="drive.php"><i class="fa-solid fa-hard-drive"></i> 网盘</a>
            <a class="header-drive-link" href="admin/index.php"><i class="fa-solid fa-gear"></i> 后台</a>
        </div>
    </header>

    <?php if ($errorMessage !== ''): ?>
        <div class="forum-alert forum-alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>
    <?php if ($successMessage !== ''): ?>
        <div class="forum-alert forum-alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    <?php if (!$adminUser): ?>
        <div class="forum-alert forum-alert-info">管理员登录后可以删除帖子。<a href="admin/index.php">进入后台登录</a></div>
    <?php endif; ?>

    <section class="post-composer">
        <div class="section-heading">
            <div>
                <p class="section-kicker">NEW POST</p>
                <h2>发布新帖子</h2>
            </div>
            <i class="fa-solid fa-pen-to-square"></i>
        </div>
        <form action="create-post.php" method="post" enctype="multipart/form-data" class="post-form" id="postForm">
            <label>
                标题
                <input type="text" name="title" maxlength="200" placeholder="请输入帖子标题" required>
            </label>
            <label>
                作者
                <input type="text" name="author" maxlength="64" placeholder="你的名字" value="匿名" required>
            </label>
            <label>
                正文
                <textarea name="content" rows="6" maxlength="100000" placeholder="写下你想分享的内容……" required></textarea>
            </label>
            <div class="media-upload">
                <label class="media-picker" for="mediaInput">
                    <i class="fa-solid fa-photo-film"></i>
                    <span>添加图片、视频或文件</span>
                    <small>支持多选，最多 8 个，单个文件最大 <?php echo htmlspecialchars(formatSize($postMaxFileSize)); ?></small>
                </label>
                <input class="media-input" id="mediaInput" type="file" name="media[]"
                       accept="<?php echo htmlspecialchars(implode(',', array_map(static function (string $extension): string { return '.' . $extension; }, ALLOWED_EXTENSIONS))); ?>" multiple>
                <div class="media-preview" id="mediaPreview" aria-live="polite"></div>
            </div>
            <div class="upload-progress" id="uploadProgress" hidden aria-live="polite">
                <div class="upload-progress-header">
                    <span id="uploadProgressText">准备上传</span>
                    <strong id="uploadProgressPercent">0%</strong>
                </div>
                <div class="upload-progress-track">
                    <div class="upload-progress-bar" id="uploadProgressBar"></div>
                </div>
            </div>
            <button class="forum-button forum-button-primary" id="postSubmitButton" type="submit"><i class="fa-solid fa-paper-plane"></i> 发布帖子</button>
        </form>
    </section>

    <section class="post-list-section">
        <div class="section-heading section-heading-list">
            <div>
                <p class="section-kicker">LATEST</p>
                <h2>最新帖子</h2>
            </div>
            <span class="post-count"><?php echo count($posts); ?> 篇</span>
        </div>

        <?php if (!$posts): ?>
            <div class="empty-posts"><i class="fa-regular fa-comment-dots"></i><p>还没有帖子，发布第一篇吧。</p></div>
        <?php else: ?>
            <div class="post-list">
                <?php foreach ($posts as $item): ?>
                    <?php $excerpt = trim(preg_replace('/\s+/', ' ', strip_tags($item['content']))); ?>
                    <div class="post-card-row">
                        <a class="post-card" href="post.php?id=<?php echo (int) $item['id']; ?>">
                            <div class="post-card-main">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo htmlspecialchars(function_exists('mb_substr') ? mb_substr($excerpt, 0, 160, 'UTF-8') : substr($excerpt, 0, 160)); ?></p>
                            </div>
                            <div class="post-meta">
                                <span><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($item['author']); ?></span>
                                <time datetime="<?php echo htmlspecialchars($item['created_at']); ?>"><?php echo htmlspecialchars($item['created_at']); ?></time>
                                <i class="fa-solid fa-chevron-right post-card-arrow"></i>
                            </div>
                        </a>
                        <?php if ($adminUser): ?>
                            <form class="delete-post-form post-card-delete" action="delete-post.php" method="post"
                                  data-confirm="确定删除这篇帖子吗？帖子中的图片、视频和附件也会被删除。">
                                <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                <button class="forum-button forum-button-danger" type="submit" title="删除帖子">
                                    <i class="fa-solid fa-trash"></i><span>删除</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<script src="assets/js/forum.js" defer></script>
</body>
</html>
