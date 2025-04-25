<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Config;

class EnvCheck extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('env:check')
            ->setDescription('Check environment variables configuration');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('OldBili Player 环境变量检查工具');
        $output->writeln('=======================');
        
        // 检查.env文件
        $envFile = app()->getRootPath() . '.env';
        if (!file_exists($envFile)) {
            $output->error('.env 文件不存在，请从 .env.example 复制并配置');
            return 1;
        }
        
        $output->info('.env 文件存在: ' . $envFile);
        
        // 读取.env文件内容
        $envContent = file_get_contents($envFile);
        $output->writeln('');
        $output->writeln('环境变量内容:');
        
        // 检查 Cloudflare Turnstile 配置
        $cfEnabled = $this->extractEnvValue($envContent, 'CLOUDFLARE_TURNSTILE_ENABLED');
        $cfSiteKey = $this->extractEnvValue($envContent, 'CLOUDFLARE_TURNSTILE_SITE_KEY');
        $cfSecretKey = $this->extractEnvValue($envContent, 'CLOUDFLARE_TURNSTILE_SECRET_KEY');
        
        $output->writeln('Cloudflare Turnstile 配置:');
        $output->writeln('- CLOUDFLARE_TURNSTILE_ENABLED: ' . ($cfEnabled ?: '<未设置>'));
        $output->writeln('- CLOUDFLARE_TURNSTILE_SITE_KEY: ' . ($cfSiteKey ? substr($cfSiteKey, 0, 10) . '...' : '<未设置>'));
        $output->writeln('- CLOUDFLARE_TURNSTILE_SECRET_KEY: ' . ($cfSecretKey ? substr($cfSecretKey, 0, 10) . '...' : '<未设置>'));
        
        // 检查配置文件中的值
        $output->writeln('');
        $output->writeln('配置文件中的值:');
        $output->writeln('- cloudflare.turnstile_enabled: ' . (Config::get('cloudflare.turnstile_enabled') ? 'true' : 'false'));
        $output->writeln('- cloudflare.turnstile_site_key: ' . substr(Config::get('cloudflare.turnstile_site_key', ''), 0, 10) . '...');
        
        // 检查是否使用测试密钥
        if (Config::get('cloudflare.turnstile_site_key') === '1x00000000000000000000AA') {
            $output->error('警告: 正在使用 Cloudflare Turnstile 测试站点密钥，请检查 .env 配置');
        }
        
        if (Config::get('cloudflare.turnstile_secret_key') === '1x0000000000000000000000000000000AA') {
            $output->error('警告: 正在使用 Cloudflare Turnstile 测试密钥，请检查 .env 配置');
        }
        
        // 检查 PHP 环境变量
        $output->writeln('');
        $output->writeln('PHP 环境变量:');
        $output->writeln('- $_ENV[\'CLOUDFLARE_TURNSTILE_ENABLED\']: ' . (isset($_ENV['CLOUDFLARE_TURNSTILE_ENABLED']) ? $_ENV['CLOUDFLARE_TURNSTILE_ENABLED'] : '<未设置>'));
        $output->writeln('- $_ENV[\'CLOUDFLARE_TURNSTILE_SITE_KEY\']: ' . (isset($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']) ? substr($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY'], 0, 10) . '...' : '<未设置>'));
        
        $output->writeln('');
        $output->writeln('环境变量检查完成');
        
        return 0;
    }
    
    /**
     * 从环境变量内容中提取值
     * @param string $content 环境变量内容
     * @param string $key 键名
     * @return string|null
     */
    private function extractEnvValue($content, $key)
    {
        if (preg_match('/'. $key .'\s*=\s*([^\r\n]+)/i', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
