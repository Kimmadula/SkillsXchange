export class TaskManager {
    constructor(tradeId, userId, echo) {
        this.tradeId = tradeId;
        this.userId = userId;
        this.echo = echo;

        // DOM elements
        this.addTaskBtn = document.querySelector('.add-task-btn');
        this.myTasksList = document.getElementById('my-tasks');
        this.partnerTasksList = document.getElementById('partner-tasks');
        this.addTaskModal = document.getElementById('add-task-modal');
        this.editTaskModal = document.getElementById('edit-task-modal');
        this.addTaskForm = document.getElementById('add-task-form');
        this.editTaskForm = document.getElementById('edit-task-form');

        // CSRF token
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                        document.querySelector('[name="csrf-token"]')?.content ||
                        document.querySelector('input[name="_token"]')?.value;
    }

    initialize() {
        this.setupEventListeners();
        this.setupEchoListeners();
        this.updateProgress();
        this.updateTaskCount();
    }

    setupEventListeners() {
        // Add task button
        if (this.addTaskBtn) {
            this.addTaskBtn.addEventListener('click', () => {
                this.showAddTaskModal();
            });
        }

        // Task checkbox toggles (using event delegation)
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('task-checkbox') || 
                (e.target.type === 'checkbox' && e.target.closest('.task-item'))) {
                const taskItem = e.target.closest('.task-item') || e.target.closest('.task-card');
                if (taskItem) {
                    const taskId = taskItem.dataset.taskId || taskItem.getAttribute('data-task-id');
                    if (taskId) {
                        this.toggleTask(parseInt(taskId));
                    }
                }
            }
        });

        // Edit task buttons (using event delegation)
        document.addEventListener('click', (e) => {
            if (e.target.closest('[onclick*="editTask"]') || 
                (e.target.textContent.includes('Edit') && e.target.closest('.task-item'))) {
                const taskItem = e.target.closest('.task-item') || e.target.closest('.task-card');
                if (taskItem) {
                    const taskId = taskItem.dataset.taskId || taskItem.getAttribute('data-task-id');
                    if (taskId) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.editTask(parseInt(taskId));
                    }
                }
            }
        });

        // Delete task buttons (using event delegation)
        document.addEventListener('click', (e) => {
            if (e.target.closest('[onclick*="deleteTask"]') || 
                (e.target.textContent.includes('Delete') && e.target.closest('.task-item'))) {
                const taskItem = e.target.closest('.task-item') || e.target.closest('.task-card');
                if (taskItem) {
                    const taskId = taskItem.dataset.taskId || taskItem.getAttribute('data-task-id');
                    if (taskId) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.deleteTask(parseInt(taskId));
                    }
                }
            }
        });

        // Modal close handlers
        if (this.addTaskModal) {
            this.addTaskModal.addEventListener('click', (e) => {
                if (e.target.id === 'add-task-modal') {
                    this.hideAddTaskModal();
                }
            });
        }

        if (this.editTaskModal) {
            this.editTaskModal.addEventListener('click', (e) => {
                if (e.target.id === 'edit-task-modal') {
                    this.hideEditTaskModal();
                }
            });
        }

        // Form submissions
        if (this.addTaskForm) {
            this.addTaskForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleAddTaskSubmit(e);
            });
        }

        if (this.editTaskForm) {
            this.editTaskForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleEditTaskSubmit(e);
            });
        }

        // Submission requirements toggle
        const requiresSubmission = document.getElementById('requires-submission');
        if (requiresSubmission) {
            requiresSubmission.addEventListener('change', (e) => {
                const submissionOptions = document.getElementById('submission-options');
                if (submissionOptions) {
                    submissionOptions.style.display = e.target.checked ? 'block' : 'none';
                }
            });
        }

        const editRequiresSubmission = document.getElementById('edit-requires-submission');
        if (editRequiresSubmission) {
            editRequiresSubmission.addEventListener('change', (e) => {
                const submissionOptions = document.getElementById('edit-submission-options');
                if (submissionOptions) {
                    submissionOptions.style.display = e.target.checked ? 'block' : 'none';
                }
            });
        }

        // Make functions globally available for compatibility
        window.toggleTask = (taskId) => this.toggleTask(taskId);
        window.editTask = (taskId) => this.editTask(taskId);
        window.deleteTask = (taskId) => this.deleteTask(taskId);
        window.showAddTaskModal = () => this.showAddTaskModal();
        window.hideAddTaskModal = () => this.hideAddTaskModal();
        window.showEditTaskModal = () => this.showEditTaskModal();
        window.hideEditTaskModal = () => this.hideEditTaskModal();
        window.handleEditTaskModalClick = (event) => this.handleEditTaskModalClick(event);
        window.addTaskToUI = (task) => this.addTaskToUI(task);
        window.removeTaskFromUI = (taskId) => this.removeTaskFromUI(taskId);
        window.updateTaskInUI = (task) => this.updateTaskInUI(task);
    }

    setupEchoListeners() {
        if (!this.echo) {
            console.warn('Laravel Echo not available for task updates');
            return;
        }

        this.echo.channel(`trade-${this.tradeId}`)
            .listen('task-created', (data) => {
                console.log('✅ Received task created event:', data);
                this.addTaskToUI(data.task);
                this.updateTaskCount();
                this.updateProgress();
                this.updateTaskCountBadge();
                this.showNotification(`New task created: ${data.task.title}`, 'success');
            })
            .listen('task-updated', (data) => {
                console.log('✅ Received task update event:', data);
                this.updateTaskInUI(data.task);
                this.updateProgress();
            })
            .listen('task-deleted', (data) => {
                console.log('Received task deleted event:', data);
                this.removeTaskFromUI(data.task_id);
                this.updateTaskCount();
                this.updateProgress();
                this.updateTaskCountBadge();
                this.showNotification('A task has been deleted', 'info');
            });
    }

    async toggleTask(taskId) {
        try {
            const response = await fetch(`/chat/task/${taskId}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateTaskInUI(data.task);
                this.updateProgress();
                
                // Refresh skill learning status if function exists
                if (typeof loadSkillLearningStatus === 'function') {
                    loadSkillLearningStatus();
                }
            } else {
                throw new Error(data.error || 'Failed to toggle task');
            }
        } catch (error) {
            console.error('Toggle task error:', error);
            this.showError('Failed to update task');
        }
    }

    async createTask(taskData) {
        try {
            // Get route from data attribute or use default
            const route = document.querySelector('[data-create-task-route]')?.getAttribute('data-create-task-route') ||
                         `/chat/${this.tradeId}/task`;

            const response = await fetch(route, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(taskData)
            });

            // Check if response is ok
            if (!response.ok) {
                const errorText = await response.text();
                let errorMessage = `Server error: ${response.status}`;
                try {
                    const errorData = JSON.parse(errorText);
                    errorMessage = errorData.error || errorData.message || errorMessage;
                } catch (e) {
                    errorMessage = errorText || errorMessage;
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();

            if (data.success) {
                this.addTaskToUI(data.task);
                this.updateTaskCount();
                this.updateProgress();
                this.hideAddTaskModal();
                this.clearTaskForm();
                this.showSuccess('Task created successfully!');
            } else {
                throw new Error(data.error || 'Failed to create task');
            }
        } catch (error) {
            console.error('Create task error:', error);
            this.showError('Failed to create task: ' + error.message);
        }
    }

    async editTask(taskId) {
        try {
            const response = await fetch(`/tasks/${taskId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showEditTaskModal(data.task);
            } else {
                throw new Error(data.error || 'Failed to load task details');
            }
        } catch (error) {
            console.error('Edit task error:', error);
            this.showError('Failed to load task details: ' + error.message);
        }
    }

    async updateTask(taskId, taskData) {
        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('_token', this.csrfToken);
            formData.append('title', taskData.title);
            formData.append('description', taskData.description || '');
            formData.append('priority', taskData.priority || 'medium');
            
            if (taskData.due_date && taskData.due_date.trim() !== '') {
                formData.append('due_date', taskData.due_date);
            }
            
            formData.append('requires_submission', taskData.requires_submission ? '1' : '0');
            formData.append('submission_instructions', taskData.submission_instructions || '');
            
            // Add file types
            if (taskData.allowed_file_types) {
                taskData.allowed_file_types.forEach(type => {
                    formData.append('allowed_file_types[]', type);
                });
            }

            const response = await fetch(`/tasks/${taskId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                credentials: 'same-origin'
            });

            if (response.status === 429) {
                this.showError('Too many requests. Please slow down and try again in a moment.');
                return;
            }

            const data = await response.json();

            if (data && data.success) {
                this.hideEditTaskModal();
                this.showSuccess('Task updated successfully!');
                // Reload to show updated task
                location.reload();
            } else if (data) {
                if (data.errors) {
                    this.showError('Validation failed: ' + JSON.stringify(data.errors));
                } else {
                    this.showError('Failed to update task: ' + (data.error || 'Unknown error'));
                }
            }
        } catch (error) {
            console.error('Update task error:', error);
            this.showError('Failed to update task. Please try again.');
        }
    }

    async deleteTask(taskId) {
        if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`/tasks/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.removeTaskFromUI(taskId);
                this.updateTaskCount();
                this.updateProgress();
                this.updateTaskCountBadge();
                this.showSuccess('Task deleted successfully!');
            } else {
                throw new Error(data.error || 'Failed to delete task');
            }
        } catch (error) {
            console.error('Delete task error:', error);
            this.showError('Failed to delete task: ' + error.message);
        }
    }

    handleAddTaskSubmit(e) {
        const form = e.target;
        const title = form.querySelector('#task-title')?.value;
        const description = form.querySelector('#task-description')?.value;
		// Hidden assignee field may be placed outside the <form>, so fall back to a global lookup
		let assignedTo = form.querySelector('#task-assignee')?.value;
		if (!assignedTo) {
			assignedTo = document.getElementById('task-assignee')?.value;
		}
        const priority = form.querySelector('#task-priority')?.value || 'medium';
        const dueDate = form.querySelector('#task-due-date')?.value;
        const requiresSubmission = form.querySelector('#requires-submission')?.checked || false;
        const submissionInstructions = form.querySelector('#submission-instructions')?.value || '';

        // Get selected file types
        const fileTypeCheckboxes = form.querySelectorAll('input[name="file-types"]:checked');
        const selectedFileTypes = Array.from(fileTypeCheckboxes).map(cb => cb.value);

        // Validate file types if submission is required
        if (requiresSubmission && selectedFileTypes.length === 0) {
            this.showError('Please select at least one file type when requiring submission.');
            return;
        }

		// Validate assignee
		if (!assignedTo) {
			this.showError('The assigned to field is required.');
			return;
		}

        this.createTask({
            title,
            description,
            assigned_to: assignedTo,
            priority,
            due_date: dueDate,
            requires_submission: requiresSubmission,
            allowed_file_types: selectedFileTypes,
            submission_instructions: submissionInstructions
        });
    }

    handleEditTaskSubmit(e) {
        const form = e.target;
        const taskId = form.querySelector('#edit-task-id')?.value;
        const title = form.querySelector('#edit-task-title')?.value;
        const description = form.querySelector('#edit-task-description')?.value;
        const priority = form.querySelector('#edit-task-priority')?.value || 'medium';
        const dueDate = form.querySelector('#edit-task-due-date')?.value;
        const requiresSubmission = form.querySelector('#edit-requires-submission')?.checked || false;
        const submissionInstructions = form.querySelector('#edit-submission-instructions')?.value || '';

        // Get selected file types
        const fileTypeCheckboxes = form.querySelectorAll('input[name="edit-file-types"]:checked');
        const selectedFileTypes = Array.from(fileTypeCheckboxes).map(cb => cb.value);

        // Validate file types if submission is required
        if (requiresSubmission && selectedFileTypes.length === 0) {
            this.showError('Please select at least one file type when requiring submission.');
            return;
        }

        this.updateTask(parseInt(taskId), {
            title,
            description,
            priority,
            due_date: dueDate,
            requires_submission: requiresSubmission,
            allowed_file_types: selectedFileTypes,
            submission_instructions: submissionInstructions
        });
    }

    addTaskToUI(task) {
        // Determine which container to add the task to based on who it's assigned to
        const isAssignedToMe = task.assigned_to == this.userId;
        const isCreatedByMe = task.created_by == this.userId;
        const container = isAssignedToMe ? this.myTasksList : this.partnerTasksList;

        if (!container) {
            console.error('Task container not found');
            return;
        }

        const taskDiv = document.createElement('div');
        taskDiv.className = 'task-item';
        taskDiv.setAttribute('data-task-id', task.id);
        taskDiv.style.cursor = 'pointer';
        taskDiv.addEventListener('click', () => {
            if (typeof window.showTaskDetails === 'function') {
                window.showTaskDetails(task.id);
            }
        });

        const checkboxHtml = isAssignedToMe
            ? `<input type="checkbox" class="task-checkbox" ${task.completed ? 'checked' : ''} onchange="event.stopPropagation(); toggleTask(${task.id})">`
            : `<input type="checkbox" class="task-checkbox" ${task.completed ? 'checked' : ''} disabled onclick="event.stopPropagation()">`;

        // Priority badge
        let priorityBadge = '';
        if (task.priority) {
            priorityBadge = `<span class="task-tag tag-priority-${task.priority}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)} Priority</span>`;
        }

        // Status badge
        let statusBadge = '';
        if (task.current_status) {
            const statusText = task.current_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            statusBadge = `<span class="task-tag tag-status">${statusText}</span>`;
        }

        // Edit/Delete buttons for creators (only if no submissions exist)
        let actionButtons = '';
        if (isCreatedByMe && !task.has_submission) {
            actionButtons = `
                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;" onclick="event.stopPropagation()">
                    <button onclick="editTask(${task.id})" title="Edit Task"
                            style="background:#3b82f6;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                        Edit
                    </button>
                    <button onclick="deleteTask(${task.id})" title="Delete Task"
                            style="background:#ef4444;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                        Delete
                    </button>
                </div>
            `;
        }

        taskDiv.innerHTML = `
            <div class="task-header">
                ${checkboxHtml}
                <div class="task-content">
                    <div class="task-title">${this.escapeHtml(task.title)}</div>
                    <div class="task-meta">
                        ${priorityBadge}
                        ${statusBadge}
                    </div>
                </div>
                ${actionButtons}
            </div>
        `;

        // Remove the "No tasks" message if it exists
        const noTasksMessage = container.querySelector('div[style*="text-align: center"]');
        if (noTasksMessage) {
            noTasksMessage.remove();
        }

        container.appendChild(taskDiv);
    }

    updateTaskInUI(task) {
        const taskElement = document.querySelector(`[data-task-id="${task.id}"]`);
        if (!taskElement) {
            // Task might not exist in UI yet, try adding it
            this.addTaskToUI(task);
            return;
        }

        const checkbox = taskElement.querySelector('input[type="checkbox"]');
        const title = taskElement.querySelector('span[style*="font-weight: 500"]') || 
                     taskElement.querySelector('.task-title') ||
                     taskElement.querySelector('span');

        if (checkbox) {
            checkbox.checked = task.completed;
        }

        if (title) {
            if (task.completed) {
                title.style.textDecoration = 'line-through';
                title.style.color = '#6b7280';
            } else {
                title.style.textDecoration = 'none';
                title.style.color = '';
            }
        }

        // Update status badge if it exists
        if (task.current_status) {
            const statusColors = {
                'assigned': '#6b7280',
                'in_progress': '#f59e0b',
                'submitted': '#3b82f6',
                'completed': '#10b981'
            };
            const statusColor = statusColors[task.current_status] || '#6b7280';
            const statusText = task.current_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            let statusBadge = taskElement.querySelector('span[style*="background"]');
            if (!statusBadge) {
                // Create status badge if it doesn't exist
                const badgeContainer = taskElement.querySelector('div[style*="display: flex"]');
                if (badgeContainer) {
                    statusBadge = document.createElement('span');
                    statusBadge.style.cssText = `background: ${statusColor}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;`;
                    statusBadge.textContent = statusText;
                    badgeContainer.appendChild(statusBadge);
                }
            } else {
                statusBadge.style.background = statusColor;
                statusBadge.textContent = statusText;
            }
        }
    }

    removeTaskFromUI(taskId) {
        const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);
        if (taskElement) {
            taskElement.remove();
            console.log('Task removed from UI:', taskId);
        }
    }

    updateProgress() {
        // Recalculate progress without reloading
        const myTasks = this.myTasksList ? this.myTasksList.querySelectorAll('.task-item') : [];
        const myCompletedTasks = this.myTasksList ? this.myTasksList.querySelectorAll('.task-item input[type="checkbox"]:checked') : [];
        const myProgress = myTasks.length > 0 ? (myCompletedTasks.length / myTasks.length) * 100 : 0;

        const partnerTasks = this.partnerTasksList ? this.partnerTasksList.querySelectorAll('.task-item') : [];
        const partnerCompletedTasks = this.partnerTasksList ? this.partnerTasksList.querySelectorAll('.task-item input[type="checkbox"]:checked') : [];
        const partnerProgress = partnerTasks.length > 0 ? (partnerCompletedTasks.length / partnerTasks.length) * 100 : 0;

        // Update progress bars using the new design structure
        this.updateProgressUI('my-tasks', myProgress);
        this.updateProgressUI('partner-tasks', partnerProgress);
    }

    updateProgressUI(tab, percentage) {
        const tabContent = document.querySelector(`[data-content="${tab}"]`);
        if (!tabContent) return;

        const percentageElement = tabContent.querySelector('.progress-percentage');
        const progressFill = tabContent.querySelector('.progress-fill');

        if (percentageElement) {
            percentageElement.textContent = `${Math.round(percentage)}%`;
        }

        if (progressFill) {
            progressFill.style.width = `${percentage}%`;
        }
    }

    updateTaskCount() {
        const myTasks = this.myTasksList ? this.myTasksList.querySelectorAll('.task-item').length : 0;
        const partnerTasks = this.partnerTasksList ? this.partnerTasksList.querySelectorAll('.task-item').length : 0;
        const totalTasks = myTasks + partnerTasks;

        const taskCountElement = document.getElementById('task-count');
        if (taskCountElement) {
            taskCountElement.textContent = totalTasks;

            // Update color based on task count
            if (totalTasks === 0) {
                taskCountElement.style.color = '#ef4444'; // Red for no tasks
            } else if (totalTasks < 3) {
                taskCountElement.style.color = '#f59e0b'; // Orange for few tasks
            } else {
                taskCountElement.style.color = '#10b981'; // Green for good task count
            }
        }

        // Update tab button counts
        const myTasksTab = document.querySelector('[data-tab="my-tasks"]');
        const partnerTasksTab = document.querySelector('[data-tab="partner-tasks"]');
        
        if (myTasksTab) {
            myTasksTab.textContent = `My Tasks (${myTasks})`;
        }
        if (partnerTasksTab) {
            partnerTasksTab.textContent = `Partner (${partnerTasks})`;
        }
    }

    updateTaskCountBadge() {
        // Update badge in header if it exists
        const badge = document.querySelector('.icon-btn .badge');
        if (badge) {
            const myTasks = this.myTasksList ? this.myTasksList.querySelectorAll('.task-item').length : 0;
            const partnerTasks = this.partnerTasksList ? this.partnerTasksList.querySelectorAll('.task-item').length : 0;
            badge.textContent = myTasks + partnerTasks;
        }
    }

    showAddTaskModal() {
        if (this.addTaskModal) {
            this.addTaskModal.style.display = 'flex';
            // Clear form when opening
            if (this.addTaskForm) {
                this.addTaskForm.reset();
                const submissionOptions = document.getElementById('submission-options');
                if (submissionOptions) {
                    submissionOptions.style.display = 'none';
                }
            }
        }
    }

    hideAddTaskModal() {
        if (this.addTaskModal) {
            this.addTaskModal.style.display = 'none';
            // Clear form when closing
            if (this.addTaskForm) {
                this.addTaskForm.reset();
            }
        }
    }

    showEditTaskModal(task) {
        if (!this.editTaskModal) return;

        // Populate form fields
        const editTaskId = document.getElementById('edit-task-id');
        const editTaskTitle = document.getElementById('edit-task-title');
        const editTaskDescription = document.getElementById('edit-task-description');
        const editTaskPriority = document.getElementById('edit-task-priority');
        const editTaskDueDate = document.getElementById('edit-task-due-date');
        const editRequiresSubmission = document.getElementById('edit-requires-submission');
        const editSubmissionInstructions = document.getElementById('edit-submission-instructions');
        const editSubmissionOptions = document.getElementById('edit-submission-options');

        if (editTaskId) editTaskId.value = task.id;
        if (editTaskTitle) editTaskTitle.value = task.title;
        if (editTaskDescription) editTaskDescription.value = task.description || '';
        if (editTaskPriority) editTaskPriority.value = task.priority || 'medium';

        // Format due_date for date input (YYYY-MM-DD)
        let dueDateValue = '';
        if (task.due_date) {
            if (task.due_date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                dueDateValue = task.due_date;
            } else if (task.due_date.includes('T')) {
                dueDateValue = task.due_date.split('T')[0];
            } else {
                dueDateValue = task.due_date.split(' ')[0];
            }
        }
        if (editTaskDueDate) editTaskDueDate.value = dueDateValue;

        // Handle submission requirements
        const requiresSubmission = task.requires_submission;
        if (editRequiresSubmission) {
            editRequiresSubmission.checked = requiresSubmission;
        }
        if (editSubmissionOptions) {
            editSubmissionOptions.style.display = requiresSubmission ? 'block' : 'none';
        }
        if (editSubmissionInstructions) {
            editSubmissionInstructions.value = task.submission_instructions || '';
        }

        // Set file types
        const fileTypeCheckboxes = document.querySelectorAll('input[name="edit-file-types"]');
        fileTypeCheckboxes.forEach(cb => {
            cb.checked = task.allowed_file_types && task.allowed_file_types.includes(cb.value);
        });

        this.editTaskModal.style.display = 'flex';
    }

    hideEditTaskModal() {
        if (this.editTaskModal) {
            this.editTaskModal.style.display = 'none';
            if (this.editTaskForm) {
                this.editTaskForm.reset();
            }
        }
    }

    handleEditTaskModalClick(event) {
        // Close modal if clicking on the overlay (not the modal content)
        if (event.target === this.editTaskModal) {
            this.hideEditTaskModal();
        }
    }

    clearTaskForm() {
        const form = this.addTaskForm;
        if (!form) return;

        const title = form.querySelector('#task-title');
        const description = form.querySelector('#task-description');
        const priority = form.querySelector('#task-priority');
        const dueDate = form.querySelector('#task-due-date');
        const requiresSubmission = form.querySelector('#requires-submission');
        const submissionInstructions = form.querySelector('#submission-instructions');
        const submissionOptions = document.getElementById('submission-options');

        if (title) title.value = '';
        if (description) description.value = '';
        if (priority) priority.value = 'medium';
        if (dueDate) dueDate.value = '';
        if (requiresSubmission) requiresSubmission.checked = false;
        if (submissionInstructions) submissionInstructions.value = '';
        if (submissionOptions) submissionOptions.style.display = 'none';

        // Clear file type checkboxes
        const fileTypeCheckboxes = form.querySelectorAll('input[name="file-types"]');
        fileTypeCheckboxes.forEach(cb => cb.checked = false);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showError(message) {
        console.error('Error:', message);
        
        // Try to use existing showError function
        if (typeof window.showError === 'function') {
            window.showError(message);
            return;
        }

        // Fallback to alert
        alert(message);
    }

    showSuccess(message) {
        // Try to use existing showSuccess function
        if (typeof window.showSuccess === 'function') {
            window.showSuccess(message);
            return;
        }

        // Fallback to console
        console.log('Success:', message);
    }

    showNotification(message, type = 'info') {
        // Try to use existing showNotification function
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }

        // Fallback to console
        console.log(`[${type}] ${message}`);
    }
}
