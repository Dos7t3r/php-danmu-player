<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

// 显示错误信息
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 定义应用目录
define('APP_PATH', __DIR__ . '/../app/');

// 检查是否安装
if (!is_file(__DIR__ . '/../runtime/install.lock') && !preg_match('/install\.php$/', $_SERVER['PHP_SELF'])) {
    header('Location: /install.php');
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
