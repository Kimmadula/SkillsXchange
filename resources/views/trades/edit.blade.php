@extends('layouts.app')

@section('content')
<main role="trades-edit" style="padding:32px; max-width:960px; margin:0 auto; overflow-x:hidden;">
    <style>
        @media (max-width: 480px) {
            main[role="trades-edit"] { padding:16px !important; }
            .header-bar { flex-wrap: wrap; gap: 10px; }
            .header-bar a { width: 100%; text-align: center; }
            .grid-responsive { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 360px) { .header-bar h1 { font-size: 1.125rem !important; } }
        form.trades-edit-form { overflow: hidden; }
        .wrap-anywhere { overflow-wrap: anywhere; word-break: break-word; }
        .flex-wrap-row { display:flex; gap:12px; align-items:center; flex-wrap: wrap; }
        .flex-wrap-row > label { margin-top: 6px; }
        .grid-responsive { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; }
        .w-100 { width:100%; max-width:100%; }
    </style>

    <div class="header-bar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1 style="font-size:1.5rem; margin:0;">Edit Skill Trade</h1>
        <a href="{{ route('dashboard') }}" style="padding:8px 12px; background:#6b7280; color:#fff; text-decoration:none; border-radius:6px; font-size:0.875rem;">← Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div style="background:#def7ec; color:#03543f; padding:10px 12px; border-radius:6px; margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:10px 12px; border-radius:6px; margin-bottom:16px;">
            ⚠️ {{ session('warning') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:10px 12px; border-radius:6px; margin-bottom:16px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php($hasActiveRequests = isset($hasActiveRequests) ? $hasActiveRequests : false)
    @php($activeRequestCount = isset($activeRequestCount) ? $activeRequestCount : 0)

    @if($hasActiveRequests)
        <div style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:12px 16px; border-radius:6px; margin-bottom:16px; border-left:4px solid #f59e0b;">
            <div style="display:flex; align-items:start; gap:8px;">
                <span style="font-size:1.2rem;">⚠️</span>
                <div style="flex:1;">
                    <strong style="display:block; margin-bottom:4px;">Skills Cannot Be Changed</strong>
                    <p style="margin:0; font-size:0.9rem;">
                        @if($trade->status === 'ongoing')
                            This trade session is currently ongoing.
                            You cannot change the offering or looking-for skills until the session is completed or closed.
                        @else
                            This trade has {{ $activeRequestCount }} active request(s) (pending or accepted).
                            You cannot change the offering or looking-for skills until all requests are responded to or cancelled.
                            <a href="{{ route('trades.requests') }}" style="color:#92400e; text-decoration:underline; font-weight:600;">Manage requests</a>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    @php($user = auth()->user())
    <form method="POST" action="{{ route('trades.update', $trade) }}" class="trades-edit-form" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px; display:grid; gap:16px;">
        @csrf
        @method('PUT')

        <div>
            <label style="display:block; font-weight:600; margin-bottom:4px;">Name</label>
            <div class="flex-wrap-row">
                <input type="text" value="{{ $user->firstname }} {{ $user->middlename }} {{ $user->lastname }}" readonly class="w-100" style="flex:1; padding:10px; border:1px solid #ddd; border-radius:6px; background:#f9fafb;" />
                <label style="display:flex; gap:6px; align-items:center; font-size:0.9rem;">
                    <input type="checkbox" name="use_username" value="1" {{ old('use_username', $trade->use_username ?? false) ? 'checked' : '' }} /> Use username ({{ $user->username }})
                </label>
            </div>
        </div>

        @php($currentOfferingCategory = optional($trade->offeringSkill)->category)
        <div>
            <label for="offering_skill_category" style="display:block; font-weight:600; margin-bottom:4px;">Skill Category (What you're offering)</label>
            <select id="offering_skill_category" required class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px; {{ $hasActiveRequests ? 'background:#f3f4f6; cursor:not-allowed;' : '' }}" {{ $hasActiveRequests ? 'disabled' : '' }}>
                <option value="">Select a category first</option>
                @if(isset($userAllSkills) && $userAllSkills->count() > 0)
                    @foreach($userAllSkills->groupBy('category') as $category => $group)
                        <option value="{{ $category }}" {{ $currentOfferingCategory === $category ? 'selected' : '' }}>{{ $category }} ({{ $group->count() }} skills)</option>
                    @endforeach
                @endif
            </select>
            @if($hasActiveRequests)
                <input type="hidden" name="offering_skill_id" value="{{ $trade->offering_skill_id }}">
                <small class="wrap-anywhere" style="color:#92400e; font-size:0.75rem; display:block; margin-top:4px;">
                    🔒 Locked: Cannot change while there are active requests
                </small>
            @else
                <small class="wrap-anywhere" style="color:#6b7280; font-size:0.75rem;">Select a category to see your registered and acquired skills</small>
            @endif
            @if(!isset($userAllSkills) || $userAllSkills->count() === 0)
                <div style="color:#e53e3e; font-size:0.875rem; margin-top:4px;">
                    ⚠️ You need to register or acquire a skill first to post trades. <a href="{{ route('profile.edit') }}">Add skills to your profile</a>.
                </div>
            @endif
        </div>

        <div>
            <label for="offering_skill_id" style="display:block; font-weight:600; margin-bottom:4px;">Skill Name (What you're offering)</label>
            <select id="offering_skill_id" name="offering_skill_id" required class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px; {{ $hasActiveRequests ? 'background:#f3f4f6; cursor:not-allowed;' : '' }}" {{ $hasActiveRequests || !$currentOfferingCategory ? 'disabled' : '' }}>
                @if(!$currentOfferingCategory)
                    <option value="">Select a category first</option>
                @endif
                @if(isset($userAllSkills) && $userAllSkills->count() > 0)
                    @foreach($userAllSkills as $skill)
                        <option value="{{ $skill->skill_id }}" data-category="{{ $skill->category }}" {{ $trade->offering_skill_id == $skill->skill_id ? 'selected' : '' }}>
                            {{ $skill->name }}
                        </option>
                    @endforeach
                @endif
            </select>
            @if($hasActiveRequests)
                <div style="color:#92400e; font-size:0.8rem; margin-top:2px;">
                    🔒 This field is locked because there are active requests on this trade.
                </div>
            @elseif(isset($userAllSkills) && $userAllSkills->count() > 0)
                <div style="color:#6b7280; font-size:0.8rem; margin-top:2px;">
                    Your registered and acquired skills are shown. <a href="{{ route('profile.edit') }}">Manage your skills</a>.
                </div>
            @endif
            @error('offering_skill_id')<div style="color:#e53e3e; font-size:0.875rem;">{{ $message }}</div>@enderror
        </div>

        @php($currentCategory = optional($trade->lookingSkill)->category)
        <div>
            <label for="looking_skill_category" style="display:block; font-weight:600; margin-bottom:4px;">Skill Category (What you want to learn)</label>
            <select id="looking_skill_category" required class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px; {{ $hasActiveRequests ? 'background:#f3f4f6; cursor:not-allowed;' : '' }}" {{ $hasActiveRequests ? 'disabled' : '' }}>
                <option value="">Select a category first</option>
                @foreach($skills->groupBy('category') as $category => $group)
                    <option value="{{ $category }}" {{ $currentCategory === $category ? 'selected' : '' }}>{{ $category }} ({{ $group->count() }} skills)</option>
                @endforeach
            </select>
            @if($hasActiveRequests)
                <input type="hidden" name="looking_skill_id" value="{{ $trade->looking_skill_id }}">
                <small class="wrap-anywhere" style="color:#92400e; font-size:0.75rem; display:block; margin-top:4px;">
                    🔒 Locked: Cannot change while there are active requests
                </small>
            @else
                <small class="wrap-anywhere" style="color:#6b7280; font-size:0.75rem;">Select a category to see available skills</small>
            @endif
        </div>

        <div>
            <label for="looking_skill_id" style="display:block; font-weight:600; margin-bottom:4px;">Skill Name (What you want to learn)</label>
            <select id="looking_skill_id" name="looking_skill_id" required class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px; {{ $hasActiveRequests ? 'background:#f3f4f6; cursor:not-allowed;' : '' }}" {{ $hasActiveRequests || !$currentCategory ? 'disabled' : '' }}>
                @if(!$currentCategory)
                    <option value="">Select a category first</option>
                @endif
                @foreach($skills as $s)
                    <option value="{{ $s->skill_id }}" data-category="{{ $s->category }}" {{ $trade->looking_skill_id == $s->skill_id ? 'selected' : '' }} {{ ($user->skill_id ?? null) == $s->skill_id ? 'disabled' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @if($hasActiveRequests)
                <div style="color:#92400e; font-size:0.8rem; margin-top:2px;">
                    🔒 This field is locked because there are active requests on this trade.
                </div>
            @endif
            @error('looking_skill_id')<div style="color:#e53e3e; font-size:0.875rem;">{{ $message }}</div>@enderror
        </div>

        <fieldset style="border:1px solid #eee; border-radius:8px; padding:12px;">
            <legend style="padding:0 8px; color:#374151;">Schedule Preferences</legend>
            <div class="grid-responsive">
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Start Date</label>
                    <input type="date" name="start_date" value="{{ \Illuminate\Support\Carbon::parse($trade->start_date)->format('Y-m-d') }}" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" />
                </div>
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:6px;">End Date (optional)</label>
                    <input type="date" name="end_date" value="{{ $trade->end_date ? \Illuminate\Support\Carbon::parse($trade->end_date)->format('Y-m-d') : '' }}" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" />
                </div>
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:6px;">Available From</label>
                    <input type="time" name="available_from" value="{{ $trade->available_from }}" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" />
            </div>
            <div>
                    <label style="display:block; font-weight:600; margin-bottom:6px;">Available To</label>
                    <input type="time" name="available_to" value="{{ $trade->available_to }}" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" />
            </div>

        </div>
            <div style="margin-top:10px; display:flex; gap:12px; flex-wrap:wrap;">
                @php($days=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'])
                @php($selectedDays = is_array($trade->preferred_days) ? $trade->preferred_days : (is_string($trade->preferred_days) ? (json_decode($trade->preferred_days, true) ?? explode(',', $trade->preferred_days)) : []))
                @foreach($days as $d)
                    <label style="display:flex; gap:6px; align-items:center;">
                        <input type="checkbox" name="preferred_days[]" value="{{ $d }}" {{ in_array($d, $selectedDays ?? []) ? 'checked' : '' }} /> {{ $d }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset style="border:1px solid #eee; border-radius:8px; padding:12px;">
            <legend style="padding:0 8px; color:#374151;">Other Preferences</legend>
            <div class="grid-responsive">
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:4px;">Gender Preference</label>
                    <select name="gender_pref" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <option value="any" {{ ($trade->gender_pref ?? 'any')==='any' ? 'selected' : '' }}>Any gender</option>
                        <option value="male" {{ ($trade->gender_pref ?? '')==='male' ? 'selected' : '' }}>Male only</option>
                        <option value="female" {{ ($trade->gender_pref ?? '')==='female' ? 'selected' : '' }}>Female only</option>
                    </select>
                    <div style="color:#6b7280; font-size:0.8rem; margin-top:2px;">Your gender: <strong>{{ ucfirst($user->gender ?? 'Not specified') }}</strong></div>
                </div>
                <div>
                    <label for="location" style="display:block; font-weight:600; margin-bottom:4px;">Location (Cebu, Philippines)</label>
                    <input type="text" id="location" name="location" value="{{ $trade->location }}" placeholder="Enter your location in Cebu" class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px;" list="location-suggestions" autocomplete="off" />
                    <datalist id="location-suggestions"></datalist>
                    <div class="wrap-anywhere" style="color:#6b7280; font-size:0.8rem; margin-top:2px;">Start typing to see Cebu city and barangay suggestions</div>
                    @error('location')<div style="color:#e53e3e; font-size:0.875rem;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:4px;">Session Type</label>
                    <select name="session_type" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <option value="any" {{ $trade->session_type==='any' ? 'selected' : '' }}>Any</option>
                        <option value="online" {{ $trade->session_type==='online' ? 'selected' : '' }}>Online</option>
                        <option value="onsite" {{ $trade->session_type==='onsite' ? 'selected' : '' }}>On-site</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <div>
            <button type="submit" style="padding:10px 14px; background:#2563eb; color:#fff; border:none; border-radius:6px;">Save Changes</button>
            <a href="{{ route('trades.manage') }}" style="margin-left:8px;">Cancel</a>
        </div>
    </form>

    <script>
        // Skill category selection for both "offering" and "looking for"
        document.addEventListener('DOMContentLoaded', function() {
            const hasActiveRequests = @json($hasActiveRequests ?? false);

            // Function to handle category-based skill filtering
            function setupSkillFilter(categorySelectId, skillSelectId) {
                const categorySelect = document.getElementById(categorySelectId);
                const skillSelect = document.getElementById(skillSelectId);

                // Don't setup if fields are disabled due to active requests
                if (hasActiveRequests && (categorySelect.disabled || skillSelect.disabled)) {
                    return;
                }

                const allOptions = Array.from(skillSelect.options);
                const currentCategory = categorySelect.value;
                const currentSelected = skillSelect.value;

                function repopulateSkills(category) {
                    skillSelect.innerHTML = '';
                    let placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = category ? 'Select a skill' : 'Select a category first';
                    if (!category) placeholder.selected = true;
                    skillSelect.appendChild(placeholder);

                    if (!category) {
                        skillSelect.disabled = true;
                        return;
                    }

                    skillSelect.disabled = false;
                    allOptions.forEach(option => {
                        if (!option.value) return;
                        if (option.getAttribute('data-category') === category) {
                            const clone = option.cloneNode(true);
                            skillSelect.appendChild(clone);
                        }
                    });

                    // Try to re-select existing value
                    if (currentSelected) {
                        const toSelect = Array.from(skillSelect.options).find(o => o.value === currentSelected);
                        if (toSelect) toSelect.selected = true;
                    }
                }

                // Initial population by current category
                if (currentCategory) {
                    repopulateSkills(currentCategory);
                }

                categorySelect.addEventListener('change', function() {
                    if (!hasActiveRequests) {
                        repopulateSkills(this.value);
                    }
                });
            }

            // Setup for "offering" skill (only if not locked)
            if (!hasActiveRequests) {
                setupSkillFilter('offering_skill_category', 'offering_skill_id');
            }

            // Setup for "looking for" skill (only if not locked)
            if (!hasActiveRequests) {
                setupSkillFilter('looking_skill_category', 'looking_skill_id');
            }
        });

        // Location suggestions for Cebu
        const locationInput = document.getElementById('location');
        const locationSuggestions = document.getElementById('location-suggestions');
        if (locationInput) {
            locationInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length < 2) { locationSuggestions.innerHTML = ''; return; }
                fetch(`/api/addresses/cebu/suggest?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        locationSuggestions.innerHTML = '';
                        data.suggestions.forEach(suggestion => {
                            const option = document.createElement('option');
                            option.value = suggestion;
                            locationSuggestions.appendChild(option);
                        });
                    })
                    .catch(() => {});
            });
        }
    </script>
</main>
@endsection


