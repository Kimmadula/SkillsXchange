<aside class="tasks-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-title-container">
            <span class="sidebar-icon">☑️</span>
            <h2 class="sidebar-title">Session Tasks</h2>
        </div>
        <div class="tabs-container">
            <button class="tab-btn active" data-tab="my-tasks">My Tasks ({{ $myTasks->count() }})</button>
            <button class="tab-btn" data-tab="partner-tasks">Partner ({{ $partnerTasks->count() }})</button>
        </div>
    </div>

    <div class="tasks-content">
        <!-- My Tasks Tab -->
        <div class="tab-content" data-content="my-tasks">
            @php
                $myProgressValue = max(0, min(100, round($myProgress ?? 0)));
            @endphp
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ $myProgressValue }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress-width: {{ $myProgressValue }}%; width: var(--progress-width);"></div>
                </div>
            </div>
            
            <div class="task-list" id="my-tasks">
                @foreach($myTasks as $task)
                    <div class="task-item" data-task-id="{{ $task->id }}" onclick="showTaskDetails({{ $task->id }})" style="cursor: pointer;">
                        <div class="task-header">
                            <input type="checkbox" class="task-checkbox" 
                                {{ $task->completed ? 'checked' : '' }}
                                onclick="event.stopPropagation(); toggleTask({{ $task->id }})">
                            <div class="task-content">
                                <div class="task-title">{{ $task->title }}</div>
                                <div class="task-meta">
                                    @if($task->priority)
                                        <span class="task-tag tag-priority-{{ $task->priority }}">
                                            {{ ucfirst($task->priority) }} Priority
                                        </span>
                                    @endif
                                    @if($task->current_status)
                                        <span class="task-tag tag-status">
                                            {{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if(Auth::id() === $task->created_by)
                                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;" onclick="event.stopPropagation()">
                                    <button onclick="editTask({{ $task->id }})" title="Edit Task"
                                            style="background:#3b82f6;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Edit
                                    </button>
                                    <button onclick="deleteTask({{ $task->id }})" title="Delete Task"
                                            style="background:#ef4444;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Delete
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Partner Tasks Tab -->
        <div class="tab-content" data-content="partner-tasks" style="display: none;">
            @php
                $partnerProgressValue = max(0, min(100, round($partnerProgress ?? 0)));
            @endphp
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ $partnerProgressValue }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress-width: {{ $partnerProgressValue }}%; width: var(--progress-width);"></div>
                </div>
            </div>
            
            <div class="task-list" id="partner-tasks">
                @foreach($partnerTasks as $task)
                    <div class="task-item" data-task-id="{{ $task->id }}" onclick="showTaskDetails({{ $task->id }})" style="cursor: pointer;">
                        <div class="task-header">
                            <input type="checkbox" class="task-checkbox" 
                                {{ $task->completed ? 'checked' : '' }}
                                disabled
                                onclick="event.stopPropagation()">
                            <div class="task-content">
                                <div class="task-title">{{ $task->title }}</div>
                                <div class="task-meta">
                                    @if($task->priority)
                                        <span class="task-tag tag-priority-{{ $task->priority }}">
                                            {{ ucfirst($task->priority) }} Priority
                                        </span>
                                    @endif
                                    @if($task->current_status)
                                        <span class="task-tag tag-status">
                                            {{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if(Auth::id() === $task->created_by)
                                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;" onclick="event.stopPropagation()">
                                    <button onclick="editTask({{ $task->id }})" title="Edit Task"
                                            style="background:#3b82f6;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Edit
                                    </button>
                                    <button onclick="deleteTask({{ $task->id }})" title="Delete Task"
                                            style="background:#ef4444;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Delete
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="sidebar-footer">
        <button class="add-task-btn" onclick="showAddTaskModal()">
            <span class="plus-icon">+</span>
            Add Task
        </button>
    </div>

    <div id="taskDetailsModal" class="modal-overlay" style="display: none;" onclick="closeTaskDetails(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Task Details</h3>
            <button class="modal-close" onclick="closeTaskDetails()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-group">
                <label class="detail-label">Title</label>
                <div class="detail-value" id="detailTitle"></div>
            </div>
            <div class="detail-group">
                <label class="detail-label">Description</label>
                <div class="detail-value" id="detailDescription"></div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Priority</label>
                    <div class="detail-value" id="detailPriority"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Status</label>
                    <div class="detail-value" id="detailStatus"></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Required File Types</label>
                    <div class="detail-value" id="detailFileTypes"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Created By</label>
                    <div class="detail-value" id="detailCreatedBy"></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Due Date</label>
                    <div class="detail-value" id="detailDueDate"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Completed</label>
                    <div class="detail-value" id="detailCompleted"></div>
                </div>
            </div>
            <div class="detail-group">
                <label class="detail-label">Created At</label>
                <div class="detail-value" id="detailCreatedAt"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
        </div>
    </div>
</div>

<script>
// Function to show task details
function showTaskDetails(taskId) {
    // Fetch task details via AJAX
    fetch(`/tasks/${taskId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.task) {
                throw new Error(data.error || 'Failed to load task details');
            }
            
            const task = data.task;
            
            // Populate modal with task data
            document.getElementById('detailTitle').textContent = task.title || 'N/A';
            document.getElementById('detailDescription').textContent = task.description || 'No description provided';
            
            // Priority with color badge
            const priorityEl = document.getElementById('detailPriority');
            if (task.priority) {
                priorityEl.innerHTML = `<span class="task-tag tag-priority-${task.priority}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}</span>`;
            } else {
                priorityEl.textContent = 'Not set';
            }
            
            // Status with badge
            const statusEl = document.getElementById('detailStatus');
            if (task.current_status) {
                const statusText = task.current_status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                statusEl.innerHTML = `<span class="task-tag tag-status">${statusText}</span>`;
            } else {
                statusEl.textContent = 'Not set';
            }
            
            // File types
            const fileTypesEl = document.getElementById('detailFileTypes');
            if (task.allowed_file_types && task.allowed_file_types.length > 0) {
                fileTypesEl.textContent = task.allowed_file_types.join(', ');
            } else {
                fileTypesEl.textContent = 'Any file type';
            }
            
            // Created by (from loaded relationship or fallback)
            const createdByEl = document.getElementById('detailCreatedBy');
            if (task.creator && task.creator.firstname) {
                createdByEl.textContent = `${task.creator.firstname || ''} ${task.creator.lastname || ''}`.trim() || 'Unknown';
            } else {
                createdByEl.textContent = 'Unknown';
            }
            
            // Assigned to (if element exists)
            const assignedToEl = document.getElementById('detailAssignedTo');
            if (assignedToEl) {
                if (task.assignee && task.assignee.firstname) {
                    assignedToEl.textContent = `${task.assignee.firstname || ''} ${task.assignee.lastname || ''}`.trim() || 'Unassigned';
                } else {
                    assignedToEl.textContent = 'Unassigned';
                }
            }
            
            document.getElementById('detailDueDate').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date';
            document.getElementById('detailCompleted').innerHTML = task.completed ? 
                '<span style="color: #10b981; font-weight: 600;">✓ Yes</span>' : 
                '<span style="color: #6b7280;">✗ No</span>';
            document.getElementById('detailCreatedAt').textContent = task.created_at ? new Date(task.created_at).toLocaleString() : 'Unknown';
            
            // Show modal
            document.getElementById('taskDetailsModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error fetching task details:', error);
            alert('Failed to load task details: ' + error.message);
        });
}

// Function to close modal
function closeTaskDetails(event) {
    if (!event || event.target.classList.contains('modal-overlay')) {
        document.getElementById('taskDetailsModal').style.display = 'none';
    }
}
</script>
</aside>
