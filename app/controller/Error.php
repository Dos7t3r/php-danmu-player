<?php
namespace app\controller;

use app\BaseController;
use think\facade\View;

class Error extends BaseController
{
    public function index()
    {
        // 记录错误日志
        trace('访问的页面不存在: ' . request()->url(), 'error');
        
        // 返回404页面
        return View::fetch(app()->getRootPath() . 'app/view/error/404.html');
    }
}
