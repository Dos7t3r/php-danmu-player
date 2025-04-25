<?php
/**
 * OldBili Player - 环境变量检查工具
 * 
 * 此文件用于检查环境变量是否正确配置
 */

// 显示所有错误
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>OldBili Player 环境变量检查工具</h1>";

// 检查.env文件
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "<div style='color: red; margin: 10px 0;'>.env 文件不存在，请从 .env.example 复制并配置</div>";
} else {
    echo "<div style='color: green; margin: 10px 0;'>.env 文件存在: " . $envFile . "</div>";
    
    // 读取.env文件内容
    $envContent = file_get_contents($envFile);
    
    echo "<h2>环境变量内容:</h2>";
    
    // 检查 Cloudflare Turnstile 配置
    $cfEnabled = extractEnvValue($envContent, 'CLOUDFLARE_TURNSTILE_ENABLED');
    $cfSiteKey = extractEnvValue($envContent, 'CLOUDFLARE_TURNSTILE_SITE_KEY');
    $cfSecretKey = extractEnvValue($envContent, 'CLOUDFLARE_TURNSTILE_SECRET_KEY');
    
    echo "<h3>Cloudflare Turnstile 配置:</h3>";
    echo "<ul>";
    echo "<li>CLOUDFLARE_TURNSTILE_ENABLED: " . ($cfEnabled ?: '<未设置>') . "</li>";
    echo "<li>CLOUDFLARE_TURNSTILE_SITE_KEY: " . ($cfSiteKey ? substr($cfSiteKey, 0, 10) . '...' : '<未设置>') . "</li>";
    echo "<li>CLOUDFLARE_TURNSTILE_SECRET_KEY: " . ($cfSecretKey ? substr($cfSecretKey, 0, 10) . '...' : '<未设置>') . "</li>";
    echo "</ul>";
    
    // 检查 PHP 环境变量
    echo "<h3>PHP 环境变量:</h3>";
    echo "<ul>";
    echo "<li>\$_ENV['CLOUDFLARE_TURNSTILE_ENABLED']: " . (isset($_ENV['CLOUDFLARE_TURNSTILE_ENABLED']) ? $_ENV['CLOUDFLARE_TURNSTILE_ENABLED'] : '<未设置>') . "</li>";
    echo "<li>\$_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']: " . (isset($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']) ? substr($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY'], 0, 10) . '...' : '<未设置>') . "</li>";
    echo "</ul>";
    
    // 检查是否使用测试密钥
    if ($cfSiteKey === '1x00000000000000000000AA') {
        echo "<div style='color: red; margin: 10px 0;'>警告: 正在使用 Cloudflare Turnstile 测试站点密钥，请检查 .env 配置</div>";
    }
    
    if ($cfSecretKey === '1x0000000000000000000000000000000AA') {
        echo "<div style='color: red; margin: 10px 0;'>警告: 正在使用 Cloudflare Turnstile 测试密钥，请检查 .env 配置</div>";
    }
}

echo "<h2>PHP 信息</h2>";
echo "<ul>";
echo "<li>PHP 版本: " . PHP_VERSION . "</li>";
echo "<li>操作系统: " . PHP_OS . "</li>";
echo "<li>Web 服务器: " . ($_SERVER['SERVER_SOFTWARE'] ?? '未知') . "</li>";
echo "</ul>";

echo "<h2>环境变量加载方式</h2>";
echo "<p>ThinkPHP 通常使用以下方式加载环境变量:</p>";
echo "<ol>";
echo "<li>通过 PHP 的 getenv() 函数</li>";
echo "<li>通过 \$_ENV 超全局变量</li>";
echo "<li>通过 vlucas/phpdotenv 库解析 .env 文件</li>";
echo "</ol>";

echo "<h2>解决方案</h2>";
echo "<p>如果环境变量未正确加载，请尝试以下解决方案:</p>";
echo "<ol>";
echo "<li>确保 .env 文件位于项目根目录</li>";
echo "<li>确保 .env 文件格式正确，每行一个变量，格式为 KEY=VALUE</li>";
echo "<li>确保 Web 服务器有权限读取 .env 文件</li>";
echo "<li>尝试在 php.ini 中设置 variables_order = \"EGPCS\"</li>";
echo "<li>尝试在 .htaccess 文件中添加环境变量</li>";
echo "<li>尝试直接在配置文件中硬编码值（不推荐用于生产环境）</li>";
echo "</ol>";

echo "<p><a href='/'>返回首页</a></p>";

/**
 * 从环境变量内容中提取值
 * @param string $content 环境变量内容
 * @param string $key 键名
 * @return string|null
 */
function extractEnvValue($content, $key) {
    if (preg_match('/'. $key .'\s*=\s*([^\r\n]+)/i', $content, $matches)) {
        return trim($matches[1]);
    }
    return null;
}
