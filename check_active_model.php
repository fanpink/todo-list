<?php
$db = new SQLite3('todo.db');
$result = $db->query('SELECT * FROM ai_models WHERE is_active = 1');
$row = $result->fetchArray(SQLITE3_ASSOC);
echo json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
