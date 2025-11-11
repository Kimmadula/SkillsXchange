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
                    <div class="task-item" data-task-id="{{ $task->id }}">
                        <div class="task-header">
                            <input type="checkbox" class="task-checkbox" 
                                {{ $task->completed ? 'checked' : '' }}
                                onchange="toggleTask({{ $task->id }})">
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
                                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;">
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
                    <div class="task-item" data-task-id="{{ $task->id }}">
                        <div class="task-header">
                            <input type="checkbox" class="task-checkbox" 
                                {{ $task->completed ? 'checked' : '' }}
                                disabled>
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
                                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;">
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
</aside>
