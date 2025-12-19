<?php
/**
 * 数据库初始化脚本
 * 创建 SQLite 数据库和表结构
 * 支持 PDO 和 SQLite3
 */

// 使用相对路径
$dbPath = './todo.db';

// 检查 SQLite 支持
$hasPDO = class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers());
$hasSQLite3 = class_exists('SQLite3');

if (!$hasPDO && !$hasSQLite3) {
    echo "✗ 错误：PHP 没有 SQLite 支持\n";
    echo "\n请启用 PDO SQLite 或 SQLite3 扩展\n";
    echo "运行: php --ini 查看 php.ini 位置\n";
    exit(1);
}

$usePDO = $hasPDO;
echo "\n使用 " . ($usePDO ? 'PDO SQLite' : 'SQLite3') . " 驱动\n";
echo str_repeat('=', 50) . "\n\n";

try {
    // 创建数据库连接
    if ($usePDO) {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } else {
        $db = new SQLite3($dbPath);
    }
    
    // 启用外键约束
    if ($usePDO) {
        $db->exec('PRAGMA foreign_keys = ON');
    } else {
        $db->exec('PRAGMA foreign_keys = ON;');
    }
    
    // 创建列表表
    $db->exec('
    CREATE TABLE IF NOT EXISTS lists (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        icon TEXT DEFAULT "list",
        is_system INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    // 创建任务表
    $db->exec('
    CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        list_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        note TEXT,
        is_completed INTEGER DEFAULT 0,
        is_important INTEGER DEFAULT 0,
        is_my_day INTEGER DEFAULT 0,
        due_date DATE,
        reminder_time DATETIME,
        repeat_rule TEXT,
        completed_at DATETIME,
        sort_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
    )');
    
    // 创建步骤表
    $db->exec('
    CREATE TABLE IF NOT EXISTS steps (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        is_completed INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    )');
    
    // 创建AI模型配置表
    $db->exec('
    CREATE TABLE IF NOT EXISTS ai_models (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        type VARCHAR(50) NOT NULL,
        api_url TEXT NOT NULL,
        model_name VARCHAR(100) NOT NULL,
        api_key TEXT,
        is_active INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    // 创建AI解析历史表
    $db->exec('
    CREATE TABLE IF NOT EXISTS ai_parse_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        input_text TEXT NOT NULL,
        output_json TEXT NOT NULL,
        model_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    // 检查是否已经有系统列表
    if ($usePDO) {
        $result = $db->query('SELECT COUNT(*) as count FROM lists WHERE is_system = 1');
        $row = $result->fetch(PDO::FETCH_ASSOC);
    } else {
        $result = $db->query('SELECT COUNT(*) as count FROM lists WHERE is_system = 1');
        $row = $result->fetchArray(SQLITE3_ASSOC);
    }
    
    if ($row['count'] == 0) {
        // 插入系统默认列表
        $db->exec("
        INSERT INTO lists (name, icon, is_system, sort_order) VALUES 
            ('任务', 'home', 1, 1)
        ");
        
        echo "✓ 数据库初始化成功！\n";
        echo "✓ 已创建默认系统列表\n";
    } else {
        echo "✓ 数据库已存在，表结构验证完成\n";
    }
    
    // 检查是否已有AI模型配置
    if ($usePDO) {
        $result = $db->query('SELECT COUNT(*) as count FROM ai_models');
        $row = $result->fetch(PDO::FETCH_ASSOC);
    } else {
        $result = $db->query('SELECT COUNT(*) as count FROM ai_models');
        $row = $result->fetchArray(SQLITE3_ASSOC);
    }
    
    if ($row['count'] == 0) {
        // 插入默认AI模型配置
        $db->exec("
        INSERT INTO ai_models (name, type, api_url, model_name, api_key, is_active) VALUES 
            ('Ollama 本地', 'ollama', 'http://localhost:11434', 'qwen2.5', NULL, 1),
            ('DeepSeek', 'deepseek', 'https://api.deepseek.com', 'deepseek-chat', NULL, 0)
        ");
        
        echo "✓ 已创建默认AI模型配置\n";
    }
    
    if (!$usePDO) {
        $db->close();
    }
    $db = null;
    
} catch (Exception $e) {
    echo "✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
?>
