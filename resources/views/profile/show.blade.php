@extends('layouts.app')

@section('content')
<main role="profile" style="padding:32px; max-width:720px; margin:0 auto; overflow-x:hidden;">
    <style>
        @media (max-width: 480px) {
            main[role="profile"] { padding:16px !important; }
            .profile-header { flex-direction: column; text-align: center; }
            .profile-photo { margin: 0 auto 16px; }
        }
        .profile-card {
            background:#fff;
            border:1px solid #e5e7eb;
            border-radius:8px;
            padding:20px;
            margin-bottom:16px;
        }
        .form-row {
            display:grid;
            grid-template-columns: 140px 1fr;
            gap:12px;
            align-items:center;
            margin-bottom:12px;
            padding-bottom:12px;
            border-bottom:1px solid #f3f4f6;
        }
        .form-row:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
        .form-label {
            font-weight:600;
            color:#374151;
            font-size:0.875rem;
        }
        .form-input {
            width:100%;
            padding:8px 12px;
            border:1px solid #d1d5db;
            border-radius:6px;
            font-size:0.875rem;
            background:#f9fafb;
        }
        .form-input:disabled {
            background:#f3f4f6;
            color:#6b7280;
            cursor:not-allowed;
        }
        .form-input:not(:disabled) {
            background:#fff;
            border-color:#2563eb;
        }
        .skill-pill {
            display:inline-flex;
            align-items:center;
            padding:6px 12px;
            border-radius:16px;
            font-size:0.8rem;
            font-weight:500;
            margin:4px;
        }
        .skill-pill-registered {
            background:linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color:#fff;
        }
        .skill-pill-acquired {
            background:linear-gradient(135deg, #10b981 0%, #059669 100%);
            color:#fff;
        }
        .btn-compact {
            padding:8px 14px;
            border:none;
            border-radius:6px;
            font-size:0.875rem;
            font-weight:500;
            cursor:pointer;
            transition:all 0.2s;
        }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-primary:hover { background:#1d4ed8; }
        .btn-success { background:#10b981; color:#fff; }
        .btn-success:hover { background:#059669; }
        .btn-danger { background:#ef4444; color:#fff; }
        .btn-danger:hover { background:#dc2626; }
        .btn-secondary { background:#6b7280; color:#fff; }
        .btn-secondary:hover { background:#4b5563; }
        .profile-photo-wrapper {
            position:relative;
            width:96px;
            height:96px;
            border-radius:50%;
            overflow:hidden;
            border:3px solid rgba(37,99,235,.3);
            margin-right:20px;
        }
        .profile-photo-wrapper img {
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .profile-photo-placeholder {
            width:100%;
            height:100%;
            background:rgba(37,99,235,.1);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:2rem;
            color:#6b7280;
        }
        .photo-overlay {
            position:absolute;
            top:0;
            left:0;
            right:0;
            bottom:0;
            background:rgba(0,0,0,.5);
            display:flex;
            align-items:center;
            justify-content:center;
            opacity:0;
            transition:opacity 0.2s;
        }
        .profile-photo-wrapper:hover .photo-overlay { opacity:1; }
        .section-title {
            font-size:1rem;
            font-weight:600;
            color:#1f2937;
            margin-bottom:12px;
            padding-bottom:8px;
            border-bottom:2px solid #e5e7eb;
        }
        .alert-compact {
            padding:10px 12px;
            border-radius:6px;
            font-size:0.875rem;
            margin-bottom:16px;
        }
        .alert-success { background:#d1fae5; color:#065f46; }
        .alert-warning { background:#fef3c7; color:#92400e; }
        .alert-danger { background:#fee2e2; color:#991b1b; }
        .alert-info { background:#dbeafe; color:#1e40af; }
    </style>

        @if(!$user)
        <div class="alert-compact alert-danger">
            <i class="fas fa-exclamation-triangle"></i> User data not found.
            </div>
        @else
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h1 style="font-size:1.5rem; margin:0; color:#1f2937;">Profile</h1>
            <a href="{{ route('dashboard') }}" class="btn-compact btn-secondary">
                ← Back to Dashboard
            </a>
        </div>

        <div id="profile-alert-container"></div>

        @if(session('status') === 'password-updated')
            <div class="alert-compact alert-success">
                <i class="fas fa-check-circle"></i> Password updated successfully!
            </div>
        @endif

        @if(!$user->is_verified)
            <div class="alert-compact alert-warning">
                <i class="fas fa-info-circle"></i> Awaiting admin verification. Profile photo locked until verified.
            </div>
        @else
            <div class="alert-compact alert-success">
                <i class="fas fa-check-circle"></i> Account verified. Photo and username editable.
            </div>
        @endif

        <!-- Profile Header Card -->
        <div class="profile-card">
            <div class="profile-header" style="display:flex; align-items:center; margin-bottom:20px;">
                <form id="photoUploadForm" method="POST" action="{{ route('profile.save') }}" enctype="multipart/form-data">
                                @csrf
                    <input type="hidden" name="intent" value="photo">
                                @if($user->is_verified)
                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;" onchange="uploadPhoto()">
                        <label for="photoInput" style="cursor:pointer;">
                                @endif
                        <div class="profile-photo-wrapper profile-photo">
                                    @if($user->photo && file_exists(storage_path('app/public/' . $user->photo)))
                                <img src="{{ asset('storage/' . $user->photo) }}" alt="Profile">
                                            @if($user->is_verified)
                                    <div class="photo-overlay">
                                        <i class="fas fa-camera" style="color:#fff; font-size:1.5rem;"></i>
                                                </div>
                                            @else
                                    <div style="position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-lock" style="color:#fff;" title="Locked"></i>
                                                </div>
                                            @endif
                                    @else
                                <div class="profile-photo-placeholder">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    @endif
                        </div>
                                @if($user->is_verified)
                                </label>
                                @endif
                            </form>
                <div>
                    <h2 style="margin:0 0 4px; font-size:1.25rem; color:#1f2937;">{{ $user->name }}</h2>
                    <p style="margin:0 0 8px; color:#6b7280; font-size:0.875rem;">{{ $user->email }}</p>
                    <div>
                        <span class="skill-pill skill-pill-registered">{{ ucfirst($user->role) }}</span>
                        <span class="skill-pill skill-pill-acquired">{{ ucfirst($user->plan) }} Plan</span>
                        </div>
                            </div>
                        </div>
                    </div>

        <!-- Profile Information Form -->
        <form id="profileForm" method="POST" action="{{ route('profile.save') }}" class="profile-card">
            @csrf
            <input type="hidden" name="intent" value="update">
            <div class="section-title">
                <i class="fas fa-user"></i> Personal Information
                </div>

            @if($user->is_verified)
                <!-- Verified: Only username editable -->
                <div class="form-row">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" value="{{ $user->username }}" class="form-input" required>
            </div>
                <div class="form-row">
                    <label class="form-label">Password</label>
                    <input type="password" name="current_password" class="form-input" placeholder="Confirm to save" required>
        </div>
                <div class="form-row">
                    <label class="form-label">Full Name</label>
                    <input type="text" value="{{ $user->name }}" class="form-input" disabled>
        </div>
                <div class="form-row">
                    <label class="form-label">Email</label>
                    <input type="email" value="{{ $user->email }}" class="form-input" disabled>
        </div>
                <div class="form-row">
                    <label class="form-label">Gender</label>
                    <input type="text" value="{{ ucfirst($user->gender) }}" class="form-input" disabled>
        </div>
                <div class="form-row">
                    <label class="form-label">Birth Date</label>
                    <input type="text" value="{{ $user->bdate ? $user->bdate->format('M j, Y') : 'Not provided' }}" class="form-input" disabled>
                        </div>
                <div class="form-row">
                    <label class="form-label">Age</label>
                    <input type="text" value="{{ $user->bdate ? $user->bdate->diffInYears(now()) . ' years' : 'Not provided' }}" class="form-input" disabled>
                        </div>
                <div class="form-row">
                    <label class="form-label">Address</label>
                    <input type="text" value="{{ $user->address ?: 'Not provided' }}" class="form-input" disabled>
                        </div>
            @else
                <!-- Unverified: Most fields editable -->
                <div class="form-row">
                    <label class="form-label">First Name</label>
                    <input type="text" name="firstname" value="{{ $user->firstname }}" class="form-input" required>
                        </div>
                <div class="form-row">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="lastname" value="{{ $user->lastname }}" class="form-input" required>
                        </div>
                <div class="form-row">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middlename" value="{{ $user->middlename }}" class="form-input">
                        </div>
                <div class="form-row">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" value="{{ $user->username }}" class="form-input" required>
                        </div>
                <div class="form-row">
                    <label class="form-label">Email</label>
                    <input type="email" value="{{ $user->email }}" class="form-input" disabled title="Email editing coming soon">
                        </div>
                <div class="form-row">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-input" required>
                        <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ $user->gender == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                        </div>
                <div class="form-row">
                    <label class="form-label">Birth Date</label>
                    <input type="date" name="bdate" value="{{ $user->bdate ? $user->bdate->format('Y-m-d') : '' }}" class="form-input">
                    </div>
                <div class="form-row">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-input" rows="2" style="resize:vertical;">{{ $user->address }}</textarea>
                </div>
            @endif

            <div class="form-row">
                <label class="form-label">Member Since</label>
                <input type="text" value="{{ $user->created_at->format('M j, Y') }}" class="form-input" disabled>
            </div>
            <div class="form-row">
                <label class="form-label">Total Skills</label>
                <input type="text" value="{{ $acquiredSkills ? $acquiredSkills->count() : 0 }} skills" class="form-input" disabled>
            </div>

            <div style="display:flex; gap:8px; margin-top:16px;">
                <button type="submit" class="btn-compact btn-primary">
                    <i class="fas fa-save"></i> Save Profile
                        </button>
                <button type="button" class="btn-compact btn-success" onclick="document.getElementById('changePasswordModal').style.display='flex'">
                    <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
        </form>

        <!-- Skills Card -->
        <div class="profile-card">
            <div class="section-title">
                <i class="fas fa-trophy"></i> My Skills
            </div>
                    @if($acquiredSkills && $acquiredSkills->count() > 0)
                        @php
                            $allUserSkills = $user->skills;
                            $acquiredSkillsList = $user->getAcquiredSkills();
                            $registeredSkills = $allUserSkills->filter(function($skill) use ($acquiredSkillsList) {
                                return !$acquiredSkillsList->contains('skill_id', $skill->skill_id);
                            });
                        @endphp

                        @if($registeredSkills->count() > 0)
                    <h6 style="font-size:0.8rem; color:#6b7280; margin:8px 0 4px;">Registered Skills</h6>
                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-bottom:12px;">
                                    @foreach($registeredSkills as $skill)
                                        <span class="skill-pill skill-pill-registered">
                                            {{ $skill->name }}
                                            @if($skill->skill_id == ($user->skill_id ?? null))
                                    <i class="fas fa-star" style="margin-left:4px; font-size:0.7rem;"></i>
                                            @endif
                                        </span>
                                    @endforeach
                            </div>
                        @endif

                        @if($acquiredSkillsList->count() > 0)
                    <h6 style="font-size:0.8rem; color:#6b7280; margin:8px 0 4px;">Acquired Skills</h6>
                    <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                    @foreach($acquiredSkillsList as $skill)
                                        <span class="skill-pill skill-pill-acquired">
                                            {{ $skill->name }}
                                <i class="fas fa-graduation-cap" style="margin-left:4px; font-size:0.7rem;"></i>
                                        </span>
                                    @endforeach
                            </div>
                        @endif
                    @else
                <p style="color:#6b7280; font-size:0.875rem; margin:8px 0;">No skills yet. Start trading to acquire new skills!</p>
                    @endif
                </div>

        <!-- Feedback Card -->
        <div class="profile-card">
            <div class="section-title">
                <i class="fas fa-star"></i> Feedback & Ratings
            </div>
                    <div id="user-feedback-section">
                <div style="text-align:center; padding:20px; color:#6b7280;">
                    <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;"></i>
                    <p style="margin-top:8px; font-size:0.875rem;">Loading feedback...</p>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="profile-card" style="border-color:#ef4444;">
            <div class="section-title" style="color:#ef4444;">
                <i class="fas fa-exclamation-triangle"></i> Danger Zone
                </div>
            <p style="color:#6b7280; font-size:0.875rem; margin-bottom:12px;">Once deleted, there's no going back.</p>
            <button type="button" class="btn-compact btn-danger" onclick="document.getElementById('deleteAccountModal').style.display='flex'">
                <i class="fas fa-trash"></i> Delete Account
                    </button>
</div>

<!-- Change Password Modal -->
        <div id="changePasswordModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center;">
            <div style="background:#fff; border-radius:8px; padding:24px; max-width:400px; width:90%;">
                <h3 style="margin:0 0 16px; font-size:1.125rem;">Change Password</h3>
                <form method="POST" action="{{ route('profile.save') }}">
                @csrf
                    <input type="hidden" name="intent" value="password">
                    <div style="margin-bottom:12px;">
                        <label style="display:block; font-size:0.875rem; margin-bottom:4px; font-weight:600;">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label style="display:block; font-size:0.875rem; margin-bottom:4px; font-weight:600;">New Password</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <div style="margin-bottom:16px;">
                        <label style="display:block; font-size:0.875rem; margin-bottom:4px; font-weight:600;">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-input" required>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn-compact btn-success">Change Password</button>
                        <button type="button" class="btn-compact btn-secondary" onclick="document.getElementById('changePasswordModal').style.display='none'">Cancel</button>
                </div>
            </form>
    </div>
</div>

<!-- Delete Account Modal -->
        <div id="deleteAccountModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center;">
            <div style="background:#fff; border-radius:8px; padding:24px; max-width:400px; width:90%;">
                <h3 style="margin:0 0 16px; font-size:1.125rem; color:#ef4444;">Delete Account</h3>
                <p style="color:#6b7280; font-size:0.875rem; margin-bottom:16px;">This action cannot be undone.</p>
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <div style="margin-bottom:16px;">
                        <label style="display:block; font-size:0.875rem; margin-bottom:4px; font-weight:600;">Confirm Password</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn-compact btn-danger">Delete Account</button>
                        <button type="button" class="btn-compact btn-secondary" onclick="document.getElementById('deleteAccountModal').style.display='none'">Cancel</button>
                    </div>
                </form>
        </div>
    </div>
    @endif
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Photo upload
    window.uploadPhoto = function() {
        const fileInput = document.getElementById('photoInput');
        const file = fileInput?.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            alert('File must be less than 2MB');
            return;
        }
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image (JPEG, PNG, GIF, WebP)');
            return;
        }

        const form = document.getElementById('photoUploadForm');
        const formData = new FormData();
        formData.append('photo', file);
        // unified endpoint uses POST; no method override needed

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data?.success && data.photo_url) {
                const img = form.querySelector('img');
                if (img) img.src = data.photo_url + '?t=' + Date.now();
                alert('Profile photo updated!');
            } else {
                alert(data?.message || 'Failed to update photo');
            }
        })
        .catch(() => alert('Upload failed'))
        .finally(() => fileInput.value = '');
    };

    // Load feedback
    loadUserFeedback();
});

window.loadUserFeedback = async function() {
    try {
        const response = await fetch('/api/user-ratings/{{ $user->id }}', {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (!response.ok) throw new Error('Failed to load');

        const data = await response.json();
        if (data.success && data.ratings?.length > 0) {
                displayUserFeedback(data.ratings);
            } else {
            displayNoFeedback();
        }
    } catch (error) {
        displayError('Failed to load feedback');
    }
};

window.displayUserFeedback = function(ratings) {
    const container = document.getElementById('user-feedback-section');
    if (!ratings?.length) return displayNoFeedback();

    container.innerHTML = ratings.slice(0, 5).map(r => `
        <div style="border:1px solid #e5e7eb; border-radius:6px; padding:12px; margin-bottom:8px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <div>
                    <strong style="font-size:0.875rem;">${r.rater?.name || 'Anonymous'}</strong>
                    <small style="color:#6b7280; display:block; font-size:0.75rem;">${new Date(r.created_at).toLocaleDateString()}</small>
                            </div>
                <div style="color:#fbbf24;">
                    ${generateStars(r.overall_rating)} <small>${r.overall_rating}/5</small>
                            </div>
                        </div>
            ${r.written_feedback ? `<p style="font-size:0.8rem; color:#4b5563; font-style:italic; margin:8px 0;">"${r.written_feedback}"</p>` : ''}
            <small style="color:#6b7280; font-size:0.75rem;">
                💬 ${r.communication_rating}/5 • 🤝 ${r.helpfulness_rating}/5 • 🧠 ${r.knowledge_rating}/5
                            </small>
                        </div>
    `).join('') + (ratings.length > 5 ? `<small style="color:#6b7280; text-align:center; display:block; font-size:0.75rem;">Showing 5 of ${ratings.length}</small>` : '');
};

window.displayNoFeedback = function() {
    document.getElementById('user-feedback-section').innerHTML = `
        <div style="text-align:center; padding:20px; color:#6b7280;">
            <i class="fas fa-star" style="font-size:2rem; margin-bottom:8px;"></i>
            <p style="font-size:0.875rem; margin:0;">No feedback yet</p>
        </div>
    `;
};

window.displayError = function(msg) {
    document.getElementById('user-feedback-section').innerHTML = `
        <div style="text-align:center; padding:12px; background:#fee2e2; color:#991b1b; border-radius:6px; font-size:0.875rem;">
            <i class="fas fa-exclamation-triangle"></i> ${msg}
        </div>
    `;
};

window.generateStars = function(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= Math.floor(rating)) stars += '★';
        else if (i - 0.5 <= rating) stars += '½';
        else stars += '☆';
    }
    return stars;
};

// Modal click outside to close
document.addEventListener('click', function(e) {
    ['changePasswordModal', 'deleteAccountModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (e.target === modal) modal.style.display = 'none';
    });
});
</script>
<script>
// AJAX save for profile form with inline success/failure alerts
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');
    const alerts = document.getElementById('profile-alert-container');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        // Ensure AJAX headers for controller to return JSON
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(async (res) => {
            const isJson = (res.headers.get('content-type') || '').includes('application/json');
            const data = isJson ? await res.json() : null;
            if (!res.ok) {
                const msg = data?.message || 'Failed to update profile';
                throw new Error(msg);
            }
            if (data?.success) {
                showProfileAlert('Profile updated successfully!', 'success');
                // Update visible fields if provided
                if (data.user) {
                    const nameEl = document.querySelector('.profile-header h2');
                    if (nameEl && data.user.name) nameEl.textContent = data.user.name;
                    const emailEl = document.querySelector('.profile-header p');
                    if (emailEl && data.user.email) emailEl.textContent = data.user.email;
                }
            } else {
                showProfileAlert(data?.message || 'Failed to update profile', 'danger');
            }
        })
        .catch((err) => {
            showProfileAlert(err.message || 'Failed to update profile', 'danger');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        });
    });

    function showProfileAlert(message, type) {
        if (!alerts) return;
        const cls = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
        alerts.innerHTML = `
            <div class="alert-compact ${cls}">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i> ${message}
            </div>
        `;
        // Scroll page to top so the message is visible
        try {
            if (alerts.scrollIntoView) {
                alerts.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        } catch (e) {
            window.scrollTo(0, 0);
        }
        // Auto-clear after a while
        setTimeout(() => { if (alerts) alerts.innerHTML = ''; }, 5000);
    }
});
</script>
@endsection
