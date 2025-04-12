<?php
/**
 * 在线用户统计服务
 * 功能：记录在线用户、清理超时用户、返回当前在线人数
 */

// 配置参数
$config = [
    'online_file' => 'online_users.xml',  // 在线用户XML文件
    'timeout' => 120,                     // 超时时间（秒）
    'clean_probability' => 10,            // 清理概率（百分比）
    'video_id' => ''                      // 视频ID（可选，用于区分不同视频的在线用户）
];

// 获取视频ID参数（如果提供）
if (isset($_GET['video_id']) || isset($_POST['video_id'])) {
    $config['video_id'] = isset($_GET['video_id']) ? $_GET['video_id'] : $_POST['video_id'];
}

// 如果有视频ID，则使用特定的XML文件
if (!empty($config['video_id'])) {
    $config['online_file'] = 'online_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $config['video_id']) . '.xml';
}

// 确保XML文件存在
if (!file_exists($config['online_file'])) {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $root = $dom->createElement('online_users');
    $dom->appendChild($root);
    $dom->formatOutput = true;
    $dom->save($config['online_file']);
    chmod($config['online_file'], 0666); // 确保文件可写
}

// 处理请求
$action = isset($_GET['action']) ? $_GET['action'] : 'heartbeat';

switch ($action) {
    case 'heartbeat':
        handleHeartbeat($config);
        break;
    case 'count':
        getOnlineCount($config);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

/**
 * 处理心跳请求
 */
function handleHeartbeat($config) {
    // 获取用户ID，如果没有则生成新ID
    $userId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    if (empty($userId)) {
        $userId = isset($_COOKIE['online_user_id']) ? $_COOKIE['online_user_id'] : '';
    }
    if (empty($userId)) {
        $userId = generateUserId();
        // 设置cookie，有效期30天
        setcookie('online_user_id', $userId, time() + 86400 * 30, '/');
    }

    // 更新用户状态
    updateUserStatus($userId, $config);

    // 随机清理过期用户
    if (mt_rand(1, 100) <= $config['clean_probability']) {
        cleanExpiredUsers($config);
    }

    // 获取当前在线人数
    $count = countOnlineUsers($config);

    // 返回结果
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'count' => $count
    ]);
}

/**
 * 获取在线人数
 */
function getOnlineCount($config) {
    // 随机清理过期用户
    if (mt_rand(1, 100) <= $config['clean_probability']) {
        cleanExpiredUsers($config);
    }

    // 获取当前在线人数
    $count = countOnlineUsers($config);

    // 返回结果
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
}

/**
 * 生成唯一用户ID
 */
function generateUserId() {
    return md5(uniqid(mt_rand(), true));
}

/**
 * 更新用户状态
 */
function updateUserStatus($userId, $config) {
    $dom = new DOMDocument();
    $dom->load($config['online_file']);
    $root = $dom->documentElement;

    // 查找用户节点
    $userNode = null;
    $users = $root->getElementsByTagName('user');
    foreach ($users as $user) {
        if ($user->getAttribute('id') === $userId) {
            $userNode = $user;
            break;
        }
    }

    // 如果用户不存在，创建新节点
    if ($userNode === null) {
        $userNode = $dom->createElement('user');
        $userNode->setAttribute('id', $userId);
        $root->appendChild($userNode);
    }

    // 更新最后活动时间
    $userNode->setAttribute('last_active', time());

    // 保存XML
    $dom->save($config['online_file']);
}

/**
 * 清理过期用户
 */
function cleanExpiredUsers($config) {
    $dom = new DOMDocument();
    $dom->load($config['online_file']);
    $root = $dom->documentElement;

    $users = $root->getElementsByTagName('user');
    $currentTime = time();
    $nodesToRemove = [];

    // 找出过期的用户节点
    for ($i = 0; $i < $users->length; $i++) {
        $user = $users->item($i);
        $lastActive = (int)$user->getAttribute('last_active');
        if ($currentTime - $lastActive > $config['timeout']) {
            $nodesToRemove[] = $user;
        }
    }

    // 移除过期节点
    foreach ($nodesToRemove as $node) {
        $root->removeChild($node);
    }

    // 保存XML
    $dom->save($config['online_file']);
}

/**
 * 统计在线用户数
 */
function countOnlineUsers($config) {
    $dom = new DOMDocument();
    $dom->load($config['online_file']);
    $users = $dom->documentElement->getElementsByTagName('user');
    return $users->length;
}
