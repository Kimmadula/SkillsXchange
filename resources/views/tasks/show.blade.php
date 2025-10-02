@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
                        <li class="breadcrumb-item active">{{ $task->title }}</li>
                    </ol>
                </nav>
                <h1 class="h2 fw-bold text-dark mb-2">{{ $task->title }}</h1>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="badge bg-{{ $task->status_color }} fs-6">
                        <i class="{{ $task->status_icon }} me-1"></i>{{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                    </span>
                    <span class="badge bg-{{ $task->priority_color }}">{{ ucfirst($task->priority) }} Priority</span>
                    @if($task->is_overdue)
                    <span class="badge bg-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                    </span>
                    @endif
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-flex gap-2">
                @if($task->created_by === Auth::id())
                    <!-- Task Creator Actions -->
                    <a href="{{ route('tasks.edit', $task) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    @if($task->canBeEvaluated())
                    <a href="{{ route('tasks.evaluate', $task) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-star me-1"></i>Evaluate
                    </a>
                    @endif
                    <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="d-inline" 
                          onsubmit="return confirm('Are you sure you want to delete this task?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </form>
                @elseif($task->assigned_to === Auth::id())
                    <!-- Task Assignee Actions -->
                    @if($task->canBeStarted())
                    <form action="{{ route('tasks.start', $task) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-play me-1"></i>Start Task
                        </button>
                    </form>
                    @endif
                    @if($task->canBeSubmitted())
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#submitModal">
                        <i class="fas fa-upload me-1"></i>Submit Work
                    </button>
                    @endif
                @endif
            </div>
        </div>

        <div class="row g-4">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Task Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Task Details</h5>
                        
                        @if($task->description)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Description</h6>
                            <p class="mb-0">{{ $task->description }}</p>
                        </div>
                        @endif

                        @if($task->submission_instructions)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Submission Instructions</h6>
                            <div class="bg-light p-3 rounded">
                                <p class="mb-0">{{ $task->submission_instructions }}</p>
                            </div>
                        </div>
                        @endif

                        @if($task->requires_submission && $task->hasAllowedFileTypes())
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Required File Types</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($task->allowed_file_types as $fileType)
                                    @php
                                        $typeConfig = [
                                            'image' => ['icon' => 'fas fa-image', 'color' => 'primary', 'label' => 'Images'],
                                            'video' => ['icon' => 'fas fa-video', 'color' => 'danger', 'label' => 'Videos'],
                                            'pdf' => ['icon' => 'fas fa-file-pdf', 'color' => 'danger', 'label' => 'PDF'],
                                            'word' => ['icon' => 'fas fa-file-word', 'color' => 'primary', 'label' => 'Word'],
                                            'excel' => ['icon' => 'fas fa-file-excel', 'color' => 'success', 'label' => 'Excel']
                                        ];
                                        $config = $typeConfig[$fileType] ?? ['icon' => 'fas fa-file', 'color' => 'secondary', 'label' => ucfirst($fileType)];
                                    @endphp
                                    <span class="badge bg-{{ $config['color'] }} fs-6">
                                        <i class="{{ $config['icon'] }} me-1"></i>{{ $config['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($task->hasAssociatedSkills())
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Skills You'll Learn</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($task->getAssociatedSkillNames() as $skillName)
                                <span class="badge bg-info fs-6">
                                    <i class="fas fa-graduation-cap me-1"></i>{{ $skillName }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Submissions -->
                @if($task->submissions->count() > 0)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Submissions</h5>
                        
                        @foreach($task->submissions->sortByDesc('submitted_at') as $submission)
                        <div class="border rounded p-3 mb-3 {{ $submission->is_latest ? 'border-primary bg-light' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong>{{ $submission->submitter->name }}</strong>
                                    @if($submission->is_latest)
                                    <span class="badge bg-primary ms-2">Latest</span>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $submission->submitted_at->format('M j, Y g:i A') }}</small>
                            </div>
                            
                            @if($submission->submission_notes)
                            <p class="mb-2">{{ $submission->submission_notes }}</p>
                            @endif
                            
                            @if($submission->hasFiles())
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($submission->file_names as $index => $fileName)
                                <a href="{{ route('submissions.download', [$submission, $index]) }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-download me-1"></i>{{ $fileName }}
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Evaluation -->
                @if($task->latestEvaluation)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Evaluation</h5>
                        
                        @php $evaluation = $task->latestEvaluation @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="display-4 fw-bold text-{{ $evaluation->status_color }}">
                                        {{ $evaluation->score_percentage }}%
                                    </div>
                                    <div class="text-muted">Score</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="display-6 fw-bold text-{{ $evaluation->status_color }}">
                                        {{ $evaluation->grade_letter }}
                                    </div>
                                    <div class="text-muted">Grade</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <span class="badge bg-{{ $evaluation->status_color }} fs-5">
                                        <i class="{{ $evaluation->status_icon }} me-1"></i>{{ ucfirst($evaluation->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($evaluation->feedback)
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Feedback</h6>
                            <p class="mb-0">{{ $evaluation->feedback }}</p>
                        </div>
                        @endif

                        @if($evaluation->improvement_notes)
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Improvement Notes</h6>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <p class="mb-0">{{ $evaluation->improvement_notes }}</p>
                            </div>
                        </div>
                        @endif

                        @if($evaluation->hasSkillsToAdd())
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Skills {{ $evaluation->skills_added ? 'Added' : 'To Be Added' }}</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($evaluation->getSkillsToAddNames() as $skillName)
                                <span class="badge bg-{{ $evaluation->skills_added ? 'success' : 'warning' }} fs-6">
                                    <i class="fas fa-{{ $evaluation->skills_added ? 'check' : 'clock' }} me-1"></i>{{ $skillName }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="text-muted small">
                            Evaluated by {{ $evaluation->evaluator->name }} on {{ $evaluation->evaluated_at->format('M j, Y g:i A') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Task Info -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Task Information</h6>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Created by</small>
                            <strong>{{ $task->creator->name }}</strong>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Assigned to</small>
                            <strong>{{ $task->assignee->name }}</strong>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Trade</small>
                            <strong>{{ $task->trade->offeringSkill->name }} ↔ {{ $task->trade->lookingSkill->name }}</strong>
                        </div>
                        
                        @if($task->due_date)
                        <div class="mb-3">
                            <small class="text-muted d-block">Due Date</small>
                            <strong class="{{ $task->is_overdue ? 'text-danger' : '' }}">
                                {{ $task->due_date->format('M j, Y') }}
                                @if($task->days_until_due !== null)
                                    <small class="text-muted">
                                        ({{ $task->days_until_due >= 0 ? $task->days_until_due . ' days left' : abs($task->days_until_due) . ' days overdue' }})
                                    </small>
                                @endif
                            </strong>
                        </div>
                        @endif
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Created</small>
                            <strong>{{ $task->created_at->format('M j, Y g:i A') }}</strong>
                        </div>

                        @if($task->requires_submission)
                        <div class="mb-3">
                            <small class="text-muted d-block">Submission Required</small>
                            <span class="badge bg-info">
                                <i class="fas fa-upload me-1"></i>Yes
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Scoring Info -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Scoring Information</h6>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Maximum Score</small>
                            <strong>{{ $task->max_score }} points</strong>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Passing Score</small>
                            <strong>{{ $task->passing_score }} points ({{ round(($task->passing_score / $task->max_score) * 100) }}%)</strong>
                        </div>
                        
                        @if($task->latestEvaluation)
                        <div class="progress mb-2">
                            <div class="progress-bar bg-{{ $task->latestEvaluation->status_color }}" 
                                 style="width: {{ ($task->latestEvaluation->score_percentage / $task->max_score) * 100 }}%">
                            </div>
                        </div>
                        <small class="text-muted">
                            Current Score: {{ $task->latestEvaluation->score_percentage }}/{{ $task->max_score }}
                        </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Submit Work Modal -->
@if($task->assigned_to === Auth::id() && $task->canBeSubmitted())
<div class="modal fade" id="submitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('tasks.submit', $task) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Submit Your Work</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($task->submission_instructions)
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Instructions:</h6>
                        <p class="mb-0">{{ $task->submission_instructions }}</p>
                    </div>
                    @endif

                    @if($task->hasAllowedFileTypes())
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">Required File Types:</h6>
                        <p class="mb-0">{{ $task->getAllowedFileTypesDisplay() }}</p>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="submission_notes" class="form-label">Notes (Optional)</label>
                        <textarea name="submission_notes" id="submission_notes" rows="4" 
                                  class="form-control" placeholder="Add any notes about your submission..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="files" class="form-label">Upload Files</label>
                        <input type="file" name="files[]" id="files" class="form-control" multiple 
                               accept="{{ $task->hasAllowedFileTypes() ? $task->getAcceptAttribute() : '*' }}">
                        <small class="text-muted">Maximum 10 files, 50MB each</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i>Submit Work
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
