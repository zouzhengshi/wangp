# 文件管理系统

基于 PHP 7.4+、PDO 和 MySQL 的轻量级文件管理系统，支持文件夹上传、搜索、预览、下载、批量操作、发帖分享和管理员设置。

## 目录结构

```text
.
├── app/
│   ├── config/
│   │   ├── config.php          # 本地数据库与应用配置（不提交）
│   │   └── config.example.php  # 配置模板
│   ├── controllers/             # 请求处理与业务预处理
│   │   ├── file-manager.php
│   │   ├── admin.php
│   │   └── admin-login.php
│   └── views/                   # PHP 页面模板
│       ├── file-manager.php
│       └── admin/
│           ├── index.php
│           └── login.php
├── public/                     # Web 服务器站点根目录
│   ├── index.php               # 网盘/发帖入口选择页
│   ├── drive.php               # 文件管理首页
│   ├── assets/
│   │   ├── css/                # 页面样式
│   │   └── js/                 # 页面脚本
│   ├── api.php                 # 文件列表与统计 API
│   ├── posts.php               # 帖子列表与发布页面
│   ├── post.php                # 帖子详情页面
│   ├── create-post.php         # 发布帖子接口
│   ├── post-media.php           # 帖子媒体预览与附件下载接口
│   ├── upload.php              # 文件上传接口
│   ├── download.php            # 文件下载接口
│   ├── batch_download.php      # 批量下载接口
│   ├── view.php                # 文件预览接口
│   ├── thumb.php               # 网格缩略图接口
│   ├── delete.php              # 文件与文件夹删除接口
│   ├── mkdir.php               # 新建文件夹接口
│   ├── init.php                # 数据库初始化脚本
│   ├── 404.html                # 404 页面
│   └── admin/                  # 管理员后台入口
├── storage/
│   └── uploads/                # 用户上传文件（不提交到 Git）
├── .gitignore
└── README.md
```

请求入口位于 `public/`，页面业务逻辑位于 `app/controllers/`，页面模板位于 `app/views/`，样式和脚本统一放在 `public/assets/`。模板中仅保留必要的 PHP 数据输出，不再内嵌整段 CSS 或 JavaScript。

`storage/uploads/` 位于 `public/` 之外，上传文件只能通过应用接口读取，避免直接暴露存储目录。

## 发帖与附件

访问 `public/posts.php` 可以发布帖子。每篇帖子支持同时上传最多 8 个媒体或附件，单个文件大小由后台的“发帖上传大小限制”控制，允许的文件类型沿用系统文件白名单。

- JPG、PNG、GIF、WebP、BMP 图片支持详情页预览。
- MP4、WebM、MOV 视频支持在线播放。
- PDF、Office 文档、压缩包、代码和其他允许的文件会以附件卡片展示，并提供下载按钮。
- 发帖表单使用异步上传，上传附件时会显示实时进度条，并在完成后自动跳转到帖子详情。

## 环境要求

- PHP 7.4 或更高版本
- PDO MySQL 扩展
- MySQL 5.7 或更高版本
- Nginx、Apache 或 IIS

## 快速开始

### 1. 配置数据库

```bash
cp app/config/config.example.php app/config/config.php
```

然后编辑 `app/config/config.php`，填写数据库连接信息。Windows 环境可直接复制文件后重命名。

### 2. 配置站点根目录

Web 服务器的站点根目录必须指向项目的 `public/` 目录，而不是项目根目录。

Nginx 示例：

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/wangp/public;

    client_max_body_size 10g;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. 初始化数据库

访问 `http://your-domain.com/init.php` 创建数据表和默认管理员，然后立即删除 `public/init.php`。

默认管理员账号为 `admin`，默认密码为 `admin123`。首次登录后请立即修改密码。

### 4. 配置上传限制

除了后台中的应用上传限制外，PHP 和 Web 服务器本身也必须允许对应大小的请求。请按实际需求调整 `php.ini` 中的 `upload_max_filesize`、`post_max_size` 和 `max_file_uploads`，并确保 Nginx 的 `client_max_body_size` 或 IIS 请求大小限制不小于发帖文件限制。修改后需要重启 PHP-FPM、Apache 或 IIS。

## Git 注意事项

- `app/config/config.php` 包含本地数据库凭据，已加入忽略规则。
- `storage/uploads/` 中的用户文件已加入忽略规则。
- 只提交源代码、配置模板和部署文档，不提交生产数据。

## License

MIT
