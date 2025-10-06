@extends('layouts.guest')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <h2 class="auth-title">Complete Your Profile</h2>
        <p class="auth-subtitle">Add your skills and preferences to get started</p>
        @if(auth()->user()->firebase_provider === 'google')
            <div class="google-user-notice">
                <i class="fab fa-google me-2"></i>
                <strong>Google Account Connected:</strong> Please complete your profile information below to continue.
            </div>
        @endif
    </div>

    <div class="auth-body">
        <form method="POST" action="{{ route('firebase.profile.complete') }}" enctype="multipart/form-data" id="profileCompleteForm">
            @csrf

            <!-- Personal Information -->
            <div class="form-section">
                <h3 class="form-section-title">Personal Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname" class="form-label">First Name</label>
                        <input id="firstname" class="form-input" type="text" name="firstname" value="{{ old('firstname', $firebaseUser['firstname'] ?? '') }}" required autofocus autocomplete="firstname" />
                        <x-input-error :messages="$errors->get('firstname')" class="mt-2" />
                    </div>
                    <div class="form-group">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input id="lastname" class="form-input" type="text" name="lastname" value="{{ old('lastname', $firebaseUser['lastname'] ?? '') }}" required autocomplete="lastname" />
                        <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="middlename" class="form-label">Middle Name</label>
                    <input id="middlename" class="form-input" type="text" name="middlename" value="{{ old('middlename') }}" autocomplete="middlename" />
                    <x-input-error :messages="$errors->get('middlename')" class="mt-2" />
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" name="gender" class="form-input" required>
                            <option value="">Select gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                    </div>
                    <div class="form-group">
                        <label for="bdate" class="form-label">Birthdate</label>
                        <input id="bdate" class="form-input" type="date" name="bdate" value="{{ old('bdate') }}" required />
                        <x-input-error :messages="$errors->get('bdate')" class="mt-2" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Address (Cebu, Philippines only)</label>
                    <input id="address" class="form-input" type="text" name="address" list="address_suggestions" value="{{ old('address') }}" required />
                    <datalist id="address_suggestions"></datalist>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input id="username" class="form-input" type="text" name="username" value="{{ old('username', $firebaseUser['username'] ?? '') }}" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div class="form-group">
                    <label for="photo" class="form-label">Profile Picture</label>
                    <input id="photo" type="file" name="photo" accept="image/*" class="form-input">
                    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                </div>
            </div>

            <!-- Skills Selection -->
            <div class="form-section">
                <h3 class="form-section-title">Your Skills</h3>
                
                <div class="form-group">
                    <label for="skill_category" class="form-label">Skill Category</label>
                    <select id="skill_category" class="form-input" required>
                        <option value="">Select a category</option>
                        @foreach($skills->groupBy('category') as $category => $group)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Select Your Skills</label>
                    <div id="skills-container" class="skills-container">
                        <p class="text-gray-500 text-sm">Please select a category first to choose skills.</p>
                    </div>
                    <input type="hidden" name="selected_skills" id="selected_skills" value="">
                    <x-input-error :messages="$errors->get('selected_skills')" class="mt-2" />
                </div>
            </div>

            <!-- Skills data for JavaScript -->
            <div id="skills-data" data-skills='{!! json_encode($skills->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT) !!}' style="display: none;"></div>

            <div class="form-footer">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check me-2"></i>Complete Profile
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #374151;
}

.skill-checkbox {
    width: 14px;
    height: 14px;
    margin-right: 6px;
    cursor: pointer;
}

.skills-container {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 0.5rem;
}

.skills-container label {
    transition: background-color 0.2s ease;
    padding: 0.25rem 0.5rem;
    margin: 0.125rem 0;
    font-size: 0.85rem;
}

.skills-container label:hover {
    background-color: #f8f9fa;
}

.skills-container label:has(input:checked) {
    background-color: #e3f2fd;
    border-color: #2196f3;
}

.skills-container label.checked {
    background-color: #e3f2fd;
    border-color: #2196f3;
}

.google-user-notice {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 1rem;
    color: #0369a1;
    font-size: 0.875rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.google-user-notice i {
    color: #4285f4;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('skill_category');
    const skillsContainer = document.getElementById('skills-container');
    const selectedSkillsInput = document.getElementById('selected_skills');
    
    // Get skills data from the data attribute
    const skillsDataElement = document.getElementById('skills-data');
    let allSkills = [];
    let selectedSkills = [];
    
    try {
        if (skillsDataElement && skillsDataElement.dataset.skills) {
            allSkills = JSON.parse(skillsDataElement.dataset.skills);
            console.log('Skills loaded successfully:', allSkills.length, 'skills');
        } else {
            console.error('Skills data element not found');
        }
    } catch (error) {
        console.error('Error parsing skills JSON:', error);
        skillsContainer.innerHTML = '<p class="text-red-500 text-sm">Error loading skills data. Please refresh the page.</p>';
    }

    categorySelect.addEventListener('change', function() {
        const selectedCategory = this.value;
        
        if (!selectedCategory) {
            skillsContainer.innerHTML = '<p class="text-gray-500 text-sm">Please select a category first to choose skills.</p>';
            selectedSkills = [];
            updateSelectedSkillsInput();
            return;
        }

        // Filter skills by category
        const categorySkills = allSkills.filter(skill => skill.category === selectedCategory);
        
        if (categorySkills.length === 0) {
            skillsContainer.innerHTML = '<p class="text-gray-500 text-sm">No skills found for this category.</p>';
            return;
        }
        
        // Create skill selection interface
        let skillsHTML = '<div class="space-y-2">';
        skillsHTML += '<p class="text-sm font-medium text-gray-700">Select your skills from ' + selectedCategory + ':</p>';
        
        categorySkills.forEach(skill => {
            skillsHTML += `
                <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded border border-gray-200">
                    <input type="checkbox" 
                           class="skill-checkbox" 
                           value="${skill.skill_id}" 
                           data-skill-name="${skill.name}"
                           onchange="toggleSkill(this)">
                    <span class="text-sm text-gray-700">${skill.name}</span>
                </label>
            `;
        });
        
        skillsHTML += '</div>';
        skillsContainer.innerHTML = skillsHTML;
    });

    // Global function to toggle skill selection
    window.toggleSkill = function(checkbox) {
        const skillId = checkbox.value;
        const skillName = checkbox.getAttribute('data-skill-name');
        const label = checkbox.closest('label');
        
        if (checkbox.checked) {
            if (!selectedSkills.find(skill => skill.id === skillId)) {
                selectedSkills.push({ id: skillId, name: skillName });
            }
            label.classList.add('checked');
        } else {
            selectedSkills = selectedSkills.filter(skill => skill.id !== skillId);
            label.classList.remove('checked');
        }
        
        updateSelectedSkillsInput();
        updateSkillsDisplay();
    };

    function updateSelectedSkillsInput() {
        selectedSkillsInput.value = JSON.stringify(selectedSkills);
    }

    function updateSkillsDisplay() {
        if (selectedSkills.length > 0) {
            const displayHTML = `
                <div class="mt-2">
                    <p class="text-sm font-medium text-gray-700 mb-2">Selected Skills:</p>
                    <div class="flex flex-wrap gap-2">
                        ${selectedSkills.map(skill => `
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                ${skill.name}
                                <button type="button" 
                                        onclick="removeSkill('${skill.id}')" 
                                        class="ml-1 text-blue-600 hover:text-blue-800">
                                    ×
                                </button>
                            </span>
                        `).join('')}
                    </div>
                </div>
            `;
            
            // Add to skills container
            const existingDisplay = skillsContainer.querySelector('.selected-skills-display');
            if (existingDisplay) {
                existingDisplay.remove();
            }
            
            const displayDiv = document.createElement('div');
            displayDiv.className = 'selected-skills-display';
            displayDiv.innerHTML = displayHTML;
            skillsContainer.appendChild(displayDiv);
        } else {
            const existingDisplay = skillsContainer.querySelector('.selected-skills-display');
            if (existingDisplay) {
                existingDisplay.remove();
            }
        }
    }

    // Global function to remove skill
    window.removeSkill = function(skillId) {
        selectedSkills = selectedSkills.filter(skill => skill.id !== skillId);
        updateSelectedSkillsInput();
        updateSkillsDisplay();
        
        // Uncheck the corresponding checkbox and remove visual feedback
        const checkbox = document.querySelector(`input[value="${skillId}"]`);
        if (checkbox) {
            checkbox.checked = false;
            const label = checkbox.closest('label');
            if (label) {
                label.classList.remove('checked');
            }
        }
    };

    // Address suggestions (Cebu only)
    const addressInput = document.getElementById('address');
    const dataList = document.getElementById('address_suggestions');

    function debounce(fn, delay) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    async function fetchSuggestions(query) {
        try {
            const url = '/api/addresses/cebu/suggest?q=' + encodeURIComponent(query || '');
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            dataList.innerHTML = '';
            (data.suggestions || []).forEach(item => {
                const opt = document.createElement('option');
                opt.value = item;
                dataList.appendChild(opt);
            });
        } catch (e) {}
    }

    addressInput.addEventListener('input', debounce(function(e) {
        fetchSuggestions(e.target.value);
    }, 250));

    // Preload some suggestions initially
    fetchSuggestions('');

    // Form validation
    const form = document.getElementById('profileCompleteForm');
    form.addEventListener('submit', function(e) {
        // Validate skills selection
        if (selectedSkills.length === 0) {
            e.preventDefault();
            alert('Please select at least one skill.');
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Completing Profile...';
        submitBtn.disabled = true;
        
        // Re-enable button after a short delay (in case of validation errors)
        setTimeout(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 2000);
    });
});
</script>
@endsection
