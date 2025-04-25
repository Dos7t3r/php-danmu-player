-- OldBili Player 数据库结构
-- 使用ThinkPHP迁移后的SQL文件

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 用户表
-- ----------------------------
DROP TABLE IF EXISTS `obp_admin`;
CREATE TABLE `obp_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0禁用，1启用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 插入默认管理员账号
INSERT INTO `obp_admin` VALUES (1, 'admin', '$2y$10$YMpVnZmWnqWgAVm0JsG.0.Xt9aZfBQcVVzNxUZPPRUTQj0yLsz0yK', '管理员', 'admin@example.com', NULL, 1, NULL, NULL, NOW(), NOW());

-- ----------------------------
-- 视频表
-- ----------------------------
DROP TABLE IF EXISTS `obp_videos`;
CREATE TABLE `obp_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '视频标题',
  `video_url` varchar(255) NOT NULL COMMENT '视频URL',
  `subtitle_url` varchar(255) DEFAULT NULL COMMENT '字幕URL',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图片',
  `description` text COMMENT '视频描述',
  `play_count` int(11) DEFAULT '0' COMMENT '播��次数',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0禁用，1启用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频表';

-- ----------------------------
-- 在线用户表
-- ----------------------------
DROP TABLE IF EXISTS `obp_online_users`;
CREATE TABLE `obp_online_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(64) NOT NULL COMMENT '用户唯一标识',
  `video_id` varchar(64) DEFAULT '' COMMENT '视频ID',
  `ip_address` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(255) DEFAULT NULL COMMENT '用户代理',
  `last_active` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后活跃时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `video_id` (`video_id`),
  KEY `last_active` (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在线用户表';

-- ----------------------------
-- 系统设置表
-- ----------------------------
DROP TABLE IF EXISTS `obp_settings`;
CREATE TABLE `obp_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL COMMENT '设置键',
  `value` text COMMENT '设置值',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- 插入默认设置
INSERT INTO `obp_settings` VALUES (1, 'site_name', 'OldBili Player', '站点名称', NOW(), NOW());
INSERT INTO `obp_settings` VALUES (2, 'site_description', '弹幕视频播放器', '站点描述', NOW(), NOW());
INSERT INTO `obp_settings` VALUES (3, 'player_version', '2.0.0', '播放器版本', NOW(), NOW());
INSERT INTO `obp_settings` VALUES (4, 'online_timeout', '120', '在线超时时间(秒)', NOW(), NOW());

-- ----------------------------
-- 操作日志表
-- ----------------------------
DROP TABLE IF EXISTS `obp_operation_log`;
CREATE TABLE `obp_operation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL COMMENT '管理员ID',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `operation` varchar(255) NOT NULL COMMENT '操作内容',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `method` varchar(10) DEFAULT NULL COMMENT '请求方法',
  `url` varchar(255) DEFAULT NULL COMMENT '请求URL',
  `params` text COMMENT '请求参数',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

SET FOREIGN_KEY_CHECKS = 1;
