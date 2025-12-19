@echo off
chcp 65001 >nul
echo ================================================
echo   Todo List - SQLite3 扩展快速启用工具
echo ================================================
echo.

echo [1/3] 检查 SQLite3 扩展状态...
php -m | findstr /i sqlite >nul 2>&1
if %errorlevel% == 0 (
    echo ✅ SQLite3 扩展已启用！
    echo.
    echo 您可以直接使用应用了。
    pause
    exit /b 0
)

echo ❌ SQLite3 扩展未启用
echo.

echo [2/3] 查找 php.ini 文件位置...
php --ini
echo.

echo [3/3] 如何启用 SQLite3：
echo.
echo 请按照以下步骤操作：
echo.
echo 1. 复制上面显示的 php.ini 文件路径
echo 2. 用记事本打开该文件
echo 3. 查找以下两行（按 Ctrl+F 搜索）：
echo    ;extension=sqlite3
echo    ;extension=pdo_sqlite
echo.
echo 4. 删除行首的分号（;），使其变为：
echo    extension=sqlite3
echo    extension=pdo_sqlite
echo.
echo 5. 保存文件
echo 6. 重新运行本脚本验证
echo.

pause
