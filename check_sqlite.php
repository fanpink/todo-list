<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQLite3 扩展检查</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Microsoft YaHei', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .steps {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .steps h3 {
            margin-top: 0;
            color: #333;
        }
        .steps ol {
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        code {
            background-color: #e9ecef;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .info {
            margin-top: 20px;
            padding: 15px;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 6px;
            color: #0c5460;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 SQLite3 扩展检查</h1>
        
        <?php
        if (class_exists('SQLite3')) {
            echo '<div class="status success">✅ SQLite3 扩展已启用！</div>';
            echo '<p>您的 PHP 环境已正确配置，可以正常使用待办事项应用。</p>';
            
            // 检查数据库是否已初始化
            if (file_exists('./todo.db')) {
                echo '<div class="info">✅ 数据库文件已存在，应用可以正常使用。</div>';
                echo '<a href="./" class="button">返回应用</a>';
            } else {
                echo '<div class="info">⚠️ 数据库尚未初始化，请先运行初始化脚本。</div>';
                echo '<div class="steps">';
                echo '<h3>初始化数据库</h3>';
                echo '<p>在命令行中运行：</p>';
                echo '<code>php init_db.php</code>';
                echo '</div>';
            }
        } else {
            echo '<div class="status error">❌ SQLite3 扩展未启用</div>';
            echo '<p>您需要启用 SQLite3 扩展才能使用本应用。</p>';
            
            echo '<div class="steps">';
            echo '<h3>启用 SQLite3 扩展的步骤：</h3>';
            echo '<ol>';
            echo '<li>找到 php.ini 配置文件：<br><code>php --ini</code></li>';
            echo '<li>用文本编辑器打开 php.ini 文件</li>';
            echo '<li>找到以下两行并删除前面的分号（;）：<br>';
            echo '<code>;extension=sqlite3</code> → <code>extension=sqlite3</code><br>';
            echo '<code>;extension=pdo_sqlite</code> → <code>extension=pdo_sqlite</code></li>';
            echo '<li>保存 php.ini 文件</li>';
            echo '<li>重启 PHP 服务器（如果使用 php -S 命令，按 Ctrl+C 停止，然后重新运行）</li>';
            echo '<li>刷新本页面，检查是否已启用</li>';
            echo '</ol>';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<strong>注意：</strong>不同的操作系统和 PHP 安装方式，php.ini 的位置可能不同：<br>';
            echo '• Windows: 通常在 PHP 安装目录下<br>';
            echo '• Linux: 可能在 /etc/php/[版本]/cli/php.ini<br>';
            echo '• macOS: 可能在 /usr/local/etc/php/[版本]/php.ini';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <p><strong>PHP 版本：</strong><?php echo phpversion(); ?></p>
            <p><strong>已加载的扩展：</strong></p>
            <code><?php echo implode(', ', get_loaded_extensions()); ?></code>
        </div>
    </div>
</body>
</html>
