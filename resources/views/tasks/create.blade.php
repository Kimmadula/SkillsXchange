@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Header -->
                <div class="mb-4">
                    <h1 class="h2 fw-bold text-dark mb-2">Create New Task</h1>
                    <p class="text-muted">Add a new task to track progress in your skill trades</p>
                </div>

                <!-- Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('tasks.store') }}" method="POST">
                            @csrf

                            <!-- Trade Selection -->
                            <div class="mb-4">
                                <label for="trade_id" class="form-label fw-semibold">Select Trade <span
                                        class="text-danger">*</span></label>
                                <select name="trade_id" id="trade_id"
                                    class="form-select @error('trade_id') is-invalid @enderror" required>
                                    <option value="">Choose a trade...</option>
                                    @foreach($activeTrades as $trade)
                                    <option value="{{ $trade->id }}" {{ old('trade_id')==$trade->id ? 'selected' : ''
                                        }}>
                                        {{ $trade->offeringSkill->name }} ↔ {{ $trade->lookingSkill->name }}
                                        @if($trade->user_id === Auth::id())
                                        (You are offering {{ $trade->offeringSkill->name }})
                                        @else
                                        (You are learning {{ $trade->offeringSkill->name }})
                                        @endif
                                    </option>
                                    @endforeach
                                </select>
                                @error('trade_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Task Title -->
                            <div class="mb-4">
                                <label for="title" class="form-label fw-semibold">Task Title <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="title" id="title"
                                    class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}"
                                    placeholder="Enter task title..." required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Task Description -->
                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">Description</label>
                                <textarea name="description" id="description" rows="4"
                                    class="form-control @error('description') is-invalid @enderror"
                                    placeholder="Enter task description (optional)...">{{ old('description') }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Assign To -->
                            <div class="mb-4">
                                <label for="assigned_to" class="form-label fw-semibold">Assign To <span
                                        class="text-danger">*</span></label>
                                <select name="assigned_to" id="assigned_to"
                                    class="form-select @error('assigned_to') is-invalid @enderror" required>
                                    <option value="">Choose who to assign this task to...</option>
                                </select>
                                @error('assigned_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Priority -->
                            <div class="mb-4">
                                <label for="priority" class="form-label fw-semibold">Priority</label>
                                <select name="priority" id="priority"
                                    class="form-select @error('priority') is-invalid @enderror">
                                    <option value="low" {{ old('priority')=='low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', 'medium' )=='medium' ? 'selected' : '' }}>
                                        Medium</option>
                                    <option value="high" {{ old('priority')=='high' ? 'selected' : '' }}>High</option>
                                </select>
                                @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Due Date -->
                            <div class="mb-4">
                                <label for="due_date" class="form-label fw-semibold">Due Date</label>
                                <input type="date" name="due_date" id="due_date"
                                    class="form-control @error('due_date') is-invalid @enderror"
                                    value="{{ old('due_date') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Task
                                </button>
                                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const tradeSelect = document.getElementById('trade_id');
    const assignedToSelect = document.getElementById('assigned_to');
    
    // Update assignee options when trade is selected
    tradeSelect.addEventListener('change', function() {
        const tradeId = this.value;
        assignedToSelect.innerHTML = '<option value="">Choose who to assign this task to...</option>';
        
        if (tradeId) {
            // Fetch trade details and update assignee options
            fetch(`/api/trades/${tradeId}/participants`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.participants.forEach(participant => {
                            const option = document.createElement('option');
                            option.value = participant.id;
                            option.textContent = participant.name;
                            if (participant.id == {{ Auth::id() }}) {
                                option.textContent += ' (You)';
                            }
                            assignedToSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching trade participants:', error);
                    // Fallback: add current user and partner
                    const currentUser = document.createElement('option');
                    currentUser.value = {{ Auth::id() }};
                    currentUser.textContent = '{{ Auth::user()->firstname }} {{ Auth::user()->lastname }} (You)';
                    assignedToSelect.appendChild(currentUser);
                });
        }
    });
});
</script>
@endsection