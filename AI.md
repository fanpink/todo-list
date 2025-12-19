# 增加一个大模型功能
## 一、页面中左侧栏下方增加一个大模型管理按钮，并实现以下功能：
### 1.点击按钮，在页面中可以管理大模型，增删配置大模型的"类型"、"URL"、"模型名称"、"api"
### 2.增加时包含 ollama 、deepseek 等常用大模型

## 二、页面中左侧栏下方增加一个智能导入按钮，并实现以下功能：
### 1.点击按钮，在页面中有一个可以通过粘贴文本的文本框，有一个识别按钮，点击后，将文本内容提交给选中大模型（大模型选择按钮），大模型识别任务，返回一个或多个任务
### 2.大模型返回的任务应该包括 任务名称，按照任务列表"我的列表"中的子列表名称识别任务类别（子列表名称），完成时限，任务的步骤
### 3.大模型返回的结果显示在待确认区域（可以修改），用户修改后 ，点击确认自动将任务提交到"我的列表"中的子列表名称识别任务类别（子列表名称）中

---

# 详细实现规划

## 阶段一：数据库设计

### 1.1 创建大模型配置表
```sql
CREATE TABLE IF NOT EXISTS ai_models (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,           -- 模型显示名称（如"本地Ollama"）
    type VARCHAR(50) NOT NULL,            -- 模型类型（ollama, deepseek, openai, custom）
    api_url TEXT NOT NULL,                -- API地址
    model_name VARCHAR(100) NOT NULL,     -- 模型名称（如qwen2.5, deepseek-chat）
    api_key TEXT,                         -- API密钥（可选，ollama不需要）
    is_active INTEGER DEFAULT 0,          -- 是否为当前激活模型（0或1）
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 1.2 预设常用模型配置
插入默认配置：
- Ollama本地模型（http://localhost:11434）
- DeepSeek API (https://api.deepseek.com)
- OpenAI兼容接口

---

## 阶段二：后端API开发

### 2.1 大模型管理API (api.php)

**接口列表：**

#### 2.1.1 获取所有模型配置
```
GET api.php?action=ai_models
返回：
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "本地Ollama",
            "type": "ollama",
            "api_url": "http://localhost:11434",
            "model_name": "qwen2.5",
            "api_key": null,
            "is_active": 1
        }
    ]
}
```

#### 2.1.2 创建模型配置
```
POST api.php?action=create_ai_model
Body: {
    "name": "DeepSeek",
    "type": "deepseek",
    "api_url": "https://api.deepseek.com",
    "model_name": "deepseek-chat",
    "api_key": "sk-xxx"
}
```

#### 2.1.3 更新模型配置
```
PUT api.php?action=update_ai_model&id=1
Body: { "name": "新名称", "api_key": "新密钥" }
```

#### 2.1.4 删除模型配置
```
DELETE api.php?action=delete_ai_model&id=1
```

#### 2.1.5 设置激活模型
```
POST api.php?action=set_active_ai_model
Body: { "id": 1 }
```

### 2.2 AI任务识别API

#### 2.2.1 调用AI识别任务
```
POST api.php?action=ai_parse_tasks
Body: {
    "text": "用户输入的文本",
    "model_id": 1  // 可选，不传则使用激活的模型
}

返回：
{
    "success": true,
    "data": [
        {
            "title": "完成项目报告",
            "list_name": "工作",
            "due_date": "2025-12-25",
            "steps": [
                "收集数据",
                "整理分析",
                "撰写报告",
                "审核修改"
            ]
        },
        {
            "title": "购买圣诞礼物",
            "list_name": "购物",
            "due_date": "2025-12-24",
            "steps": []
        }
    ]
}
```

### 2.3 PHP实现调用大模型

**支持的模型类型和调用方法：**

#### Ollama调用示例
```php
function callOllama($apiUrl, $modelName, $prompt) {
    $url = rtrim($apiUrl, '/') . '/api/generate';
    $data = [
        'model' => $modelName,
        'prompt' => $prompt,
        'stream' => false,
        'format' => 'json'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
```

#### DeepSeek/OpenAI调用示例
```php
function callOpenAICompatible($apiUrl, $modelName, $apiKey, $prompt) {
    $url = rtrim($apiUrl, '/') . '/v1/chat/completions';
    $data = [
        'model' => $modelName,
        'messages' => [
            ['role' => 'system', 'content' => '你是一个任务识别助手'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'response_format' => ['type' => 'json_object']
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
```

---

## 阶段三：前端UI开发

### 3.1 左侧栏新增按钮

在 `index.html` 的侧边栏底部添加：
```html
<div class="sidebar-footer">
    <button class="nav-item" id="btn-ai-models">
        <svg class="icon"><!-- 设置图标 --></svg>
        <span>AI模型管理</span>
    </button>
    <button class="nav-item" id="btn-ai-import">
        <svg class="icon"><!-- 魔法棒图标 --></svg>
        <span>智能导入</span>
    </button>
</div>
```

### 3.2 大模型管理弹窗

**弹窗结构 (index.html)：**
```html
<div class="modal" id="ai-models-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>AI模型管理</h2>
            <button class="btn-close">×</button>
        </div>
        <div class="modal-body">
            <button class="btn-primary" id="btn-add-model">+ 添加模型</button>
            <table class="models-table">
                <thead>
                    <tr>
                        <th>名称</th>
                        <th>类型</th>
                        <th>API地址</th>
                        <th>模型</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="models-list">
                    <!-- 动态生成 -->
                </tbody>
            </table>
        </div>
    </div>
</div>
```

**添加/编辑模型表单：**
```html
<div class="modal" id="ai-model-form-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>配置AI模型</h2>
        </div>
        <div class="modal-body">
            <form id="ai-model-form">
                <div class="form-group">
                    <label>模型名称</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>模型类型</label>
                    <select name="type" required>
                        <option value="ollama">Ollama (本地)</option>
                        <option value="deepseek">DeepSeek</option>
                        <option value="openai">OpenAI</option>
                        <option value="custom">自定义</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>API地址</label>
                    <input type="url" name="api_url" required>
                    <small>例如：http://localhost:11434 或 https://api.deepseek.com</small>
                </div>
                <div class="form-group">
                    <label>模型名称</label>
                    <input type="text" name="model_name" required>
                    <small>例如：qwen2.5, deepseek-chat, gpt-4</small>
                </div>
                <div class="form-group">
                    <label>API密钥 (可选)</label>
                    <input type="password" name="api_key">
                    <small>Ollama本地模型无需填写</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">保存</button>
                    <button type="button" class="btn-cancel">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

### 3.3 智能导入弹窗

```html
<div class="modal" id="ai-import-modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>智能导入任务</h2>
            <button class="btn-close">×</button>
        </div>
        <div class="modal-body">
            <!-- 步骤1：输入文本 -->
            <div class="import-step" id="import-step-1">
                <div class="form-group">
                    <label>选择AI模型</label>
                    <select id="ai-model-select">
                        <!-- 动态加载激活的模型 -->
                    </select>
                </div>
                <div class="form-group">
                    <label>粘贴任务描述文本</label>
                    <textarea id="ai-input-text" rows="8" placeholder="例如：\n明天下午3点前完成项目报告，需要先收集数据、分析结果、撰写文档\n周末去超市买圣诞礼物\n下周一提交年度总结"></textarea>
                </div>
                <button class="btn-primary" id="btn-ai-parse">🤖 识别任务</button>
            </div>
            
            <!-- 步骤2：确认任务 -->
            <div class="import-step" id="import-step-2" style="display: none;">
                <div class="import-preview">
                    <h3>识别到以下任务，请确认或修改：</h3>
                    <div id="ai-tasks-preview">
                        <!-- 动态生成任务卡片 -->
                    </div>
                    <div class="form-actions">
                        <button class="btn-primary" id="btn-confirm-import">✓ 确认导入</button>
                        <button class="btn-secondary" id="btn-back-to-input">← 重新输入</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

**任务预览卡片模板：**
```html
<div class="ai-task-card" data-index="0">
    <div class="task-card-header">
        <input type="text" class="task-title-input" value="任务标题">
        <button class="btn-remove-task">×</button>
    </div>
    <div class="task-card-body">
        <div class="form-group">
            <label>分配到列表</label>
            <select class="task-list-select">
                <option value="1">任务</option>
                <option value="2">工作</option>
                <option value="3">学习</option>
            </select>
        </div>
        <div class="form-group">
            <label>完成时限</label>
            <input type="date" class="task-due-date" value="2025-12-25">
        </div>
        <div class="form-group">
            <label>任务步骤</label>
            <div class="task-steps-list">
                <div class="step-item">
                    <input type="text" value="步骤1">
                    <button class="btn-remove-step">×</button>
                </div>
            </div>
            <button class="btn-add-step">+ 添加步骤</button>
        </div>
    </div>
</div>
```

### 3.4 JavaScript逻辑 (app.js)

**新增模块：**
```javascript
const AIManager = {
    // 加载模型列表
    async loadModels() { ... },
    
    // 显示模型管理弹窗
    showModelsModal() { ... },
    
    // 添加/编辑模型
    async saveModel(data) { ... },
    
    // 删除模型
    async deleteModel(id) { ... },
    
    // 设置激活模型
    async setActiveModel(id) { ... },
    
    // 显示智能导入弹窗
    showImportModal() { ... },
    
    // 调用AI解析任务
    async parseTasks(text, modelId) { ... },
    
    // 显示任务预览
    renderTasksPreview(tasks) { ... },
    
    // 确认导入任务
    async confirmImport(tasks) { ... }
};
```

---

## 阶段四：AI提示词设计

### 4.1 核心提示词模板

**系统提示词：**
```
你是一个智能任务识别助手。你的任务是：
1. 从用户输入的自然语言文本中识别出任务
2. 为每个任务提取关键信息：任务名称、类别、截止日期、子步骤
3. 必须以JSON格式返回结果

当前可用的任务列表类别：{{available_lists}}

输出格式要求：
{
  "tasks": [
    {
      "title": "任务标题（简短明确）",
      "list_name": "从可用列表中选择最合适的，如果都不合适则使用'任务'",
      "due_date": "YYYY-MM-DD格式，如果文本中没有明确日期则为null",
      "steps": ["步骤1", "步骤2", ...] // 如果文本中有明确步骤则提取，否则为空数组
    }
  ]
}

注意事项：
- 任务标题要简洁，不超过50个字
- 日期必须是有效的YYYY-MM-DD格式
- 如果文本中包含"今天"、"明天"、"下周"等相对时间，请转换为具体日期（今天是{{current_date}}）
- 每个任务的steps数组不超过10个步骤
- 如果一段文本包含多个任务，请分别识别
- 必须返回有效的JSON，不要包含任何其他文字
```

### 4.2 用户提示词模板

```
请从以下文本中识别任务：

"""
{{user_input}}
"""

当前日期：{{current_date}}
可用的任务列表：{{available_lists_names}}

请识别文本中的所有任务，并按JSON格式返回。
```

### 4.3 提示词变量替换

在调用AI前，需要替换的变量：
- `{{available_lists}}` - 从数据库查询用户的自定义列表
- `{{available_lists_names}}` - 列表名称的逗号分隔字符串
- `{{current_date}}` - 当前日期，格式：2025-12-19
- `{{user_input}}` - 用户输入的文本

**PHP实现示例：**
```php
function buildPrompt($userInput) {
    global $db;
    
    // 获取用户的自定义列表
    $lists = $db->query("SELECT name FROM lists WHERE is_system = 0")->fetchAll();
    $listNames = array_column($lists, 'name');
    
    $availableLists = implode('、', $listNames);
    $currentDate = date('Y-m-d');
    
    $systemPrompt = str_replace(
        ['{{available_lists}}', '{{current_date}}'],
        [$availableLists, $currentDate],
        SYSTEM_PROMPT_TEMPLATE
    );
    
    $userPrompt = str_replace(
        ['{{user_input}}', '{{current_date}}', '{{available_lists_names}}'],
        [$userInput, $currentDate, $availableLists],
        USER_PROMPT_TEMPLATE
    );
    
    return [$systemPrompt, $userPrompt];
}
```

---

## 阶段五：测试方案

### 5.1 单元测试用例

#### 测试用例1：简单单任务识别
**输入文本：**
```
明天下午3点前完成项目报告
```

**期望输出：**
```json
{
  "tasks": [
    {
      "title": "完成项目报告",
      "list_name": "工作",
      "due_date": "2025-12-20",
      "steps": []
    }
  ]
}
```

#### 测试用例2：多任务识别
**输入文本：**
```
本周需要完成以下事项：
1. 提交年度总结报告（周五前）
2. 购买圣诞礼物
3. 整理办公桌
```

**期望输出：**
```json
{
  "tasks": [
    {
      "title": "提交年度总结报告",
      "list_name": "工作",
      "due_date": "2025-12-20",
      "steps": []
    },
    {
      "title": "购买圣诞礼物",
      "list_name": "购物",
      "due_date": null,
      "steps": []
    },
    {
      "title": "整理办公桌",
      "list_name": "任务",
      "due_date": null,
      "steps": []
    }
  ]
}
```

#### 测试用例3：带步骤的任务
**输入文本：**
```
准备下周的演讲，需要：
1. 确定主题
2. 收集资料
3. 制作PPT
4. 排练演讲
```

**期望输出：**
```json
{
  "tasks": [
    {
      "title": "准备演讲",
      "list_name": "工作",
      "due_date": "2025-12-26",
      "steps": [
        "确定主题",
        "收集资料",
        "制作PPT",
        "排练演讲"
      ]
    }
  ]
}
```

#### 测试用例4：复杂场景
**输入文本：**
```
项目计划：
需要在12月25日前完成新功能开发，包括前端界面设计、后端API开发、数据库设计和测试。
另外，别忘了周末去超市买菜，需要买蔬菜、水果、肉类。
还要记得给妈妈打电话。
```

**期望输出：**
```json
{
  "tasks": [
    {
      "title": "完成新功能开发",
      "list_name": "工作",
      "due_date": "2025-12-25",
      "steps": [
        "前端界面设计",
        "后端API开发",
        "数据库设计",
        "测试"
      ]
    },
    {
      "title": "超市买菜",
      "list_name": "购物",
      "due_date": "2025-12-21",
      "steps": [
        "买蔬菜",
        "买水果",
        "买肉类"
      ]
    },
    {
      "title": "给妈妈打电话",
      "list_name": "任务",
      "due_date": null,
      "steps": []
    }
  ]
}
```

### 5.2 边界测试用例

#### 测试用例5：空输入
**输入：** `""`
**期望：** `{"tasks": []}`

#### 测试用例6：无效日期
**输入：** `"2月30日前完成报告"`
**期望：** 返回null或最近的有效日期

#### 测试用例7：超长文本
**输入：** 5000字的长文本
**期望：** 能够识别其中的所有任务（测试性能）

### 5.3 集成测试流程

**测试步骤：**

1. **测试模型配置**
   - [ ] 添加Ollama模型配置
   - [ ] 添加DeepSeek模型配置
   - [ ] 编辑模型配置
   - [ ] 删除模型配置
   - [ ] 切换激活模型

2. **测试智能导入 - Ollama**
   - [ ] 打开智能导入弹窗
   - [ ] 选择Ollama模型
   - [ ] 输入测试用例1的文本
   - [ ] 点击识别，验证返回结果
   - [ ] 修改识别结果（标题、列表、日期）
   - [ ] 添加/删除步骤
   - [ ] 确认导入，验证任务创建成功

3. **测试智能导入 - DeepSeek**
   - [ ] 切换到DeepSeek模型
   - [ ] 输入测试用例4的复杂文本
   - [ ] 验证识别准确性
   - [ ] 验证日期转换（相对时间→绝对日期）
   - [ ] 确认导入所有任务

4. **测试错误处理**
   - [ ] API密钥错误时的提示
   - [ ] 网络连接失败的提示
   - [ ] 模型返回非JSON格式的处理
   - [ ] 模型服务不可用的提示

### 5.4 性能测试

**测试指标：**
- AI响应时间：< 5秒（Ollama本地）
- AI响应时间：< 10秒（在线API）
- 单次识别任务数量：≤ 20个
- 单个任务步骤数量：≤ 10个

---

## 阶段六：优化提示词策略

### 6.1 提示词优化技巧

**1. 使用Few-Shot示例**

在系统提示词中添加示例：
```
示例1：
输入："明天下午3点开会"
输出：{"tasks": [{"title": "开会", "list_name": "工作", "due_date": "2025-12-20", "steps": []}]}

示例2：
输入："准备演讲：确定主题、制作PPT、排练"
输出：{"tasks": [{"title": "准备演讲", "list_name": "工作", "due_date": null, "steps": ["确定主题", "制作PPT", "排练"]}]}
```

**2. 明确约束条件**
```
严格约束：
- title字段：长度1-50字符，不包含特殊符号
- list_name字段：必须从提供的列表中选择，区分大小写
- due_date字段：必须是YYYY-MM-DD格式或null，不能是其他格式
- steps数组：每个元素长度1-100字符，数组长度0-10
```

**3. 时间解析规则**
```
时间关键词映射（当前日期：{{current_date}}）：
- "今天"、"今日" → {{current_date}}
- "明天"、"明日" → {{tomorrow}}
- "后天" → {{day_after_tomorrow}}
- "本周五"、"这周五" → {{this_friday}}
- "下周一" → {{next_monday}}
- "周末" → {{this_saturday}}
- "月底" → {{end_of_month}}
```

### 6.2 列表名称匹配优化

**模糊匹配规则：**
```
任务关键词 → 列表名称映射：

工作相关：
- 关键词：报告、会议、项目、开发、设计、测试、文档、邮件
- 映射到："工作"

学习相关：
- 关键词：学习、课程、作业、考试、阅读、笔记、复习
- 映射到："学习"

购物相关：
- 关键词：购买、买、超市、商场、网购、订购
- 映射到："购物"

健康相关：
- 关键词：锻炼、运动、健身、跑步、瑜伽、体检
- 映射到："健康"

如果没有匹配的关键词，使用"任务"作为默认列表。
```

### 6.3 错误处理和重试

**AI返回格式错误时的处理：**

```javascript
async function parseTasksWithRetry(text, modelId, maxRetries = 2) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            const response = await API.aiParseTasks({ text, model_id: modelId });
            
            // 验证返回格式
            if (!response.data || !Array.isArray(response.data)) {
                throw new Error('返回格式错误');
            }
            
            // 验证每个任务的字段
            for (const task of response.data) {
                if (!task.title || typeof task.title !== 'string') {
                    throw new Error('任务标题无效');
                }
                if (task.due_date && !/^\d{4}-\d{2}-\d{2}$/.test(task.due_date)) {
                    task.due_date = null; // 修正无效日期
                }
                if (!Array.isArray(task.steps)) {
                    task.steps = [];
                }
            }
            
            return response.data;
            
        } catch (error) {
            if (i === maxRetries - 1) {
                throw error;
            }
            // 等待后重试
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
    }
}
```

### 6.4 提示词A/B测试

准备2-3个不同版本的提示词，测试哪个效果最好：

**版本A：简洁型**
```
从文本中识别任务，返回JSON：
{"tasks": [{"title": "", "list_name": "", "due_date": "", "steps": []}]}
```

**版本B：详细型**
```
（当前使用的完整版本）
```

**版本C：示例驱动型**
```
基于以下示例识别任务：
示例1: ... 示例2: ... 示例3: ...
现在识别：{{user_input}}
```

**评估指标：**
- 识别准确率（任务数量正确）
- 分类准确率（list_name匹配）
- 日期解析准确率
- 步骤提取完整性

---

## 阶段七：用户体验优化

### 7.1 加载状态提示

```javascript
// 显示AI识别中的加载动画
function showAILoading() {
    const loading = document.createElement('div');
    loading.className = 'ai-loading';
    loading.innerHTML = `
        <div class="loading-spinner"></div>
        <p>AI正在识别任务...</p>
        <small>这可能需要几秒钟</small>
    `;
    document.getElementById('ai-import-modal').appendChild(loading);
}
```

### 7.2 错误提示优化

**错误类型和提示：**

| 错误场景 | 用户提示 | 建议操作 |
|---------|---------|--------|
| API密钥无效 | "AI模型认证失败，请检查API密钥" | 打开模型管理，重新配置 |
| 网络超时 | "网络连接超时，请检查网络或稍后重试" | 重试按钮 |
| 模型服务不可用 | "Ollama服务未启动，请先启动Ollama" | 显示启动指南链接 |
| 返回格式错误 | "AI返回数据格式异常，请重试" | 切换到其他模型 |
| 无任务识别 | "未能从文本中识别出任务，请尝试更明确的描述" | 显示示例文本 |

### 7.3 快捷操作

**预设模板：**
```javascript
const quickTemplates = [
    {
        name: '工作周报',
        text: '本周完成：项目进度报告、团队会议、代码审查\n下周计划：新功能开发、bug修复'
    },
    {
        name: '周末计划',
        text: '周六：早上跑步、下午购物、晚上看电影\n周日：学习新技能、整理房间'
    },
    {
        name: '学习计划',
        text: '阅读《XXX》书籍第1-3章\n完成在线课程第5讲\n整理笔记并复习'
    }
];
```

### 7.4 历史记录

保存最近使用的AI识别记录，方便用户查看和复用：
```sql
CREATE TABLE ai_parse_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    input_text TEXT NOT NULL,
    output_json TEXT NOT NULL,
    model_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 阶段八：安全性考虑

### 8.1 API密钥加密存储

```php
// 加密API密钥
function encryptApiKey($apiKey) {
    $key = hash('sha256', SECRET_KEY); // 在config.php中定义
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($apiKey, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

// 解密API密钥
function decryptApiKey($encryptedData) {
    $key = hash('sha256', SECRET_KEY);
    list($encrypted, $iv) = explode('::', base64_decode($encryptedData), 2);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}
```

### 8.2 输入验证

```php
function validateAIInput($text) {
    // 长度限制
    if (strlen($text) > 10000) {
        throw new Exception('输入文本过长，最多10000字符');
    }
    
    // XSS防护
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // SQL注入防护（使用PDO预处理）
    return $text;
}
```

### 8.3 速率限制

```php
// 防止滥用AI接口
function checkRateLimit($userId) {
    $key = "ai_rate_limit_{$userId}";
    $count = apcu_fetch($key);
    
    if ($count >= 20) { // 每小时最多20次
        throw new Exception('请求过于频繁，请稍后再试');
    }
    
    apcu_store($key, ($count ?: 0) + 1, 3600);
}
```

---

## 实施时间表

**总预计时间：2-3天**

### Day 1：基础架构
- [ ] 数据库表设计和创建（30分钟）
- [ ] 后端API开发（3小时）
  - AI模型CRUD
  - AI调用接口
- [ ] 测试后端API（1小时）

### Day 2：前端UI
- [ ] HTML结构（2小时）
  - 模型管理弹窗
  - 智能导入弹窗
- [ ] CSS样式（2小时）
- [ ] JavaScript逻辑（3小时）
  - 模型管理
  - AI调用
  - 任务预览和编辑

### Day 3：测试和优化
- [ ] 提示词测试和优化（2小时）
- [ ] 功能测试（2小时）
- [ ] Bug修复（2小时）
- [ ] 文档编写（1小时）

---

## 附录：Ollama本地部署指南

### Windows安装Ollama

```bash
# 1. 下载安装器
https://ollama.com/download/windows

# 2. 安装后，在命令行运行
ollama serve

# 3. 下载推荐模型
ollama pull qwen2.5:7b
# 或
ollama pull llama3.2:3b

# 4. 测试模型
ollama run qwen2.5:7b
```

### 测试Ollama是否正常运行

```bash
curl http://localhost:11434/api/generate -d '{
  "model": "qwen2.5",
  "prompt": "你好",
  "stream": false
}'
```

### DeepSeek API申请

1. 访问：https://platform.deepseek.com
2. 注册账号
3. 创建API密钥
4. 复制密钥：`sk-xxxxxxxxxxxxx`
5. 在应用中配置

---

## 总结

**核心要点：**

1. ✅ **提示词设计最关键**
   - 使用明确的JSON格式要求
   - 提供Few-Shot示例
   - 包含时间解析规则
   - 定义列表匹配逻辑

2. ✅ **测试用例覆盖全面**
   - 简单任务、复杂任务
   - 单任务、多任务
   - 有步骤、无步骤
   - 边界情况

3. ✅ **用户体验流畅**
   - 清晰的步骤引导
   - 可编辑的预览界面
   - 友好的错误提示
   - 加载状态反馈

4. ✅ **安全性保障**
   - API密钥加密存储
   - 输入验证和过滤
   - 速率限制防滥用

**成功标准：**
- AI识别准确率 > 85%
- 用户可在30秒内完成导入
- 支持Ollama和DeepSeek两种模型
- 界面操作流畅，无明显卡顿

