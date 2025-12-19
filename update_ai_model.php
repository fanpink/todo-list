<?php
/**
 * 更新AI模型配置
 */

try {
    $db = new SQLite3('./todo.db');
    
    // 更新Ollama模型名称（去除可能的空格）
    $stmt = $db->prepare("UPDATE ai_models SET model_name = TRIM(:model_name) WHERE type = 'ollama'");
    $stmt->bindValue(':model_name', 'qwen2.5-coder:latest', SQLITE3_TEXT);
    $stmt->execute();
    
    // 清理所有模型名称的前后空格
    $db->exec("UPDATE ai_models SET model_name = TRIM(model_name)");
    
    echo "✓ AI模型名称已更新为 qwen2.5-coder:latest\n";
    
    // 显示当前配置
    $result = $db->query("SELECT * FROM ai_models");
    echo "\n当前AI模型配置：\n";
    echo str_repeat('=', 80) . "\n";
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "ID: {$row['id']}\n";
        echo "名称: {$row['name']}\n";
        echo "类型: {$row['type']}\n";
        echo "模型: {$row['model_name']}\n";
        echo "API: {$row['api_url']}\n";
        echo "状态: " . ($row['is_active'] ? '激活' : '未激活') . "\n";
        echo str_repeat('-', 80) . "\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
?>
