# 文件管理系统

基于 PHP 7.4 + MySQL 的轻量级文件管理系统，支持 100+ 文件格式的上传、预览、搜索、下载和文件夹管理。

## 功能特性

- 📁 **拖拽上传** — 支持拖拽文件/文件夹批量上传，自动保留目录结构
- 🔍 **文件搜索** — 按文件名、扩展名搜索，支持子目录递归搜索
- 📂 **文件夹管理** — 自动识别上传的目录层级，支持删除整个文件夹
- ⬇️ **断点续传** — 下载支持 ETag/304 缓存和 Range 分段请求
- 🎨 **图标展示** — 根据文件类型自动匹配对应图标
- 📊 **统计面板** — 文件总数、总大小、格式分布一目了然
- 🔐 **管理员后台** — 可设置上传大小上限、关闭下载、修改密码
- 📱 **响应式布局** — 适配桌面端和移动端
- ⚡ **超大文件** — 支持最高 10GB 单文件上传

## 支持格式

| 类别 | 格式 |
|------|------|
| 图片 | jpg, jpeg, png, gif, bmp, webp, svg, ico, tiff, psd, ai, eps |
| 文档 | pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv, rtf, odt, ods, odp |
| 压缩 | zip, rar, 7z, tar, gz, bz2, xz, iso |
| 音频 | mp3, wav, flac, aac, ogg, wma, m4a, ape |
| 视频 | mp4, avi, mkv, mov, wmv, flv, webm, m4v, 3gp |
| 代码 | html, css, js, ts, php, py, java, c, cpp, go, rs, json, xml, yaml, sql, sh |
| 字体 | ttf, otf, woff, woff2, eot |
| 其他 | apk, exe, dmg, deb, rpm, torrent, sketch, fig, fbx, stl, blend |

## 环境要求

- PHP 7.4+
- MySQL 5.7+
- Nginx / Apache

## 快速开始

### 1. 克隆项目

```bash
git clone https://github.com/zouzhengshi/wangp.git
cd wangp
```

### 2. 配置数据库

编辑 `config.php`，修改数据库连接信息：

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

### 3. 初始化数据库

访问 `http://your-domain/init.php`，自动创建数据表和默认管理员。

### 4. 删除初始化文件（安全）

```bash
rm init.php
```

### 5. 修改默认密码

默认管理员账号：**admin** / **admin123**，首次登录后请立即修改。

### 6. Nginx 配置参考

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/wangp;

    # 10GB 上传限制
    client_max_body_size 10240m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    # 保护上传目录
    location ~ /uploads/.*\.php$ {
        deny all;
    }
}
```

## 目录结构

```
.
├── index.php       # 主页面（前端界面）
├── api.php         # 文件列表 API
├── upload.php      # 文件上传处理
├── download.php    # 文件下载（支持断点续传）
├── delete.php      # 文件/文件夹删除
├── config.php      # 数据库配置 & 工具函数
├── init.php        # 数据库初始化（上线前删除）
├── admin/          # 管理员后台
├── uploads/        # 文件存储目录
└── .gitignore
```

## 管理员功能

- 修改站点名称
- 设置上传文件大小上限
- 启用/禁用下载功能
- 修改管理员密码

## License

MIT
