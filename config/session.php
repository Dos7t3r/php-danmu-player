<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2023 The ThinkPHP Team.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// | Session 配置文件
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // Session 前缀
    'prefix'         => Env::get('session.prefix', 'think'),
    // 驱动方式 支持: file|redis|memcache|memcached
    'type'           => Env::get('session.type', 'file'),
    // 是否自动开启
    'auto_start'     => Env::get('session.auto_start', true),
    // 过期时间
    'expire'         => Env::get('session.expire', 3600),
    // Session 保存目录
    'path'           => Env::get('session.path', ''),
    // Session 域名
    'domain'         => Env::get('session.domain', ''),
    // Session 会话的路径
    'secure'         => Env::get('session.secure', false),
    // httponly 设置
    'httponly'       => Env::get('session.httponly', ''),
    // Session 文件存储路径
    'store_path'     => Env::get('session.store_path', ''),
    // Session 文件存储目录
    'store_prefix'   => Env::get('session.store_prefix', ''),
    // Session 文件存储后缀
    'store_suffix'   => Env::get('session.store_suffix', 'sess'),
    // Session 文件存储的文件夹分隔符
    'store_separator' => Env::get('session.store_separator', '/'),
    // Redis 服务器地址
    'redis_host'     => Env::get('session.redis_host', '127.0.0.1'),
    // Redis 服务器端口
    'redis_port'     => Env::get('session.redis_port', 6379),
    // Redis 密码
    'redis_password' => Env::get('session.redis_password', null),
    // Redis 数据库
    'redis_database' => Env::get('session.redis_database', 0),
    // Redis 连接超时时间
    'redis_timeout'  => Env::get('session.redis_timeout', 0),
    // Redis 持久化连接
    'redis_persistent' => Env::get('session.redis_persistent', false),
    // Redis 前缀
    'redis_prefix'   => Env::get('session.redis_prefix', ''),
    // Memcache 服务器地址
    'memcache_host'  => Env::get('session.memcache_host', '127.0.0.1'),
    // Memcache 服务器端口
    'memcache_port'  => Env::get('session.memcache_port', 11211),
    // Memcache 持久化连接
    'memcache_persistent' => Env::get('session.memcache_persistent', false),
    // Memcache 前缀
    'memcache_prefix' => Env::get('session.memcache_prefix', ''),
    // Memcached 服务器地址
    'memcached_host' => Env::get('session.memcached_host', '127.0.0.1'),
    // Memcached 服务器端口
    'memcached_port' => Env::get('session.memcached_port', 11211),
    // Memcached 持久化连接
    'memcached_persistent' => Env::get('session.memcached_persistent', false),
    // Memcached 前缀
    'memcached_prefix' => Env::get('session.memcached_prefix', ''),
    // Memcached 分布式配置
    'memcached_servers' => Env::get('session.memcached_servers', []),
    // Memcached 分布式权重
    'memcached_weights' => Env::get('session.memcached_weights', []),
];