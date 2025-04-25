<?php
// reset_password.php
// 此文件用于重置管理员密码，使用后请删除

// 设置显示错误
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 加载框架
require __DIR__ . '/../vendor/autoload.php';

// 如果有POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? 'admin';
    $password = $_POST['password'] ?? 'admin123';
    
    // 生成密码哈希
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // 连接数据库
        $dbConfig = parse_ini_file(__DIR__ . '/../.env', true)['DATABASE'] ?? [];
        $dsn = "mysql:host={$dbConfig['HOSTNAME']};dbname={$dbConfig['DATABASE']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['USERNAME'], $dbConfig['PASSWORD']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 更新密码
        $stmt = $pdo->prepare("UPDATE obp_admin SET password = ? WHERE username = ?");
        $result = $stmt->execute([$hash, $username]);
        
        if ($result) {
            echo "<div style='color:green'>密码已成功重置！</div>";
            echo "<div>用户名: {$username}</div>";
            echo "<div>密码: {$password}</div>";
            echo "<div>哈希值: {$hash}</div>";
        } else {
            echo "<div style='color:red'>密码重置失败！</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color:red'>错误: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>重置管理员密码</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .warning { background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>重置管理员密码</h1>
    
    <div class="warning">
        <strong>警告：</strong> 此工具用于重置管理员密码。使用后请立即删除此文件！
    </div>
    
    <form method="post">
        <div class="form-group">
            <label for="username">用户名</label>
            <input type="text" id="username" name="username" value="admin" required>
        </div>
        
        <div class="form-group">
            <label for="password">新密码</label>
            <input type="password" id="password" name="password" value="admin123" required>
        </div>
        
        <button type="submit">重置密码</button>
    </form>
</body>
</html>