/**
 * 主应用逻辑
 * 处理UI交互和业务逻辑
 */

const App = {
    // 应用状态
    state: {
        currentView: 'my-day',
        currentListId: null,
        lists: [],
        tasks: [],
        selectedTask: null,
        currentRecurrenceTaskId: null
    },

    // 初始化应用
    async init() {
        await this.loadLists();
        this.bindEvents();
        this.switchView('my-day');
        this.updateCurrentDate();
        
        // 每分钟更新一次日期显示
        setInterval(() => this.updateCurrentDate(), 60000);
    },

    // 绑定事件
    bindEvents() {
        // 导航切换
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const view = e.currentTarget.dataset.view;
                const listId = e.currentTarget.dataset.listId;
                
                if (view) {
                    this.switchView(view, listId);
                }
            });
        });

        // 添加列表
        document.getElementById('btn-add-list').addEventListener('click', () => {
            this.showAddListDialog();
        });

        // 添加任务按钮
        document.getElementById('btn-add-task').addEventListener('click', () => {
            document.getElementById('btn-add-task').style.display = 'none';
            document.getElementById('add-task-input-wrapper').style.display = 'block';
            document.getElementById('add-task-input').focus();
        });

        // 保存任务
        document.getElementById('btn-save-task').addEventListener('click', () => {
            this.createTask();
        });

        // 取消添加任务
        document.getElementById('btn-cancel-task').addEventListener('click', () => {
            document.getElementById('add-task-input').value = '';
            document.getElementById('add-task-input-wrapper').style.display = 'none';
            document.getElementById('btn-add-task').style.display = 'flex';
        });

        // 任务输入框回车
        document.getElementById('add-task-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.createTask();
            }
        });

        // 关闭任务详情
        document.getElementById('btn-close-detail').addEventListener('click', () => {
            this.closeTaskDetail();
        });
        
        // 关闭列表详情
        document.getElementById('btn-close-list-detail').addEventListener('click', () => {
            this.closeListDetail();
        });
        
        // AI模型管理按钮
        document.getElementById('btn-ai-models').addEventListener('click', () => {
            this.showAIModelsModal();
        });
        
        // AI智能导入按钮
        document.getElementById('btn-ai-import').addEventListener('click', () => {
            this.showAIImportModal();
        });
        
        // 关闭AI模型管理弹窗
        document.getElementById('btn-close-models-modal').addEventListener('click', () => {
            document.getElementById('ai-models-modal').style.display = 'none';
        });
        
        // 添加模型按钮
        document.getElementById('btn-add-model').addEventListener('click', () => {
            this.showAIModelForm();
        });
        
        // 关闭模型表单弹窗
        document.getElementById('btn-close-model-form').addEventListener('click', () => {
            document.getElementById('ai-model-form-modal').style.display = 'none';
        });
        
        document.getElementById('btn-cancel-model').addEventListener('click', () => {
            document.getElementById('ai-model-form-modal').style.display = 'none';
        });
        
        // 模型表单提交
        document.getElementById('ai-model-form').addEventListener('submit', (e) => {
            this.saveAIModel(e);
        });
        
        // 关闭AI导入弹窗
        document.getElementById('btn-close-import-modal').addEventListener('click', () => {
            document.getElementById('ai-import-modal').style.display = 'none';
        });
        
        // AI识别任务按钮
        document.getElementById('btn-ai-parse').addEventListener('click', () => {
            this.aiParseTasks();
        });
        
        // 返回输入步骤
        document.getElementById('btn-back-to-input').addEventListener('click', () => {
            document.getElementById('import-step-2').style.display = 'none';
            document.getElementById('import-step-1').style.display = 'block';
        });
        
        // 确认导入按钮
        document.getElementById('btn-confirm-import').addEventListener('click', () => {
            this.confirmAIImport();
        });
        
        // 循环规则弹窗事件
        document.getElementById('btn-close-recurrence-modal').addEventListener('click', () => {
            document.getElementById('recurrence-modal').style.display = 'none';
        });
        
        document.getElementById('btn-cancel-recurrence').addEventListener('click', () => {
            document.getElementById('recurrence-modal').style.display = 'none';
        });
        
        document.getElementById('recurrence-type').addEventListener('change', () => {
            this.updateRecurrenceUI();
        });
        
        document.getElementById('recurrence-interval').addEventListener('input', () => {
            this.updateRecurrenceUI();
        });
        
        document.getElementById('recurrence-end-type').addEventListener('change', () => {
            this.updateRecurrenceUI();
        });
        
        document.getElementById('btn-save-recurrence').addEventListener('click', () => {
            if (this.state.currentRecurrenceTaskId) {
                this.saveRecurrenceRule(this.state.currentRecurrenceTaskId);
            }
        });
        
        document.getElementById('btn-remove-recurrence').addEventListener('click', () => {
            if (this.state.currentRecurrenceTaskId) {
                this.removeRecurrenceRule(this.state.currentRecurrenceTaskId);
            }
        });
    },

    // 加载列表
    async loadLists() {
        try {
            const result = await API.getLists();
            this.state.lists = result.data;
            this.renderCustomLists();
            this.updateTaskCounts();
        } catch (error) {
            console.error('加载列表失败:', error);
            this.showError('加载列表失败', error.message);
        }
    },

    // 渲染自定义列表
    renderCustomLists() {
        const container = document.getElementById('custom-lists');
        const customLists = this.state.lists.filter(list => !list.is_system);
        
        container.innerHTML = customLists.map(list => `
            <div class="custom-list-item" data-list-id="${list.id}">
                <button class="nav-item" data-view="list" data-list-id="${list.id}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M3 3h18v18H3z"></path>
                    </svg>
                    <span>${this.escapeHtml(list.name)}</span>
                    <span class="task-count">${list.task_count || 0}</span>
                </button>
                <button class="btn-delete-list" data-list-id="${list.id}" title="删除列表" style="display: none;">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </button>
            </div>
        `).join('');

        // 绑定列表项点击事件
        container.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const listId = e.currentTarget.dataset.listId;
                this.switchView('list', listId);
            });
        });
        
        // 绑定删除按钮事件
        container.querySelectorAll('.btn-delete-list').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const listId = e.currentTarget.dataset.listId;
                await this.deleteList(listId);
            });
        });
        
        // 绑定列表项的拖放事件
        container.querySelectorAll('.custom-list-item').forEach(listItem => {
            const listId = listItem.dataset.listId;
            
            listItem.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                listItem.classList.add('drag-over');
            });
            
            listItem.addEventListener('dragleave', (e) => {
                if (e.target === listItem || !listItem.contains(e.relatedTarget)) {
                    listItem.classList.remove('drag-over');
                }
            });
            
            listItem.addEventListener('drop', async (e) => {
                e.preventDefault();
                listItem.classList.remove('drag-over');
                
                const taskId = e.dataTransfer.getData('text/plain');
                if (taskId) {
                    await this.moveTaskToList(taskId, listId);
                }
            });
        });
        
        // 更新当前选中的列表的删除按钮显示状态
        this.updateDeleteButtonVisibility();
    },

    // 切换视图
    async switchView(view, listId = null) {
        this.state.currentView = view;
        this.state.currentListId = listId;

        // 更新导航激活状态
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        if (view === 'list' && listId) {
            document.querySelector(`[data-list-id="${listId}"]`).classList.add('active');
        } else {
            document.querySelector(`[data-view="${view}"]`)?.classList.add('active');
        }
        
        // 更新删除按钮显示状态
        this.updateDeleteButtonVisibility();

        // 加载任务
        await this.loadTasks();
        this.closeTaskDetail();
        
        // 如果是自定义列表，显示列表详情
        if (view === 'list' && listId) {
            this.showListDetail(listId);
        } else {
            this.closeListDetail();
        }
    },
    
    // 更新删除按钮显示状态（只在选中的列表上显示）
    updateDeleteButtonVisibility() {
        // 隐藏所有删除按钮
        document.querySelectorAll('.btn-delete-list').forEach(btn => {
            btn.style.display = 'none';
        });
        
        // 如果当前视图是自定义列表，显示对应的删除按钮
        if (this.state.currentView === 'list' && this.state.currentListId) {
            const deleteBtn = document.querySelector(`.custom-list-item[data-list-id="${this.state.currentListId}"] .btn-delete-list`);
            if (deleteBtn) {
                deleteBtn.style.display = 'flex';
            }
        }
    },

    // 加载任务
    async loadTasks() {
        try {
            this.showLoading();

            let options = {};
            
            if (this.state.currentView === 'list') {
                options.list_id = this.state.currentListId;
            } else if (this.state.currentView === 'tasks') {
                options.list_id = 1; // 默认任务列表
            } else {
                options.view = this.state.currentView;
            }

            const result = await API.getTasks(options);
            
            // 处理计划内视图的分组数据
            if (this.state.currentView === 'planned' && typeof result.data === 'object' && !Array.isArray(result.data)) {
                this.renderPlannedTasks(result.data);
            } else {
                this.state.tasks = result.data;
                this.renderTasks();
            }

            this.updateViewTitle();
            this.hideLoading();
        } catch (error) {
            this.hideLoading();
            console.error('加载任务失败:', error);
            this.showError('加载任务失败', error.message);
        }
    },

    // 渲染任务列表
    renderTasks() {
        const container = document.getElementById('task-list');
        
        if (!this.state.tasks || this.state.tasks.length === 0) {
            container.innerHTML = this.renderEmptyState();
            return;
        }

        container.innerHTML = this.state.tasks.map(task => this.renderTaskItem(task)).join('');
        this.bindTaskEvents();
    },

    // 渲染计划内任务（分组）
    renderPlannedTasks(grouped) {
        const container = document.getElementById('task-list');
        const groups = [
            { key: 'overdue', title: '过期' },
            { key: 'today', title: '今天' },
            { key: 'tomorrow', title: '明天' },
            { key: 'this_week', title: '本周' },
            { key: 'later', title: '以后' }
        ];

        let html = '';
        let isEmpty = true;

        groups.forEach(group => {
            const tasks = grouped[group.key] || [];
            if (tasks.length > 0) {
                isEmpty = false;
                html += `
                    <div class="task-group">
                        <h3 class="task-group-title">${group.title}</h3>
                        ${tasks.map(task => this.renderTaskItem(task)).join('')}
                    </div>
                `;
            }
        });

        container.innerHTML = isEmpty ? this.renderEmptyState() : html;
        this.bindTaskEvents();
    },

    // 渲染单个任务项
    renderTaskItem(task) {
        const dueDate = task.due_date ? this.formatDate(task.due_date) : '';
        const stepsInfo = task.steps_total > 0 ? 
            `<span class="task-meta-item">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                ${task.steps_completed}/${task.steps_total}
            </span>` : '';
        
        const recurrenceInfo = task.recurrence_rule_id ? 
            `<span class="task-meta-item recurrence-badge" title="循环任务">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
            </span>` : '';

        return `
            <div class="task-item ${task.is_completed ? 'completed' : ''}" 
                 data-task-id="${task.id}" 
                 data-list-id="${task.list_id}"
                 draggable="true">
                <div class="task-checkbox ${task.is_completed ? 'checked' : ''}" data-task-id="${task.id}"></div>
                <div class="task-content">
                    <div class="task-title">${this.escapeHtml(task.title)}</div>
                    ${(dueDate || stepsInfo || recurrenceInfo) ? `
                        <div class="task-meta">
                            ${dueDate ? `<span class="task-meta-item">${dueDate}</span>` : ''}
                            ${stepsInfo}
                            ${recurrenceInfo}
                        </div>
                    ` : ''}
                </div>
                <div class="task-actions">
                    <button class="btn-star ${task.is_important ? 'active' : ''}" data-task-id="${task.id}">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    },

    // 渲染空状态
    renderEmptyState() {
        const messages = {
            'my-day': '今天还没有任务，添加一个开始吧！',
            'important': '没有重要任务',
            'planned': '没有计划内的任务',
            'tasks': '还没有任务，创建一个开始吧！',
            'completed': '还没有完成任何任务'
        };

        return `
            <div class="empty-state">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                </svg>
                <div class="empty-state-title">暂无任务</div>
                <div class="empty-state-text">${messages[this.state.currentView] || '暂无任务'}</div>
            </div>
        `;
    },

    // 绑定任务事件
    bindTaskEvents() {
        // 先移除旧的事件监听器，避免重复绑定
        const taskList = document.getElementById('task-list');
        
        // 使用事件委托，只在容器上绑定一次
        // 移除旧的监听器（如果存在）
        if (this._taskListHandler) {
            taskList.removeEventListener('click', this._taskListHandler);
        }
        
        // 创建新的事件处理器
        this._taskListHandler = async (e) => {
            // 处理复选框点击
            if (e.target.classList.contains('task-checkbox')) {
                e.stopPropagation();
                const taskId = e.target.dataset.taskId;
                const isCompleted = e.target.classList.contains('checked');
                await this.toggleTaskComplete(taskId, !isCompleted);
                return;
            }
            
            // 处理星标点击
            const starBtn = e.target.closest('.btn-star');
            if (starBtn) {
                e.stopPropagation();
                const taskId = starBtn.dataset.taskId;
                const isImportant = starBtn.classList.contains('active');
                await this.toggleTaskImportant(taskId, !isImportant);
                return;
            }
            
            // 处理任务项点击
            const taskItem = e.target.closest('.task-item');
            if (taskItem && !e.target.closest('.task-checkbox') && !e.target.closest('.btn-star')) {
                const taskId = taskItem.dataset.taskId;
                this.showTaskDetail(taskId);
            }
        };
        
        // 绑定事件委托
        taskList.addEventListener('click', this._taskListHandler);
        
        // 绑定拖拽事件
        this.bindDragEvents();
    },
    
    // 绑定拖拽事件
    bindDragEvents() {
        const taskItems = document.querySelectorAll('.task-item');
        
        taskItems.forEach(item => {
            // 开始拖拽
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', e.target.dataset.taskId);
                e.target.classList.add('dragging');
            });
            
            // 拖拽结束
            item.addEventListener('dragend', (e) => {
                e.target.classList.remove('dragging');
            });
        });
    },

    // 创建任务
    async createTask() {
        const input = document.getElementById('add-task-input');
        const title = input.value.trim();

        if (!title) {
            this.showToast('请输入任务标题');
            return;
        }

        try {
            let listId = 1; // 默认列表ID
            
            if (this.state.currentView === 'list') {
                listId = this.state.currentListId;
            }

            const data = {
                list_id: listId,
                title: title,
                is_my_day: this.state.currentView === 'my-day' ? 1 : 0
            };

            await API.createTask(data);
            
            input.value = '';
            document.getElementById('add-task-input-wrapper').style.display = 'none';
            document.getElementById('btn-add-task').style.display = 'flex';
            
            await this.loadTasks();
            // 创建任务后重新加载列表以更新计数
            await this.loadLists();
            this.scheduleCountUpdate();
            this.showToast('任务创建成功');
        } catch (error) {
            this.showToast('任务创建失败');
        }
    },

    // 切换任务完成状态
    async toggleTaskComplete(taskId, isCompleted) {
        try {
            await API.toggleTaskComplete(taskId, isCompleted);
            // 只重新加载当前视图的任务
            await this.loadTasks();
            // 延迟更新计数，避免同时发起太多请求
            this.scheduleCountUpdate();
        } catch (error) {
            this.showToast('操作失败');
        }
    },

    // 切换任务重要状态
    async toggleTaskImportant(taskId, isImportant) {
        try {
            await API.toggleTaskImportant(taskId, isImportant);
            // 只重新加载当前视图的任务
            await this.loadTasks();
            // 延迟更新计数，避免同时发起太多请求
            this.scheduleCountUpdate();
        } catch (error) {
            this.showToast('操作失败');
        }
    },
    
    // 延迟更新计数（防抖）
    scheduleCountUpdate() {
        if (this._countUpdateTimer) {
            clearTimeout(this._countUpdateTimer);
        }
        this._countUpdateTimer = setTimeout(() => {
            this.updateTaskCounts();
        }, 500); // 500ms后更新，避免频繁请求
    },

    // 显示任务详情
    async showTaskDetail(taskId) {
        try {
            const result = await API.getTask(taskId);
            const task = result.data;
            this.state.selectedTask = task;

            const content = document.getElementById('task-detail-content');
            content.innerHTML = `
                <div class="detail-section">
                    <div class="task-checkbox ${task.is_completed ? 'checked' : ''}" 
                         data-task-id="${task.id}" 
                         style="width: 24px; height: 24px; margin-bottom: 16px;"></div>
                    <input type="text" class="detail-title" id="detail-title" value="${this.escapeHtml(task.title)}">
                </div>

                <div class="detail-section">
                    <button class="detail-field" id="btn-add-to-my-day">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v6l4 2"></path>
                        </svg>
                        <span>${task.is_my_day ? '从"我的一天"中移除' : '添加到"我的一天"'}</span>
                    </button>
                </div>

                <div class="detail-section">
                    <div class="detail-label">截止日期</div>
                    <input type="date" class="detail-field" id="detail-due-date" 
                           value="${task.due_date || ''}" 
                           style="width: 100%; border: 1px solid var(--border-color); border-radius: 4px; padding: 8px;">
                </div>

                <div class="detail-section">
                    <button class="detail-field" id="btn-set-recurrence">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                        <span>设置循环</span>
                    </button>
                </div>

                <div class="detail-section">
                    <div class="detail-label">备注</div>
                    <textarea class="detail-textarea" id="detail-note" placeholder="添加备注">${task.note || ''}</textarea>
                </div>

                <div class="detail-section">
                    <div class="detail-label">步骤</div>
                    <div class="steps-list" id="steps-list">
                        ${task.steps.map(step => `
                            <div class="step-item ${step.is_completed ? 'completed' : ''}">
                                <div class="step-checkbox ${step.is_completed ? 'checked' : ''}" 
                                     data-step-id="${step.id}"></div>
                                <span class="step-title">${this.escapeHtml(step.title)}</span>
                            </div>
                        `).join('')}
                    </div>
                    <input type="text" class="add-step-input" id="add-step-input" placeholder="添加步骤">
                </div>

                <div class="detail-section">
                    <button class="btn" id="btn-delete-task" style="width: 100%; color: var(--danger-color);">删除任务</button>
                </div>
            `;

            document.getElementById('task-detail-panel').style.display = 'block';

            // 绑定详情面板事件
            this.bindDetailEvents(task);
        } catch (error) {
            this.showToast('加载任务详情失败');
        }
    },

    // 绑定详情面板事件
    bindDetailEvents(task) {
        // 标题更新
        const titleInput = document.getElementById('detail-title');
        titleInput.addEventListener('blur', async () => {
            const newTitle = titleInput.value.trim();
            if (newTitle && newTitle !== task.title) {
                await API.updateTask(task.id, { title: newTitle });
                await this.loadTasks();
            }
        });

        // 完成状态
        document.querySelector('#task-detail-content .task-checkbox').addEventListener('click', async (e) => {
            const isCompleted = e.target.classList.contains('checked');
            await this.toggleTaskComplete(task.id, !isCompleted);
            this.closeTaskDetail();
        });

        // 添加到我的一天
        document.getElementById('btn-add-to-my-day').addEventListener('click', async () => {
            await API.toggleTaskMyDay(task.id, !task.is_my_day);
            await this.loadTasks();
            this.scheduleCountUpdate();
            this.closeTaskDetail();
        });

        // 截止日期
        document.getElementById('detail-due-date').addEventListener('change', async (e) => {
            await API.updateTask(task.id, { due_date: e.target.value || null });
            await this.loadTasks();
            this.scheduleCountUpdate();
        });

        // 设置循环
        document.getElementById('btn-set-recurrence').addEventListener('click', async () => {
            this.state.currentRecurrenceTaskId = task.id;
            await this.showRecurrenceModal(task.id);
        });

        // 备注
        const noteTextarea = document.getElementById('detail-note');
        noteTextarea.addEventListener('blur', async () => {
            await API.updateTask(task.id, { note: noteTextarea.value.trim() || null });
        });

        // 步骤复选框
        document.querySelectorAll('.step-checkbox').forEach(checkbox => {
            checkbox.addEventListener('click', async (e) => {
                const stepId = e.target.dataset.stepId;
                const isCompleted = e.target.classList.contains('checked');
                await API.toggleStepComplete(stepId, !isCompleted);
                await this.showTaskDetail(task.id);
            });
        });

        // 添加步骤
        const stepInput = document.getElementById('add-step-input');
        stepInput.addEventListener('keypress', async (e) => {
            if (e.key === 'Enter') {
                const title = stepInput.value.trim();
                if (title) {
                    await API.createStep({ task_id: task.id, title });
                    stepInput.value = '';
                    await this.showTaskDetail(task.id);
                }
            }
        });

        // 删除任务
        document.getElementById('btn-delete-task').addEventListener('click', async () => {
            if (confirm('确定要删除这个任务吗？')) {
                await API.deleteTask(task.id);
                await this.loadTasks();
                this.scheduleCountUpdate();
                this.closeTaskDetail();
                this.showToast('任务已删除');
            }
        });
    },

    // 关闭任务详情
    closeTaskDetail() {
        document.getElementById('task-detail-panel').style.display = 'none';
        this.state.selectedTask = null;
    },
    
    // 显示列表详情
    showListDetail(listId) {
        const list = this.state.lists.find(l => l.id == listId);
        if (!list) return;
        
        const content = document.getElementById('list-detail-content');
        content.innerHTML = `
            <div class="detail-section">
                <input type="text" class="list-name-input" id="list-name-input" value="${this.escapeHtml(list.name)}" placeholder="列表名称">
            </div>
            
            <div class="list-info-section">
                <div class="list-info-item">
                    <span class="list-info-label">任务数量</span>
                    <span class="list-info-value">${list.task_count || 0}</span>
                </div>
                <div class="list-info-item">
                    <span class="list-info-label">创建时间</span>
                    <span class="list-info-value">${new Date(list.created_at).toLocaleDateString('zh-CN')}</span>
                </div>
            </div>
        `;
        
        document.getElementById('list-detail-panel').style.display = 'block';
        
        // 绑定列表名称输入事件
        const nameInput = document.getElementById('list-name-input');
        nameInput.addEventListener('blur', async () => {
            const newName = nameInput.value.trim();
            if (newName && newName !== list.name) {
                await this.updateListName(listId, newName);
            } else if (!newName) {
                nameInput.value = list.name; // 恢复原名称
            }
        });
        
        nameInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                nameInput.blur();
            }
        });
    },
    
    // 更新列表名称
    async updateListName(listId, newName) {
        try {
            await API.updateList(listId, { name: newName });
            await this.loadLists();
            this.showToast('列表名称已更新');
            // 重新显示列表详情以更新信息
            this.showListDetail(listId);
        } catch (error) {
            console.error('更新列表名称失败:', error);
            this.showToast('更新失败');
        }
    },
    
    // 关闭列表详情
    closeListDetail() {
        document.getElementById('list-detail-panel').style.display = 'none';
    },

    // 显示添加列表对话框
    showAddListDialog() {
        const name = prompt('请输入列表名称：');
        if (name && name.trim()) {
            this.createList(name.trim());
        }
    },

    // 创建列表
    async createList(name) {
        try {
            await API.createList({ name });
            await this.loadLists();
            this.showToast('列表创建成功');
        } catch (error) {
            this.showToast('列表创建失败');
        }
    },
    
    // 删除列表
    async deleteList(listId) {
        try {
            // 获取列表信息
            const list = this.state.lists.find(l => l.id == listId);
            if (!list) {
                this.showToast('列表不存在');
                return;
            }
            
            // 确认删除
            const taskCountText = list.task_count > 0 ? `（包含 ${list.task_count} 个任务）` : '';
            if (!confirm(`确定要删除列表「${list.name}」${taskCountText}吗？\n\n删除后无法恢复！`)) {
                return;
            }
            
            await API.deleteList(listId);
            
            // 如果当前正在查看该列表，切换到默认视图
            if (this.state.currentView === 'list' && this.state.currentListId == listId) {
                await this.switchView('my-day');
            }
            
            await this.loadLists();
            this.showToast('列表已删除');
        } catch (error) {
            console.error('删除列表失败:', error);
            this.showToast('删除列表失败');
        }
    },
    
    // 将任务移动到其他列表
    async moveTaskToList(taskId, targetListId) {
        try {
            // 获取任务信息
            const task = this.state.tasks.find(t => t.id == taskId);
            if (!task) {
                this.showToast('任务不存在');
                return;
            }
            
            // 如果是同一个列表，不需要移动
            if (task.list_id == targetListId) {
                return;
            }
            
            // 获取目标列表名称
            const targetList = this.state.lists.find(l => l.id == targetListId);
            if (!targetList) {
                this.showToast('目标列表不存在');
                return;
            }
            
            // 更新任务的列表ID
            await API.updateTask(taskId, { list_id: targetListId });
            
            // 重新加载任务和列表
            await this.loadTasks();
            await this.loadLists();
            
            this.showToast(`已移动到「${targetList.name}」`);
        } catch (error) {
            console.error('移动任务失败:', error);
            this.showToast('移动失败');
        }
    },

    // 更新任务计数
    async updateTaskCounts() {
        try {
            // 更新我的一天
            const myDayResult = await API.getTasks({ view: 'my-day' });
            document.getElementById('my-day-count').textContent = 
                myDayResult.data.filter(t => !t.is_completed).length;

            // 更新重要
            const importantResult = await API.getTasks({ view: 'important' });
            document.getElementById('important-count').textContent = importantResult.data.length;

            // 更新计划内
            const plannedResult = await API.getTasks({ view: 'planned' });
            let plannedCount = 0;
            if (typeof plannedResult.data === 'object' && !Array.isArray(plannedResult.data)) {
                Object.values(plannedResult.data).forEach(tasks => {
                    plannedCount += tasks.filter(t => !t.is_completed).length;
                });
            }
            document.getElementById('planned-count').textContent = plannedCount;

            // 更新任务列表
            const tasksResult = await API.getTasks({ list_id: 1 });
            document.getElementById('tasks-count').textContent = 
                tasksResult.data.filter(t => !t.is_completed).length;
            
            // 更新已完成
            const completedResult = await API.getTasks({ view: 'completed' });
            document.getElementById('completed-count').textContent = completedResult.data.length;

            // 更新自定义列表计数（直接获取列表数据，不再调用loadLists）
            const listsResult = await API.getLists();
            listsResult.data.forEach(list => {
                const countElement = document.getElementById(`list-${list.id}-count`);
                if (countElement) {
                    countElement.textContent = list.task_count || 0;
                }
            });
        } catch (error) {
            console.error('更新计数失败:', error);
        }
    },

    // 更新视图标题
    updateViewTitle() {
        const titles = {
            'my-day': '我的一天',
            'important': '重要',
            'planned': '计划内',
            'tasks': '任务',
            'completed': '已完成'
        };

        let title = titles[this.state.currentView];
        
        if (this.state.currentView === 'list') {
            const list = this.state.lists.find(l => l.id == this.state.currentListId);
            title = list ? list.name : '列表';
        }

        document.getElementById('view-title').textContent = title;
    },

    // 更新当前日期显示
    updateCurrentDate() {
        const date = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = 
            date.toLocaleDateString('zh-CN', options);
    },

    // 格式化日期
    formatDate(dateString) {
        const date = new Date(dateString);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const taskDate = new Date(date);
        taskDate.setHours(0, 0, 0, 0);

        const diffDays = Math.floor((taskDate - today) / (1000 * 60 * 60 * 24));

        if (diffDays === 0) return '今天';
        if (diffDays === 1) return '明天';
        if (diffDays === -1) return '昨天';
        if (diffDays < 0) return '已过期';

        return `${date.getMonth() + 1}月${date.getDate()}日`;
    },

    // HTML 转义
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    // 显示加载状态
    showLoading() {
        document.getElementById('loading').style.display = 'flex';
    },

    // 隐藏加载状态
    hideLoading() {
        document.getElementById('loading').style.display = 'none';
    },

    // 显示 Toast 提示
    showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    },

    // 显示错误对话框
    showError(title, message) {
        // 如果是 SQLite3 未启用错误，显示更详细的信息
        if (message.includes('SQLite3')) {
            alert(`❗ ${title}

${message}

请按照以上步骤启用 SQLite3 扩展后重启 PHP 服务器。`);
        } else {
            alert(`❗ ${title}\n\n${message}`);
        }
    },
    
    // ========== 循环规则功能 ==========
    
    // 显示循环规则配置弹窗
    async showRecurrenceModal(taskId) {
        document.getElementById('recurrence-modal').style.display = 'flex';
        this.state.currentRecurrenceTaskId = taskId;
        
        // 加载任务的循环规则
        try {
            const result = await API.getRecurrenceRules(taskId);
            const rules = result.data || [];
            
            if (rules.length > 0) {
                const rule = rules[0];
                document.getElementById('recurrence-type').value = rule.recurrence_type;
                document.getElementById('recurrence-interval').value = rule.interval;
                document.getElementById('recurrence-end-type').value = rule.end_type;
                document.getElementById('recurrence-end-count').value = rule.end_count || 10;
                document.getElementById('recurrence-end-date').value = rule.end_date || '';
                document.getElementById('btn-remove-recurrence').style.display = 'inline-block';
            } else {
                this.resetRecurrenceForm();
                document.getElementById('btn-remove-recurrence').style.display = 'none';
            }
            
            this.updateRecurrenceUI();
            await this.previewRecurrenceRule(taskId);
        } catch (error) {
            console.error('加载循环规则失败:', error);
        }
    },
    
    // 重置循环规则表单
    resetRecurrenceForm() {
        document.getElementById('recurrence-type').value = 'weekly';
        document.getElementById('recurrence-interval').value = 1;
        document.getElementById('recurrence-end-type').value = 'never';
        document.getElementById('recurrence-end-count').value = 10;
        document.getElementById('recurrence-end-date').value = '';
        this.updateRecurrenceUI();
    },
    
    // 更新循环规则UI
    updateRecurrenceUI() {
        const interval = document.getElementById('recurrence-interval').value;
        document.getElementById('interval-display').textContent = interval;
        
        const endType = document.getElementById('recurrence-end-type').value;
        document.getElementById('end-count-group').style.display = endType === 'count' ? 'block' : 'none';
        document.getElementById('end-date-group').style.display = endType === 'date' ? 'block' : 'none';
    },
    
    // 预览循环规则
    async previewRecurrenceRule(taskId) {
        const type = document.getElementById('recurrence-type').value;
        const interval = parseInt(document.getElementById('recurrence-interval').value);
        const endType = document.getElementById('recurrence-end-type').value;
        const endCount = parseInt(document.getElementById('recurrence-end-count').value);
        const endDate = document.getElementById('recurrence-end-date').value;
        
        const preview = document.getElementById('recurrence-preview');
        
        // 获取任务信息以获取起始日期
        try {
            const taskResult = await API.getTask(taskId);
            const task = taskResult.data;
            const startDate = task.due_date || new Date().toISOString().split('T')[0];
            
            // 生成预览日期
            const dates = [];
            let currentDate = new Date(startDate);
            let count = 0;
            const maxPreview = 10;
            
            while (count < maxPreview) {
                if (endType === 'count' && count >= endCount) break;
                if (endType === 'date' && currentDate.toISOString().split('T')[0] > endDate) break;
                
                dates.push(currentDate.toISOString().split('T')[0]);
                
                // 计算下一个日期
                switch (type) {
                    case 'daily':
                        currentDate.setDate(currentDate.getDate() + interval);
                        break;
                    case 'weekly':
                        currentDate.setDate(currentDate.getDate() + (interval * 7));
                        break;
                    case 'monthly':
                        currentDate.setMonth(currentDate.getMonth() + interval);
                        break;
                    case 'yearly':
                        currentDate.setFullYear(currentDate.getFullYear() + interval);
                        break;
                }
                
                count++;
            }
            
            const typeNames = {
                daily: '每天',
                weekly: '每周',
                monthly: '每月',
                yearly: '每年'
            };
            
            const endTypeNames = {
                never: '永不结束',
                count: `重复${endCount}次`,
                date: `直到${endDate}`
            };
            
            preview.innerHTML = `
                <h4>循环规则预览</h4>
                <div class="rule-summary">
                    <strong>${typeNames[type]}</strong>
                    ${interval > 1 ? `，每${interval}个周期` : ''}
                    ，${endTypeNames[endType]}
                </div>
                <div class="rule-dates">
                    <h5>即将生成的任务日期（前${dates.length}个）：</h5>
                    <div class="dates-list">
                        ${dates.map(date => `<span class="date-tag">${date}</span>`).join('')}
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('预览循环规则失败:', error);
        }
    },
    
    // 保存循环规则
    async saveRecurrenceRule(taskId) {
        const data = {
            task_id: taskId,
            recurrence_type: document.getElementById('recurrence-type').value,
            interval: parseInt(document.getElementById('recurrence-interval').value),
            end_type: document.getElementById('recurrence-end-type').value,
            end_count: document.getElementById('recurrence-end-type').value === 'count' ? 
                parseInt(document.getElementById('recurrence-end-count').value) : null,
            end_date: document.getElementById('recurrence-end-type').value === 'date' ? 
                document.getElementById('recurrence-end-date').value : null
        };
        
        try {
            await API.createRecurrenceRule(data);
            this.showToast('循环规则设置成功');
            document.getElementById('recurrence-modal').style.display = 'none';
            this.loadTasks();
        } catch (error) {
            console.error('保存循环规则失败:', error);
            this.showToast('保存失败: ' + (error.message || '未知错误'));
        }
    },
    
    // 移除循环规则
    async removeRecurrenceRule(taskId) {
        if (!confirm('确定要移除循环规则吗？已生成的循环任务将被保留。')) {
            return;
        }
        
        try {
            const result = await API.getRecurrenceRules(taskId);
            const rules = result.data || [];
            
            if (rules.length > 0) {
                await API.deleteRecurrenceRule(rules[0].id);
                this.showToast('循环规则已移除');
                document.getElementById('recurrence-modal').style.display = 'none';
                this.loadTasks();
            }
        } catch (error) {
            console.error('移除循环规则失败:', error);
            this.showToast('移除失败');
        }
    },
    
    // ========== AI功能 ==========
    
    // 显示AI模型管理弹窗
    showAIModelsModal() {
        document.getElementById('ai-models-modal').style.display = 'flex';
        this.loadAIModels();
    },
    
    // 加载AI模型列表
    async loadAIModels() {
        try {
            const result = await API.getAIModels();
            const models = result.data || [];
            
            const tbody = document.getElementById('models-list');
            tbody.innerHTML = models.map(model => `
                <tr>
                    <td>${this.escapeHtml(model.name)}</td>
                    <td>${this.escapeHtml(model.type)}</td>
                    <td>${this.escapeHtml(model.api_url)}</td>
                    <td>${this.escapeHtml(model.model_name)}</td>
                    <td>
                        <span class="model-status ${model.is_active ? 'active' : 'inactive'}">
                            ${model.is_active ? '激活' : '未激活'}
                        </span>
                    </td>
                    <td class="model-actions">
                        ${!model.is_active ? `<button class="btn btn-primary" onclick="App.setActiveModel(${model.id})">激活</button>` : '<span class="model-status active">当前激活</span>'}
                        <button class="btn" onclick="App.editAIModel(${model.id})">编辑</button>
                        <button class="btn" onclick="App.deleteAIModel(${model.id})">删除</button>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            console.error('加载AI模型失败:', error);
            this.showToast('加载AI模型失败');
        }
    },
    
    // 显示AI模型表单
    showAIModelForm(modelId = null) {
        const modal = document.getElementById('ai-model-form-modal');
        const form = document.getElementById('ai-model-form');
        
        if (modelId) {
            // 编辑模式
            API.getAIModels().then(result => {
                const model = result.data.find(m => m.id == modelId);
                if (model) {
                    document.getElementById('model-form-title').textContent = '编辑AI模型';
                    document.getElementById('model-id').value = model.id;
                    document.getElementById('model-name').value = model.name;
                    document.getElementById('model-type').value = model.type;
                    document.getElementById('model-api-url').value = model.api_url;
                    document.getElementById('model-model-name').value = model.model_name;
                    document.getElementById('model-api-key').value = model.api_key || '';
                }
            });
        } else {
            // 新增模式
            document.getElementById('model-form-title').textContent = '添加AI模型';
            form.reset();
            document.getElementById('model-id').value = '';
        }
        
        modal.style.display = 'flex';
    },
    
    // 保存AI模型
    async saveAIModel(event) {
        event.preventDefault();
        
        const modelId = document.getElementById('model-id').value;
        const data = {
            name: document.getElementById('model-name').value,
            type: document.getElementById('model-type').value,
            api_url: document.getElementById('model-api-url').value,
            model_name: document.getElementById('model-model-name').value,
            api_key: document.getElementById('model-api-key').value || null
        };
        
        try {
            if (modelId) {
                await API.updateAIModel(modelId, data);
                this.showToast('模型更新成功');
            } else {
                await API.createAIModel(data);
                this.showToast('模型添加成功');
            }
            
            document.getElementById('ai-model-form-modal').style.display = 'none';
            this.loadAIModels();
        } catch (error) {
            console.error('保存模型失败:', error);
            this.showToast('保存失败');
        }
    },
    
    // 编辑AI模型
    editAIModel(modelId) {
        this.showAIModelForm(modelId);
    },
    
    // 删除AI模型
    async deleteAIModel(modelId) {
        if (!confirm('确定要删除这个模型吗？')) return;
        
        try {
            await API.deleteAIModel(modelId);
            this.showToast('模型已删除');
            this.loadAIModels();
        } catch (error) {
            console.error('删除模型失败:', error);
            this.showToast('删除失败');
        }
    },
    
    // 设置激活AI模型
    async setActiveModel(modelId) {
        try {
            await API.setActiveAIModel(modelId);
            this.showToast('激活模型设置成功');
            this.loadAIModels();
        } catch (error) {
            console.error('设置激活模型失败:', error);
            this.showToast('设置失败');
        }
    },
    
    // 显示AI导入弹窗
    async showAIImportModal() {
        document.getElementById('ai-import-modal').style.display = 'flex';
        document.getElementById('import-step-1').style.display = 'block';
        document.getElementById('import-step-2').style.display = 'none';
        document.getElementById('ai-input-text').value = '';
        
        // 加载模型列表
        try {
            const result = await API.getAIModels();
            const models = result.data || [];
            const select = document.getElementById('ai-model-select');
            select.innerHTML = models.map(model => 
                `<option value="${model.id}" ${model.is_active ? 'selected' : ''}>${this.escapeHtml(model.name)}</option>`
            ).join('');
        } catch (error) {
            console.error('加载模型列表失败:', error);
        }
    },
    
    // AI识别任务
    async aiParseTasks() {
        const text = document.getElementById('ai-input-text').value.trim();
        if (!text) {
            this.showToast('请输入任务描述文本');
            return;
        }
        
        const modelId = document.getElementById('ai-model-select').value;
        
        try {
            // 显示加载状态
            document.getElementById('btn-ai-parse').disabled = true;
            document.getElementById('btn-ai-parse').textContent = '识别中...';
            
            const result = await API.aiParseTasks({ text, model_id: modelId });
            const tasks = result.data || [];
            
            if (tasks.length === 0) {
                this.showToast('未能识别出任务，请尝试更明确的描述');
                return;
            }
            
            // 显示任务预览
            this.renderAITasksPreview(tasks);
            document.getElementById('import-step-1').style.display = 'none';
            document.getElementById('import-step-2').style.display = 'block';
            
        } catch (error) {
            console.error('AI识别失败:', error);
            this.showToast('AI识别失败: ' + (error.message || '请检查模型配置'));
        } finally {
            document.getElementById('btn-ai-parse').disabled = false;
            document.getElementById('btn-ai-parse').textContent = '🤖 识别任务';
        }
    },
    
    // 渲染AI任务预览
    async renderAITasksPreview(tasks) {
        const lists = await API.getLists();
        const listsData = lists.data || [];
        
        const preview = document.getElementById('ai-tasks-preview');
        preview.innerHTML = tasks.map((task, index) => {
            const listOptions = listsData.map(list => {
                const isSelected = list.name === task.list_name || (task.list_name === '任务' && list.id == 1);
                return `<option value="${list.id}" ${isSelected ? 'selected' : ''}>${this.escapeHtml(list.name)}</option>`;
            }).join('');
            
            const steps = task.steps || [];
            const stepsHtml = steps.map((step, stepIndex) => `
                <div class="step-item">
                    <input type="text" value="${this.escapeHtml(step)}" data-task-index="${index}" data-step-index="${stepIndex}">
                    <button type="button" class="btn-remove-step" onclick="this.parentElement.remove()">×</button>
                </div>
            `).join('');
            
            return `
                <div class="ai-task-card" data-index="${index}">
                    <div class="task-card-header">
                        <input type="text" class="task-title-input" value="${this.escapeHtml(task.title)}" data-task-index="${index}">
                        <button type="button" class="btn-remove-task" onclick="this.closest('.ai-task-card').remove()">×</button>
                    </div>
                    <div class="task-card-body">
                        <div class="form-group">
                            <label>分配到列表</label>
                            <select class="task-list-select" data-task-index="${index}">
                                ${listOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>完成时限</label>
                            <input type="date" class="task-due-date" value="${task.due_date || ''}" data-task-index="${index}">
                        </div>
                        <div class="form-group">
                            <label>任务步骤</label>
                            <div class="task-steps-list" data-task-index="${index}">
                                ${stepsHtml}
                            </div>
                            <button type="button" class="btn-add-step" onclick="App.addStepToPreview(${index})">+ 添加步骤</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },
    
    // 添加步骤到预览
    addStepToPreview(taskIndex) {
        const stepsList = document.querySelector(`.task-steps-list[data-task-index="${taskIndex}"]`);
        const stepIndex = stepsList.children.length;
        const stepItem = document.createElement('div');
        stepItem.className = 'step-item';
        stepItem.innerHTML = `
            <input type="text" placeholder="步骤描述" data-task-index="${taskIndex}" data-step-index="${stepIndex}">
            <button type="button" class="btn-remove-step" onclick="this.parentElement.remove()">×</button>
        `;
        stepsList.appendChild(stepItem);
    },
    
    // 确认导入AI任务
    async confirmAIImport() {
        const taskCards = document.querySelectorAll('.ai-task-card');
        if (taskCards.length === 0) {
            this.showToast('没有可导入的任务');
            return;
        }
        
        try {
            const tasks = [];
            taskCards.forEach(card => {
                const index = card.dataset.index;
                const title = card.querySelector('.task-title-input').value.trim();
                const listId = card.querySelector('.task-list-select').value;
                const dueDate = card.querySelector('.task-due-date').value;
                
                if (!title) return;
                
                // 收集步骤
                const stepInputs = card.querySelectorAll('.step-item input');
                const steps = Array.from(stepInputs)
                    .map(input => input.value.trim())
                    .filter(step => step.length > 0);
                
                tasks.push({ title, listId, dueDate, steps });
            });
            
            if (tasks.length === 0) {
                this.showToast('请至少填写一个任务标题');
                return;
            }
            
            // 创建任务
            for (const task of tasks) {
                const taskData = {
                    list_id: task.listId,
                    title: task.title,
                    due_date: task.dueDate || null
                };
                
                const result = await API.createTask(taskData);
                const taskId = result.data.id;
                
                // 创建步骤
                for (const stepTitle of task.steps) {
                    await API.createStep({ task_id: taskId, title: stepTitle });
                }
            }
            
            this.showToast(`成功导入 ${tasks.length} 个任务`);
            document.getElementById('ai-import-modal').style.display = 'none';
            
            // 刷新任务列表
            await this.loadTasks();
            await this.loadLists();
            this.scheduleCountUpdate();
            
        } catch (error) {
            console.error('导入任务失败:', error);
            this.showToast('导入失败');
        }
    }
};

// 页面加载完成后初始化应用
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
