-- =============================================
-- 核销与统计增量脚本
-- 版本: 1.1
-- 日期: 2026-06-21
-- 说明: 增加核销字段、API密钥表、统计与导出功能、完整闭环
-- =============================================

-- 1. 邀请码表增加核销相关字段
ALTER TABLE `invitation_codes`
  ADD COLUMN `used_at` DATETIME DEFAULT NULL COMMENT '使用时间' AFTER `used_by`,
  ADD COLUMN `used_ip` VARCHAR(45) DEFAULT NULL COMMENT '使用时IP' AFTER `used_at`;

-- 为已有已使用记录填充默认值
UPDATE `invitation_codes` SET `used_at` = `updated_at` WHERE `status` = 2 AND `used_by` IS NOT NULL AND `used_at` IS NULL;

-- 为核销查询优化添加索引
ALTER TABLE `invitation_codes` ADD INDEX `idx_used_at` (`used_at`);
ALTER TABLE `invitation_codes` ADD INDEX `idx_expire_at` (`expire_at`);

-- 2. API密钥表（用于外部系统核销认证）
DROP TABLE IF EXISTS `api_keys`;
CREATE TABLE `api_keys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` VARCHAR(64) NOT NULL COMMENT '密钥名称/用途',
  `api_key` VARCHAR(64) NOT NULL COMMENT 'API密钥',
  `is_deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除:0否 1是',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API密钥表';

-- 初始API密钥（用于测试，生产环境请更换）
INSERT INTO `api_keys` (`name`, `api_key`) VALUES
('默认核销密钥', 'sk_inv_default_2026_demo');

-- 3. 更新操作日志表，增加状态变更动作支持（如果需要）
-- 操作日志表中 action 字段已涵盖 verify/status_change 等类型
-- 可根据需要为日志表增加索引
ALTER TABLE `admin_logs` ADD INDEX `idx_module_action` (`module`, `action`);
