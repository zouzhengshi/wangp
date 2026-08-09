<?php
require_once __DIR__ . '/../config/config.php';

$siteName = htmlspecialchars(getSetting('site_name', '文件管理系统'));
require_once __DIR__ . '/../views/home.php';
