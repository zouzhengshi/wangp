<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $post ? htmlspecialchars($post['title']) : '帖子不存在'; ?> - <?php echo $siteName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/forum.css">
</head>
<body>
<main class="forum-shell forum-detail-shell">
    <header class="forum-header">
        <div>
            <a class="back-home" href="posts.php"><i class="fa-solid fa-arrow-left"></i> 返回帖子列表</a>
            <h1><i class="fa-solid fa-file-lines"></i> 帖子详情</h1>
        </div>
        <a class="header-drive-link" href="drive.php"><i class="fa-solid fa-hard-drive"></i> 网盘</a>
    </header>

    <?php if (!$post): ?>
        <div class="forum-alert forum-alert-error"><?php echo htmlspecialchars($postError); ?></div>
    <?php else: ?>
        <article class="post-detail-card">
            <div class="post-title-row">
                <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                <?php if ($adminUser): ?>
                    <form class="delete-post-form" action="delete-post.php" method="post"
                          data-confirm="确定删除这篇帖子吗？帖子中的图片、视频和附件也会被删除。">
                        <input type="hidden" name="id" value="<?php echo (int) $post['id']; ?>">
                        <button class="forum-button forum-button-danger" type="submit"><i class="fa-solid fa-trash"></i> 删除帖子</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="detail-meta">
                <span><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span>
                <time datetime="<?php echo htmlspecialchars($post['created_at']); ?>"><?php echo htmlspecialchars($post['created_at']); ?></time>
            </div>
            <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
            <?php if ($media): ?>
                <div class="post-media-grid" aria-label="帖子媒体">
                    <?php foreach ($media as $item): ?>
                        <div class="post-media-item">
                            <?php if ($item['media_type'] === 'video'): ?>
                                <div class="media-player" data-media-player data-source="post-media.php?id=<?php echo (int) $item['id']; ?>">
                                    <div class="media-player-stage">
                                        <video class="media-player-video" preload="metadata" playsinline
                                               src="post-media.php?id=<?php echo (int) $item['id']; ?>"
                                               type="<?php echo htmlspecialchars($item['mime_type']); ?>">
                                            当前浏览器不支持视频播放。
                                        </video>
                                        <button class="media-player-center" type="button" data-player-action="toggle-play" aria-label="播放">
                                            <i class="fa-solid fa-play"></i>
                                        </button>
                                    </div>
                                    <div class="media-player-controls">
                                        <button class="media-player-button" type="button" data-player-action="toggle-play" aria-label="播放">
                                            <i class="fa-solid fa-play"></i>
                                        </button>
                                        <label class="media-player-progress" aria-label="播放进度">
                                            <input type="range" min="0" max="100" step="0.1" value="0" data-player-progress>
                                        </label>
                                        <span class="media-player-time" data-player-time>00:00 / 00:00</span>
                                        <button class="media-player-button" type="button" data-player-action="toggle-mute" aria-label="静音">
                                            <i class="fa-solid fa-volume-high"></i>
                                        </button>
                                        <label class="media-player-volume" aria-label="音量">
                                            <input type="range" min="0" max="1" step="0.05" value="1" data-player-volume>
                                        </label>
                                        <div class="media-player-menu" data-player-menu data-menu-type="speed">
                                            <button class="media-player-menu-button" type="button" data-menu-toggle aria-expanded="false">
                                                倍速 <strong data-speed-label>1x</strong>
                                            </button>
                                            <div class="media-player-menu-list" data-menu-list>
                                                <button type="button" data-speed="0.5">0.5x</button>
                                                <button type="button" data-speed="1">1x</button>
                                                <button type="button" data-speed="1.25">1.25x</button>
                                                <button type="button" data-speed="1.5">1.5x</button>
                                                <button type="button" data-speed="2">2x</button>
                                            </div>
                                        </div>
                                        <div class="media-player-menu" data-player-menu data-menu-type="quality">
                                            <button class="media-player-menu-button" type="button" data-menu-toggle aria-expanded="false">
                                                清晰度 <strong data-quality-label>自动</strong>
                                            </button>
                                            <div class="media-player-menu-list" data-menu-list>
                                                <button type="button" data-quality="auto">自动</button>
                                                <button type="button" data-quality="original">原画 <span data-original-resolution></span></button>
                                            </div>
                                        </div>
                                        <button class="media-player-button" type="button" data-player-action="fullscreen" aria-label="全屏">
                                            <i class="fa-solid fa-expand"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php elseif ($item['media_type'] === 'image'): ?>
                                <a href="post-media.php?id=<?php echo (int) $item['id']; ?>" target="_blank" rel="noopener">
                                    <div class="post-media-visual">
                                        <img class="post-media-content" src="post-media.php?id=<?php echo (int) $item['id']; ?>" alt="<?php echo htmlspecialchars($item['original_name']); ?>" loading="lazy" decoding="async">
                                    </div>
                                </a>
                            <?php else: ?>
                                <?php $fileExtension = strtolower(pathinfo($item['original_name'], PATHINFO_EXTENSION)); ?>
                                <div class="post-file-card">
                                    <div class="post-file-icon"><i class="fa-solid <?php echo htmlspecialchars(getFileIcon($fileExtension)); ?>"></i></div>
                                    <div class="post-file-info">
                                        <strong title="<?php echo htmlspecialchars($item['original_name']); ?>"><?php echo htmlspecialchars($item['original_name']); ?></strong>
                                        <span><?php echo htmlspecialchars(formatSize((int) $item['file_size'])); ?> · <?php echo htmlspecialchars(strtoupper($fileExtension ?: '文件')); ?></span>
                                    </div>
                                    <a class="forum-button forum-button-primary post-file-download"
                                       href="post-media.php?id=<?php echo (int) $item['id']; ?>&amp;download=1"
                                       download>
                                        <i class="fa-solid fa-download"></i> 下载
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</main>
<script src="assets/js/forum.js" defer></script>
</body>
</html>
