-- =============================================
-- 管理员管理模块 数据库脚本
-- 版本: 1.0
-- 日期: 2026-06-21
-- 说明: 管理员表、分组表、权限表、操作日志表
-- =============================================

-- =============================================
-- 1. 管理员分组表
-- =============================================
DROP TABLE IF EXISTS `admin_groups`;

CREATE TABLE `admin_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` VARCHAR(64) NOT NULL COMMENT '分组名称',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '分组描述',
  `permissions` TEXT COMMENT '权限列表(JSON格式)',
  `is_deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除:0否 1是',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员分组表';

-- =============================================
-- 2. 管理员表
-- =============================================
DROP TABLE IF EXISTS `admins`;

CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username` VARCHAR(64) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码(加密)',
  `real_name` VARCHAR(64) DEFAULT NULL COMMENT '真实姓名',
  `email` VARCHAR(128) DEFAULT NULL COMMENT '邮箱',
  `group_id` INT UNSIGNED DEFAULT NULL COMMENT '所属分组ID',
  `is_super` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超级管理员:0否 1是',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:1启用 2禁用',
  `last_login_ip` VARCHAR(45) DEFAULT NULL COMMENT '最后登录IP',
  `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `login_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录次数',
  `is_deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除:0否 1是',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- =============================================
-- 3. 操作日志表
-- =============================================
DROP TABLE IF EXISTS `admin_logs`;

CREATE TABLE `admin_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `admin_id` INT UNSIGNED DEFAULT NULL COMMENT '管理员ID',
  `admin_name` VARCHAR(64) DEFAULT NULL COMMENT '管理员名称',
  `module` VARCHAR(32) NOT NULL COMMENT '模块:invitation/admin/group/log',
  `action` VARCHAR(32) NOT NULL COMMENT '操作:create/update/delete/login/logout',
  `title` VARCHAR(255) NOT NULL COMMENT '操作标题',
  `content` TEXT COMMENT '操作详情(JSON)',
  `ip` VARCHAR(45) DEFAULT NULL COMMENT '操作IP',
  `user_agent` VARCHAR(255) DEFAULT NULL COMMENT '浏览器信息',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_module` (`module`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- =============================================
-- 初始数据
-- =============================================

-- 默认分组
INSERT INTO `admin_groups` (`name`, `description`, `permissions`) VALUES
('超级管理员', '拥有所有权限', '["invitation:view","invitation:create","invitation:edit","invitation:delete","invitation:batch","apikey:view","apikey:create","apikey:delete","admin:view","admin:create","admin:edit","admin:delete","group:view","group:create","group:edit","group:delete","log:view"]'),
('操作员', '邀请码日常操作', '["invitation:view","invitation:create","invitation:edit","invitation:batch","apikey:view"]'),
('审核员', '仅查看和审核', '["invitation:view","invitation:edit","apikey:view"]'),
('访客', '仅查看权限', '["invitation:view","apikey:view"]');

-- 初始超级管理员 (密码: admin123456)
INSERT INTO `admins` (`username`, `password`, `real_name`, `group_id`, `is_super`, `status`) VALUES
('admin', '$2y$10$9ZYDI2l5.y09Ie5TkHZwmeMJsCDZknP9myWmFiXk72X/fMlqXWjKO', '超级管理员', 1, 1, 1);
