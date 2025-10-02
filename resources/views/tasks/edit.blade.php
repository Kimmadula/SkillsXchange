@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Header -->
                <div class="mb-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                    <h1 class="h2 fw-bold text-dark mb-2">Edit Task</h1>
                    <p class="text-muted">Update task details and requirements</p>
                </div>

                <!-- Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('tasks.update', $task) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Task Title -->
                            <div class="mb-4">
                                <label for="title" class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title"
                                    class="form-control @error('title') is-invalid @enderror" 
                                    value="{{ old('title', $task->title) }}" placeholder="Enter task title..." required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Task Description -->
                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">Description</label>
                                <textarea name="description" id="description" rows="4"
                                    class="form-control @error('description') is-invalid @enderror"
                                    placeholder="Enter task description (optional)...">{{ old('description', $task->description) }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Priority -->
                            <div class="mb-4">
                                <label for="priority" class="form-label fw-semibold">Priority</label>
                                <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror">
                                    <option value="low" {{ old('priority', $task->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', $task->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority', $task->priority) == 'high' ? 'selected' : '' }}>High</option>
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
                                    value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}" 
                                    min="{{ date('Y-m-d') }}">
                                @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Submission Requirements -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="requires_submission" 
                                           id="requires_submission" {{ old('requires_submission', $task->requires_submission) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="requires_submission">
                                        Require File Submission
                                    </label>
                                </div>
                                <small class="text-muted">Check this if the assignee needs to submit files for this task</small>
                            </div>

                            <!-- File Type Requirements -->
                            <div id="submission_requirements" class="mb-4" style="{{ old('requires_submission', $task->requires_submission) ? 'display: block;' : 'display: none;' }}">
                                <label class="form-label fw-semibold">Required File Types</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card border">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-3">Select Required File Types:</h6>
                                                @php $allowedTypes = old('allowed_file_types', $task->allowed_file_types ?? []); @endphp
                                                
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="allowed_file_types[]" 
                                                           value="image" id="file_type_image" {{ in_array('image', $allowedTypes) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="file_type_image">
                                                        <i class="fas fa-image text-primary me-2"></i>Images (JPG, PNG, GIF)
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="allowed_file_types[]" 
                                                           value="video" id="file_type_video" {{ in_array('video', $allowedTypes) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="file_type_video">
                                                        <i class="fas fa-video text-danger me-2"></i>Videos (MP4, MOV, AVI)
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="allowed_file_types[]" 
                                                           value="pdf" id="file_type_pdf" {{ in_array('pdf', $allowedTypes) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="file_type_pdf">
                                                        <i class="fas fa-file-pdf text-danger me-2"></i>PDF Documents
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="allowed_file_types[]" 
                                                           value="word" id="file_type_word" {{ in_array('word', $allowedTypes) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="file_type_word">
                                                        <i class="fas fa-file-word text-primary me-2"></i>Word Documents (DOC, DOCX)
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="allowed_file_types[]" 
                                                           value="excel" id="file_type_excel" {{ in_array('excel', $allowedTypes) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="file_type_excel">
                                                        <i class="fas fa-file-excel text-success me-2"></i>Excel Files (XLS, XLSX)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-3">Submission Instructions:</h6>
                                                <textarea name="submission_instructions" id="submission_instructions" 
                                                          rows="6" class="form-control" 
                                                          placeholder="Provide specific instructions for what the assignee should submit...">{{ old('submission_instructions', $task->submission_instructions) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Scoring Configuration -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Scoring Configuration</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="max_score" class="form-label">Maximum Score</label>
                                        <input type="number" name="max_score" id="max_score" 
                                               class="form-control @error('max_score') is-invalid @enderror" 
                                               value="{{ old('max_score', $task->max_score) }}" min="1" max="1000">
                                        @error('max_score')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="passing_score" class="form-label">Passing Score</label>
                                        <input type="number" name="passing_score" id="passing_score" 
                                               class="form-control @error('passing_score') is-invalid @enderror" 
                                               value="{{ old('passing_score', $task->passing_score) }}" min="1" max="1000">
                                        @error('passing_score')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <small class="text-muted">Set the maximum possible score and minimum score needed to pass</small>
                            </div>

                            <!-- Skills Association -->
                            @if(isset($skills) && $skills->count() > 0)
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Associated Skills (Optional)</label>
                                <select name="associated_skills[]" id="associated_skills" 
                                        class="form-select @error('associated_skills') is-invalid @enderror" multiple>
                                    @php $taskSkills = old('associated_skills', $task->associated_skills ?? []); @endphp
                                    @foreach($skills as $skill)
                                    <option value="{{ $skill->skill_id }}" 
                                            {{ in_array($skill->skill_id, $taskSkills) ? 'selected' : '' }}>
                                        {{ $skill->name }} ({{ $skill->category }})
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Select skills that will be added to the learner's profile when this task is completed successfully</small>
                                @error('associated_skills')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endif

                            <!-- Form Actions -->
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Task
                                </button>
                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-secondary">
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
    const requiresSubmissionCheckbox = document.getElementById('requires_submission');
    const submissionRequirements = document.getElementById('submission_requirements');
    const associatedSkillsSelect = document.getElementById('associated_skills');

    // Handle submission requirements toggle
    requiresSubmissionCheckbox.addEventListener('change', function() {
        if (this.checked) {
            submissionRequirements.style.display = 'block';
        } else {
            submissionRequirements.style.display = 'none';
        }
    });

    // Initialize Select2 for better multi-select experience
    if (associatedSkillsSelect && typeof $ !== 'undefined' && $.fn.select2) {
        $(associatedSkillsSelect).select2({
            placeholder: 'Select skills to associate with this task...',
            allowClear: true,
            width: '100%'
        });
    }

    // Validate file type selection
    const fileTypeCheckboxes = document.querySelectorAll('input[name="allowed_file_types[]"]');
    
    function validateFileTypes() {
        if (requiresSubmissionCheckbox.checked) {
            const checkedTypes = Array.from(fileTypeCheckboxes).some(cb => cb.checked);
            if (!checkedTypes) {
                fileTypeCheckboxes.forEach(cb => {
                    cb.closest('.form-check').classList.add('text-warning');
                });
                return false;
            } else {
                fileTypeCheckboxes.forEach(cb => {
                    cb.closest('.form-check').classList.remove('text-warning');
                });
                return true;
            }
        }
        return true;
    }

    // Validate on form submit
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!validateFileTypes()) {
            e.preventDefault();
            alert('Please select at least one file type when requiring submissions.');
            return false;
        }
    });

    // Real-time validation
    fileTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', validateFileTypes);
    });
});
</script>
@endsection
