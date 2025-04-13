<?php
/**
 * 在线用户统计服务（使用 MySQL 数据库）
 */
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// 数据库配置（根据你的实际情况修改）
$dbConfig = [
    'host'     => 'localhost',
    'dbname'   => 'database-name',
    'username' => 'database-username',
    'password' => 'database-pass',
    'charset'  => 'utf8mb4'
];

// 超时时间（秒）
$timeout = 120;

// 连接数据库（PDO方式）
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// 获取请求参数
$action = $_GET['action'] ?? 'heartbeat';
$video_id = $_POST['video_id'] ?? $_GET['video_id'] ?? '';
$user_id = $_POST['user_id'] ?? $_COOKIE['online_user_id'] ?? '';

// 根据动作执行对应功能
switch ($action) {
    case 'heartbeat':
        handleHeartbeat($pdo, $user_id, $video_id, $timeout);
        break;

    case 'count':
        getOnlineCount($pdo, $video_id, $timeout);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

// 心跳处理函数
function handleHeartbeat($pdo, $user_id, $video_id, $timeout) {
    if (empty($user_id)) {
        $user_id = generateUserId();
        setcookie('online_user_id', $user_id, time() + 86400 * 30, '/');
    }

    // 插入或更新用户最后活跃时间
    $stmt = $pdo->prepare("
        INSERT INTO `online_users` (`user_id`, `video_id`, `last_active`)
        VALUES (:user_id, :video_id, NOW())
        ON DUPLICATE KEY UPDATE `last_active` = NOW(), `video_id` = :video_id
    ");
    $stmt->execute(['user_id' => $user_id, 'video_id' => $video_id]);

    // 清理过期用户
    cleanExpiredUsers($pdo, $timeout);

    // 返回当前人数
    $count = countOnlineUsers($pdo, $video_id, $timeout);

    echo json_encode([
        'success' => true,
        'user_id' => $user_id,
        'count' => $count
    ]);
}

// 获取当前在线人数
function getOnlineCount($pdo, $video_id, $timeout) {
    cleanExpiredUsers($pdo, $timeout);
    $count = countOnlineUsers($pdo, $video_id, $timeout);
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
}

// 清理超时用户
function cleanExpiredUsers($pdo, $timeout) {
    $stmt = $pdo->prepare("
        DELETE FROM `online_users` 
        WHERE `last_active` < (NOW() - INTERVAL :timeout SECOND)
    ");
    $stmt->execute(['timeout' => $timeout]);
}

// 统计当前在线人数
function countOnlineUsers($pdo, $video_id, $timeout) {
    if (!empty($video_id)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM `online_users`
            WHERE `video_id` = :video_id
            AND `last_active` >= (NOW() - INTERVAL :timeout SECOND)
        ");
        $stmt->execute(['video_id' => $video_id, 'timeout' => $timeout]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM `online_users`
            WHERE `last_active` >= (NOW() - INTERVAL :timeout SECOND)
        ");
        $stmt->execute(['timeout' => $timeout]);
    }

    return (int)$stmt->fetchColumn();
}

// 生成唯一用户ID
function generateUserId() {
    return md5(uniqid(mt_rand(), true));
}
