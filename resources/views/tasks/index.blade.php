@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 fw-bold text-dark mb-2">My Tasks</h1>
                <p class="text-muted">Manage your tasks and track progress</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Task
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-tasks text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">Total Tasks</p>
                                <h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">Completed</p>
                                <h3 class="fw-bold mb-0">{{ $stats['completed'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">Pending</p>
                                <h3 class="fw-bold mb-0">{{ $stats['pending'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-shield-check text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">Verified</p>
                                <h3 class="fw-bold mb-0">{{ $stats['verified'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks List -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">All Tasks</h5>
            </div>
            <div class="card-body p-0">
                @if($myTasks->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($myTasks as $task)
                    <div class="list-group-item border-0">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-3">
                                <div class="form-check">
                                    <input class="form-check-input task-checkbox" type="checkbox" {{ $task->completed ?
                                    'checked' : '' }}
                                    data-task-id="{{ $task->id }}"
                                    {{ $task->assigned_to !== Auth::id() ? 'disabled' : '' }}>
                                </div>
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6
                                            class="mb-1 {{ $task->completed ? 'text-decoration-line-through text-muted' : '' }}">
                                            {{ $task->title }}
                                        </h6>
                                        @if($task->description)
                                        <p class="text-muted small mb-2">{{ $task->description }}</p>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Priority Badge -->
                                        <span class="badge
                                                @if($task->priority === 'high') bg-danger
                                                @elseif($task->priority === 'medium') bg-warning
                                                @else bg-success
                                                @endif">
                                            {{ ucfirst($task->priority) }}
                                        </span>

                                        <!-- Status Badge -->
                                        @if($task->completed)
                                        <span class="badge bg-success">Completed</span>
                                        @elseif($task->verified)
                                        <span class="badge bg-info">Verified</span>
                                        @else
                                        <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small text-muted">
                                        <div>
                                            <strong>Trade:</strong> {{ $task->trade->offeringSkill->name }} ↔ {{
                                            $task->trade->lookingSkill->name }}
                                        </div>
                                        <div>
                                            <strong>Assigned to:</strong> {{ $task->assignee->firstname }} {{
                                            $task->assignee->lastname }}
                                        </div>
                                        @if($task->due_date)
                                        <div>
                                            <strong>Due:</strong> {{ $task->due_date->format('M d, Y') }}
                                        </div>
                                        @endif
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="{{ route('tasks.show', $task) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($task->created_by === Auth::id())
                                        <a href="{{ route('tasks.edit', $task) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this task?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-white border-top">
                    {{ $myTasks->links() }}
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-tasks text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No tasks found</h5>
                    <p class="text-muted">You don't have any tasks yet. Create your first task to get started!</p>
                    <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Your First Task
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    // Task toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.task-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.disabled) return;

            const taskId = this.dataset.taskId;
            const isCompleted = this.checked;

            fetch(`/tasks/${taskId}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI based on completion status
                    const taskRow = this.closest('.list-group-item');
                    const title = taskRow.querySelector('h6');
                    const statusBadge = taskRow.querySelector('.badge');

                    if (isCompleted) {
                        title.classList.add('text-decoration-line-through', 'text-muted');
                        statusBadge.textContent = 'Completed';
                        statusBadge.className = 'badge bg-success';
                    } else {
                        title.classList.remove('text-decoration-line-through', 'text-muted');
                        statusBadge.textContent = 'Pending';
                        statusBadge.className = 'badge bg-warning';
                    }
                } else {
                    // Revert checkbox if request failed
                    this.checked = !isCompleted;
                    alert('Failed to update task: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isCompleted;
                alert('Failed to update task. Please try again.');
            });
        });
    });
});
</script>
@endsection
