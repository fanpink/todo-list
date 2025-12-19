<?php
/**
 * AI 功能调试脚本
 */

// 启用详细错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>AI 功能调试信息</h2>";

// 1. 检查 PHP 版本
echo "<h3>1. PHP 版本</h3>";
echo "版本: " . PHP_VERSION . "<br>";

// 2. 检查必要扩展
echo "<h3>2. PHP 扩展</h3>";
$extensions = ['sqlite3', 'curl', 'json'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "$ext: " . ($loaded ? '✓ 已加载' : '✗ 未加载') . "<br>";
}

// 3. 检查数据库
echo "<h3>3. 数据库连接</h3>";
try {
    $db = new SQLite3('./todo.db');
    echo "✓ 数据库连接成功<br>";
    
    // 检查 AI 模型表
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='ai_models'");
    if ($result->fetchArray()) {
        echo "✓ ai_models 表存在<br>";
        
        // 查询激活的模型
        $modelResult = $db->query('SELECT * FROM ai_models WHERE is_active = 1');
        $model = $modelResult->fetchArray(SQLITE3_ASSOC);
        
        if ($model) {
            echo "<h4>激活的 AI 模型：</h4>";
            echo "<pre>";
            print_r($model);
            echo "</pre>";
        } else {
            echo "⚠ 未找到激活的模型<br>";
            
            // 显示所有模型
            $allModels = $db->query('SELECT * FROM ai_models');
            echo "<h4>所有 AI 模型：</h4>";
            echo "<pre>";
            while ($m = $allModels->fetchArray(SQLITE3_ASSOC)) {
                print_r($m);
                echo "\n";
            }
            echo "</pre>";
        }
    } else {
        echo "✗ ai_models 表不存在，请运行 php init_db.php<br>";
    }
    
    $db->close();
} catch (Exception $e) {
    echo "✗ 数据库错误: " . $e->getMessage() . "<br>";
}

// 4. 检查 Ollama 连接
echo "<h3>4. Ollama 连接测试</h3>";
if (function_exists('curl_init')) {
    $url = 'http://localhost:11434/api/tags';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✓ Ollama 服务运行正常<br>";
        $data = json_decode($response, true);
        if ($data && isset($data['models'])) {
            echo "已安装的模型：<br>";
            echo "<ul>";
            foreach ($data['models'] as $model) {
                echo "<li>" . htmlspecialchars($model['name']) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "✗ Ollama 连接失败<br>";
        echo "HTTP 状态码: $httpCode<br>";
        if ($error) {
            echo "错误: $error<br>";
        }
        echo "<br>请确保：<br>";
        echo "1. Ollama 已安装并启动<br>";
        echo "2. 运行命令: ollama serve<br>";
    }
} else {
    echo "✗ curl 扩展未加载<br>";
}

// 5. 测试 API 请求解析
echo "<h3>5. API 请求解析测试</h3>";
echo "模拟 POST 请求数据：<br>";

$testData = json_encode([
    'text' => '明天下午3点开会',
    'model_id' => 1
]);

echo "<pre>";
echo "JSON 数据: " . $testData . "\n";
echo "解析结果: ";
print_r(json_decode($testData, true));
echo "</pre>";

// 6. 检查 CA 证书配置
echo "<h3>6. cURL CA 证书配置</h3>";
$caInfo = ini_get('curl.cainfo');
if ($caInfo) {
    echo "curl.cainfo = $caInfo<br>";
    if (file_exists($caInfo)) {
        echo "✓ CA 证书文件存在<br>";
    } else {
        echo "✗ CA 证书文件不存在<br>";
        echo "请下载: <a href='https://curl.se/ca/cacert.pem' target='_blank'>https://curl.se/ca/cacert.pem</a><br>";
    }
} else {
    echo "⚠ 未配置 curl.cainfo<br>";
    echo "这可能导致 HTTPS 请求失败<br>";
}

// 7. 显示 php.ini 路径
echo "<h3>7. PHP 配置文件</h3>";
echo "php.ini 路径: " . php_ini_loaded_file() . "<br>";

echo "<hr>";
echo "<p><strong>调试完成！</strong></p>";
echo "<p>如果发现问题，请根据上面的提示进行修复。</p>";
?>
