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

$db->close();
?>
