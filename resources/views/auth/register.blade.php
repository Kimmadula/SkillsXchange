<x-guest-layout>
    <!-- Error Popup Modal -->
    @if($errors->any())
    <div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-gray-900">Registration Error</h3>
                </div>
            </div>
            <div class="mb-4">
                <ul class="text-sm text-gray-600">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeErrorModal()" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Success Popup Modal -->
    @if(session('status'))
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-gray-900">Registration Successful</h3>
                </div>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-600">{{ session('status') }}</p>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeSuccessModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                    Continue to Login
                </button>
            </div>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" id="registerForm">
        @csrf

        <!-- Name Fields Row -->
        <div class="form-row">
            <div class="form-group">
                <label for="firstname" class="form-label">First Name</label>
                <input id="firstname" class="form-input" type="text" name="firstname" value="{{ old('firstname') }}" required autofocus autocomplete="firstname" />
                <x-input-error :messages="$errors->get('firstname')" class="mt-2" />
            </div>
            <div class="form-group">
                <label for="lastname" class="form-label">Last Name</label>
                <input id="lastname" class="form-input" type="text" name="lastname" value="{{ old('lastname') }}" required autocomplete="lastname" />
                <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
            </div>
        </div>

        <!-- Middle Name -->
        <div class="form-group">
            <label for="middlename" class="form-label">Middle Name</label>
            <input id="middlename" class="form-input" type="text" name="middlename" value="{{ old('middlename') }}" />
            <x-input-error :messages="$errors->get('middlename')" class="mt-2" />
        </div>

        <!-- Gender and Birthdate Row -->
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

        <!-- Address (Cebu, Philippines only) with suggestions -->
        <div class="form-group">
            <label for="address" class="form-label">Address (Cebu, Philippines only)</label>
            <input id="address" class="form-input" type="text" name="address" list="address_suggestions" value="{{ old('address') }}" required />
            <datalist id="address_suggestions"></datalist>
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <!-- Username and Email Row -->
        <div class="form-row">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input id="username" class="form-input" type="text" name="username" value="{{ old('username') }}" required autocomplete="username" />
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        </div>

        <!-- Password Fields Row -->
        <div class="form-row">
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input id="password" class="form-input" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <!-- Profile Picture -->
        <div class="form-group">
            <label for="photo" class="form-label">Profile Picture</label>
            <input id="photo" type="file" name="photo" accept="image/*" class="form-input">
            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        </div>

        <!-- Skill Category (for filtering only, not submitted) -->
        <div class="form-group">
            <label for="skill_category" class="form-label">Skill Category</label>
            <select id="skill_category" class="form-input" required>
                <option value="">Select a category</option>
                @foreach($skills->groupBy('category') as $category => $group)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </select>
        </div>

        <!-- Skills Selection (multiple skills) -->
        <div class="form-group">
            <label class="form-label">Select Your Skills</label>
            <div id="skills-container" class="skills-container">
                <p class="text-gray-500 text-sm">Please select a category first to choose skills.</p>
            </div>
            <input type="hidden" name="selected_skills" id="selected_skills" value="">
            <x-input-error :messages="$errors->get('selected_skills')" class="mt-2" />
            
            <!-- Helpful message about skills -->
            <div class="skills-help-message">
                <i class="fas fa-info-circle me-2"></i>
                <span class="text-sm text-gray-600">
                    You can add more skills to your profile after registration. Contact an admin to add additional skills or modify your skill set.
                </span>
            </div>
        </div>

        <!-- Skills data for JavaScript -->
        <div id="skills-data" data-skills='{!! json_encode($skills->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT) !!}' style="display: none;"></div>

        <style>
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
        
        /* Fallback for browsers that don't support :has() */
        .skills-container label.checked {
            background-color: #e3f2fd;
            border-color: #2196f3;
        }
        
        .skills-help-message {
            margin-top: 12px;
            padding: 12px 16px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        
        .skills-help-message i {
            color: #3b82f6;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .skills-help-message span {
            line-height: 1.5;
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
                console.log('Raw skills data:', skillsDataElement ? skillsDataElement.dataset.skills : 'Element not found');
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
                
                // Debug: Log the filtered skills
                console.log('Selected category:', selectedCategory);
                console.log('Filtered skills:', categorySkills);
                
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

            // Trigger change event on page load if a category is already selected
            if (categorySelect.value) {
                categorySelect.dispatchEvent(new Event('change'));
            }

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

            // Simple form validation
            const form = document.getElementById('registerForm');
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
                submitBtn.textContent = 'Registering...';
                submitBtn.disabled = true;
                
                // Re-enable button after a short delay (in case of validation errors)
                setTimeout(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }, 2000);
            });

            // Real-time validation
            const inputs = form.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                // Add real-time validation for birthdate field
                if (input.name === 'bdate') {
                    input.addEventListener('change', function() {
                        validateField(this);
                    });
                    input.addEventListener('input', function() {
                        // Clear previous validation when user starts typing
                        this.classList.remove('border-red-500', 'border-green-500');
                        const existingError = this.parentNode.querySelector('.field-error');
                        if (existingError) {
                            existingError.remove();
                        }
                    });
                }
            });

            function validateField(field) {
                const value = field.value.trim();
                const fieldName = field.name;
                let isValid = true;
                let errorMessage = '';

                // Remove existing error styling
                field.classList.remove('border-red-500', 'border-green-500');
                const existingError = field.parentNode.querySelector('.field-error');
                if (existingError) {
                    existingError.remove();
                }

                // Validation rules
                if (field.hasAttribute('required') && !value) {
                    isValid = false;
                    errorMessage = `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required.`;
                } else if (fieldName === 'email' && value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address.';
                    }
                } else if (fieldName === 'password' && value) {
                    if (value.length < 8) {
                        isValid = false;
                        errorMessage = 'Password must be at least 8 characters long.';
                    }
                } else if (fieldName === 'password_confirmation' && value) {
                    const password = document.getElementById('password').value;
                    if (value !== password) {
                        isValid = false;
                        errorMessage = 'Passwords do not match.';
                    }
                } else if (fieldName === 'username' && value) {
                    if (value.length < 3) {
                        isValid = false;
                        errorMessage = 'Username must be at least 3 characters long.';
                    }
                } else if (fieldName === 'bdate' && value) {
                    const birthDate = new Date(value);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();
                    
                    // Check if birthday hasn't occurred this year
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    
                    if (age < 18) {
                        isValid = false;
                        errorMessage = 'You must be 18 years or above to register.';
                    }
                }

                // Apply styling and show error
                if (!isValid) {
                    field.classList.add('border-red-500');
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'field-error text-red-600 text-sm mt-1';
                    errorDiv.textContent = errorMessage;
                    field.parentNode.appendChild(errorDiv);
                } else if (value) {
                    field.classList.add('border-green-500');
                }
            }
        });

        function showErrorModal(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorModal').classList.remove('hidden');
        }

        function closeErrorModal() {
            document.getElementById('errorModal').classList.add('hidden');
        }

        function showSuccessModal() {
            document.getElementById('successModal').classList.remove('hidden');
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            window.location.href = '{{ route("login") }}';
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'errorModal') {
                closeErrorModal();
            }
            if (e.target.id === 'successModal') {
                closeSuccessModal();
            }
        });
        </script>

        <div class="form-footer">
            <a href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="submit" class="btn-primary">
                {{ __('REGISTER') }}
            </button>
        </div>
    </form>


    <!-- Back button outside form -->
    <div class="form-back-button">
        <a href="{{ url('/') }}" class="btn-secondary">
            {{ __('← Back to Home') }}
        </a>
    </div>

</x-guest-layout>
