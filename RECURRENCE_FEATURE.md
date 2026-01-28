# 循环事件功能说明

## 功能概述

本项目已成功实现完整的事件循环功能，允许用户为任务设置循环规则，自动生成重复任务。

## 已实现的功能

### 1. 数据库设计

#### 新增表：recurrence_rules
存储循环规则配置：
- `id`: 规则ID
- `task_id`: 关联的任务ID
- `recurrence_type`: 循环类型（daily/weekly/monthly/yearly）
- `interval`: 循环间隔（如每2周）
- `end_type`: 结束类型（never/count/date）
- `end_count`: 结束次数（当end_type=count时）
- `end_date`: 结束日期（当end_type=date时）

#### 扩展表：tasks
新增字段：
- `recurrence_rule_id`: 关联的循环规则ID
- `is_recurrence_instance`: 是否为循环实例
- `recurrence_parent_id`: 父任务ID（用于循环实例）

### 2. API接口

#### 循环规则管理
- `GET /api.php?action=recurrence_rules` - 获取循环规则列表
- `GET /api.php?action=recurrence_rules&id={id}` - 获取单个循环规则
- `POST /api.php?action=recurrence_rules` - 创建循环规则
- `PUT /api.php?action=recurrence_rules&id={id}` - 更新循环规则
- `DELETE /api.php?action=recurrence_rules&id={id}` - 删除循环规则

#### 循环任务生成
- `POST /api.php?action=generate_recurrence_tasks` - 根据规则生成循环任务

#### 循环任务操作
- `PUT /api.php?action=recurrence_tasks&id={id}` - 修改循环任务（支持single/series/future作用域）
- `DELETE /api.php?action=recurrence_tasks&id={id}` - 删除循环任务（支持single/series/future作用域）

### 3. 前端界面

#### 循环规则配置弹窗
- 循环类型选择器（每天/每周/每月/每年）
- 循环间隔设置
- 结束条件设置（永不结束/指定次数/指定日期）
- 实时预览即将生成的任务日期

#### 任务列表显示
- 循环任务显示循环图标徽章
- 任务详情中添加"设置循环"按钮

### 4. 循环逻辑

#### 核心类：RecurrenceHelper
提供以下方法：
- `getNextDate()` - 计算下一个循环日期
- `generateAllDates()` - 生成所有循环日期
- `validateRule()` - 验证循环规则
- `formatRule()` - 格式化规则为可读字符串

#### 特殊日期处理
- 月底日期自动调整（如1月31日->2月28/29日）
- 闰年2月29日处理
- 年底日期保持

### 5. 单元测试

测试覆盖：
- 每日/每周/每月/每年循环
- 不同循环间隔
- 三种结束条件
- 月底边界情况
- 闰年处理
- 规则验证

所有25个测试用例全部通过 ✓

## 使用方法

### 设置循环规则

1. 创建或编辑一个任务
2. 在任务详情中点击"设置循环"按钮
3. 配置循环参数：
   - 选择循环类型（每天/每周/每月/每年）
   - 设置循环间隔（如每2周）
   - 选择结束条件：
     - 永不结束
     - 指定次数（如重复10次）
     - 指定日期（如到2025-12-31）
4. 查看预览并保存

### 修改循环任务

修改循环任务时可以选择作用域：
- **单个实例**：只修改当前任务
- **整个系列**：修改所有循环实例
- **当前及后续**：修改当前任务及之后的所有实例

### 删除循环任务

删除循环任务时可以选择作用域：
- **单个实例**：只删除当前任务
- **整个系列**：删除所有循环实例
- **当前及后续**：删除当前任务及之后的所有实例

## 技术特点

1. **智能日期处理**：自动处理月底、闰年等特殊日期情况
2. **灵活的循环配置**：支持多种循环类型和结束条件
3. **批量操作支持**：支持对整个循环系列进行批量修改/删除
4. **实时预览**：配置时可以预览即将生成的任务日期
5. **完整的测试覆盖**：包含边界条件和特殊情况的单元测试

## 文件清单

### 后端文件
- `migrate_add_recurrence.php` - 数据库迁移脚本
- `recurrence_helper.php` - 循环逻辑辅助类
- `api.php` - API接口（已扩展）

### 前端文件
- `index.html` - 循环规则配置弹窗
- `js/api.js` - API封装（已扩展）
- `js/app.js` - 应用逻辑（已扩展）
- `css/style.css` - 样式（已扩展）

### 测试文件
- `test_recurrence.php` - 单元测试

## 安装步骤

1. 运行数据库迁移：
   ```bash
   php migrate_add_recurrence.php
   ```

2. 运行单元测试验证功能：
   ```bash
   php test_recurrence.php
   ```

3. 重启PHP服务器：
   ```bash
   php -S localhost:8000
   ```

4. 访问应用并开始使用循环功能

## 注意事项

1. 循环规则需要任务有截止日期才能正确生成
2. 月底日期会自动调整到目标月份的月底
3. 闰年2月29日会自动调整到平年的2月28日
4. 删除循环规则不会删除已生成的循环任务
5. 修改循环规则后需要重新生成任务

## 未来扩展

可以考虑添加的功能：
- 按工作日循环（跳过周末）
- 自定义循环规则（如每月第3个周五）
- 循环任务提醒
- 循环任务统计
- 循环规则模板
