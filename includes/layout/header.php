<?php
if (!defined('IN_SYSTEM')) {
    exit;
}

require_once __DIR__ . '/../Auth.php';

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($page_title) ? Utils::e($page_title) . ' - ' : ''; ?>邀请码管理系统</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<nav class="navbar navbar-static-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
                <span class="sr-only">切换导航</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="index.php">
                <i class="fa fa-ticket" style="margin-right:8px;"></i>邀请码管理系统
            </a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <li<?php echo ($current_page === 'invitation') ? ' class="active"' : ''; ?>>
                    <a href="index.php"><i class="fa fa-ticket"></i> 邀请码管理</a>
                </li>
                <li<?php echo ($current_page === 'apikey') ? ' class="active"' : ''; ?>>
                    <a href="apikeys.php" data-permission="apikey:view"><i class="fa fa-key"></i> API密钥</a>
                </li>
                <li class="dropdown<?php echo (in_array($current_page, array('admin', 'group'))) ? ' active' : ''; ?>">
                    <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-users"></i> 系统管理 <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="admins.php"><i class="fa fa-user-circle"></i> 管理员管理</a></li>
                        <li><a href="groups.php"><i class="fa fa-sitemap"></i> 分组权限</a></li>
                        <li><a href="logs.php"><i class="fa fa-file-text-o"></i> 操作日志</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-user-circle-o"></i> <span id="navUsername">加载中...</span> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="javascript:;" id="changePasswordBtn"><i class="fa fa-key"></i> 修改密码</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="javascript:;" id="logoutBtn"><i class="fa fa-sign-out"></i> 退出登录</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="main-container">
