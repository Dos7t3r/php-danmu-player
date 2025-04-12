<?php
// 接收前端提交的数据（支持 JSON 或 表单提交）
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if ($data) {
    $dmkParam   = $data['dmkUrl'] ?? '';
    $text       = $data['text'] ?? '';
    $time       = $data['time'] ?? '';
    $colorInput = $data['color'] ?? '';
    $modeInput  = $data['mode'] ?? '';
    // 添加时间戳参数，如果前端没有提供则使用当前时间
    $timestamp  = $data['timestamp'] ?? time();
} else {
    $dmkParam   = $_POST['dmk'] ?? ($_POST['dmkUrl'] ?? '');
    $text       = $_POST['text'] ?? '';
    $time       = $_POST['time'] ?? '';
    $colorInput = $_POST['color'] ?? '';
    $modeInput  = $_POST['mode'] ?? '';
    $timestamp  = $_POST['timestamp'] ?? time();
}

// 基本校验
if (!$dmkParam || !$text) {
    exit('参数不完整');
}

// 安全过滤：防止路径注入
$dmkParam = trim($dmkParam);
$dmkParam = str_replace('\\', '/', $dmkParam);           // 规范路径分隔符
if (strpos($dmkParam, '../') !== false || strpos($dmkParam, '..\\') !== false) {
    exit('非法路径');
}
if (!preg_match('/\.xml$/i', $dmkParam)) {
    exit('非法文件类型');
}

// 如果传入的是URL，去掉域名，只取路径部分
if (preg_match('/^https?:\\/\\//i', $dmkParam)) {
    $urlParts = parse_url($dmkParam);
    if (isset($urlParts['path'])) {
        $dmkParam = ltrim($urlParts['path'], '/');
    }
}

// 确定服务器上的文件路径（相对于网站根目录）
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');              // 网站根目录绝对路径
$xmlFile = $docRoot . '/' . ltrim($dmkParam, '/');             // 构造完整文件路径

// 确保目录存在（如 XML 路径包含子目录）
$dir = dirname($xmlFile);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);  // 尝试创建目录
}

// 初始化 DOMDocument
$dom = new DOMDocument('1.0', 'UTF-8');
if (file_exists($xmlFile)) {
    // 加载已有的 XML 文件
    libxml_use_internal_errors(true);
    if (!$dom->load($xmlFile)) {
        // 若加载失败（文件损坏），重新创建根元素<i>
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createElement('i'));
    }
    libxml_clear_errors();
} else {
    // 文件不存在则新建根元素<i>
    $dom->appendChild($dom->createElement('i'));
}

// 获取根元素 <i>
$root = $dom->documentElement;

// 创建新的 <d> 弹幕节点，文本内容做 XML 实体转义
$danmuText = trim($text);
$danmuTextEscaped = htmlspecialchars($danmuText, ENT_XML1 | ENT_COMPAT, 'UTF-8');
$dElem = $dom->createElement('d', $danmuTextEscaped);

// 计算各字段值
$timeVal = is_numeric($time) ? floatval($time) : 0.0;    // 时间（秒）
if ($timeVal < 0) $timeVal = 0.0;

// 模式映射：0->1(滚动), 1->5(顶部), 2->4(底部)
$modeInt = is_numeric($modeInput) ? intval($modeInput) : 0;
switch ($modeInt) {
    case 1:  $mode = 5; break;  // 顶部
    case 2:  $mode = 4; break;  // 底部
    default: $mode = 1;        // 滚动 (默认)
}

// 字号固定为25（中号弹幕）
$fontSize = 25;

// 将颜色转换为十进制颜色值（默认为白色）
$color = 16777215;  // 默认颜色: 白色 (#FFFFFF)
$colorStr = trim($colorInput);
if ($colorStr !== '') {
    // 去掉前导 '#' 符号
    $hex = ltrim($colorStr, '#');
    // 如果是有效的6位十六进制字符串，则转换；如果是纯数字则直接取整
    if (preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
        $color = hexdec($hex);
    } elseif (is_numeric($colorStr)) {
        $color = intval($colorStr);
    }
}

// 使用传入的时间戳或当前时间
$timestamp = is_numeric($timestamp) ? intval($timestamp) : time();
$pool = 0;                                             // 弹幕池: 0-普通池
// 生成发送者ID（8位随机十六进制数）
$userId = strtoupper(dechex(random_int(0, 0xFFFFFFFF)));
$userId = str_pad($userId, 8, '0', STR_PAD_LEFT);
// 生成弹幕ID：使用当前时间毫秒+随机值确保唯一
$danmuId = intval(microtime(true) * 1000) . random_int(100, 999);

// 构造弹幕 <d> 节点的 p 属性值（按 Bilibili 弹幕规范的8个字段顺序）
$pValue = sprintf('%s,%d,%d,%d,%d,%d,%s,%s',
    rtrim(rtrim(number_format($timeVal, 3, '.', ''), '0'), '.'),  // 保留最多3位小数的时间
    $mode,        // 弹幕模式（1滚动,5顶部,4底部）
    $fontSize,    // 字体大小
    $color,       // 颜色（十进制）
    $timestamp,   // 时间戳
    $pool,        // 弹幕池
    $userId,      // 发送者ID (8位HEX)
    $danmuId      // 弹幕ID
);
$dElem->setAttribute('p', $pValue);

// 将新弹幕节点追加到 XML 根节点 <i> 下
$root->appendChild($dElem);

// 保存更新后的 XML 文件
$dom->formatOutput = true;  // 美化XML格式（可选）
if ($dom->save($xmlFile) === false) {
    exit('写入失败');
}

// 输出结果（可根据需要输出JSON，这里输出简单字符串）
echo "ok";
