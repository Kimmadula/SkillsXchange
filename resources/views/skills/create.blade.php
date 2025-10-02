@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 fw-bold text-dark mb-2">Add New Skill</h1>
                        <p class="text-muted">Create a new skill for the community</p>
                    </div>
                    <a href="{{ route('skills.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Skills
                    </a>
                </div>

                <!-- Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form action="{{ route('skills.store') }}" method="POST">
                            @csrf

                            <!-- Skill Name -->
                            <div class="mb-4">
                                <label for="name" class="form-label fw-semibold">Skill Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" 
                                       placeholder="e.g., Web Development, Graphic Design, Data Analysis"
                                       required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Choose a clear, descriptive name for the skill</small>
                            </div>

                            <!-- Category -->
                            <div class="mb-4">
                                <label for="category" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="category" id="category" 
                                           class="form-control @error('category') is-invalid @enderror" 
                                           value="{{ old('category') }}" 
                                           placeholder="e.g., Technology, Design, Business"
                                           list="category-suggestions"
                                           required>
                                    <datalist id="category-suggestions">
                                        @foreach($categories as $existingCategory)
                                        <option value="{{ $existingCategory }}">
                                        @endforeach
                                        <option value="Technology">
                                        <option value="Design">
                                        <option value="Business">
                                        <option value="Marketing">
                                        <option value="Education">
                                        <option value="Arts & Crafts">
                                        <option value="Health & Fitness">
                                        <option value="Languages">
                                        <option value="Music">
                                        <option value="Cooking">
                                    </datalist>
                                </div>
                                @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select an existing category or create a new one</small>
                            </div>

                            <!-- Difficulty Level -->
                            <div class="mb-4">
                                <label for="difficulty_level" class="form-label fw-semibold">Difficulty Level</label>
                                <select name="difficulty_level" id="difficulty_level" 
                                        class="form-select @error('difficulty_level') is-invalid @enderror">
                                    <option value="beginner" {{ old('difficulty_level') == 'beginner' ? 'selected' : '' }}>
                                        Beginner - No prior experience needed
                                    </option>
                                    <option value="intermediate" {{ old('difficulty_level') == 'intermediate' ? 'selected' : '' }}>
                                        Intermediate - Some experience required
                                    </option>
                                    <option value="advanced" {{ old('difficulty_level') == 'advanced' ? 'selected' : '' }}>
                                        Advanced - Significant experience needed
                                    </option>
                                    <option value="expert" {{ old('difficulty_level') == 'expert' ? 'selected' : '' }}>
                                        Expert - Professional level expertise
                                    </option>
                                </select>
                                @error('difficulty_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">Description</label>
                                <textarea name="description" id="description" rows="4"
                                          class="form-control @error('description') is-invalid @enderror" 
                                          placeholder="Describe what this skill involves, what someone would learn, and how it can be applied...">{{ old('description') }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Provide a detailed description to help others understand this skill</small>
                            </div>

                            <!-- Prerequisites -->
                            <div class="mb-4">
                                <label for="prerequisites" class="form-label fw-semibold">Prerequisites</label>
                                <textarea name="prerequisites" id="prerequisites" rows="3"
                                          class="form-control @error('prerequisites') is-invalid @enderror" 
                                          placeholder="List any skills, knowledge, or tools needed before learning this skill...">{{ old('prerequisites') }}</textarea>
                                @error('prerequisites')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">What should someone know or have before learning this skill?</small>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex gap-3 justify-content-end">
                                <a href="{{ route('skills.index') }}" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Skill
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tips Card -->
                <div class="card border-0 bg-light mt-4">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-lightbulb text-warning me-2"></i>Tips for Creating Great Skills
                        </h6>
                        <ul class="mb-0 small text-muted">
                            <li>Use clear, specific names that others can easily understand</li>
                            <li>Choose appropriate categories to help with discovery</li>
                            <li>Write detailed descriptions explaining what the skill involves</li>
                            <li>Be honest about difficulty levels to set proper expectations</li>
                            <li>List realistic prerequisites to help learners prepare</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-suggest categories as user types
    const categoryInput = document.getElementById('category');
    const categoryDatalist = document.getElementById('category-suggestions');
    
    categoryInput.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        const options = categoryDatalist.querySelectorAll('option');
        
        options.forEach(option => {
            const optionValue = option.value.toLowerCase();
            if (optionValue.includes(value)) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
    });
});
</script>
@endsection
