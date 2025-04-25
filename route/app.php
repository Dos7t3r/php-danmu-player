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

use think\facade\Route;

// 播放器路由
Route::get('/', 'player/index');
Route::get('index', 'player/index');
Route::post('sendDanmaku', 'player/sendDanmaku');
Route::post('heartbeat', 'player/heartbeat');
Route::get('onlineCount', 'player/onlineCount');

// 管理后台路由
Route::group('admin', function () {
  // 登录相关
  Route::get('/', 'admin/login');
  Route::get('login', 'admin/login');
  Route::post('doLogin', 'admin/doLogin');
  Route::get('logout', 'admin/logout');
  
  // 后台首页
  Route::get('index', 'admin/index');
  
  // 视频管理
  Route::get('videos', 'admin/videos');
  Route::get('addVideo', 'admin/addVideo');
  Route::post('doAddVideo', 'admin/doAddVideo');
  Route::get('editVideo', 'admin/editVideo');
  Route::post('doEditVideo', 'admin/doEditVideo');
  Route::post('deleteVideo', 'admin/deleteVideo');
  
  // 弹幕管理
  Route::get('danmaku', 'admin/danmaku');
  Route::post('deleteDanmaku', 'admin/deleteDanmaku');
  Route::post('clearDanmaku', 'admin/clearDanmaku');
  
  // XML导入
  Route::get('importXml', 'admin/importXml');
  Route::post('doImportXml', 'admin/doImportXml');
  Route::post('uploadXml', 'admin/uploadXml');
  
  // 系统设置
  Route::get('settings', 'admin/settings');
  Route::post('saveSettings', 'admin/saveSettings');
  
  // 在线用户
  Route::get('onlineUsers', 'admin/onlineUsers');
  
  // 操作日志
  Route::get('logs', 'admin/logs');
  
  // 个人资料
  Route::get('profile', 'admin/profile');
  Route::post('updateProfile', 'admin/updateProfile');
});

// 兼容旧版URL参数
Route::get('obv/:id', function ($id) {
  return redirect('/?obv=' . $id);
});
