<?php
namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Session;
use think\facade\Db;
use think\facade\Request;
use think\facade\Config;
use think\facade\Log;

$thinkphpVersion = \think\App::VERSION;
View::assign('thinkphpVersion', $thinkphpVersion);

class Admin extends BaseController
{
    protected $middleware = ['app\middleware\CloudflareAccess'];
    
    // 登录页面
    public function login()
    {
        if (Session::has('admin')) {
            return redirect((string)url('admin/index'));
        }
        
        // 获取 Cloudflare Turnstile 站点密钥
        $cfSiteKey = Config::get('cloudflare.turnstile_site_key', '');
        View::assign('cf_site_key', $cfSiteKey);
        
        // 使用绝对路径指定模板文件
        return View::fetch(app()->getRootPath() . 'app/view/admin/login.html');
    }

    // 登录处理
    public function doLogin()
    {
        $username = input('post.username');
        $password = input('post.password');
        $cfTurnstileResponse = input('post.cf-turnstile-response', '');
        
        // 验证 Cloudflare Turnstile
        if (Config::get('cloudflare.turnstile_enabled', false)) {
            // 获取 Turnstile 响应令牌
            $cfTurnstileResponse = input('post.cf-turnstile-response', '');

            // 如果启用了 Turnstile 但没有提供响应令牌，则拒绝登录
            if (Config::get('cloudflare.turnstile_enabled', false) && empty($cfTurnstileResponse)) {
                return json(['code' => 0, 'msg' => '请完成人机验证']);
            }
            $verified = $this->verifyCloudflareTurnstile($cfTurnstileResponse);
            if (!$verified) {
                // 记录可能的机器人攻击
                Log::record("Failed Cloudflare Turnstile verification for username: {$username}, IP: " . Request::ip(), 'warning');
                return json(['code' => 0, 'msg' => '人机验证失败，请重试']);
            }
        } else {
            // 如果 Turnstile 被禁用，记录此情况
            Log::record("Cloudflare Turnstile is disabled. Login attempt for: {$username}, IP: " . Request::ip(), 'info');
        }
        
        // 检查登录失败次数，防止暴力破解
        $loginFailures = $this->checkLoginFailures(Request::ip());
        if ($loginFailures >= Config::get('cloudflare.max_login_attempts', 5)) {
            Log::record("Too many login attempts for IP: " . Request::ip(), 'warning');
            return json(['code' => 0, 'msg' => '登录尝试次数过多，请稍后再试']);
        }
        
        $admin = Db::name('admin')->where('username', $username)->find();
        
        if (!$admin) {
            // 记录登录失败
            $this->recordLoginFailure(Request::ip());
            $this->logOperation('登录失败 - 用户名不存在', $username);
            return json(['code' => 0, 'msg' => '用户名或密码错误']);
        }
        
        if (!password_verify($password, $admin['password'])) {
            // 记录登录失败
            $this->recordLoginFailure(Request::ip());
            $this->logOperation('登录失败 - 密码错误', $username);
            return json(['code' => 0, 'msg' => '用户名或密码错误']);
        }
        
        // 登录成功，清除失败记录
        $this->clearLoginFailures(Request::ip());
        
        // 更新登录信息
        Db::name('admin')->where('id', $admin['id'])->update([
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => Request::ip()
        ]);
        
        // 记录登录成功
        $this->logOperation('登录成功', $username);
        
        // 存储登录状态
        Session::set('admin', [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'nickname' => $admin['nickname'] ?: $admin['username']
        ]);
        
        // 调试日志
        $sessionId = session_id();
        $sessionDataSet = Session::all();
        trace("[DoLogin] Session Set. Session ID: {$sessionId}, Session Data: " . json_encode($sessionDataSet), 'session');
        
        // 同时设置cookie备份，以防会话丢失
        $adminData = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'nickname' => $admin['nickname'] ?: $admin['username']
        ];
        cookie('admin_auth', json_encode($adminData), ['expire' => 7200, 'httponly' => true]);
        
        return json(['code' => 1, 'msg' => '登录成功', 'url' => (string)url('admin/index')]);
    }
    
    /**
     * 验证 Cloudflare Turnstile 响应
     * @param string $response Turnstile 响应令牌
     * @return bool 验证是否成功
     */
    private function verifyCloudflareTurnstile($response)
    {
        if (empty($response)) {
            return false;
        }
        
        $secretKey = Config::get('cloudflare.turnstile_secret_key', '');
        if (empty($secretKey)) {
            // 如果未配置密钥，记录警告并默认通过
            Log::record('Cloudflare Turnstile secret key not configured', 'warning');
            return true;
        }
        
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => Request::ip()
        ];
        
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify ');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($result === false) {
            Log::record('Cloudflare Turnstile verification request failed: ' . $curlError, 'error');
            return false;
        }
        
        $json = json_decode($result, true);
        if (!isset($json['success'])) {
            Log::record('Invalid Cloudflare Turnstile response: ' . $result, 'error');
            return false;
        }
        
        if (!$json['success']) {
            $errorCodes = $json['error-codes'] ?? ['unknown'];
            Log::record('Cloudflare Turnstile verification failed: ' . implode(', ', $errorCodes), 'warning');
            return false;
        }
        
        return true;
    }
    
    /**
     * 检查登录失败次数
     * @param string $ip 客户端IP
     * @return int 失败次数
     */
    private function checkLoginFailures($ip)
    {
        $cacheKey = 'login_failures:' . md5($ip);
        $failures = cache($cacheKey);
        return $failures ? intval($failures) : 0;
    }
    
    /**
     * 记录登录失败
     * @param string $ip 客户端IP
     */
    private function recordLoginFailure($ip)
    {
        $cacheKey = 'login_failures:' . md5($ip);
        $failures = $this->checkLoginFailures($ip);
        $failures++;
        
        // 设置缓存，默认30分钟过期
        $expireTime = Config::get('cloudflare.login_lockout_time', 1800);
        cache($cacheKey, $failures, $expireTime);
    }
    
    /**
     * 清除登录失败记录
     * @param string $ip 客户端IP
     */
    private function clearLoginFailures($ip)
    {
        $cacheKey = 'login_failures:' . md5($ip);
        cache($cacheKey, null);
    }

    // 退出登录
    public function logout()
    {
        if (Session::has('admin')) {
            $admin = Session::get('admin');
            $this->logOperation('退出登录', $admin['username']);
            Session::delete('admin');
        }
        return redirect((string)url('admin/login'));
    }

    // 检查登录状态
    private function checkLogin()
    {
        // 调试日志
        $sessionId = session_id();
        $sessionData = Session::all();
        trace("[CheckLogin] Session ID: {$sessionId}, Session Data: " . json_encode($sessionData), 'session');
        
        if (!Session::has('admin')) {
            trace("[CheckLogin] Failed: 'admin' key not found in session.", 'session');
        
            // 尝试从cookie中恢复会话
            $adminCookie = cookie('admin_auth');
            if ($adminCookie) {
                try {
                    $adminData = json_decode($adminCookie, true);
                    if (is_array($adminData) && isset($adminData['id'])) {
                        // 从数据库验证用户
                        $admin = Db::name('admin')->where('id', $adminData['id'])->find();
                        if ($admin) {
                            // 重新设置会话
                            Session::set('admin', [
                                'id' => $admin['id'],
                                'username' => $admin['username'],
                                'nickname' => $admin['nickname'] ?: $admin['username']
                            ]);
                            trace("[CheckLogin] Recovered session from cookie.", 'session');
                            return true;
                        }
                    }
                } catch (\Exception $e) {
                    trace("[CheckLogin] Cookie recovery failed: " . $e->getMessage(), 'session');
                }
            }
            
            if (Request::isAjax()) {
                echo json_encode(['code' => -1, 'msg' => '请先登录']);
                exit;
            } else {
                redirect((string)url('admin/login'))->send();
                exit;
            }
        } else {
            trace("[CheckLogin] Success: 'admin' key FOUND.", 'session');
            return true;
        }
    }

    // 记录操作日志
    private function logOperation($operation, $username = '')
    {
        $admin = Session::get('admin');
        $adminId = $admin ? $admin['id'] : 0;
        $username = $username ?: ($admin ? $admin['username'] : '');
        
        Db::name('operation_log')->insert([
            'admin_id' => $adminId,
            'username' => $username,
            'operation' => $operation,
            'ip' => Request::ip(),
            'method' => Request::method(),
            'url' => Request::url(),
            'params' => json_encode(Request::param()),
            'create_time' => date('Y-m-d H:i:s')
        ]);
    }

    // 后台首页
    public function index()
    {
        $this->checkLogin();
        
        // 统计数据
        $data = [
            'videoCount' => Db::name('videos')->count(),
            'onlineUsers' => Db::name('online_users')
                ->where('last_active', '>', date('Y-m-d H:i:s', time() - 120))
                ->count(),
            'totalDanmaku' => $this->countTotalDanmaku(),
            'recentVideos' => Db::name('videos')
                ->order('create_time', 'desc')
                ->limit(5)
                ->select()
                ->toArray()
        ];
        
        View::assign('data', $data);
        return View::fetch();
    }

    // 生成管理员访问令牌
    public function generateAdminToken()
    {
        $this->checkLogin();
        
        try {
            // 生成随机token
            $token = md5(uniqid(mt_rand(), true));
            
            // 获取当前管理员信息
            $admin = Session::get('admin');
            
            // 保存token到文件
            $tokenFile = app()->getRootPath() . 'runtime/admin_token.php';
            $tokens = [];
            
            if (file_exists($tokenFile)) {
                $tokens = include $tokenFile;
                if (!is_array($tokens)) {
                    $tokens = [];
                }
            }
            
            // 添加新token，限制最多保存10个token
            $tokens[] = $token;
            if (count($tokens) > 10) {
                array_shift($tokens); // 移除最旧的token
            }
            
            // 写入文件
            file_put_contents($tokenFile, '<?php return ' . var_export($tokens, true) . ';');
            
            // 记录操作
            $this->logOperation("使用管理token预览视频", $admin['username']);
            
            return json(['code' => 1, 'msg' => '令牌生成成功', 'token' => $token]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '令牌生成失败: ' . $e->getMessage()]);
        }
    }

    // 视频管理
    public function videos()
    {
        $this->checkLogin();
        
        $keyword = input('keyword', '');
        $page = input('page', 1, FILTER_VALIDATE_INT);
        $limit = input('limit', 10, FILTER_VALIDATE_INT);
        
        $query = Db::name('videos');
        
        if (!empty($keyword)) {
            $query = $query->where('title', 'like', "%{$keyword}%");
        }
        
        $count = $query->count();
        $videos = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select()
            ->toArray();
        
        // 获取每个视频的弹幕数量
        foreach ($videos as &$video) {
            $video['danmaku_count'] = $this->countVideoDanmaku($video['id']);
        }
        
        View::assign([
            'videos' => $videos,
            'count' => $count,
            'keyword' => $keyword,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($count / $limit)
        ]);
        
        return View::fetch();
    }

    // 添加视频页面
    public function addVideo()
    {
        $this->checkLogin();
        return View::fetch();
    }

    // 添加视频处理
    public function doAddVideo()
    {
        $this->checkLogin();
        
        $data = [
            'title' => input('post.title', ''),
            'video_url' => input('post.video_url', ''),
            'subtitle_url' => input('post.subtitle_url', ''),
            'description' => input('post.description', ''),
            'status' => input('post.status', 1, FILTER_VALIDATE_INT),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s')
        ];
        
        // 验证数据
        if (empty($data['title']) || empty($data['video_url'])) {
            return json(['code' => 0, 'msg' => '标题和视频URL不能为空']);
        }
        
        // 自定义ID
        $customId = input('post.custom_id', 0, FILTER_VALIDATE_INT);
        if ($customId > 0) {
            // 检查ID是否已存在
            $exists = Db::name('videos')->where('id', $customId)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => "ID {$customId} 已被使用"]);
            }
            $data['id'] = $customId;
        }
        
        try {
            Db::startTrans();
            
            // 插入视频记录
            $videoId = Db::name('videos')->insertGetId($data);
            
            // 创建弹幕表
            $danmakuTable = "obp_danmu_{$videoId}";
            Db::execute("
                CREATE TABLE IF NOT EXISTS `{$danmakuTable}` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    text TEXT NOT NULL,
                    time FLOAT NOT NULL,
                    mode INT DEFAULT 0,
                    color VARCHAR(20) DEFAULT '#FFFFFF',
                    timestamp BIGINT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            Db::commit();
            
            // 记录操作
            $admin = Session::get('admin');
            $this->logOperation("添加视频 ID:{$videoId} 标题:{$data['title']}", $admin['username']);
            
            return json(['code' => 1, 'msg' => '添加成功', 'id' => $videoId]);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '添加失败: ' . $e->getMessage()]);
        }
    }

    // 编辑视频页面
    public function editVideo()
    {
        $this->checkLogin();
        
        $id = input('id', 0, FILTER_VALIDATE_INT);
        if ($id <= 0) {
            return $this->error('参数错误');
        }
        
        $video = Db::name('videos')->where('id', $id)->find();
        if (!$video) {
            return $this->error('视频不存在');
        }
        
        View::assign('video', $video);
        return View::fetch();
    }

    // 编辑视频处理
    public function doEditVideo()
    {
        $this->checkLogin();
        
        $id = input('post.id', 0, FILTER_VALIDATE_INT);
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $data = [
            'title' => input('post.title', ''),
            'video_url' => input('post.video_url', ''),
            'subtitle_url' => input('post.subtitle_url', ''),
            'description' => input('post.description', ''),
            'status' => input('post.status', 1, FILTER_VALIDATE_INT),
            'update_time' => date('Y-m-d H:i:s')
        ];
        
        // 验证数据
        if (empty($data['title']) || empty($data['video_url'])) {
            return json(['code' => 0, 'msg' => '标题和视频URL不能为空']);
        }
        
        try {
            Db::name('videos')->where('id', $id)->update($data);
            
            // 记录操作
            $admin = Session::get('admin');
            $this->logOperation("编辑视频 ID:{$id} 标题:{$data['title']}", $admin['username']);
            
            return json(['code' => 1, 'msg' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新失败: ' . $e->getMessage()]);
        }
    }

    // 删除视频
    public function deleteVideo()
    {
        $this->checkLogin();
        
        $id = input('id', 0, FILTER_VALIDATE_INT);
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        try {
            Db::startTrans();
            
            // 获取视频信息（用于日志）
            $video = Db::name('videos')->where('id', $id)->find();
            
            // 删除视频记录
            Db::name('videos')->where('id', $id)->delete();
            
            // 删除弹幕表
            $danmakuTable = "obp_danmu_{$id}";
            Db::execute("DROP TABLE IF EXISTS `{$danmakuTable}`");
            
            Db::commit();
            
            // 记录操作
            $admin = Session::get('admin');
            $this->logOperation("删除视频 ID:{$id} 标题:{$video['title']}", $admin['username']);
            
            return json(['code' => 1, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '删除失败: ' . $e->getMessage()]);
        }
    }

    // 弹幕管理
    public function danmaku()
    {
        $this->checkLogin();
        
        $videoId = input('video_id', 0, FILTER_VALIDATE_INT);
        if ($videoId <= 0) {
            return $this->error('参数错误');
        }
        
        $video = Db::name('videos')->where('id', $videoId)->find();
        if (!$video) {
            return $this->error('视频不存在');
        }
        
        // 获取弹幕列表
        $danmakuTable = "obp_danmu_{$videoId}";
        $danmakuList = [];
        
        try {
            // 检查表是否存在
            $exists = Db::query("SHOW TABLES LIKE '{$danmakuTable}'");
            if ($exists) {
                $danmakuList = Db::table($danmakuTable)
                    ->order('time', 'asc')
                    ->select()
                    ->toArray();
            }
        } catch (\Exception $e) {
            // 表不存在或其他错误
        }
        
        View::assign([
            'video' => $video,
            'danmakuList' => $danmakuList
        ]);
        
        return View::fetch();
    }
         // 合并XML保存到服务器
        public function saveMergedDanmaku()
        {
            $this->checkLogin(); // 验证登录
        
            $mergedXML = Request::post('merged_xml');
        
            if (empty($mergedXML)) {
                return json(['code' => 0, 'msg' => '未收到XML内容']);
            }
        
            $saveDir = root_path() . 'public/danmaku/';
            if (!is_dir($saveDir)) {
                mkdir($saveDir, 0777, true);
            }
        
            $filename = 'merged_' . date('Ymd_His') . '.xml';
            $filePath = $saveDir . $filename;
        
            if (file_put_contents($filePath, $mergedXML)) {
                // 记录操作日志
                $admin = Session::get('admin');
                $this->logOperation("合并保存弹幕XML文件: {$filename}", $admin['username']);
        
                return json(['status' => 1, 'msg' => '保存成功', 'filename' => $filename]);
            } else {
                return json(['status' => 0, 'msg' => '文件保存失败']);
            }
        }


    // 删除弹幕
    public function deleteDanmaku()
    {
        $this->checkLogin();
        
        $videoId = input('video_id', 0, FILTER_VALIDATE_INT);
        $danmakuId = input('danmaku_id', 0, FILTER_VALIDATE_INT);
        
        if ($videoId <= 0 || $danmakuId <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $danmakuTable = "obp_danmu_{$videoId}";
        
        try {
            // 检查表是否存在
            $exists = Db::query("SHOW TABLES LIKE '{$danmakuTable}'");
            if (!$exists) {
                return json(['code' => 0, 'msg' => '弹幕表不存在']);
            }
            
            // 删除弹幕
            Db::table($danmakuTable)->where('id', $danmakuId)->delete();
            
            // 记录操作
            $admin = Session::get('admin');
            $this->logOperation("删除弹幕 视频ID:{$videoId} 弹幕ID:{$danmakuId}", $admin['username']);
            
            return json(['code' => 1, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '删除失败: ' . $e->getMessage()]);
        }
    }

    // 清空弹幕
    public function clearDanmaku()
    {
        $this->checkLogin();
        
        $videoId = input('video_id', 0, FILTER_VALIDATE_INT);
        if ($videoId <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $danmakuTable = "obp_danmu_{$videoId}";
        
        try {
            // 检查表是否存在
            $exists = Db::query("SHOW TABLES LIKE '{$danmakuTable}'");
            if (!$exists) {
                return json(['code' => 0, 'msg' => '弹幕表不存在']);
            }
            
            // 清空弹幕表
            Db::execute("TRUNCATE TABLE `{$danmakuTable}`");
            
            // 记录操作
            $admin = Session::get('admin');
            $this->logOperation("清空弹幕 视频ID:{$videoId}", $admin['username']);
            
            return json(['code' => 1, 'msg' => '清空成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '清空失败: ' . $e->getMessage()]);
        }
    }

    // 导入XML弹幕页面
    public function importXml()
    {
        $this->checkLogin();
        
        // 获取视频列表
        $videos = Db::name('videos')->field('id, title')->select()->toArray();
        
        // 获取XML文件列表
        $xmlDir = root_path() . 'public/danmaku/';
        $xmlFiles = [];
        
        if (is_dir($xmlDir)) {
            if ($handle = opendir($xmlDir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != ".." && pathinfo($file, PATHINFO_EXTENSION) == 'xml') {
                        $xmlFiles[] = $file;
                    }
                }
                closedir($handle);
            }
        }
        
        sort($xmlFiles);
        
        View::assign([
            'videos' => $videos,
            'xmlFiles' => $xmlFiles
        ]);
        
        return View::fetch();
    }

    // 上传XML文件
    public function uploadXml()
    {
        try {
            $this->checkLogin();
            
            // 记录请求信息
            Log::record('开始处理文件上传请求', 'info');
            Log::record('POST数据: ' . json_encode($_POST), 'info');
            Log::record('FILES数据: ' . json_encode($_FILES), 'info');
            
            $file = request()->file('file');
            if (!$file) {
                Log::record('未找到上传文件', 'error');
                return json(['code' => 0, 'msg' => '没有上传文件']);
            }
            
            // 验证文件类型
            $mimeType = $file->getMime();
            $extension = strtolower($file->getOriginalExtension());
            Log::record("文件信息 - MIME类型: {$mimeType}, 扩展名: {$extension}", 'info');
            
            if ($extension !== 'xml' || !in_array($mimeType, ['text/xml', 'application/xml'])) {
                Log::record("文件类型验证失败 - MIME类型: {$mimeType}, 扩展名: {$extension}", 'error');
                return json(['code' => 0, 'msg' => '只允许上传XML文件']);
            }
            
            // 验证文件大小（10MB）
            $fileSize = $file->getSize();
            Log::record("文件大小: {$fileSize} 字节", 'info');
            
            if ($fileSize > 10 * 1024 * 1024) {
                Log::record("文件大小超过限制: {$fileSize} 字节", 'error');
                return json(['code' => 0, 'msg' => '文件大小不能超过10MB']);
            }
            
            $xmlDir = root_path() . 'public/danmaku/';
            if (!is_dir($xmlDir)) {
                Log::record("创建上传目录: {$xmlDir}", 'info');
                if (!mkdir($xmlDir, 0755, true)) {
                    Log::record("创建目录失败: {$xmlDir}", 'error');
                    return json(['code' => 0, 'msg' => '创建上传目录失败']);
                }
            }
            
            // 获取原始文件名并处理
            $originalName = $file->getOriginalName();
            $originalNameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            // 生成安全的文件名，保留原始文件名
            $fileName = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]/u', '_', $originalNameWithoutExt) . '.xml';
            $savePath = $xmlDir . $fileName;
            Log::record("准备保存文件到: {$savePath}", 'info');
            
            // 直接移动文件（修正：传入目录和文件名，防止生成文件夹）
            if (!$file->move($xmlDir, $fileName)) {
                Log::record("文件移动失败: {$xmlDir}{$fileName}", 'error');
                return json(['code' => 0, 'msg' => '文件保存失败']);
            }
            
            // 判断路径是否为文件
            if (!is_file($savePath)) {
                Log::record("不是有效的文件: {$savePath}", 'error');
                return json(['code' => 0, 'msg' => '不是有效的文件']);
            }
            
            $xmlContent = file_get_contents($savePath);
            if (empty($xmlContent)) {
                Log::record("文件内容为空: {$savePath}", 'error');
                return json(['code' => 0, 'msg' => '文件内容为空']);
            }
            
            // 使用simplexml_load_string解析XML内容
            $xml = simplexml_load_string($xmlContent);
            if (!$xml) {
                Log::record("XML解析失败: " . libxml_get_last_error()->message, 'error');
                @unlink($savePath);
                return json(['code' => 0, 'msg' => '无法解析XML文件']);
            }
            
            // 记录操作
            $admin = Session::get('admin');
            $this->logOperation("上传XML文件 {$originalName} -> {$fileName}", $admin['username']);
            
            Log::record("文件上传成功: {$fileName}", 'info');
            return json(['code' => 1, 'msg' => '上传成功', 'file' => $fileName]);
            
        } catch (\Exception $e) {
            // 记录详细的错误信息
            Log::record("文件上传异常: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            
            // 发生错误时删除已上传的文件
            if (isset($savePath) && file_exists($savePath)) {
                @unlink($savePath);
            }
            
            return json(['code' => 0, 'msg' => '上传失败: ' . $e->getMessage()]);
        }
    }

    // 系统设置页面
    public function settings()
    {
        $this->checkLogin();
        
        $settings = Db::name('settings')->select()->toArray();
        $settingsData = [];
        
        foreach ($settings as $setting) {
            $settingsData[$setting['key']] = $setting['value'];
        }
        
        View::assign('settings', $settingsData);
        return View::fetch();
    }

    // 保存系统设置
    public function saveSettings()
    {
        $this->checkLogin();
        
        $data = input('post.');
        
        try {
            foreach ($data as $key => $value) {
                // 跳过token字段
                if ($key == '__token__') continue;
                
                Db::name('settings')->where('key', $key)->update([
                    'value' => $value,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            }
            
            // 记录操作
            $admin = Session::get('admin');
            $this->logOperation("更新系统设置", $admin['username']);
            
            return json(['code' => 1, 'msg' => '设置保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '设置保存失败: ' . $e->getMessage()]);
        }
    }

    // 在线用户管理
    public function onlineUsers()
    {
        $this->checkLogin();
        
        $timeout = Db::name('settings')->where('key', 'online_timeout')->value('value') ?: 120;
        
        $onlineUsers = Db::name('online_users')
            ->where('last_active', '>', date('Y-m-d H:i:s', time() - $timeout))
            ->order('last_active', 'desc')
            ->select()
            ->toArray();
        
        // 获取视频信息
        foreach ($onlineUsers as &$user) {
            if (!empty($user['video_id'])) {
                $video = Db::name('videos')->where('id', $user['video_id'])->find();
                $user['video_title'] = $video ? $video['title'] : '未知视频';
            } else {
                $user['video_title'] = '未观看视频';
            }
        }
        
        View::assign([
            'onlineUsers' => $onlineUsers,
            'count' => count($onlineUsers),
            'timeout' => $timeout
        ]);
        
        return View::fetch();
    }

    // 操作日志
    public function logs()
    {
        $this->checkLogin();
        
        $page = input('page', 1, FILTER_VALIDATE_INT);
        $limit = input('limit', 20, FILTER_VALIDATE_INT);
        $username = input('username', '');
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');
        
        $query = Db::name('operation_log');
        
        if (!empty($username)) {
            $query = $query->where('username', 'like', "%{$username}%");
        }
        
        if (!empty($startDate)) {
            $query = $query->where('create_time', '>=', $startDate . ' 00:00:00');
        }
        
        if (!empty($endDate)) {
            $query = $query->where('create_time', '<=', $endDate . ' 23:59:59');
        }
        
        $count = $query->count();
        $logs = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select()
            ->toArray();
        
        View::assign([
            'logs' => $logs,
            'count' => $count,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($count / $limit),
            'username' => $username,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
        
        return View::fetch();
    }

    // 个人资料
    public function profile()
    {
        $this->checkLogin();
        
        $admin = Session::get('admin');
        $adminInfo = Db::name('admin')->where('id', $admin['id'])->find();
        
        View::assign('admin', $adminInfo);
        return View::fetch();
    }

    // 更新个人资料
    public function updateProfile()
    {
        $this->checkLogin();
        
        $admin = Session::get('admin');
        $data = [
            'nickname' => input('post.nickname', ''),
            'email' => input('post.email', ''),
            'update_time' => date('Y-m-d H:i:s')
        ];
        
        // 如果提供了新密码
        $password = input('post.password', '');
        $confirmPassword = input('post.confirm_password', '');
        
        if (!empty($password)) {
            if ($password !== $confirmPassword) {
                return json(['code' => 0, 'msg' => '两次输入的密码不一致']);
            }
            
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        try {
            Db::name('admin')->where('id', $admin['id'])->update($data);
            
            // 更新session中的昵称
            $admin['nickname'] = $data['nickname'];
            Session::set('admin', $admin);
            
            // 记录操作
            $this->logOperation("更新个人资料", $admin['username']);
            
            return json(['code' => 1, 'msg' => '资料更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '资料更新失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 验证 Cloudflare Access 请求
     * @return bool
     */
    private function checkCloudflareAccess()
    {
        // 检查是否启用了 Cloudflare Access
        if (!Config::get('cloudflare.access_enabled', false)) {
            return false;
        }
        
        // 获取 Cloudflare Access 头信息
        $cfAccessJwt = Request::header('CF-Access-Jwt-Assertion');
        $cfAccessEmail = Request::header('CF-Access-Authenticated-User-Email');
        
        // 如果没有 JWT 令牌，则验证失败
        if (empty($cfAccessJwt)) {
            Log::record('Cloudflare Access JWT not found', 'warning');
            return false;
        }
        
        // 验证 Cloudflare 访问 IP
        $cfConnectingIP = Request::header('CF-Connecting-IP');
        $allowedIPs = Config::get('cloudflare.allowed_ips', []);
        
        if (!empty($allowedIPs) && !in_array($cfConnectingIP, $allowedIPs)) {
            Log::record('IP not allowed: ' . $cfConnectingIP, 'warning');
            return false;
        }
        
        // 如果配置了 Cloudflare Access 验证，但没有安装 JWT 库，记录警告
        if (!class_exists('Firebase\JWT\JWT')) {
            Log::record('Firebase JWT library not installed. Cannot verify Cloudflare Access token.', 'warning');
            // 如果没有安装 JWT 库，但配置了 Cloudflare Access，则仅检查头信息存在
            return !empty($cfAccessJwt);
        }
        
        try {
            // 获取 Cloudflare 公钥
            $teamDomain = Config::get('cloudflare.team_domain');
            if (empty($teamDomain)) {
                Log::record('Cloudflare team domain not configured', 'error');
                return false;
            }
            
            $certsUrl = "https://{$teamDomain}/cdn-cgi/access/certs";
            
            // 获取公钥（实际应用中应该缓存此结果）
            $ch = curl_init($certsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if (!$response) {
                Log::record('Failed to fetch Cloudflare Access certificates: ' . $curlError, 'error');
                return false;
            }
            
            $jwks = json_decode($response, true);
            if (!isset($jwks['keys']) || !is_array($jwks['keys'])) {
                Log::record('Invalid JWKS format', 'error');
                return false;
            }
            
            // 验证 JWT 令牌
            $decoded = null;
            $lastError = null;
            
            foreach ($jwks['keys'] as $key) {
                try {
                    $jwk = $key;
                    $pem = \Firebase\JWT\JWK::parseKey($jwk, 'RS256');
                    $decoded = \Firebase\JWT\JWT::decode($cfAccessJwt, $pem, ['RS256']);
                    break;
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    continue;
                }
            }
            
            if ($decoded === null) {
                Log::record('Failed to verify JWT: ' . $lastError, 'error');
                return false;
            }
            
            // 验证 AUD 声明
            $aud = Config::get('cloudflare.application_aud');
            if (!empty($aud) && (!isset($decoded->aud) || $decoded->aud !== $aud)) {
                Log::record('Invalid audience in JWT: ' . ($decoded->aud ?? 'none') . ' expected: ' . $aud, 'warning');
                return false;
            }
            
            // 验证过期时间
            if (!isset($decoded->exp) || $decoded->exp < time()) {
                Log::record('JWT token expired', 'warning');
            return false;
        }
        
        // 如果配置了允许的邮箱域名，验证邮箱
        $allowedDomains = Config::get('cloudflare.allowed_email_domains', []);
        if (!empty($allowedDomains) && !empty($cfAccessEmail)) {
            $emailDomain = substr(strrchr($cfAccessEmail, "@"), 1);
            if (!in_array($emailDomain, $allowedDomains)) {
                Log::record('Email domain not allowed: ' . $emailDomain, 'warning');
                return false;
            }
        }
        
        // 验证通过
        Log::record('Cloudflare Access verification successful for: ' . ($cfAccessEmail ?? 'unknown'), 'info');
        return true;
        
    } catch (\Exception $e) {
        Log::record('Cloudflare Access verification error: ' . $e->getMessage(), 'error');
        return false;
    }
}

    // 统计视频弹幕数量
    private function countVideoDanmaku($videoId)
    {
        $danmakuTable = "obp_danmu_{$videoId}";
        
        try {
            // 检查表是否存在
            $exists = Db::query("SHOW TABLES LIKE '{$danmakuTable}'");
            if (!$exists) {
                return 0;
            }
            
            return Db::table($danmakuTable)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    // 统计所有弹幕数量
    private function countTotalDanmaku()
    {
        $total = 0;
        
        try {
            // 获取所有danmu_表
            $tables = Db::query("SHOW TABLES LIKE 'obp_danmu_%'");
            
            foreach ($tables as $table) {
                $tableName = current($table);
                $count = Db::table($tableName)->count();
                $total += $count;
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        
        return $total;
    }
    // 显示合并工具页面
    public function danmakuMerger() {
        return View::fetch('admin/danmaku_merger');
    }
    
    // 导入XML弹幕处理
    public function doImportXml()
    {
        $this->checkLogin();
        
        $videoId = input('post.video_id', 0, FILTER_VALIDATE_INT);
        $xmlFile = input('post.xml_file', '');
        $clearExisting = input('post.clear_existing', false);
        
        if ($videoId <= 0 || empty($xmlFile)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $xmlPath = root_path() . 'public/danmaku/' . $xmlFile;
        Log::record("尝试读取XML文件: {$xmlPath}", 'info');
        
        if (!file_exists($xmlPath)) {
            Log::record("XML文件不存在: {$xmlPath}", 'error');
            return json(['code' => 0, 'msg' => 'XML文件不存在']);
        }
        
        try {
            // 检查视频是否存在
            $video = Db::name('videos')->where('id', $videoId)->find();
            if (!$video) {
                return json(['code' => 0, 'msg' => '视频不存在']);
            }
            
            // 弹幕表名
            $danmakuTable = "obp_danmu_{$videoId}";
            
            // 检查表是否存在，不存在则创建
            $exists = Db::query("SHOW TABLES LIKE '{$danmakuTable}'");
            if (!$exists) {
                Db::execute("
                    CREATE TABLE `{$danmakuTable}` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        text TEXT NOT NULL,
                        time FLOAT NOT NULL,
                        mode INT DEFAULT 0,
                        color VARCHAR(20) DEFAULT '#FFFFFF',
                        timestamp BIGINT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            
            // 如果选择清空现有弹幕
            if ($clearExisting) {
                Db::execute("TRUNCATE TABLE `{$danmakuTable}`");
            }
            
            // 判断路径是否为文件
            if (!is_file($xmlPath)) {
                Log::record("不是有效的文件: {$xmlPath}", 'error');
                return json(['code' => 0, 'msg' => '不是有效的文件']);
            }
            
            $xmlContent = file_get_contents($xmlPath);
            if (empty($xmlContent)) {
                Log::record("文件内容为空: {$xmlPath}", 'error');
                return json(['code' => 0, 'msg' => '文件内容为空']);
            }
            
            // 使用simplexml_load_string解析XML内容
            $xml = simplexml_load_string($xmlContent);
            if (!$xml) {
                Log::record("XML解析失败: " . libxml_get_last_error()->message, 'error');
                return json(['code' => 0, 'msg' => '无法解析XML文件']);
            }
            
            // 导入弹幕
            $count = 0;
            foreach ($xml->d as $d) {
                $p = (string)$d->attributes()->p;
                $text = (string)$d;
                
                if ($p && $text) {
                    $parts = explode(',', $p);
                    if (count($parts) >= 5) {
                        $time = floatval($parts[0]);
                        $type = intval($parts[1]); // 1=滚动, 4=底部, 5=顶部
                        $color = intval($parts[3]); // XML color is decimal
                        $timestamp = intval($parts[4]) * 1000; // Unix timestamp in seconds
                        
                        // 转换模式: 1->0(滚动), 5->1(顶部), 4->2(底部)
                        $mode = 0;
                        if ($type === 5) $mode = 1;
                        else if ($type === 4) $mode = 2;
                        
                        // 转换颜色为十六进制
                        $colorHex = '#' . dechex($color);
                        
                        // 插入数据库
                        Db::table($danmakuTable)->insert([
                            'text' => $text,
                            'time' => $time,
                            'mode' => $mode,
                            'color' => $colorHex,
                            'timestamp' => $timestamp
                        ]);
                        
                        $count++;
                    }
                }
            }
            
            // 记录操作
            $admin = Session::get('admin');
            $action = $clearExisting ? "导入XML弹幕(清空原有)" : "导入XML弹幕(追加)";
            $this->logOperation("{$action} 视频ID:{$videoId} 文件:{$xmlFile} 数量:{$count}", $admin['username']);
            
            return json(['code' => 1, 'msg' => "成功导入 {$count} 条弹幕"]);
        } catch (\Exception $e) {
            Log::record("XML导入异常: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            return json(['code' => 0, 'msg' => '导入失败: ' . $e->getMessage()]);
        }
    }
}
