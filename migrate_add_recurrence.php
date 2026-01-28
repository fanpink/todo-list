<?php
/**
 * 数据库迁移脚本：添加循环事件功能
 * 创建循环规则表和相关索引
 */

require_once './config.php';

$db = getDB();

echo "开始迁移数据库：添加循环事件功能\n";
echo str_repeat('=', 50) . "\n\n";

try {
    // 创建循环规则表
    $db->exec('
    CREATE TABLE IF NOT EXISTS recurrence_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task_id INTEGER NOT NULL,
        recurrence_type VARCHAR(20) NOT NULL,
        interval INTEGER DEFAULT 1,
        end_type VARCHAR(20) DEFAULT "never",
        end_count INTEGER,
        end_date DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    )');
    
    echo "✓ 创建 recurrence_rules 表成功\n";
    
    // 检查tasks表是否需要添加recurrence_rule_id字段
    $result = $db->query("PRAGMA table_info(tasks)");
    $columns = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }
    
    if (!in_array('recurrence_rule_id', $columns)) {
        $db->exec('ALTER TABLE tasks ADD COLUMN recurrence_rule_id INTEGER REFERENCES recurrence_rules(id) ON DELETE SET NULL');
        echo "✓ 添加 tasks.recurrence_rule_id 字段成功\n";
    } else {
        echo "✓ tasks.recurrence_rule_id 字段已存在\n";
    }
    
    // 检查tasks表是否需要添加is_recurrence_instance字段
    if (!in_array('is_recurrence_instance', $columns)) {
        $db->exec('ALTER TABLE tasks ADD COLUMN is_recurrence_instance INTEGER DEFAULT 0');
        echo "✓ 添加 tasks.is_recurrence_instance 字段成功\n";
    } else {
        echo "✓ tasks.is_recurrence_instance 字段已存在\n";
    }
    
    // 检查tasks表是否需要添加recurrence_parent_id字段
    if (!in_array('recurrence_parent_id', $columns)) {
        $db->exec('ALTER TABLE tasks ADD COLUMN recurrence_parent_id INTEGER REFERENCES tasks(id) ON DELETE CASCADE');
        echo "✓ 添加 tasks.recurrence_parent_id 字段成功\n";
    } else {
        echo "✓ tasks.recurrence_parent_id 字段已存在\n";
    }
    
    // 创建索引以提高查询性能
    $db->exec('CREATE INDEX IF NOT EXISTS idx_recurrence_rules_task_id ON recurrence_rules(task_id)');
    echo "✓ 创建索引 idx_recurrence_rules_task_id\n";
    
    $db->exec('CREATE INDEX IF NOT EXISTS idx_tasks_recurrence_rule_id ON tasks(recurrence_rule_id)');
    echo "✓ 创建索引 idx_tasks_recurrence_rule_id\n";
    
    $db->exec('CREATE INDEX IF NOT EXISTS idx_tasks_recurrence_parent_id ON tasks(recurrence_parent_id)');
    echo "✓ 创建索引 idx_tasks_recurrence_parent_id\n";
    
    $db->exec('CREATE INDEX IF NOT EXISTS idx_tasks_is_recurrence_instance ON tasks(is_recurrence_instance)');
    echo "✓ 创建索引 idx_tasks_is_recurrence_instance\n";
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "✓ 数据库迁移完成！\n";
    
} catch (Exception $e) {
    echo "\n✗ 迁移失败: " . $e->getMessage() . "\n";
    exit(1);
}
?>
