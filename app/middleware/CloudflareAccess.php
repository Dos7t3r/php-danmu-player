<?php
declare (strict_types = 1);

namespace app\middleware;

use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\facade\Session;

/**
* Cloudflare 保护中间件
*/
class CloudflareAccess
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        // 记录请求信息，用于调试
        Log::record("CloudflareAccess middleware: " . $request->controller() . '/' . $request->action() . ", Session ID: " . session_id(), 'info');
        
        // 登录页面不需要验证
        if ($request->controller() == 'Admin' && $request->action() == 'login') {
            return $next($request);
        }
        
        // 登录处理页面不需要验证
        if ($request->controller() == 'Admin' && $request->action() == 'doLogin') {
            return $next($request);
        }
        
        // 如果已经登录，则允许访问
        if (Session::has('admin')) {
            Log::record("User is logged in, allowing access", 'info');
            return $next($request);
        }
        
        // 尝试从cookie中恢复会话
        $adminCookie = cookie('admin_auth');
        if ($adminCookie) {
            try {
                $adminData = json_decode($adminCookie, true);
                if (is_array($adminData) && isset($adminData['id'])) {
                    // 从数据库验证用户
                    $admin = \think\facade\Db::name('admin')->where('id', $adminData['id'])->find();
                    if ($admin) {
                        // 重新设置会话
                        Session::set('admin', [
                            'id' => $admin['id'],
                            'username' => $admin['username'],
                            'nickname' => $admin['nickname'] ?: $admin['username']
                        ]);
                        Log::record("Session restored from cookie for user: " . $admin['username'], 'info');
                        return $next($request);
                    }
                }
            } catch (\Exception $e) {
                Log::record("Cookie recovery failed: " . $e->getMessage(), 'error');
            }
        }
        
        // 检查 Cloudflare 头信息（如果有）
        $cfRay = $request->header('CF-RAY');
        $cfIpCountry = $request->header('CF-IPCountry');
        
        // 记录访问信息
        if ($cfRay) {
            Log::record("Cloudflare protected request: CF-RAY={$cfRay}, Country={$cfIpCountry}, IP=" . $request->ip(), 'info');
        }
        
        // 未登录用户重定向到登录页面
        if ($request->isAjax()) {
            Log::record("Redirecting AJAX request to login", 'info');
            return json(['code' => -1, 'msg' => '请先登录']);
        } else {
            Log::record("Redirecting to login page", 'info');
            return redirect((string)url('admin/login'));
        }
    }
}
