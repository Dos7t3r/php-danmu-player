<?php
namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;

class Player extends BaseController
{
    // 播放器首页
    public function index()
    {
        // 验证访问权限
        $allowedDomains = ['iob.lol', 'oldbili.fun', 'www.iob.lol', 'www.oldbili.fun'];
        $referer = Request::header('referer');
        $hasValidReferer = false;

        // 检查Referer是否来自允许的域名
        if ($referer) {
            foreach ($allowedDomains as $domain) {
                if (strpos($referer, $domain) !== false) {
                    $hasValidReferer = true;
                    break;
                }
            }
        }

        // 获取token参数
        $token = input('admin_token', '');

        // 检查是否是管理员访问
        $isAdmin = $this->checkAdminAccess();

        // 验证逻辑：
        // 1. 如果是管理员请求，允许访问
        // 2. 如果有有效的Referer，允许访问
        // 3. 否则拒绝访问
        if (!$isAdmin && !$hasValidReferer) {
            // 记录访问尝试
            trace("未授权的播放器访问尝试: Referer={$referer}, IP=" . Request::ip(), 'warning');
            
            // 返回错误页面
            View::assign([
                'errorTitle' => '未授权访问',
                'errorMessage' => '访问被拒绝：缺少相关参数，请使用正确的打开方式！ '
            ]);
            return View::fetch(app()->getRootPath() . 'app/view/error/403.html');
        }

        // 默认值
        $videoUrl = '/static/player/sample/video.mp4';
        $subtitleUrl = '';
        $videoTitle = 'OBP Player';
        $danmakuDataJson = '[]';
        $hasDanmuTable = false;
        
        // 如果有obv参数，从数据库获取视频信息
        $obv = input('obv', 0, FILTER_VALIDATE_INT);
        if ($obv > 0) {
            try {
                // 获取视频信息
                $video = Db::name('videos')->where('id', $obv)->find();
                
                if ($video) {
                    $videoUrl = $video['video_url'];
                    $subtitleUrl = $video['subtitle_url'];
                    $videoTitle = $video['title'] ?: 'OBP Player';
                    
                    // 更新播放次数
                    Db::name('videos')->where('id', $obv)->inc('play_count')->update();
                    
                    // 获取弹幕表名
                    $danmuTableName = "obp_danmu_{$obv}";
                    
                    // 检查弹幕表是否存在
                    $exists = Db::query("SHOW TABLES LIKE '{$danmuTableName}'");
                    $hasDanmuTable = !empty($exists);
                    
                    if ($hasDanmuTable) {
                        // 从数据库获取弹幕数据
                        $danmakuData = Db::table($danmuTableName)
                            ->order('time', 'asc')
                            ->select()
                            ->toArray();
                        
                        // 格式化弹幕数据
                        $formattedData = [];
                        foreach ($danmakuData as $row) {
                            $formattedData[] = [
                                'text' => $row['text'],
                                'time' => (float)$row['time'],
                                'mode' => (int)$row['mode'],
                                'color' => $row['color'],
                                'timestamp' => (int)$row['timestamp'],
                                'border' => false
                            ];
                        }
                        
                        // 将弹幕数据转为JSON
                        $danmakuDataJson = json_encode($formattedData);
                    }
                }
            } catch (\Exception $e) {
                // 记录错误但不暴露给用户
                trace("数据库错误: " . $e->getMessage(), 'error');
            }
        }
        
        // Legacy URL参数支持
        if (input('?url')) $videoUrl = input('url');
        if (input('?sub')) $subtitleUrl = input('sub');
        
        // 视频ID用于在线用户跟踪
        $videoId = $obv > 0 ? $obv : (input('id', ''));
        
        // 传递数据到视图
        View::assign([
            'videoUrl' => $videoUrl,
            'subtitleUrl' => $subtitleUrl,
            'videoTitle' => $videoTitle,
            'hasDanmuTable' => $hasDanmuTable,
            'danmakuDataJson' => $danmakuDataJson,
            'videoId' => $videoId,
            'obvId' => $obv
        ]);
        
        // 使用绝对路径指定模板文件
        return View::fetch(app()->getRootPath() . 'app/view/player/index.html');
    }

    /**
     * 检查是否是管理员访问
     * @return bool
     */
    private function checkAdminAccess()
    {
        // 检查管理员会话
        $adminSession = Session::get('admin');
        if ($adminSession) {
            return true;
        }
        
        // 检查管理员cookie
        $adminCookie = cookie('admin_auth');
        if ($adminCookie) {
            try {
                $adminData = json_decode($adminCookie, true);
                if (is_array($adminData) && isset($adminData['id'])) {
                    // 从数据库验证用户
                    $admin = Db::name('admin')->where('id', $adminData['id'])->find();
                    if ($admin) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                trace("管理员Cookie验证失败: " . $e->getMessage(), 'error');
            }
        }
        
        // 检查特定的管理员token参数
        $adminToken = input('admin_token', '');
        if (!empty($adminToken)) {
            // 从runtime/admin_token.php文件中获取有效的管理员token
            $tokenFile = app()->getRootPath() . 'runtime/admin_token.php';
            if (file_exists($tokenFile)) {
                $tokens = include $tokenFile;
                if (is_array($tokens) && in_array($adminToken, $tokens)) {
                    return true;
                }
            }
        }
        
        // 检查随机用户ID
        $userId = cookie('online_user_id');
        if (!empty($userId)) {
            // 验证用户ID是否有效
            $user = Db::name('online_users')->where('user_id', $userId)->find();
            if ($user) {
                return true;
            }
        }
        
        return false;
    }

    // 发送弹幕
    public function sendDanmaku()
    {
        // 只接受POST请求
        if (!Request::isPost()) {
            return json(['success' => false, 'message' => '只接受POST请求']);
        }
        
        // 获取JSON数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证必要字段
        if (!$data || !isset($data['text']) || !isset($data['time']) || !isset($data['obvId'])) {
            return json(['success' => false, 'message' => '缺少必要参数']);
        }
        
        // 处理数据
        $text = trim($data['text']);
        $time = floatval($data['time']);
        $color = isset($data['color']) ? $data['color'] : '#FFFFFF';
        $mode = isset($data['mode']) ? intval($data['mode']) : 0;
        $timestamp = isset($data['timestamp']) ? intval($data['timestamp']) : time() * 1000;
        $obvId = intval($data['obvId']);
        
        // 存储到数据库
        if ($obvId > 0) {
            try {
                // 检查视频是否存在
                $video = Db::name('videos')->where('id', $obvId)->find();
                
                if (!$video) {
                    return json(['success' => false, 'message' => '视频ID不存在']);
                }
                
                // 获取弹幕表名
                $danmakuTable = "obp_danmu_{$obvId}";
                
                // 检查弹幕表是否存在
                $exists = Db::query("SHOW TABLES LIKE '{$danmakuTable}'");
                
                if (empty($exists)) {
                    // 创建弹幕表
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
                
                // 插入弹幕
                $id = Db::table($danmakuTable)->insertGetId([
                    'text' => $text,
                    'time' => $time,
                    'mode' => $mode,
                    'color' => $color,
                    'timestamp' => $timestamp
                ]);
                
                return json([
                    'success' => true,
                    'message' => '弹幕发送成功',
                    'data' => [
                        'id' => $id,
                        'text' => $text,
                        'time' => $time,
                        'mode' => $mode,
                        'color' => $color,
                        'timestamp' => $timestamp
                    ]
                ]);
            } catch (\Exception $e) {
                trace("发送弹幕错误: " . $e->getMessage(), 'error');
                return json(['success' => false, 'message' => '数据库错误，弹幕发送失败']);
            }
        } else {
            return json(['success' => false, 'message' => '缺少有效的视频ID']);
        }
    }

    // 在线用户心跳
    public function heartbeat()
    {
        // 确保返回JSON格式
        header('Content-Type: application/json');
        
        $userId = input('post.user_id', '');
        $videoId = input('post.video_id', '');
        $timeout = Db::name('settings')->where('key', 'online_timeout')->value('value') ?: 120;
        
        if (empty($userId)) {
            $userId = md5(uniqid(mt_rand(), true));
            cookie('online_user_id', $userId, 86400 * 30);
        }
        
        try {
            // 更新或插入用户记录
            $exists = Db::name('online_users')->where('user_id', $userId)->find();
            
            if ($exists) {
                Db::name('online_users')->where('user_id', $userId)->update([
                    'video_id' => $videoId,
                    'ip_address' => Request::ip(),
                    'user_agent' => Request::header('user-agent'),
                    'last_active' => date('Y-m-d H:i:s')
                ]);
            } else {
                Db::name('online_users')->insert([
                    'user_id' => $userId,
                    'video_id' => $videoId,
                    'ip_address' => Request::ip(),
                    'user_agent' => Request::header('user-agent'),
                    'last_active' => date('Y-m-d H:i:s')
                ]);
            }
            
            // 清理过期用户
            Db::name('online_users')
                ->where('last_active', '<', date('Y-m-d H:i:s', time() - $timeout))
                ->delete();
            
            // 统计在线人数
            $count = $this->countOnlineUsers($videoId, $timeout);
            
            return json([
                'success' => true,
                'user_id' => $userId,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            trace("在线用户心跳错误: " . $e->getMessage(), 'error');
            return json(['success' => false, 'message' => '数据库操作失败']);
        }
    }

    // 获取在线人数
    public function onlineCount()
    {
        // 确保返回JSON格式
        header('Content-Type: application/json');
        
        $videoId = input('video_id', '');
        $timeout = Db::name('settings')->where('key', 'online_timeout')->value('value') ?: 120;
        
        try {
            $count = $this->countOnlineUsers($videoId, $timeout);
            
            return json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            trace("获取在线人数错误: " . $e->getMessage(), 'error');
            return json(['success' => false, 'message' => '数据库操作失败']);
        }
    }

    // 统计在线人数
    private function countOnlineUsers($videoId, $timeout)
    {
        $query = Db::name('online_users')
            ->where('last_active', '>=', date('Y-m-d H:i:s', time() - $timeout));
        
        if (!empty($videoId)) {
            $query = $query->where('video_id', $videoId);
        }
        
        return $query->count();
    }
}
