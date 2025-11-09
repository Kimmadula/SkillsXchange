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
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ round($myProgress) }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $myProgress }}%"></div>
                </div>
            </div>
            
            <div class="task-list">
                @foreach($myTasks as $task)
                    <div class="task-card">
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
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Partner Tasks Tab -->
        <div class="tab-content" data-content="partner-tasks" style="display: none;">
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ round($partnerProgress) }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $partnerProgress }}%"></div>
                </div>
            </div>
            
            <div class="task-list">
                @foreach($partnerTasks as $task)
                    <div class="task-card">
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
