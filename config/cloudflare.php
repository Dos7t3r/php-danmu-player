<?php
// Cloudflare 配置文件

return [
    // Cloudflare Turnstile 配置（人机验证）
    'turnstile_enabled' => env('CLOUDFLARE_TURNSTILE_ENABLED', false),
    'turnstile_site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY', '0x4AAAAAAAc9n3jebUhX8Ds9'),
    'turnstile_secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY', '0x4AAAAAAAc9ny8EfPkAmzeUNWBIoROxdl8'),
    
    // 登录保护配置
    'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
    'login_lockout_time' => env('LOGIN_LOCKOUT_TIME', 1800), // 锁定时间（秒）
    
    // Cloudflare API 配置（如果需要）
    'api_token' => env('CLOUDFLARE_API_TOKEN', ''),
    'zone_id' => env('CLOUDFLARE_ZONE_ID', ''),
];
