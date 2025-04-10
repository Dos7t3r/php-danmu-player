# PHP增强版弹幕播放器（PHP Enhanced Danmaku Player）

🎬 **一个基于 ArtPlayer.js 构建的现代化弹幕播放器，集成高颜值通知系统与动态弹幕管理，开箱即用。**

---

## 📖 项目介绍

本项目是一个功能全面、界面美观的 HTML5 弹幕播放器，基于 ArtPlayer.js 和 artplayer-plugin-danmuku 插件开发。适合快速部署到个人博客、视频网站或本地使用，提供了视频播放、实时发送弹幕、弹幕数据动态加载等功能。

---

## 🚀 主要特性

- 🎨 **现代化通知系统**：清晰、优雅地显示播放器的各种状态（成功、失败、加载中、信息通知）。
- 📺 **弹幕功能全面支持**：发送弹幕时自定义颜色、位置（滚动、顶部、底部），实时显示。
- 🔄 **动态弹幕加载**：支持通过 URL 参数加载不同的视频和弹幕文件，便于快速切换视频资源。
- ⚙️ **自动创建弹幕库**：当用户播放的视频没有对应的弹幕文件时，自动在服务端创建一个新的 XML 弹幕文件。
- 🚨 **完善的错误处理**：弹幕库创建或发送失败时，清晰提示用户错误原因。
- 📱 **移动端自适应**：自动适配各种屏幕尺寸，支持移动端观看。

---

## 📺 演示站点
https://oldbili.fun

---

## 🔧 技术栈

- **ArtPlayer.js** - 现代化 HTML5 视频播放器库
- **artplayer-plugin-danmuku** - ArtPlayer 弹幕插件
- **HTML / CSS / JavaScript** - 前端基础技术栈
- **PHP** - 后端处理弹幕存储（sendDanmu.php）

---


## 🛠️ 使用方法

### 1. 克隆或下载项目

```bash
git clone https://github.com/Dos7t3r/php-danmu-player
```

### 2. 部署到服务器

将项目文件放置到 Web 服务器目录下，确保服务器支持 PHP。

```
- your-web-root/
  ├── index.html
  ├── sendDanmu.php
```

### 3. 配置播放器 URL 参数

播放器通过 URL 参数自动加载视频和弹幕文件。

- `url`：视频文件路径。
- `dmk`：弹幕 XML 文件路径（可选，不指定则自动创建）。

示例访问方式：

```url
https://your-domain.com/index.html?url=https://example.com/video.mp4&dmk=https://your-domain.com/dmk/danmu.xml
```


---

## 📝 文件说明

- `index.html`：播放器主页面，包含前端界面与逻辑。
- `sendDanmu.php`：弹幕数据的后端接收与 XML 文件存储。


---

## 🔑 后端接口说明

- 请求方式：`POST`
- 内容类型：`application/json`

### 请求参数示例：

```json
{
    "dmkUrl": "/dmk/video.xml",
    "text": "这是一条弹幕",
    "time": 12.345,
    "color": "#FF0000",
    "mode": 0
}
```

### 响应参数示例：

```json
成功：{"success":true,"message":"弹幕发送成功"}
失败：{"success":false,"message":"弹幕保存失败，权限不足"}
```

---

## 🎯 注意事项

- 服务器端需开启 PHP 支持，并确保弹幕 XML 文件夹具有正确的读写权限。
- 推荐使用 HTTPS 部署，避免弹幕数据传输过程中被篡改。

---

## 📜 开源协议

本项目基于 MIT 协议开源，欢迎自由使用与二次开发。

---

## 💬 联系方式

如有任何问题或建议，欢迎提交 Issue 或 PR！

## 🧠 Tip

项目开发By ChatGPT & v0.dev
