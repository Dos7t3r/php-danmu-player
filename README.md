以下是将你提供的两个 README 合并整理后的最终完整版，保留了两者的核心功能和优势，并进行了结构优化与语言统一，适合用于开源项目说明文档：

---

# 🎥 OldBili Player v1.3.2 - PHP 增强版弹幕播放器

**一个仿哔哩哔哩风格、基于 ArtPlayer.js 构建的现代化弹幕视频播放器，支持在线人数统计、动态弹幕系统、热度图与通知系统等高级功能，开箱即用，适合嵌入网站、博客与各类 Web 应用中。**

---

## ✨ 功能亮点

- 📺 **弹幕全面支持**：发送弹幕时支持自定义颜色、位置（滚动 / 顶部 / 底部），支持实时展示。
- 🔄 **响应式弹幕字号**：自动根据屏幕尺寸调整字体大小，优化桌面与移动端体验。
- 📋 **弹幕列表面板**：可一键查看全部弹幕，包含发送时间、内容、时间点等。
- 📡 **在线人数统计**：通过定时心跳机制记录用户在线状态，展示当前在线人数与互动热度图。
- 🎨 **高颜值通知系统**：加载中、发送成功、失败提示等状态清晰美观。
- ⚙️ **动态弹幕加载**：通过 URL 参数灵活加载不同视频与弹幕资源，支持无弹幕文件时自动创建。
- 🚨 **前端安全提示**：防止恶意代码粘贴，提升安全性。
- 📱 **移动端自适应**：支持不同设备访问，流畅切换。

---

## 🔧 技术栈

- **ArtPlayer.js** - HTML5 视频播放器核心组件
- **artplayer-plugin-danmuku** - 弹幕插件支持
- **JavaScript + DOM 操作** - 实现 UI 控制与交互逻辑
- **Fetch API** - 前后端数据交互（弹幕、在线统计）
- **PHP** - 后端处理弹幕接收、XML 文件存储与在线人数计算

---

## 📁 项目结构说明

```
project-root/
├── index.html              // 播放器主页面
├── sendDanmu.php           // 处理弹幕接收与存储
├── online.php              // 在线人数统计接口
├── online_users.xml        // 在线用户记录文件
├── style/oldbiliplayer.css // 播放器自定义样式（可合并到 index.html）
├── oldbiliplayer.js        // 前端逻辑控制（可合并到 index.html）
```

---

## 🔌 快速使用

### 1. 克隆或下载项目

```bash
git clone https://github.com/Dos7t3r/php-danmu-player
```

### 2. 部署到支持 PHP 的服务器

将文件部署到支持 PHP 的 Web 服务器（如 Apache/Nginx）。

### 3. 设置播放器参数

访问方式示例：

```url
https://your-domain.com/index.html?url=/path/to/video.mp4&dmk=/path/to/danmuku.xml&id=video001
```

- `url`: 视频文件路径
- `dmk`: 弹幕 XML 文件路径（可选，不填将自动创建）
- `id`: 视频唯一标识（用于在线统计）

---

## 📮 弹幕发送接口说明

- 请求方式：`POST`
- 内容类型：`application/json`

### 示例请求参数：

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


![image](https://github.com/user-attachments/assets/8e06707a-76c7-43f9-942e-66b8cd53c1c1)
---
![image](https://github.com/user-attachments/assets/db70cd73-3e2c-4e38-9211-e8b016f020cc)

播放器包含：

- 视频播放区域
- 自定义控制栏（弹幕开关、列表、全屏、快进等）
- ![image](https://github.com/user-attachments/assets/3db7035d-9461-4ffb-9d67-1d890fb6409a)

- 弹幕列表面板（可切换）
- 在线人数统计与热度展示
- ![image](https://github.com/user-attachments/assets/959830c0-3259-4a91-973b-08ea5bdd8932)

- 通知反馈系统（状态提示）
- 

---

## ⚠️ 注意事项

- 请确保服务器开启 PHP 并配置文件夹读写权限。
- 推荐使用 HTTPS 以保证数据传输安全。
- 弹幕存储为 XML 格式，注意格式合法性与文件权限。
- 控制台内置警告防止粘贴恶意脚本。

---

## 🧪 演示站点

👉 [在线预览地址](https://player.oldbili.fun/?url=https://limeblogs.github.io/ubc2/vid/av14224600125.mp4&dmk=https://oldbili.github.io/dmku/%E2%80%9C%E4%B8%80%E6%BC%94%E4%B8%81%E7%9C%9F_%E4%BE%BF%E5%85%A5%E6%88%8F_%E5%BE%97%E5%A4%AA%E6%B7%B1%E2%80%9D%E2%80%94%E2%80%94%E4%B8%81%E7%9C%9F%E8%83%BD%E9%87%8F%E5%8D%95%E6%9B%B2%E3%80%8A%E7%BE%A4%E4%B8%81%E3%80%8B.26554729651.xml)

---

## 📜 开源协议

MIT License - 欢迎二次开发、修改与部署。

---

## 👥 开发团队

- GitHub：[@oldbili](https://github.com/oldbili) / @nsty / @Dos7t3r(https://github.com/Dos7t3r) 
- 协作工具：ChatGPT & [v0.dev](https://v0.dev)

---

