# 文件管理系统

一个基于 PHP、PDO 和 MySQL 的轻量级文件管理与内容分享系统。项目同时提供网盘、发帖、附件上传、媒体预览、管理员后台和基础 API，适合部署在个人服务器、局域网或小型团队环境中。

## 功能概览

### 网盘

- 支持单文件、多文件和文件夹拖拽上传。
- 文件夹上传时保留原始目录结构。
- 支持文件名搜索、扩展名筛选、排序、分页和目录导航。
- 支持列表视图和网格视图，可预览图片、视频、音频、PDF 和文本类文件。
- 支持文件下载、批量下载、批量删除和新建文件夹。
- 下载和媒体预览支持 ETag、`304 Not Modified`、HTTP Range 断点读取。
- 首页显示文件数量、总大小和格式统计。
- 单文件最大上传大小、下载开关等参数可在后台调整。

### 发帖

- 发布标题、作者和正文。
- 每篇帖子最多上传 8 个图片、视频或普通附件。
- 图片和视频在详情页中预览或播放，普通文件显示附件信息并提供下载。
- 支持 PDF、Office 文档、压缩包、代码文件等系统允许格式。
- 使用异步上传，上传过程中显示实时进度条，完成后自动跳转到帖子详情。
- 管理员登录后可以删除帖子及其关联文件。

### 管理后台

- 管理站点名称。
- 设置网盘上传大小限制和发帖上传大小限制，范围为 1–10240 MB。
- 开启或关闭文件下载与预览访问。
- 查看文件总数和占用空间。
- 修改管理员密码。
- 管理员登录状态用于删除帖子和访问系统设置。

## 技术栈

- PHP 7.4+
- PDO MySQL
- MySQL 5.7+ 或兼容版本
- 原生 HTML、CSS 和 JavaScript
- Font Awesome 6（页面图标，通过 CDN 加载）
- 支持 Nginx、Apache 和 IIS

## 项目结构

```text
.
├── app/
│   ├── config/
│   │   ├── config.php              # 本地数据库配置，不提交到 Git
│   │   └── config.example.php      # 配置模板
│   ├── controllers/
│   │   ├── home.php                # 首页入口
│   │   ├── file-manager.php        # 网盘页面准备逻辑
│   │   ├── forum.php               # 帖子列表准备逻辑
│   │   ├── post.php                # 帖子详情准备逻辑
│   │   ├── admin-login.php         # 后台登录逻辑
│   │   └── admin.php               # 后台设置与统计逻辑
│   └── views/
│       ├── home.php                # 首页
│       ├── file-manager.php        # 网盘页面
│       ├── forum/                  # 帖子列表与详情页面
│       ├── admin/                  # 后台页面
│       └── init.php                # 初始化结果页面
├── public/                         # Web 服务器站点根目录
│   ├── index.php                   # 首页入口
│   ├── drive.php                   # 网盘页面入口
│   ├── posts.php                   # 发帖页面入口
│   ├── post.php                    # 帖子详情入口
│   ├── admin/                      # 后台登录、设置和退出入口
│   ├── assets/
│   │   ├── css/                    # 页面样式
│   │   └── js/                     # 网盘和发帖脚本
│   ├── api.php                     # 文件列表和统计 API
│   ├── upload.php                  # 网盘文件上传
│   ├── download.php                # 网盘文件下载
│   ├── view.php                    # 网盘文件预览
│   ├── thumb.php                   # 网格缩略图
│   ├── batch_download.php          # 批量下载
│   ├── delete.php                  # 文件和文件夹删除
│   ├── mkdir.php                   # 新建文件夹
│   ├── create-post.php             # 创建帖子及附件
│   ├── post-media.php              # 帖子媒体读取和附件下载
│   ├── delete-post.php             # 删除帖子及附件
│   ├── init.php                    # 数据库初始化
│   ├── .user.ini                   # PHP 上传和执行参数示例
│   └── 404.html                    # 404 页面
├── storage/
│   └── uploads/                    # 实际上传文件，不提交用户数据
├── .gitignore
└── README.md
```

项目采用简单的控制器与视图分离结构：`public/` 只作为站点根目录，业务准备逻辑放在 `app/controllers/`，页面模板放在 `app/views/`。上传文件保存在 `storage/uploads/`，不直接暴露给 Web 服务器。

## 环境要求

- PHP 7.4 或更高版本。
- PHP PDO 和 PDO MySQL 扩展。
- MySQL 5.7 或更高版本。
- Nginx、Apache 或 IIS。
- PHP、Web 服务器和操作系统对上传目录具有读写权限。
- 若使用 CDN 图标，需要服务器或客户端可以访问 `cdnjs.cloudflare.com`。

## 快速部署

### 1. 获取项目

```bash
git clone https://github.com/zouzhengshi/wangp.git
cd wangp
```

Windows PowerShell：

```powershell
git clone https://github.com/zouzhengshi/wangp.git
Set-Location wangp
```

### 2. 创建数据库配置

Linux/macOS：

```bash
cp app/config/config.example.php app/config/config.php
```

Windows PowerShell：

```powershell
Copy-Item app/config/config.example.php app/config/config.php
```

然后编辑 `app/config/config.php`，填写以下配置：

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');
```

请确认数据库已经创建，并且配置用户拥有创建表、读写数据的权限。

### 3. 配置站点根目录

Web 服务器的站点根目录必须指向项目的 `public/`，不要指向项目根目录。这样可以避免直接暴露 `app/`、`storage/` 和配置文件。

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
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }
}
```

Apache 和 IIS 也应将站点目录设置为 `public/`，并确保 PHP 请求大小限制不小于应用设置。

### 4. 初始化数据库

浏览器访问：

```text
http://your-domain.com/init.php
```

初始化脚本会创建以下数据表：

- `files`：网盘文件和目录记录。
- `posts`：帖子记录。
- `post_media`：帖子图片、视频和附件记录。
- `admin`：管理员账号。
- `settings`：站点设置。

初始化完成后，建议立即删除或限制访问 `public/init.php`。默认管理员账号为 `admin`，默认密码为 `admin123`，首次登录后请立即修改密码。

### 5. 检查目录权限

确保 PHP 进程可以创建和写入：

```text
storage/uploads/
```

目录中保留的 `.htaccess` 和 `index.html` 用于阻止目录浏览和 PHP 文件直接执行。部署到 Nginx 或 IIS 时，也应配置等效的上传目录保护规则。

## 上传限制配置

应用层限制可以在后台设置：

- 网盘文件上传限制：默认 200 MB。
- 发帖附件上传限制：默认 500 MB。
- 单个设置最大值：10 GB。
- 每篇帖子最多附件数：8 个。

服务器层限制也必须同步调整。项目中的 `public/.user.ini` 提供了 PHP 配置示例：

```ini
upload_max_filesize = 10240M
post_max_size = 10240M
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
max_file_uploads = 200
```

同时检查：

- Nginx 的 `client_max_body_size`。
- Apache 的请求体限制和 PHP-FPM 配置。
- IIS 的 `maxAllowedContent` 和 PHP 配置。
- PHP-FPM、Apache 或 IIS 修改配置后的重启状态。

## 页面入口

| 地址 | 说明 | 权限 |
| --- | --- | --- |
| `/index.php` | 系统首页，选择网盘、发帖或后台 | 无需登录 |
| `/drive.php` | 网盘文件管理 | 无需登录 |
| `/posts.php` | 帖子列表和发布表单 | 发布无需登录 |
| `/post.php?id=1` | 查看帖子详情 | 无需登录 |
| `/admin/index.php` | 后台设置、统计和密码修改 | 管理员 |
| `/admin/login.php` | 管理员登录 | 无需登录 |
| `/init.php` | 数据库初始化 | 部署阶段使用 |

## API 与处理接口

所有接口都位于 `public/` 下。JSON 接口返回格式为：

```json
{
  "code": 200,
  "msg": "ok",
  "data": {}
}
```

### 网盘接口

| 方法和地址 | 说明 |
| --- | --- |
| `GET /api.php?action=list` | 获取当前目录文件和子目录，支持 `page`、`per_page`、`search`、`ext`、`path`、`sort`、`order` |
| `GET /api.php?action=stats` | 获取文件总数、总大小和扩展名统计 |
| `GET /api.php?action=folder_ids&path=...` | 获取目录及子目录中的文件 ID |
| `POST /upload.php` | 上传文件，使用 `files[]` 和可选的 `paths[]` 保留目录结构 |
| `GET/HEAD /download.php?id=1` | 下载网盘文件，支持 Range 和 ETag |
| `GET/HEAD /view.php?id=1` | 预览图片、视频、音频、PDF 和文本类文件 |
| `GET/HEAD /thumb.php?id=1` | 获取图片缩略图 |
| `POST /batch_download.php` | 按文件 ID 或目录路径生成 ZIP 下载 |
| `POST /delete.php` | 删除文件、文件夹或批量删除文件 |
| `POST /mkdir.php` | 创建目录占位记录 |

### 发帖接口

| 方法和地址 | 说明 |
| --- | --- |
| `POST /create-post.php` | 创建帖子，表单字段为 `title`、`author`、`content`、`media[]` |
| `GET/HEAD /post-media.php?id=1` | 读取帖子图片或视频；普通附件自动按下载处理 |
| `GET/HEAD /post-media.php?id=1&download=1` | 强制下载帖子附件或媒体 |
| `POST /delete-post.php` | 管理员删除帖子及其附件 |

## 文件格式

网盘和发帖上传共用 `app/config/config.php` 中的 `ALLOWED_EXTENSIONS` 白名单。默认覆盖以下类别：

- 图片：JPG、PNG、GIF、BMP、WebP、SVG、ICO、TIFF、PSD 等。
- 文档：PDF、DOC、DOCX、XLS、XLSX、PPT、PPTX、TXT、CSV 等。
- 压缩包：ZIP、RAR、7Z、TAR、GZ、BZ2、XZ、ISO 等。
- 音频：MP3、WAV、FLAC、AAC、OGG、WMA、M4A 等。
- 视频：MP4、AVI、MKV、MOV、WMV、FLV、WebM、M4V、3GP 等。
- 代码和配置：HTML、CSS、JS、PHP、Python、Java、C/C++、JSON、XML、YAML、SQL、Shell 等。
- 字体和设计文件：TTF、OTF、WOFF、SKETCH、FIG、XD 等。
- 3D 和其他文件：FBX、OBJ、STL、BLEND、GLB、APK、EXE、DMG 等。

如需限制格式，应同时调整白名单和前端文件选择器。修改后请重新测试网盘上传和发帖上传。

## 安全建议

- 不要提交 `app/config/config.php`，其中包含数据库密码；该文件已加入 `.gitignore`。
- 不要把 `storage/uploads/` 中的用户文件提交到 Git。
- 生产环境将站点根目录限定为 `public/`。
- 初始化完成后删除或限制 `public/init.php`。
- 首次部署后立即修改默认管理员密码，并建议启用 HTTPS。
- 不要让上传目录直接执行 PHP、脚本或其他服务端文件。
- 修改上传限制时同时检查 PHP 和 Web 服务器限制，避免大文件被中途截断。
- 关闭下载后，网盘下载和预览接口都会拒绝访问；帖子媒体接口仍按帖子附件逻辑工作。

## Git 开发约定

```bash
git status
git add -A
git commit -m "描述本次修改"
git push origin master
```

提交前请确认以下内容没有进入暂存区：

- `app/config/config.php`
- `storage/uploads/` 下的用户上传文件
- 生产环境日志和临时文件

## 常见问题

### 上传大文件失败

依次检查后台应用限制、PHP 的 `upload_max_filesize` 和 `post_max_size`、Web 服务器请求大小限制，以及 `max_execution_time` 和 `max_input_time`。

### 页面提示数据库未初始化

确认 `app/config/config.php` 的数据库连接正确，数据库服务已启动，并重新访问 `public/init.php`。

### 上传成功但无法预览或下载

检查 `storage/uploads/` 的权限、文件是否实际存在、站点根目录是否指向 `public/`，以及后台是否关闭了下载功能。

### 发帖上传进度不显示

确认浏览器启用了 JavaScript，并检查 `public/assets/js/forum.js` 是否能正常加载。进度条基于浏览器的 `XMLHttpRequest.upload` 事件，只在实际通过发帖表单上传时显示。

## License

MIT
