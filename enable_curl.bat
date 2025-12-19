@echo off
chcp 65001 >nul
echo ========================================
echo       启用 PHP cURL 扩展
echo ========================================
echo.

set PHP_INI=D:\php-8.4.16-nts-Win32-vs17-x64\php.ini

echo 正在检查 php.ini 文件...
if not exist "%PHP_INI%" (
    echo ❌ 错误: 找不到 php.ini 文件
    echo 路径: %PHP_INI%
    pause
    exit /b 1
)

echo ✓ 找到 php.ini 文件
echo.

echo 正在备份 php.ini...
copy "%PHP_INI%" "%PHP_INI%.backup" >nul
echo ✓ 备份完成: %PHP_INI%.backup
echo.

echo 正在启用 cURL 扩展...
powershell -Command "(Get-Content '%PHP_INI%') -replace ';extension=curl', 'extension=curl' | Set-Content '%PHP_INI%'"

echo.
echo ========================================
echo ✓ cURL 扩展已启用！
echo ========================================
echo.
echo 请重启 PHP 服务器以使更改生效
echo 按任意键关闭...
pause >nul
