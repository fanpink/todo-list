<?php
/**
 * 测试Ollama API调用
 */

$url = 'http://localhost:11434/api/generate';
$data = [
    'model' => 'qwen2.5-coder:latest',
    'prompt' => '请从以下文本中识别任务，返回JSON格式：明天下午3点开会',
    'stream' => false,
    'format' => 'json'
];

echo "正在测试Ollama API...\n";
echo "URL: $url\n";
echo "模型: qwen2.5-coder:latest\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP状态码: $httpCode\n";

if ($error) {
    echo "错误: $error\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "API返回错误，状态码: $httpCode\n";
    echo "响应: $response\n";
    exit(1);
}

echo "✓ API调用成功！\n\n";
echo "响应内容：\n";
echo $response . "\n";

$result = json_decode($response, true);
if ($result && isset($result['response'])) {
    echo "\n✓ AI生成的内容：\n";
    echo $result['response'] . "\n";
}
?>
