# OldBili Player v2.0.0

基于ThinkPHP 6 + AdminLTE 3的OldBili Player管理系统，提供美观的UI和增强的安全性。

## 特性

- 基于ThinkPHP 6框架，代码结构清晰
- 使用AdminLTE 3管理界面，响应式设计
- 完整的用户认证和权限管理
- 视频和弹幕的高效管理
- 实时在线用户统计
- 安全性增强

## 安装说明

1. 将项目文件上传到服务器
2. 配置数据库连接（.env文件）
3. 运行安装脚本：`php think install`
4. 访问管理后台：`http://您的域名/admin`
5. 默认管理员账号：admin，密码：admin123

## 目录结构

\`\`\`
project/
├── app/                    # 应用目录
│   ├── controller/         # 控制器
│   ├── model/              # 模型
│   ├── view/               # 视图
│   └── middleware/         # 中间件
├── config/                 # 配置文件
├── public/                 # 公共资源
│   ├── static/             # 静态资源
│   ├── player/             # 播放器资源
│   └── index.php           # 入口文件
├── route/                  # 路由配置
└── vendor/                 # Composer依赖
\`\`\`

## 生产环境注意事项

在部署到生产环境前，请删除以下测试/开发文件：
- migration.php
- install.php（安装完成后）

确保修改默认管理员密码，并设置适当的文件权限。
