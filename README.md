# OldBili Player v2.0.0

基于ThinkPHP 6 + AdminLTE 3的OldBili Player管理系统，提供美观的UI和增强的安全性。

## 预览
<img width="1231" alt="image" src="https://github.com/user-attachments/assets/0e94a0bf-6e84-4854-b611-cf20cab168b3" />
<img width="1231" alt="image" src="https://github.com/user-attachments/assets/e6171ce5-780a-4d4e-b186-89ce9c4744bc" />
<img width="1231" alt="image" src="https://github.com/user-attachments/assets/63b40347-67c8-4a00-b35c-c3b6f40a59da" />
<img width="1231" alt="image" src="https://github.com/user-attachments/assets/8f058e01-a135-40ae-bbd4-3bac097b0cfa" />
<img width="1231" alt="image" src="https://github.com/user-attachments/assets/1e41527c-bcfa-4994-9b12-0632e1606bcb" />
<img width="1231" alt="image" src="https://github.com/user-attachments/assets/135827d4-352c-41f4-95bf-42322f4b1d3b" />
<img width="1231" alt="image" src="https://github.com/user-attachments/assets/e03f6f21-4723-4755-8735-9036182b67f2" />
<img width="1231" alt="image" src="https://github.com/user-attachments/assets/d1cdd30f-7bd1-4e9e-aeee-ac07965db8ef" />
![image](https://github.com/user-attachments/assets/aab9b598-380e-4486-b8a3-104fac0da479)


## .env
后端设置在这里 前端设置在后台

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
4. 安装composer（推荐使用宝塔的composer，选择composer.json即可）
5. 访问管理后台：`http://您的域名/admin`
6. 默认管理员账号：admin，密码：admin123 （如果无法访问请使用fuck.php强制重设密码）

## 目录结构

```
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
```

## 生产环境注意事项

在部署到生产环境前，请删除以下测试/开发文件：
- migration.php
- install.php（安装完成后）
- fuck.php

确保修改默认管理员密码，并设置适当的文件权限。
