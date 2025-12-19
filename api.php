<?php
/**
 * RESTful API 接口
 * 处理列表、任务、步骤的 CRUD 操作
 */

require_once './config.php';

$method = getMethod();
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

$db = getDB();

// 路由处理
switch ($action) {
    // ========== 列表相关 ==========
    case 'lists':
        handleLists($db, $method, $id);
        break;
    
    // ========== 任务相关 ==========
    case 'tasks':
        handleTasks($db, $method, $id);
        break;
    
    // ========== 步骤相关 ==========
    case 'steps':
        handleSteps($db, $method, $id);
        break;
    
    // ========== AI模型管理 ==========
    case 'ai_models':
        handleAIModels($db, $method, $id);
        break;
    
    case 'set_active_ai_model':
        handleSetActiveAIModel($db, $method);
        break;
    
    // ========== AI任务识别 ==========
    case 'ai_parse_tasks':
        handleAIParseTasks($db, $method);
        break;
    
    default:
        error('无效的操作', 404);
}

/**
 * 处理列表相关操作
 */
function handleLists($db, $method, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // 获取单个列表
                $stmt = $db->prepare('SELECT * FROM lists WHERE id = :id');
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $list = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($list) {
                    success($list);
                } else {
                    error('列表不存在', 404);
                }
            } else {
                // 获取所有列表
                $result = $db->query('SELECT * FROM lists ORDER BY sort_order, id');
                $lists = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    // 获取列表中的任务数量
                    $countStmt = $db->prepare('SELECT COUNT(*) as count FROM tasks WHERE list_id = :list_id AND is_completed = 0');
                    $countStmt->bindValue(':list_id', $row['id'], SQLITE3_INTEGER);
                    $countResult = $countStmt->execute();
                    $countRow = $countResult->fetchArray(SQLITE3_ASSOC);
                    $row['task_count'] = $countRow['count'];
                    
                    $lists[] = $row;
                }
                success($lists);
            }
            break;
        
        case 'POST':
            $input = getInput();
            validateRequired($input, ['name']);
            
            $stmt = $db->prepare('INSERT INTO lists (name, icon, sort_order) VALUES (:name, :icon, :sort_order)');
            $stmt->bindValue(':name', $input['name'], SQLITE3_TEXT);
            $stmt->bindValue(':icon', $input['icon'] ?? 'list', SQLITE3_TEXT);
            $stmt->bindValue(':sort_order', $input['sort_order'] ?? 0, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $newId = $db->lastInsertRowID();
                success(['id' => $newId], '列表创建成功');
            } else {
                error('列表创建失败');
            }
            break;
        
        case 'PUT':
            if (!$id) error('缺少列表 ID');
            
            $input = getInput();
            
            $updates = [];
            $params = [];
            
            if (isset($input['name'])) {
                $updates[] = 'name = :name';
                $params[':name'] = $input['name'];
            }
            if (isset($input['icon'])) {
                $updates[] = 'icon = :icon';
                $params[':icon'] = $input['icon'];
            }
            if (isset($input['sort_order'])) {
                $updates[] = 'sort_order = :sort_order';
                $params[':sort_order'] = $input['sort_order'];
            }
            
            $updates[] = 'updated_at = CURRENT_TIMESTAMP';
            
            if (empty($updates)) error('没有可更新的字段');
            
            $sql = 'UPDATE lists SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if ($stmt->execute()) {
                success(null, '列表更新成功');
            } else {
                error('列表更新失败');
            }
            break;
        
        case 'DELETE':
            if (!$id) error('缺少列表 ID');
            
            // 检查是否是系统列表
            $stmt = $db->prepare('SELECT is_system FROM lists WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $list = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$list) error('列表不存在', 404);
            if ($list['is_system']) error('系统列表不能删除');
            
            $stmt = $db->prepare('DELETE FROM lists WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                success(null, '列表删除成功');
            } else {
                error('列表删除失败');
            }
            break;
        
        default:
            error('不支持的请求方法', 405);
    }
}

/**
 * 处理任务相关操作
 */
function handleTasks($db, $method, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // 获取单个任务（包含步骤）
                $stmt = $db->prepare('SELECT * FROM tasks WHERE id = :id');
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $task = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($task) {
                    // 获取任务的步骤
                    $stepsStmt = $db->prepare('SELECT * FROM steps WHERE task_id = :task_id ORDER BY sort_order, id');
                    $stepsStmt->bindValue(':task_id', $id, SQLITE3_INTEGER);
                    $stepsResult = $stepsStmt->execute();
                    $steps = [];
                    while ($step = $stepsResult->fetchArray(SQLITE3_ASSOC)) {
                        $steps[] = $step;
                    }
                    $task['steps'] = $steps;
                    
                    success($task);
                } else {
                    error('任务不存在', 404);
                }
            } else {
                // 根据不同视图获取任务
                $view = $_GET['view'] ?? '';
                $listId = $_GET['list_id'] ?? null;
                
                $sql = 'SELECT * FROM tasks WHERE 1=1';
                $conditions = [];
                
                if ($view === 'my-day') {
                    // 我的一天：is_my_day = 1 或今天截止
                    $sql .= ' AND (is_my_day = 1 OR date(due_date) = date("now"))';
                } elseif ($view === 'important') {
                    // 重要任务
                    $sql .= ' AND is_important = 1 AND is_completed = 0';
                } elseif ($view === 'planned') {
                    // 计划内：有截止日期的任务
                    $sql .= ' AND due_date IS NOT NULL';
                } elseif ($view === 'completed') {
                    // 已完成：所有已完成的任务
                    $sql .= ' AND is_completed = 1';
                } elseif ($listId) {
                    // 特定列表的任务
                    $sql .= ' AND list_id = ' . (int)$listId;
                }
                
                $sql .= ' ORDER BY is_completed ASC, sort_order ASC, id DESC';
                
                $result = $db->query($sql);
                $tasks = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    // 获取步骤数量
                    $stepsStmt = $db->prepare('SELECT COUNT(*) as total, SUM(is_completed) as completed FROM steps WHERE task_id = :task_id');
                    $stepsStmt->bindValue(':task_id', $row['id'], SQLITE3_INTEGER);
                    $stepsResult = $stepsStmt->execute();
                    $stepsCount = $stepsResult->fetchArray(SQLITE3_ASSOC);
                    $row['steps_total'] = $stepsCount['total'] ?? 0;
                    $row['steps_completed'] = $stepsCount['completed'] ?? 0;
                    
                    $tasks[] = $row;
                }
                
                // 如果是计划内视图，按日期分组
                if ($view === 'planned') {
                    $grouped = [
                        'overdue' => [],
                        'today' => [],
                        'tomorrow' => [],
                        'this_week' => [],
                        'later' => []
                    ];
                    
                    $today = date('Y-m-d');
                    $tomorrow = date('Y-m-d', strtotime('+1 day'));
                    $weekEnd = date('Y-m-d', strtotime('+7 days'));
                    
                    foreach ($tasks as $task) {
                        if ($task['is_completed']) continue;
                        
                        $dueDate = $task['due_date'];
                        if ($dueDate < $today) {
                            $grouped['overdue'][] = $task;
                        } elseif ($dueDate === $today) {
                            $grouped['today'][] = $task;
                        } elseif ($dueDate === $tomorrow) {
                            $grouped['tomorrow'][] = $task;
                        } elseif ($dueDate <= $weekEnd) {
                            $grouped['this_week'][] = $task;
                        } else {
                            $grouped['later'][] = $task;
                        }
                    }
                    
                    success($grouped);
                } else {
                    success($tasks);
                }
            }
            break;
        
        case 'POST':
            $input = getInput();
            validateRequired($input, ['list_id', 'title']);
            
            $stmt = $db->prepare('
                INSERT INTO tasks (list_id, title, note, is_important, is_my_day, due_date, reminder_time, repeat_rule, sort_order) 
                VALUES (:list_id, :title, :note, :is_important, :is_my_day, :due_date, :reminder_time, :repeat_rule, :sort_order)
            ');
            
            $stmt->bindValue(':list_id', $input['list_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':title', $input['title'], SQLITE3_TEXT);
            $stmt->bindValue(':note', $input['note'] ?? null, SQLITE3_TEXT);
            $stmt->bindValue(':is_important', $input['is_important'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(':is_my_day', $input['is_my_day'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(':due_date', $input['due_date'] ?? null, SQLITE3_TEXT);
            $stmt->bindValue(':reminder_time', $input['reminder_time'] ?? null, SQLITE3_TEXT);
            $stmt->bindValue(':repeat_rule', $input['repeat_rule'] ?? null, SQLITE3_TEXT);
            $stmt->bindValue(':sort_order', $input['sort_order'] ?? 0, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $newId = $db->lastInsertRowID();
                success(['id' => $newId], '任务创建成功');
            } else {
                error('任务创建失败');
            }
            break;
        
        case 'PUT':
            if (!$id) error('缺少任务 ID');
            
            $input = getInput();
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['title', 'note', 'is_completed', 'is_important', 'is_my_day', 'due_date', 'reminder_time', 'repeat_rule', 'sort_order', 'list_id'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $input[$field];
                }
            }
            
            // 如果标记为完成，设置完成时间
            if (isset($input['is_completed']) && $input['is_completed'] == 1) {
                $updates[] = 'completed_at = CURRENT_TIMESTAMP';
            } elseif (isset($input['is_completed']) && $input['is_completed'] == 0) {
                $updates[] = 'completed_at = NULL';
            }
            
            $updates[] = 'updated_at = CURRENT_TIMESTAMP';
            
            if (empty($updates)) error('没有可更新的字段');
            
            $sql = 'UPDATE tasks SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if ($stmt->execute()) {
                success(null, '任务更新成功');
            } else {
                error('任务更新失败');
            }
            break;
        
        case 'DELETE':
            if (!$id) error('缺少任务 ID');
            
            $stmt = $db->prepare('DELETE FROM tasks WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                success(null, '任务删除成功');
            } else {
                error('任务删除失败');
            }
            break;
        
        default:
            error('不支持的请求方法', 405);
    }
}

/**
 * 处理步骤相关操作
 */
function handleSteps($db, $method, $id) {
    switch ($method) {
        case 'GET':
            $taskId = $_GET['task_id'] ?? null;
            
            if (!$taskId) error('缺少任务 ID');
            
            $stmt = $db->prepare('SELECT * FROM steps WHERE task_id = :task_id ORDER BY sort_order, id');
            $stmt->bindValue(':task_id', $taskId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            $steps = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $steps[] = $row;
            }
            
            success($steps);
            break;
        
        case 'POST':
            $input = getInput();
            validateRequired($input, ['task_id', 'title']);
            
            $stmt = $db->prepare('INSERT INTO steps (task_id, title, is_completed, sort_order) VALUES (:task_id, :title, :is_completed, :sort_order)');
            $stmt->bindValue(':task_id', $input['task_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':title', $input['title'], SQLITE3_TEXT);
            $stmt->bindValue(':is_completed', $input['is_completed'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(':sort_order', $input['sort_order'] ?? 0, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $newId = $db->lastInsertRowID();
                success(['id' => $newId], '步骤创建成功');
            } else {
                error('步骤创建失败');
            }
            break;
        
        case 'PUT':
            if (!$id) error('缺少步骤 ID');
            
            $input = getInput();
            
            $updates = [];
            $params = [];
            
            if (isset($input['title'])) {
                $updates[] = 'title = :title';
                $params[':title'] = $input['title'];
            }
            if (isset($input['is_completed'])) {
                $updates[] = 'is_completed = :is_completed';
                $params[':is_completed'] = $input['is_completed'];
            }
            if (isset($input['sort_order'])) {
                $updates[] = 'sort_order = :sort_order';
                $params[':sort_order'] = $input['sort_order'];
            }
            
            if (empty($updates)) error('没有可更新的字段');
            
            $sql = 'UPDATE steps SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if ($stmt->execute()) {
                success(null, '步骤更新成功');
            } else {
                error('步骤更新失败');
            }
            break;
        
        case 'DELETE':
            if (!$id) error('缺少步骤 ID');
            
            $stmt = $db->prepare('DELETE FROM steps WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                success(null, '步骤删除成功');
            } else {
                error('步骤删除失败');
            }
            break;
        
        default:
            error('不支持的请求方法', 405);
    }
}

/**
 * 处理AI模型管理
 */
function handleAIModels($db, $method, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $db->prepare('SELECT * FROM ai_models WHERE id = :id');
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $model = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($model) {
                    success($model);
                } else {
                    error('AI模型不存在', 404);
                }
            } else {
                $result = $db->query('SELECT * FROM ai_models ORDER BY id');
                $models = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $models[] = $row;
                }
                success($models);
            }
            break;
        
        case 'POST':
            $input = getInput();
            validateRequired($input, ['name', 'type', 'api_url', 'model_name']);
            
            $stmt = $db->prepare('
                INSERT INTO ai_models (name, type, api_url, model_name, api_key) 
                VALUES (:name, :type, :api_url, :model_name, :api_key)
            ');
            $stmt->bindValue(':name', $input['name'], SQLITE3_TEXT);
            $stmt->bindValue(':type', $input['type'], SQLITE3_TEXT);
            $stmt->bindValue(':api_url', $input['api_url'], SQLITE3_TEXT);
            $stmt->bindValue(':model_name', $input['model_name'], SQLITE3_TEXT);
            $stmt->bindValue(':api_key', $input['api_key'] ?? null, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                $newId = $db->lastInsertRowID();
                success(['id' => $newId], 'AI模型创建成功');
            } else {
                error('AI模型创建失败');
            }
            break;
        
        case 'PUT':
            if (!$id) error('缺少模型id');
            
            $input = getInput();
            $updates = [];
            $params = [];
            
            if (isset($input['name'])) {
                $updates[] = 'name = :name';
                $params[':name'] = $input['name'];
            }
            if (isset($input['type'])) {
                $updates[] = 'type = :type';
                $params[':type'] = $input['type'];
            }
            if (isset($input['api_url'])) {
                $updates[] = 'api_url = :api_url';
                $params[':api_url'] = $input['api_url'];
            }
            if (isset($input['model_name'])) {
                $updates[] = 'model_name = :model_name';
                $params[':model_name'] = $input['model_name'];
            }
            if (isset($input['api_key'])) {
                $updates[] = 'api_key = :api_key';
                $params[':api_key'] = $input['api_key'];
            }
            
            if (empty($updates)) error('没有可更新的字段');
            
            $sql = 'UPDATE ai_models SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if ($stmt->execute()) {
                success(null, 'AI模型更新成功');
            } else {
                error('AI模型更新失败');
            }
            break;
        
        case 'DELETE':
            if (!$id) error('缺少模型id');
            
            $stmt = $db->prepare('DELETE FROM ai_models WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                success(null, 'AI模型删除成功');
            } else {
                error('AI模型删除失败');
            }
            break;
        
        default:
            error('不支持的请求方法', 405);
    }
}

/**
 * 设置激活AI模型
 */
function handleSetActiveAIModel($db, $method) {
    if ($method !== 'POST') error('不支持的请求方法', 405);
    
    $input = getInput();
    validateRequired($input, ['id']);
    
    // 取消所有模型的激活状态
    $db->exec('UPDATE ai_models SET is_active = 0');
    
    // 设置指定模型为激活
    $stmt = $db->prepare('UPDATE ai_models SET is_active = 1 WHERE id = :id');
    $stmt->bindValue(':id', $input['id'], SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        success(null, '激活模型设置成功');
    } else {
        error('设置失败');
    }
}

/**
 * 处理AI任务识别
 */
function handleAIParseTasks($db, $method) {
    if ($method !== 'POST') error('不支持的请求方法', 405);
    
    $input = getInput();
    
    // 调试日志：记录接收到的数据
    error_log('AI Parse Tasks - Input: ' . json_encode($input));
    
    // 检查 text 字段
    if (!isset($input['text'])) {
        error('缺少必填字段: text');
    }
    
    $text = trim($input['text']);
    if (empty($text)) {
        success([]);
        return;
    }
    
    // 获取模型配置
    $modelId = $input['model_id'] ?? null;
    if ($modelId) {
        $stmt = $db->prepare('SELECT * FROM ai_models WHERE id = :id');
        $stmt->bindValue(':id', $modelId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $model = $result->fetchArray(SQLITE3_ASSOC);
    } else {
        // 使用激活的模型
        $result = $db->query('SELECT * FROM ai_models WHERE is_active = 1 LIMIT 1');
        $model = $result->fetchArray(SQLITE3_ASSOC);
    }
    
    if (!$model) {
        error('未找到可用的AI模型，请先配置模型');
    }
    
    try {
        // 构建提示词
        $prompt = buildAIPrompt($db, $text);
        
        // 调用AI
        $tasks = callAI($model, $prompt);
        
        // 保存历史记录
        saveAIHistory($db, $text, json_encode($tasks), $model['id']);
        
        success($tasks);
        
    } catch (Exception $e) {
        error('AI识别失败: ' . $e->getMessage());
    }
}

/**
 * 构建AI提示词
 */
function buildAIPrompt($db, $userInput) {
    // 获取用户的自定义列表
    $result = $db->query('SELECT name FROM lists WHERE is_system = 0');
    $listNames = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $listNames[] = $row['name'];
    }
    $availableLists = implode('、', $listNames);
    if (empty($availableLists)) {
        $availableLists = '任务';
    }
    
    $currentDate = date('Y-m-d');
    
    $systemPrompt = "你是一个智能任务识别助手。你的任务是：
1. 从用户输入的自然语言文本中识别出任务
2. 为每个任务提取关键信息：任务名称、类别、截止日期、子步骤
3. 必须以JSON格式返回结果

当前可用的任务列表类别：{$availableLists}

输出格式要求：
{
  \"tasks\": [
    {
      \"title\": \"任务标题（简短明确）\",
      \"list_name\": \"从可用列表中选择最合适的，如果都不合适则使用'任务'\",
      \"due_date\": \"YYYY-MM-DD格式，如果文本中没有明确日期则为null\",
      \"steps\": [\"步骤1\", \"步骤2\", ...]
    }
  ]
}

注意事项：
- 任务标题要简洁，不超过50个字
- 日期必须是有效的YYYY-MM-DD格式
- 如果文本中包含\"今天\"、\"明天\"、\"下周\"等相对时间，请转换为具体日期（今天是{$currentDate}）
- 每个任务的steps数组不超过10个步骤
- 如果一段文本包含多个任务，请分别识别
- 必须返回有效的JSON，不要包含任何其他文字";
    
    $userPrompt = "请从以下文本中识别任务：\n\n\"{$userInput}\"\n\n当前日期：{$currentDate}\n可用的任务列表：{$availableLists}\n\n请识别文本中的所有任务，并按JSON格式返回。";
    
    return ['system' => $systemPrompt, 'user' => $userPrompt];
}

/**
 * 调用AI模型
 */
function callAI($model, $prompt) {
    if ($model['type'] === 'ollama') {
        return callOllama($model, $prompt);
    } else if (in_array($model['type'], ['deepseek', 'openai', 'custom'])) {
        return callOpenAICompatible($model, $prompt);
    } else {
        throw new Exception('不支持的模型类型');
    }
}

/**
 * 调用Ollama
 */
function callOllama($model, $prompt) {
    $url = rtrim($model['api_url'], '/') . '/api/generate';
    
    $fullPrompt = $prompt['system'] . "\n\n" . $prompt['user'];
    
    $data = [
        'model' => $model['model_name'],
        'prompt' => $fullPrompt,
        'stream' => false,
        'format' => 'json'
    ];
    
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
    
    if ($error) {
        throw new Exception('Ollama请求失败: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Ollama返回错误，状态码: ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    if (!$result || !isset($result['response'])) {
        throw new Exception('Ollama返回数据格式错误');
    }
    
    $tasks = json_decode($result['response'], true);
    if (!$tasks || !isset($tasks['tasks'])) {
        throw new Exception('AI返回的JSON格式错误');
    }
    
    return $tasks['tasks'];
}

/**
 * 调用OpenAI兼容API（DeepSeek/OpenAI）
 */
function callOpenAICompatible($model, $prompt) {
    $url = rtrim($model['api_url'], '/') . '/v1/chat/completions';
    
    $data = [
        'model' => $model['model_name'],
        'messages' => [
            ['role' => 'system', 'content' => $prompt['system']],
            ['role' => 'user', 'content' => $prompt['user']]
        ],
        'response_format' => ['type' => 'json_object']
    ];
    
    $headers = ['Content-Type: application/json'];
    if (!empty($model['api_key'])) {
        $headers[] = 'Authorization: Bearer ' . $model['api_key'];
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('API请求失败: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('API返回错误，状态码: ' . $httpCode . ', 响应: ' . $response);
    }
    
    $result = json_decode($response, true);
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception('API返回数据格式错误');
    }
    
    $content = $result['choices'][0]['message']['content'];
    $tasks = json_decode($content, true);
    
    if (!$tasks || !isset($tasks['tasks'])) {
        throw new Exception('AI返回的JSON格式错误');
    }
    
    return $tasks['tasks'];
}

/**
 * 保存AI解析历史
 */
function saveAIHistory($db, $inputText, $outputJson, $modelId) {
    try {
        $stmt = $db->prepare('
            INSERT INTO ai_parse_history (input_text, output_json, model_id) 
            VALUES (:input_text, :output_json, :model_id)
        ');
        $stmt->bindValue(':input_text', $inputText, SQLITE3_TEXT);
        $stmt->bindValue(':output_json', $outputJson, SQLITE3_TEXT);
        $stmt->bindValue(':model_id', $modelId, SQLITE3_INTEGER);
        $stmt->execute();
    } catch (Exception $e) {
        // 忽略历史记录保存失败
    }
}

$db->close();
?>
