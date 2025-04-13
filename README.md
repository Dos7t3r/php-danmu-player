---

# 🎥 OldBili Player v1.3.4 - PHP + MySQL 增强版弹幕播放器

**一个仿哔哩哔哩风格、基于 ArtPlayer.js 构建的现代化弹幕视频播放器，现已全面升级为 MySQL 数据库存储在线用户数据，更稳定、更高效，适合嵌入网站、博客与各类 Web 应用中。**

---

## 🚀 更新日志 (v1.3.4)

- 🛢️ **全面使用 MySQL 数据库**：高效存储在线用户数据，解决 XML 文件并发损坏问题。
- 🐞 修复在线人数心跳失败问题，提升稳定性。
- ⚙️ 优化 PHP 后端脚本，提升响应速度。

---

## ✨ 功能亮点

- 📺 **弹幕全面支持**：自定义颜色、位置（滚动 / 顶部 / 底部），实时弹幕显示。
- 🔄 **响应式弹幕字号**：自动根据屏幕尺寸调整字体大小，优化不同设备体验。
- 📋 **弹幕列表面板**：一键查看所有弹幕，含发送时间、内容、视频位置。
- 📡 **在线人数统计**：基于 MySQL 数据库高效记录在线用户，通过定时心跳实时统计。
- 🎨 **高颜值通知系统**：美观清晰展示加载中、发送成功或失败提示。
- ⚙️ **动态弹幕加载**：通过 URL 参数灵活加载视频与弹幕资源。
- 🚨 **安全警告提示**：防止控制台粘贴恶意代码。
- 📱 **移动端自适应**：全面兼容桌面与移动设备。

---

## 🔧 技术栈

- **ArtPlayer.js** - HTML5 视频播放器核心组件
- **artplayer-plugin-danmuku** - 弹幕插件支持
- **JavaScript + DOM 操作** - 实现 UI 控制与交互逻辑
- **Fetch API** - 前后端数据交互（弹幕、在线统计）
- **PHP + MySQL** - 后端处理弹幕数据与在线人数统计

---

## 📁 项目结构说明

```
project-root/
├── index.html              // 播放器主页面
├── sendDanmu.php           // 弹幕数据接收与存储
├── online.php              // 在线人数统计接口（已升级为MySQL数据库版）
├── js/
│   ├── artplayer.js              // ArtPlayer播放器核心
│   └── artplayer-plugin-danmuku.js // 弹幕插件
```

---

## 🔌 快速使用

### 1. 克隆或下载项目

```bash
git clone https://github.com/Dos7t3r/php-danmu-player
```

### 2. 创建 MySQL 数据库（推荐使用宝塔面板）

```sql
CREATE TABLE `online_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(64) NOT NULL UNIQUE,
    `video_id` VARCHAR(64) DEFAULT '',
    `last_active` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`video_id`),
    INDEX (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. 修改 PHP 数据库连接配置（`online.php`）

```php
$dbConfig = [
    'host'     => 'localhost',
    'dbname'   => '你的数据库名',
    'username' => '你的数据库用户名',
    'password' => '你的数据库密码',
    'charset'  => 'utf8mb4'
];
```

### 4. 部署到支持 PHP 与 MySQL 的服务器

推荐部署到使用 HTTPS 协议的服务器，以保障安全。

### 5. 设置播放器参数

示例访问：

```url
https://your-domain.com/index.html?url=/path/to/video.mp4&dmk=/path/to/danmuku.xml&id=video001
```

- `url`: 视频文件路径
- `dmk`: 弹幕文件路径（XML或JSON）
- `id`: 视频唯一标识，用于在线统计

---

## 📮 弹幕发送接口说明

- 请求方式：`POST`
- 内容类型：`application/json`

### 请求示例：

```json
{
  "dmkUrl": "/dmk/video.xml",
  "text": "这是一条弹幕",
  "time": 12.345,
  "color": "#FF0000",
  "mode": 0
}
```

### 响应示例：

成功：

```json
{"success": true, "message": "弹幕发送成功"}
```

失败：

```json
{"success": false, "message": "弹幕保存失败，权限不足"}
```

---

## 🖼️ 界面展示



播放器特色：

- 自定义控制栏（弹幕开关、列表、全屏、快进）
-
- 在线人数统计与热度图
-

---

## ⚠️ 注意事项

- 服务器需开启 PHP，并安装 MySQL 数据库，确保数据库访问权限。
- 推荐使用 HTTPS，保护用户数据安全。

---

## 🧪 演示站点




---

## 📜 开源协议

MIT License - 欢迎自由使用与二次开发。

---

## 👥 开发团队

- GitHub：[@oldbili](https://github.com/oldbili) / @nsty / [@Dos7t3r](https://github.com/Dos7t3r)
- 协作工具：ChatGPT & [v0.dev](https://v0.dev)

---

