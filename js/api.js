/**
 * API 封装
 * 使用相对路径调用后端 API
 */

const API = {
    // 基础 URL - 使用相对路径
    baseURL: './api.php',

    /**
     * 通用请求方法
     */
    async request(action, options = {}) {
        const { method = 'GET', data = null, params = {} } = options;
        
        // 构建 URL
        let url = `${this.baseURL}?action=${action}`;
        for (const key in params) {
            url += `&${key}=${encodeURIComponent(params[key])}`;
        }

        // 构建请求配置
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        // 添加请求体
        if (data && (method === 'POST' || method === 'PUT')) {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, config);
            
            // 尝试解析 JSON
            let result;
            try {
                result = await response.json();
            } catch (e) {
                // JSON 解析失败，可能是 PHP 错误
                throw new Error(`服务器错误 (${response.status}): 请检查 PHP 配置和错误日志`);
            }
            
            if (!result.success) {
                // 如果是 SQLite3 未启用错误，显示详细信息
                if (result.error === 'SQLITE3_NOT_ENABLED') {
                    const solution = result.solution ? '\n\n解决方法：\n' + result.solution.join('\n') : '';
                    throw new Error(result.message + solution);
                }
                throw new Error(result.message || '请求失败');
            }
            
            return result;
        } catch (error) {
            console.error('API请求错误:', error);
            throw error;
        }
    },

    // ========== 列表相关 ==========
    
    /**
     * 获取所有列表
     */
    async getLists() {
        return await this.request('lists');
    },

    /**
     * 获取单个列表
     */
    async getList(id) {
        return await this.request('lists', { params: { id } });
    },

    /**
     * 创建列表
     */
    async createList(data) {
        return await this.request('lists', { method: 'POST', data });
    },

    /**
     * 更新列表
     */
    async updateList(id, data) {
        return await this.request('lists', { method: 'PUT', params: { id }, data });
    },

    /**
     * 删除列表
     */
    async deleteList(id) {
        return await this.request('lists', { method: 'DELETE', params: { id } });
    },

    // ========== 任务相关 ==========

    /**
     * 获取任务
     * @param {Object} options - 查询选项
     * @param {string} options.view - 视图类型 (my-day, important, planned)
     * @param {number} options.list_id - 列表ID
     */
    async getTasks(options = {}) {
        return await this.request('tasks', { params: options });
    },

    /**
     * 获取单个任务
     */
    async getTask(id) {
        return await this.request('tasks', { params: { id } });
    },

    /**
     * 创建任务
     */
    async createTask(data) {
        return await this.request('tasks', { method: 'POST', data });
    },

    /**
     * 更新任务
     */
    async updateTask(id, data) {
        return await this.request('tasks', { method: 'PUT', params: { id }, data });
    },

    /**
     * 删除任务
     */
    async deleteTask(id) {
        return await this.request('tasks', { method: 'DELETE', params: { id } });
    },

    /**
     * 切换任务完成状态
     */
    async toggleTaskComplete(id, isCompleted) {
        return await this.updateTask(id, { is_completed: isCompleted ? 1 : 0 });
    },

    /**
     * 切换任务重要状态
     */
    async toggleTaskImportant(id, isImportant) {
        return await this.updateTask(id, { is_important: isImportant ? 1 : 0 });
    },

    /**
     * 切换任务"我的一天"状态
     */
    async toggleTaskMyDay(id, isMyDay) {
        return await this.updateTask(id, { is_my_day: isMyDay ? 1 : 0 });
    },

    // ========== 步骤相关 ==========

    /**
     * 获取任务的步骤
     */
    async getSteps(taskId) {
        return await this.request('steps', { params: { task_id: taskId } });
    },

    /**
     * 创建步骤
     */
    async createStep(data) {
        return await this.request('steps', { method: 'POST', data });
    },

    /**
     * 更新步骤
     */
    async updateStep(id, data) {
        return await this.request('steps', { method: 'PUT', params: { id }, data });
    },

    /**
     * 删除步骤
     */
    async deleteStep(id) {
        return await this.request('steps', { method: 'DELETE', params: { id } });
    },

    /**
     * 切换步骤完成状态
     */
    async toggleStepComplete(id, isCompleted) {
        return await this.updateStep(id, { is_completed: isCompleted ? 1 : 0 });
    },
    
    // ========== AI相关 ==========
    
    /**
     * 获取AI模型列表
     */
    async getAIModels() {
        return await this.request('ai_models');
    },
    
    /**
     * 创建AI模型
     */
    async createAIModel(data) {
        return await this.request('ai_models', { method: 'POST', data });
    },
    
    /**
     * 更新AI模型
     */
    async updateAIModel(id, data) {
        return await this.request('ai_models', { method: 'PUT', params: { id }, data });
    },
    
    /**
     * 删除AI模型
     */
    async deleteAIModel(id) {
        return await this.request('ai_models', { method: 'DELETE', params: { id } });
    },
    
    /**
     * 设置激活AI模型
     */
    async setActiveAIModel(id) {
        return await this.request('set_active_ai_model', { method: 'POST', data: { id } });
    },
    
    /**
     * AI识别任务
     */
    async aiParseTasks(data) {
        return await this.request('ai_parse_tasks', { method: 'POST', data });
    }
};
