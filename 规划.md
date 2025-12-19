# Todo List - 个人待办事项应用

> 基于 PHP + SQLite 的轻量级待办事项管理系统，参考 Microsoft To Do 核心功能设计

## 项目概述

本项目是一个单用户的 Web 待办事项应用，无需登录认证，专注于任务管理核心功能。

### 技术栈
- **后端**: PHP 7.4+
- **数据库**: SQLite 3
- **前端**: HTML5 + CSS3 + JavaScript (Vanilla JS)
- **部署**: 网站根目录下的 `todo-list` 文件夹
- **路径规则**: 所有文件引用使用相对路径

---

## 一、功能设计

### 1. 核心对象模型

#### 基础实体
- **列表（List）**
  - 自定义清单：工作、生活、阅读等
  - 系统清单：我的一天、重要、计划内、已分配给你、任务

- **任务（Task）**
  - 标题、备注
  - 截止日期、提醒时间、重复规则
  - 优先级（重要性标记）
  - 所属列表
  - 完成状态/完成时间

- **步骤（Subtasks/Steps）**
  - 任务的子步骤，用于拆解任务

- **附件**
  - 通过 OneDrive/SharePoint 存储
  - 支持链接关联



### 2. 核心功能

#### "我的一天"视图
- 手动添加任务到"我的一天"
- 自动显示今天截止的任务
- 每日自动清空（不删除原任务）

#### 多维任务视图
- **我的一天**：当日待办任务
- **重要**：标记为重要的任务
- **计划内**：按截止日期分组（今天、明天、本周、以后）
- **任务**：默认任务列表
- **自定义列表**：用户创建的分类列表

#### 任务管理功能
- 创建、编辑、删除任务
- 设置截止日期
- 标记任务为重要
- 添加任务到"我的一天"
- 标记任务完成/未完成
- 任务排序（拖拽或手动调整）

#### 任务详情
- 任务标题（必填）
- 备注说明
- 截止日期选择
- 提醒时间设置
- 重复规则（每天/每周/每月/自定义）
- 子步骤（Subtasks）管理

#### 列表管理
- 创建自定义列表
- 重命名列表
- 删除列表（系统列表不可删除）
- 列表排序

---

## 二、技术架构

### 1. 整体架构设计

```
┌─────────────────────────────────────────────────────────┐
│                   前端层 (Frontend)                       │
│  - index.html (主页面)                                   │
│  - css/style.css (样式)                                  │
│  - js/app.js (业务逻辑)                                  │
│  - js/api.js (API 封装)                                  │
└─────────────────────────────────────────────────────────┘
                          ↓↑ AJAX (Fetch API)
┌─────────────────────────────────────────────────────────┐
│                   后端层 (Backend)                        │
│  - api.php (RESTful API 接口)                           │
│  - init_db.php (数据库初始化)                            │
│  - config.php (配置文件)                                 │
└─────────────────────────────────────────────────────────┘
                          ↓↑ SQLite3
┌─────────────────────────────────────────────────────────┐
│                  数据层 (Database)                        │
│  - todo.db (SQLite 数据库文件)                           │
│    * lists (列表表)                                      │
│    * tasks (任务表)                                      │
│    * steps (步骤表)                                      │
└─────────────────────────────────────────────────────────┘
```

### 2. 目录结构

```
todo-list/
├── index.html              # 主页面
├── init_db.php            # 数据库初始化脚本
├── api.php                # API 接口
├── config.php             # 配置文件
├── todo.db                # SQLite 数据库（自动生成）
├── css/
│   └── style.css          # 样式文件
├── js/
│   ├── app.js             # 主应用逻辑
│   └── api.js             # API 调用封装
└── readme.md              # 项目文档
```

### 3. 数据库设计

#### 列表表 (lists)
```sql
CREATE TABLE lists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,                  -- 列表名称
    icon TEXT DEFAULT 'list',            -- 图标标识
    is_system INTEGER DEFAULT 0,         -- 是否系统列表 (0:否, 1:是)
    sort_order INTEGER DEFAULT 0,        -- 排序顺序
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

#### 任务表 (tasks)
```sql
CREATE TABLE tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    list_id INTEGER NOT NULL,            -- 所属列表ID
    title TEXT NOT NULL,                 -- 任务标题
    note TEXT,                           -- 备注
    is_completed INTEGER DEFAULT 0,      -- 是否完成
    is_important INTEGER DEFAULT 0,      -- 是否重要
    is_my_day INTEGER DEFAULT 0,         -- 是否在"我的一天"
    due_date DATE,                       -- 截止日期
    reminder_time DATETIME,              -- 提醒时间
    repeat_rule TEXT,                    -- 重复规则 (JSON格式)
    completed_at DATETIME,               -- 完成时间
    sort_order INTEGER DEFAULT 0,        -- 排序顺序
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
);
```

#### 步骤表 (steps)
```sql
CREATE TABLE steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL,            -- 所属任务ID
    title TEXT NOT NULL,                 -- 步骤标题
    is_completed INTEGER DEFAULT 0,      -- 是否完成
    sort_order INTEGER DEFAULT 0,        -- 排序顺序
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
```

### 4. API 接口设计

#### 列表相关
```
GET    ./api.php?action=lists                # 获取所有列表
POST   ./api.php?action=lists                # 创建列表
PUT    ./api.php?action=lists&id={id}        # 更新列表
DELETE ./api.php?action=lists&id={id}        # 删除列表
```

#### 任务相关
```
GET    ./api.php?action=tasks&list_id={id}   # 获取指定列表的任务
GET    ./api.php?action=tasks&view=my-day     # 获取"我的一天"任务
GET    ./api.php?action=tasks&view=important  # 获取重要任务
GET    ./api.php?action=tasks&view=planned    # 获取计划内任务
POST   ./api.php?action=tasks                 # 创建任务
PUT    ./api.php?action=tasks&id={id}         # 更新任务
DELETE ./api.php?action=tasks&id={id}         # 删除任务
```

#### 步骤相关
```
GET    ./api.php?action=steps&task_id={id}   # 获取任务的步骤
POST   ./api.php?action=steps                 # 创建步骤
PUT    ./api.php?action=steps&id={id}         # 更新步骤
DELETE ./api.php?action=steps&id={id}         # 删除步骤
```

### 5. 前端技术栈

- **HTML5**: 语义化标签，支持现代浏览器
- **CSS3**: Flexbox/Grid 布局，CSS Variables，动画效果
- **JavaScript (ES6+)**: 
  - Fetch API 进行 AJAX 请求
  - LocalStorage 存储用户偏好
  - Event Delegation 事件处理
  - 模板字面量构建 DOM

### 6. 核心功能实现

#### "我的一天"逻辑
- `is_my_day` 字段标记任务是否在"我的一天"
- 每日自动清空：前端检测日期变化，调用 API 批量更新
- 自动添加今日截止任务到"我的一天"

#### 计划内视图
- 根据 `due_date` 字段分组：
  - 今天：`due_date = CURRENT_DATE`
  - 明天：`due_date = CURRENT_DATE + 1`
  - 本周：`due_date BETWEEN CURRENT_DATE AND CURRENT_DATE + 7`
  - 以后：`due_date > CURRENT_DATE + 7`
  - 过期：`due_date < CURRENT_DATE AND is_completed = 0`

#### 重要任务视图
- 筛选 `is_important = 1` 且 `is_completed = 0` 的任务

#### 任务完成
- 更新 `is_completed = 1`
- 设置 `completed_at = CURRENT_TIMESTAMP`
- 前端添加完成动画效果

---

## 三、开发规范

### 1. 路径规则

**所有文件引用必须使用相对路径**，因为应用部署在 `todo-list` 子目录下：

```php
// ✅ 正确 - 使用相对路径
require_once './config.php';
$db = new SQLite3('./todo.db');
```

```html
<!-- ✅ 正确 - 使用相对路径 -->
<link rel="stylesheet" href="./css/style.css">
<script src="./js/app.js"></script>
```

```javascript
// ✅ 正确 - API 调用使用相对路径
fetch('./api.php?action=tasks')
```

```php
// ❌ 错误 - 不要使用绝对路径
require_once '/config.php';
$db = new SQLite3('/todo.db');
```

### 2. PHP 编码规范

- 使用 UTF-8 编码
- 错误处理：使用 try-catch 捕获异常
- API 返回：统一 JSON 格式 `{"success": true/false, "data": {}, "message": ""}`
- 数据验证：对所有用户输入进行验证和过滤
- SQL 注入防护：使用预处理语句（Prepared Statements）

### 3. 前端编码规范

- 使用语义化 HTML 标签
- CSS 采用 BEM 命名规范
- JavaScript 使用 ES6+ 语法
- 避免全局变量污染，使用模块化设计
- 异步操作使用 async/await

### 4. 数据安全

- SQLite 数据库文件权限控制（建议 600）
- XSS 防护：输出时转义 HTML 特殊字符
- CSRF 防护：验证请求来源（可选）
- 输入验证：前后端双重验证

---

## 四、快速开始

### 1. 环境要求

- PHP 7.4 或更高版本
- SQLite 3 支持（PHP 默认包含）
- 现代浏览器（Chrome, Firefox, Safari, Edge）

### 2. 安装步骤

#### ⚠️ 重要：首先检查 SQLite3 扩展

在开始之前，请先访问检查页面确认 SQLite3 扩展是否已启用：

```bash
# 启动服务器后，在浏览器中访问：
http://localhost:8000/check_sqlite.php
```

这个页面会告诉你：
- ✅ SQLite3 是否已启用
- 📝 如何启用 SQLite3（如果未启用）
- 🔍 当前 PHP 版本和已加载的扩展

#### Step 1: 克隆或下载项目
```bash
# 将项目放置在 Web 服务器的 todo-list 目录下
# 例如：/var/www/html/todo-list/ 或 C:/xampp/htdocs/todo-list/
```

#### Step 2: 检查并启用 SQLite3 扩展

**方法一：使用检查页面（推荐）**
```bash
# 先启动服务器
php -S localhost:8000

# 然后在浏览器访问：
http://localhost:8000/check_sqlite.php
```

**方法二：命令行检查**
```bash
php -m | grep -i sqlite
# Windows 上使用：
php -m | findstr sqlite
```

如果没有输出，说明需要启用 SQLite3：

1. 找到 php.ini 文件：
   ```bash
   php --ini
   ```

2. 编辑 php.ini，取消以下行的注释（删除前面的分号）：
   ```ini
   extension=sqlite3
   extension=pdo_sqlite
   ```

3. 保存并重启 PHP 服务器

#### Step 3: 初始化数据库
```bash
# 在项目目录下执行
php init_db.php
```

#### Step 4: 启动 PHP 内置服务器（开发环境）
```bash
php -S localhost:8000
```

#### Step 5: 访问应用
```
浏览器打开: http://localhost:8000/
```

### 3. 生产部署

#### Apache 配置
```apache
<Directory /var/www/html/todo-list>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

#### Nginx 配置
```nginx
location /todo-list {
    try_files $uri $uri/ /todo-list/index.html;
}

location ~ /todo-list/.*\.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

#### 文件权限设置
```bash
# 设置数据库文件权限
chmod 600 todo.db
chown www-data:www-data todo.db

# 设置目录权限
chmod 755 .
```

---

## 五、功能清单

### 已实现功能
- [x] 数据库初始化
- [x] API 接口开发
  - [x] 列表 CRUD
  - [x] 任务 CRUD
  - [x] 步骤 CRUD
- [x] 前端界面开发
  - [x] 主布局（侧边栏 + 主内容区）
  - [x] 列表管理
  - [x] 任务列表显示
  - [x] 任务详情面板
  - [x] "我的一天"视图
  - [x] 重要任务视图
  - [x] 计划内视图
- [x] 交互功能
  - [x] 添加/编辑/删除任务
  - [x] 标记完成/重要
  - [x] 添加到"我的一天"
  - [x] 设置截止日期
  - [x] 添加备注
  - [x] 管理步骤
- [ ] 高级功能
  - [ ] 任务排序（拖拽）
  - [ ] 搜索任务
  - [ ] 任务统计
  - [ ] 主题切换（亮色/暗色）

### 未来扩展
- [ ] 提醒通知（浏览器通知 API）
- [ ] 重复任务自动生成
- [ ] 数据导出（JSON/CSV）
- [ ] 数据导入
- [ ] 任务归档
- [ ] 标签功能
- [ ] 全文搜索
- [ ] 键盘快捷键

---

## 六、调试与测试

### 开启开发服务器
```bash
php -S localhost:8000
```

### 重置数据库
```bash
# 删除现有数据库
rm todo.db

# 重新初始化
php init_db.php
```

### 查看数据库内容
```bash
sqlite3 todo.db

# SQLite 命令
.tables          # 查看所有表
.schema tasks    # 查看表结构
SELECT * FROM tasks;  # 查询数据
.quit            # 退出
```

### 常见问题

#### 1. 数据库文件无法创建
- **检查 PHP SQLite3 扩展是否启用**
  ```bash
  php -m | grep -i sqlite
  # 或在 Windows 上：
  php -m | findstr sqlite
  ```
- **启用 SQLite3 扩展**
  - 找到 php.ini 文件：`php --ini`
  - 编辑 php.ini，取消以下行的注释（删除前面的分号）：
    ```ini
    extension=sqlite3
    extension=pdo_sqlite
    ```
  - 保存并重启 PHP 服务器
- 检查目录写权限

#### 2. API 返回 404
- 检查相对路径是否正确
- 确认 Web 服务器配置

#### 3. 中文乱码
- 确保所有文件使用 UTF-8 编码
- 检查数据库连接字符集设置

---

## 七、参考资源

### 设计参考
- [Microsoft To Do 官网](https://todo.microsoft.com/)
- [Fluent Design System](https://www.microsoft.com/design/fluent/)

### 技术文档
- [PHP 官方文档](https://www.php.net/manual/zh/)
- [SQLite 文档](https://www.sqlite.org/docs.html)
- [MDN Web Docs](https://developer.mozilla.org/zh-CN/)

### 开源项目
- [Microsoft To Do 官方开源](https://github.com/microsoft/todo)
- [TodoMVC](https://todomvc.com/)

---

## 八、贡献与反馈

本项目为个人学习项目，欢迎提出改进建议。

---

**项目状态**: ✅ 已完成核心功能  
**最后更新**: 2025-12-19  
**版本**: v1.0.0

---

## 九、项目文件说明

### 后端文件
- **init_db.php** - 数据库初始化脚本，创建表结构和默认数据
- **config.php** - 配置文件，包含数据库路径和工具函数
- **api.php** - RESTful API 接口，处理所有数据操作
- **todo.db** - SQLite 数据库文件（运行 init_db.php 后自动创建）

### 前端文件
- **index.html** - 主页面，应用的 HTML 结构
- **css/style.css** - 样式文件，Microsoft To Do 风格设计
- **js/api.js** - API 封装，处理与后端的通信
- **js/app.js** - 应用逻辑，处理所有 UI 交互和业务逻辑

### 文档
- **readme.md** - 项目文档（本文件）
- **info.php** - PHP 配置信息查看（可选）

---

## 十、功能演示

### 核心功能

1. **我的一天** - 今日待办任务集中展示
2. **重要任务** - 标记为重要的任务单独展示
3. **计划内任务** - 按截止日期分组（过期、今天、明天、本周、以后）
4. **自定义列表** - 创建个人分类列表
5. **任务详情** - 完整的任务信息管理
   - 修改标题
   - 设置截止日期
   - 添加备注
   - 管理步骤（子任务）
   - 标记完成/重要
   - 添加到"我的一天"
6. **实时更新** - 所有操作立即反映在界面上

### 使用技巧

- **快速添加任务**：点击"添加任务"按钮或按 Enter 键
- **查看任务详情**：点击任意任务即可查看详细信息
- **标记重要**：点击任务右侧的星标图标
- **完成任务**：点击任务左侧的圆形复选框
- **创建列表**：点击侧边栏"我的列表"旁的 + 按钮

---

## 十一、注意事项

⚠️ **重要提示**

1. **首次使用必须先运行数据库初始化**
   ```bash
   php init_db.php
   ```

2. **确保 PHP SQLite3 扩展已启用**（见常见问题部分）

3. **数据库文件安全**
   - todo.db 包含所有数据，请定期备份
   - 建议设置适当的文件权限（600）

4. **浏览器兼容性**
   - 推荐使用 Chrome、Firefox、Safari 或 Edge 最新版本
   - 需要支持 ES6+ 和 Fetch API

5. **相对路径部署**
   - 本应用设计为部署在 `todo-list` 子目录
   - 所有文件引用均使用相对路径
   - 如需部署到其他位置，确保保持相对路径结构
