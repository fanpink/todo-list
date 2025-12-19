<?php
/**
 * 配置文件
 */

// 检查 SQLite 支持（优先使用 PDO SQLite，其次使用 SQLite3 扩展）
$hasPDO = class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers());
$hasSQLite3 = class_exists('SQLite3');

if (!$hasPDO && !$hasSQLite3) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'SQLite 支持未启用。请启用 PDO SQLite 或 SQLite3 扩展',
        'error' => 'SQLITE_NOT_ENABLED',
        'solution' => [
            '选项1：使用 PDO SQLite（推荐）',
            '1. 找到 php.ini 文件：运行 php --ini',
            '2. 编辑 php.ini，确保以下行未被注释：',
            '   extension=pdo_sqlite',
            '',
            '选项2：使用 SQLite3 扩展',
            '1. 在 php.ini 中取消注释：',
            '   extension=sqlite3',
            '',
            '3. 保存并重启 PHP 服务器',
            '',
            '如果仍然出错，可能需要安装 Visual C++ 运行库：',
            'https://aka.ms/vs/17/release/vc_redist.x64.exe'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 使用 PDO 还是 SQLite3
// 暂时强制使用 SQLite3，因为 api.php 使用的是 SQLite3 API
define('USE_PDO', false);  // $hasPDO

// 数据库配置 - 使用相对路径
define('DB_PATH', './todo.db');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告（生产环境应关闭）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 字符编码
header('Content-Type: application/json; charset=utf-8');

/**
 * 获取数据库连接（支持 PDO 和 SQLite3）
 */
function getDB() {
    try {
        if (USE_PDO) {
            // 使用 PDO SQLite
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec('PRAGMA foreign_keys = ON');
            return $db;
        } else {
            // 使用 SQLite3
            $db = new SQLite3(DB_PATH);
            $db->exec('PRAGMA foreign_keys = ON;');
            return $db;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '数据库连接失败: ' . $e->getMessage(),
            'error' => 'DATABASE_CONNECTION_FAILED',
            'hint' => '请确保已运行 php init_db.php 初始化数据库',
            'db_type' => USE_PDO ? 'PDO' : 'SQLite3'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * 返回成功响应
 */
function success($data = null, $message = '') {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 返回错误响应
 */
function error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取请求方法
 */
function getMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * 获取 JSON 输入
 */
function getInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

/**
 * 验证必填字段
 */
function validateRequired($data, $fields) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            error("字段 {$field} 为必填项");
        }
    }
}
?>
