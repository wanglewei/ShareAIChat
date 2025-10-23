<?php
ini_set('display_errors', 1); // 开发时开启错误显示，生产环境应关闭
error_reporting(E_ALL);

// --- 配置 ---
define('DATA_DIR', __DIR__ . '/data/'); // 定义数据存储目录
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])); // 自动获取基础URL

// --- 检查请求方法 ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo '错误：只接受 POST 请求。';
    exit;
}

// --- 获取并清理数据 ---
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : ''; // 获取标题

if (empty($content)) {
    http_response_code(400); // Bad Request
    echo '错误：提交的内容不能为空。 <a href="index.php">返回</a>';
    exit;
}

// 基本的HTML清理 (更强的清理可能需要库如 HTML Purifier)
// 这里仅做最基本的转义，防止简单的XSS，但允许基本的HTML标签（如果需要）
// 如果你确定内容永远不应包含HTML，可以使用 strip_tags()
// $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
// $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

// --- 创建数据存储目录 (如果不存在) ---
if (!is_dir(DATA_DIR)) {
    if (!mkdir(DATA_DIR, 0755, true)) { // 0755 权限，允许服务器写入
        http_response_code(500);
        error_log('无法创建数据目录: ' . DATA_DIR); // 记录错误日志
        echo '服务器错误：无法创建数据存储目录。请检查权限。';
        exit;
    }
}

// --- 生成唯一ID ---
$uniqueId = uniqid('share_', true); // 基于当前时间微秒，更具唯一性
$uniqueId = str_replace('.', '', $uniqueId); // 移除点号，使其更适合做文件名或URL部分

// --- 准备要保存的数据 (使用JSON存储标题和内容) ---
$dataToSave = json_encode([
    'title' => $title,
    'content' => $content,
    'timestamp' => time()
]);

// --- 保存数据到文件 ---
$filename = DATA_DIR . $uniqueId . '.json'; // 使用 .json 扩展名

if (file_put_contents($filename, $dataToSave) === false) {
    http_response_code(500);
    error_log('无法写入文件: ' . $filename); // 记录错误日志
    echo '服务器错误：无法保存分享内容。';
    exit;
}

// --- 生成分享链接并重定向 ---
$shareUrl = rtrim(BASE_URL, '/') . '/view.php?id=' . $uniqueId;
header('Location: ' . $shareUrl);
exit; // 确保重定向后停止执行

?>
