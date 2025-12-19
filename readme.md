# 📝 Todo List - 智能待办事项管理系统

> 基于 PHP + SQLite 的轻量级待办事项管理应用，集成 AI 智能识别功能

<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![SQLite](https://img.shields.io/badge/SQLite-3-green)
![License](https://img.shields.io/badge/License-MIT-yellow)

</div>

---

## ✨ 功能特性

### 📋 核心功能
- **多维任务视图**
  - 🌅 我的一天：聚焦当日待办
  - ⭐ 重要：高优先级任务
  - 📅 计划内：按日期分组管理
  - 📂 自定义列表：灵活分类（工作、生活等）

- **任务管理**
  - ✅ 创建、编辑、删除任务
  - 📆 设置截止日期和提醒
  - 🔁 重复任务（每天/每周/每月）
  - 📝 添加子步骤拆解任务
  - 🔖 任务备注说明
  - 🎯 重要性标记

- **列表管理**
  - 📁 创建自定义列表
  - ✏️ 重命名/删除列表
  - 🎨 列表详情自定义

### 🤖 AI 智能功能

- **智能任务识别**
  - 从自然语言文本中自动提取任务
  - 智能识别截止日期（支持"今天"、"明天"等相对时间）
  - 自动分类到合适的列表
  - 提取任务步骤

- **AI 模型管理**
  - 支持本地 Ollama 模型（免费）
  - 支持 DeepSeek API
  - 支持 OpenAI 兼容接口
  - 灵活切换激活模型

### 🎨 用户体验

- 🎯 拖拽排序任务
- 💾 自动保存
- 📱 响应式设计
- 🌙 深色模式支持
- ⚡ 无需登录，开箱即用

---

## 🚀 快速开始

### 环境要求

- PHP 7.4+ （推荐 PHP 8.x）
- SQLite 3 扩展
- cURL 扩展（用于 AI 功能）
- Web 服务器（Apache/Nginx/内置服务器）

### 安装步骤

#### 1. 克隆项目

```bash
git clone https://github.com/your-username/todo-list.git
cd todo-list
```

#### 2. 启用 PHP 扩展（Windows）

如果你使用 Windows，可以运行提供的批处理脚本：

```bash
# 启用 SQLite 扩展
enable_sqlite.bat

# 启用 cURL 扩展（AI 功能需要）
enable_curl.bat
```

或手动编辑 `php.ini`，取消以下行的注释：

```ini
extension=sqlite3
extension=curl
```

#### 2.5. 配置 cURL CA 证书（重要！）

**即使使用本地 Ollama，也需要配置 CA 证书，否则 PHP cURL 可能无法正常工作。**

1. **下载 CA 证书文件**

   访问 [https://curl.se/ca/cacert.pem](https://curl.se/ca/cacert.pem) 下载 `cacert.pem` 文件

2. **保存到 PHP 目录**

   例如：`D:\php-8.4.16-nts-Win32-vs17-x64\cacert.pem`

3. **编辑 php.ini**

   找到 `curl.cainfo` 配置项（可能被注释），修改为：

   ```ini
   curl.cainfo = "D:\php-8.4.16-nts-Win32-vs17-x64\cacert.pem"
   ```

   **注意：** 路径使用双引号包裹，反斜杠 `\` 需要转义或使用正斜杠 `/`

4. **重启 PHP 服务器**

   ```bash
   # 先停止当前服务器（Ctrl + C）
   # 然后重新启动
   php -S localhost:8000
   ```

#### 3. 初始化数据库

```bash
php init_db.php
```

成功后会显示：

```
✓ 数据库初始化成功！
✓ 创建了 6 个默认列表
✓ 默认 AI 模型配置已添加
```

#### 4. 启动服务器

**使用 PHP 内置服务器：**

```bash
php -S localhost:8000
```

**使用其他 Web 服务器：**

将项目文件放到网站根目录下，配置虚拟主机指向项目目录。

**在群晖 NAS Web Station 中部署：**

详见下方「[在群晖 NAS 中部署](#-在群晖-nas-中部署)」章节。

#### 5. 访问应用

打开浏览器访问：

```
http://localhost:8000
```

---

## 🖥️ 在群晖 NAS 中部署

如果你在群晖 NAS 的 Web Station 中部署此项目，需要特别注意以下配置：

### 1. 启用 PHP 扩展

1. **SSH 登录群晖**

   ```bash
   ssh admin@your-nas-ip
   sudo -i
   ```

2. **找到 PHP 配置文件**

   群晖的 PHP 配置文件通常位于：
   ```bash
   # PHP 7.4
   /etc/php74/php.ini
   
   # PHP 8.0
   /etc/php80/php.ini
   
   # PHP 8.1
   /etc/php81/php.ini
   
   # PHP 8.2
   /etc/php82/php.ini
   
   # PHP 8.4
   /etc/php84/php.ini
   ```

3. **编辑 php.ini**

   ```bash
   # 使用 vi 或 nano 编辑
   vi /etc/php82/php.ini
   ```

   确保以下扩展已启用（去掉前面的 `;`）：
   ```ini
   extension=sqlite3
   extension=pdo_sqlite
   extension=curl
   ```

### 2. 配置 cURL CA 证书

> **推荐使用 Web Station 界面配置（方法一）**，更加简单且不需要手动编辑配置文件，适用于 DSM 7.0+ 和 PHP 8.0+ 版本。

#### 方法一：使用 Web Station 界面配置（推荐，适用于 PHP 8.0+）

1. **下载 CA 证书到群晖**

   ```bash
   # SSH 登录群晖后执行
   sudo wget -O /volume1/web/cacert.pem https://curl.se/ca/cacert.pem
   ```

2. **通过 Web Station 界面配置**

   - 打开 **Web Station** → **脚本语言设置**
   - 找到你使用的 PHP 版本（如 PHP 8.4），点击「编辑」
   - 切换到「核心」标签页
   - 找到 `curl.cainfo` 配置项
   - 填入证书路径：
     ```
     /volume1/web/cacert.pem
     ```
   - 点击「确定」保存
   - 重启 Web Station 使配置生效

#### 方法二：手动编辑 php.ini（适用于所有版本）

   ```bash
   # 使用 vi 或 nano 编辑（以 PHP 8.4 为例）
   sudo vi /etc/php84/php.ini
   ```

   添加或修改以下配置：
   ```ini
   curl.cainfo = "/volume1/web/cacert.pem"
   ```

   保存后重启 Web Station。

3. **设置文件权限**

   ```bash
   sudo chmod 644 /volume1/web/cacert.pem
   ```

### 3. 配置 Web Station

1. **打开 Web Station**
   - 控制面板 → Web Station

2. **创建虚拟主机**
   - 创建 → 基于名称的虚拟主机
   - 主机名：`todo.your-domain.com`（或使用 IP）
   - 端口：`80` 或 `443`
   - 文档根目录：选择项目上传的目录
   - PHP 版本：选择 8.0+ 或 7.4+
   - 启用 PHP 缓存：建议勾选

3. **设置目录权限**

   ```bash
   # 进入项目目录
   cd /volume1/web/todo-list
   
   # 设置权限（让 http 用户可写）
   chown -R http:http .
   chmod -R 755 .
   
   # 数据库文件需要写权限
   chmod 666 todo.db
   ```

### 4. 初始化数据库

```bash
# SSH 进入项目目录
cd /volume1/web/todo-list

# 运行初始化脚本
php82 init_db.php
```

### 5. 配置 Ollama（可选）

如果要在群晖上运行 Ollama：

1. **使用 Docker 部署 Ollama**

   在群晖 Docker 套件中：
   - 搜索并下载 `ollama/ollama` 镜像
   - 创建容器时映射端口：`11434:11434`
   - 启动容器

2. **下载模型**

   ```bash
   # 进入容器
   docker exec -it ollama-container bash
   
   # 下载模型
   ollama pull qwen2.5-coder:latest
   ```

3. **在应用中配置**

   AI 模型管理中，将 Ollama API 地址设置为：
   ```
   http://localhost:11434
   ```
   或容器的实际 IP 地址

### 6. 重启 Web Station

配置完成后，在 Web Station 中重启对应的虚拟主机使配置生效。

### 常见问题（群晖）

**问题：数据库文件权限错误**
```bash
# 解决方案
chown http:http todo.db
chmod 666 todo.db
```

**问题：PHP 扩展未加载**
```bash
# 检查扩展是否启用
php82 -m | grep -E 'sqlite|curl'

# 重启 Web Station
synoservice --restart pkgctl-WebStation
```

**问题：无法访问 Ollama**
- 检查 Docker 容器是否运行
- 检查端口映射是否正确
- 检查群晖防火墙规则

---

## 🤖 配置 AI 功能

### 方案一：使用本地 Ollama（推荐）

**优势：** 完全免费，数据本地化，无需 API Key

1. **安装 Ollama**

   访问 [https://ollama.com/download](https://ollama.com/download) 下载安装

2. **下载模型**

   ```bash
   # 推荐模型
   ollama pull qwen2.5-coder:latest
   
   # 或其他模型
   ollama pull llama3.2
   ollama pull gemma2
   ```

3. **启动 Ollama 服务**

   ```bash
   ollama serve
   ```

4. **在应用中配置**

   - 点击左侧栏「AI 模型管理」
   - 默认已配置 Ollama 本地模型
   - 点击「激活」按钮启用

### 方案二：使用 DeepSeek API

**优势：** 云端服务，无需本地部署

1. **获取 API Key**

   访问 [https://platform.deepseek.com](https://platform.deepseek.com) 注册并获取 API Key

2. **配置 SSL 证书（Windows 用户）**

   下载 CA 证书：
   ```bash
   # 访问以下链接下载 cacert.pem
   https://curl.se/ca/cacert.pem
   ```

   将文件保存到 PHP 目录，例如：
   ```
   D:\php-8.4.16-nts-Win32-vs17-x64\cacert.pem
   ```

   编辑 `php.ini`：
   ```ini
   curl.cainfo = "D:\php-8.4.16-nts-Win32-vs17-x64\cacert.pem"
   ```

3. **在应用中配置**

   - 点击左侧栏「AI 模型管理」
   - 编辑 DeepSeek 模型
   - 填入你的 API Key
   - 点击「激活」按钮启用

---

## 📖 使用指南

### 基础操作

**创建任务**
1. 在顶部输入框输入任务标题
2. 按 Enter 键创建

**管理任务**
- ✅ 点击圆圈标记完成
- ⭐ 点击星标标记重要
- 🌅 右键菜单添加到"我的一天"
- 📝 点击任务展开详情编辑

**创建列表**
1. 点击左侧栏底部「+ 新建列表」
2. 输入列表名称
3. 自定义图标和颜色（可选）

### AI 智能导入

1. 点击左侧栏「智能导入」
2. 粘贴或输入任务描述文本，例如：

   ```
   明天下午3点开会
   周五提交报告
   买一些水果和蔬菜
   下周一联系客户确认需求
   ```

3. 点击「🤖 识别任务」
4. 预览识别结果，可编辑调整
5. 点击「✓ 确认导入」

**AI 支持的时间表达：**
- 今天、明天、后天
- 本周X、下周X
- X月X日
- YYYY-MM-DD 格式

---

## 🛠️ 项目结构

```
todo-list/
├── index.html              # 主页面
├── api.php                 # RESTful API 接口
├── init_db.php             # 数据库初始化脚本
├── config.php              # 配置文件
├── todo.db                 # SQLite 数据库（运行后生成）
├── css/
│   └── style.css          # 样式文件
├── js/
│   ├── app.js             # 业务逻辑
│   └── api.js             # API 封装
├── enable_sqlite.bat       # SQLite 扩展启用脚本
├── enable_curl.bat         # cURL 扩展启用脚本
├── update_ai_model.php     # AI 模型更新工具
├── test_ollama.php         # Ollama 测试工具
└── README.md              # 项目说明
```

---

## 🗄️ 数据库设计

### 核心表结构

**lists** - 任务列表
```sql
- id: 列表ID
- name: 列表名称
- icon: 图标
- color: 颜色
- is_system: 是否系统列表
- list_order: 排序序号
```

**tasks** - 任务
```sql
- id: 任务ID
- list_id: 所属列表
- title: 任务标题
- notes: 备注
- due_date: 截止日期
- is_important: 是否重要
- is_completed: 是否完成
- task_order: 排序序号
```

**steps** - 任务步骤
```sql
- id: 步骤ID
- task_id: 所属任务
- title: 步骤标题
- is_completed: 是否完成
```

**ai_models** - AI 模型配置
```sql
- id: 模型ID
- name: 模型名称
- type: 模型类型（ollama/deepseek/openai）
- api_url: API 地址
- model_name: 模型名称
- api_key: API 密钥
- is_active: 是否激活
```

---

## 🔧 开发指南

### API 接口

所有 API 请求统一通过 `api.php?action=xxx` 访问

**任务相关：**
- `GET /api.php?action=tasks&list_id=1` - 获取任务列表
- `POST /api.php?action=tasks` - 创建任务
- `PUT /api.php?action=tasks&id=1` - 更新任务
- `DELETE /api.php?action=tasks&id=1` - 删除任务

**列表相关：**
- `GET /api.php?action=lists` - 获取所有列表
- `POST /api.php?action=lists` - 创建列表
- `PUT /api.php?action=lists&id=1` - 更新列表
- `DELETE /api.php?action=lists&id=1` - 删除列表

**AI 相关：**
- `GET /api.php?action=ai_models` - 获取 AI 模型列表
- `POST /api.php?action=ai_parse_tasks` - AI 识别任务
- `POST /api.php?action=set_active_ai_model` - 设置激活模型

### 自定义配置

编辑 `config.php` 自定义配置：

```php
<?php
// 数据库配置
define('DB_PATH', './todo.db');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 其他配置...
?>
```

---

## ❓ 常见问题

### 1. 数据库初始化失败？

**错误：** `SQLite3::open(): Unable to open database`

**解决：**
- 确认 PHP 已启用 sqlite3 扩展
- 确认项目目录有写入权限
- 运行 `enable_sqlite.bat`（Windows）

### 2. AI 功能报错 "Call to undefined function curl_init()"？

**解决：**
- 确认 PHP 已启用 curl 扩展
- 运行 `enable_curl.bat`（Windows）
- 或手动编辑 `php.ini` 取消 `extension=curl` 的注释
- **重要：** 必须配置 `curl.cainfo` 指向 CA 证书文件

### 3. AI 识别报错 "SSL certificate problem"？

**原因：** PHP cURL 缺少 CA 证书配置

**解决：**

**Windows：**
```ini
# php.ini 中添加实例
curl.cainfo = "D:\php-8.4.16-nts-Win32-vs17-x64\cacert.pem"
```

**群晖 NAS：**
通过 Web Station 界面配置（PHP 8.4+ 推荐）


1. 下载证书：
  `` 
  sudo wget -O /volume1/web/cacert.pem https://curl.se/ca/cacert.pem
  ```

2. Web Station → 脚本语言设置 → 编辑 PHP 8.4
   - 核心 → curl.cainfo = /volume1/web/cacert.pem
   
3. 重启 Web Station
```
**Linux/macOS：**
```ini
# php.ini 中添加
curl.cainfo = "/etc/ssl/certs/cacert.pem"
```

- 下载 CA 证书：https://curl.se/ca/cacert.pem
- 重启 PHP 服务器或 Web Station

### 4. Ollama 连接失败？

**解决：**
- 确认 Ollama 服务已启动：`ollama serve`
- 确认已下载模型：`ollama list`
- 检查端口 11434 是否被占用
- 在 AI 模型管理中更新模型名称

### 5. 如何备份数据？

**方法：**
- 直接复制 `todo.db` 文件
- 或使用 SQLite 导出工具

---

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

1. Fork 本项目
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

---

## 📄 开源协议

本项目采用 [MIT License](LICENSE) 开源协议

---

## 🙏 致谢

- 设计灵感来自 Microsoft To Do
- AI 功能基于 Ollama 和 DeepSeek
- 感谢所有贡献者

---

## 📮 联系方式

如有问题或建议，欢迎通过以下方式联系：

- 💬 提交 [Issue](https://github.com/your-username/todo-list/issues)
- 📧 发送邮件至：your-email@example.com

---

<div align="center">

**⭐ 如果这个项目对你有帮助，请给个 Star 支持一下！**

Made with ❤️ by [Your Name]

</div>
