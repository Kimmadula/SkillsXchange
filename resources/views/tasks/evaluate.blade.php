@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="mb-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></li>
                            <li class="breadcrumb-item active">Evaluate</li>
                        </ol>
                    </nav>
                    <h1 class="h2 fw-bold text-dark mb-2">Evaluate Task Submission</h1>
                    <p class="text-muted">Review the submitted work and provide evaluation with percentage score</p>
                </div>

                <div class="row g-4">
                    <!-- Submission Review -->
                    <div class="col-lg-8">
                        <!-- Task Details -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Task: {{ $task->title }}</h5>
                                
                                @if($task->description)
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Description</h6>
                                    <p class="mb-0">{{ $task->description }}</p>
                                </div>
                                @endif

                                @if($task->submission_instructions)
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Instructions Given</h6>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-0">{{ $task->submission_instructions }}</p>
                                    </div>
                                </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Assigned to</small>
                                        <strong>{{ $task->assignee->name }}</strong>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Submitted on</small>
                                        <strong>{{ $task->latestSubmission->submitted_at->format('M j, Y g:i A') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submission Content -->
                        @if($task->latestSubmission)
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Submitted Work</h5>
                                
                                @if($task->latestSubmission->submission_notes)
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">Submission Notes</h6>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-0">{{ $task->latestSubmission->submission_notes }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($task->latestSubmission->hasFiles())
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">Submitted Files ({{ $task->latestSubmission->getFileCount() }} files)</h6>
                                    <div class="row g-3">
                                        @foreach($task->latestSubmission->file_names as $index => $fileName)
                                        <div class="col-md-6">
                                            <div class="card border">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <h6 class="mb-1">{{ $fileName }}</h6>
                                                            <small class="text-muted">{{ $task->latestSubmission->getFormattedFileSize($index) }}</small>
                                                        </div>
                                                        <a href="{{ route('submissions.download', [$task->latestSubmission, $index]) }}" 
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Evaluation Form -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm sticky-top" style="top: 2rem;">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Evaluation Form</h5>
                                
                                <form action="{{ route('tasks.store-evaluation', $task) }}" method="POST">
                                    @csrf
                                    
                                    <!-- Score Input -->
                                    <div class="mb-4">
                                        <label for="score_percentage" class="form-label fw-semibold">
                                            Score (0-100) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="score_percentage" id="score_percentage" 
                                               class="form-control @error('score_percentage') is-invalid @enderror" 
                                               value="{{ old('score_percentage') }}" 
                                               min="0" max="100" required>
                                        <small class="text-muted">Enter a score from 0 to 100. Status will be automatically determined: Pass (≥70) or Fail (<70)</small>
                                        @error('score_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Status Display (Read-only) -->
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Status</label>
                                        <div id="statusDisplay" class="p-3 rounded text-center fw-bold" style="background: #f3f4f6; color: #6b7280;">
                                            <span id="statusText">-</span>
                                        </div>
                                        <small class="text-muted">Status is automatically calculated based on score</small>
                                    </div>

                                    <!-- Feedback -->
                                    <div class="mb-4">
                                        <label for="feedback" class="form-label fw-semibold">Feedback</label>
                                        <textarea name="feedback" id="feedback" rows="4" 
                                                  class="form-control @error('feedback') is-invalid @enderror"
                                                  placeholder="Provide detailed feedback on the submission...">{{ old('feedback') }}</textarea>
                                        @error('feedback')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>


                                    <!-- Skills Information -->
                                    @if($task->hasAssociatedSkills())
                                    <div class="mb-4">
                                        <h6 class="text-muted mb-2">Skills to be Added (if passed)</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @if($learnableSkills['can_learn'])
                                                @foreach($learnableSkills['skills'] as $skill)
                                                <span class="badge bg-success fs-6">
                                                    <i class="fas fa-plus me-1"></i>{{ $skill->name }}
                                                </span>
                                                @endforeach
                                            @endif
                                            
                                            @if($learnableSkills['already_has']->count() > 0)
                                                @foreach($learnableSkills['already_has'] as $skill)
                                                <span class="badge bg-secondary fs-6">
                                                    <i class="fas fa-check me-1"></i>{{ $skill->name }} (already has)
                                                </span>
                                                @endforeach
                                            @endif
                                        </div>
                                        
                                        @if(!$learnableSkills['can_learn'])
                                        <small class="text-muted">Learner already has all associated skills</small>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Submit Buttons -->
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-star me-2"></i>Submit Evaluation
                                        </button>
                                        <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Task
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scoreInput = document.getElementById('score_percentage');
    const statusDisplay = document.getElementById('statusDisplay');
    const statusText = document.getElementById('statusText');
    
    // Update status display when score changes
    scoreInput.addEventListener('input', function() {
        const score = parseInt(this.value) || 0;
        
        // Validate score range
        if (score < 0 || score > 100) {
            statusText.textContent = '-';
            statusDisplay.style.background = '#f3f4f6';
            statusDisplay.style.color = '#6b7280';
            this.classList.remove('border-success', 'border-danger');
            return;
        }
        
        // Auto-determine status: >= 70 = Pass, < 70 = Fail
        if (score >= 70) {
            statusText.textContent = 'Pass';
            statusDisplay.style.background = '#10b981';
            statusDisplay.style.color = '#ffffff';
            this.classList.remove('border-danger');
            this.classList.add('border-success');
        } else {
            statusText.textContent = 'Fail';
            statusDisplay.style.background = '#ef4444';
            statusDisplay.style.color = '#ffffff';
            this.classList.remove('border-success');
            this.classList.add('border-danger');
        }
    });
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const score = parseInt(scoreInput.value);
        
        if (isNaN(score) || score < 0 || score > 100) {
            e.preventDefault();
            alert('Please enter a valid score between 0 and 100.');
            scoreInput.focus();
            return false;
        }
    });
    
    // Initialize status display if score is pre-filled
    if (scoreInput.value) {
        scoreInput.dispatchEvent(new Event('input'));
    }
});
</script>
@endsection
