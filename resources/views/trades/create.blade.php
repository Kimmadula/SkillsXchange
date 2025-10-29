@extends('layouts.app')

@section('content')
<main role="trades-create" style="padding:32px; max-width:960px; margin:0 auto; overflow-x:hidden;">
    <style>
        /* Responsive helpers for this view only */
        @media (max-width: 480px) {
            main[role="trades-create"] { padding:16px !important; }
            .header-bar { flex-wrap: wrap; gap: 10px; }
            .header-bar a { width: 100%; text-align: center; }
            .grid-responsive { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 360px) {
            .header-bar h1 { font-size: 1.125rem !important; }
        }
        /* Hard overflow guards */
        form.trades-create-form { overflow: hidden; }
        .wrap-anywhere { overflow-wrap: anywhere; word-break: break-word; }
        .flex-wrap-row { display:flex; gap:12px; align-items:center; flex-wrap: wrap; }
        .flex-wrap-row > label { margin-top: 6px; }
        .w-100 { width:100%; max-width:100%; }
    </style>
    <div class="header-bar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1 style="font-size:1.5rem; margin:0;">Post a Skill Trade</h1>
        <a href="{{ route('dashboard') }}" style="padding:8px 12px; background:#6b7280; color:#fff; text-decoration:none; border-radius:6px; font-size:0.875rem;">
            ← Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div style="background:#def7ec; color:#03543f; padding:10px 12px; border-radius:6px; margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('trades.store') }}" class="trades-create-form" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px; display:grid; gap:16px;">
        @csrf

        <div>
            <label style="display:block; font-weight:600; margin-bottom:4px;">Name</label>
            <div class="flex-wrap-row">
                <input type="text" value="{{ $user->firstname }} {{ $user->middlename }} {{ $user->lastname }}" readonly class="w-100" style="flex:1; padding:10px; border:1px solid #ddd; border-radius:6px; background:#f9fafb;" />
                <label style="display:flex; gap:6px; align-items:center; font-size:0.9rem;">
                    <input type="checkbox" name="use_username" value="1" /> Use username ({{ $user->username }})
                </label>
            </div>
        </div>

        <div>
            <label for="offering_skill_id" style="display:block; font-weight:600; margin-bottom:4px;">Offering (Your Registered Skill)</label>
            <select id="offering_skill_id" name="offering_skill_id" required class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px; background:#f9fafb;" disabled>
                @if($user->skill)
                    <option value="{{ $user->skill->skill_id }}" selected>
                        {{ $user->skill->category }} - {{ $user->skill->name }}
                    </option>
                @else
                    <option value="">No skill registered</option>
                @endif
            </select>
            @if($user->skill)
                <input type="hidden" name="offering_skill_id" value="{{ $user->skill->skill_id }}">
                <div style="color:#059669; font-size:0.875rem; margin-top:4px;">
                    ✓ Your registered skill: <strong>{{ $user->skill->category }} - {{ $user->skill->name }}</strong>
                </div>
                <div style="color:#6b7280; font-size:0.8rem; margin-top:2px;">
                    You can only offer your registered skill. Change it in your profile.
                </div>
            @else
                <div style="color:#e53e3e; font-size:0.875rem; margin-top:4px;">
                    ⚠️ You need to register a skill first to post trades.
                </div>
            @endif
            @error('offering_skill_id')<div style="color:#e53e3e; font-size:0.875rem;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="looking_skill_category" style="display:block; font-weight:600; margin-bottom:4px;">Skill Category (What you want to learn)</label>
            <select id="looking_skill_category" required class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                <option value="">Select a category first</option>
                @foreach($skills->groupBy('category') as $category => $group)
                    <option value="{{ $category }}">{{ $category }} ({{ $group->count() }} skills)</option>
                @endforeach
            </select>
            <small class="wrap-anywhere" style="color:#6b7280; font-size:0.75rem;">Select a category to see available skills</small>
            @if($skills->count() === 0)
                <div style="color:#dc2626; font-size:0.875rem; margin-top:4px;">
                    ⚠️ No skills available. Please contact admin to add skills.
                </div>
            @endif
        </div>

        <div>
            <label for="looking_skill_id" style="display:block; font-weight:600; margin-bottom:4px;">Skill Name (What you want to learn)</label>
            <select id="looking_skill_id" name="looking_skill_id" required class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px;" disabled>
                <option value="">Select a category first</option>
                @foreach($skills as $s)
                    <option value="{{ $s->skill_id }}" data-category="{{ $s->category }}" {{ $user->skill_id == $s->skill_id ? 'disabled' : '' }}>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>
            @error('looking_skill_id')<div style="color:#e53e3e; font-size:0.875rem;">{{ $message }}</div>@enderror
        </div>

        <fieldset style="border:1px solid #eee; border-radius:8px; padding:12px;">
            <legend style="padding:0 8px; color:#374151;">Schedule Preferences</legend>

            <div class="grid-responsive" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:4px;">Start Date</label>
                    <input type="date" name="start_date" required min="{{ date('Y-m-d') }}" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" />
                    <small style="color:#6b7280; font-size:0.75rem;">Only today or future dates are allowed</small>
                </div>
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:4px;">End Date (optional)</label>
                    <input type="date" name="end_date" min="{{ date('Y-m-d') }}" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" />
                    <small style="color:#6b7280; font-size:0.75rem;">Must be today or later</small>
                </div>
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:4px;">Available From</label>
                    <input type="time" name="available_from" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" />
                </div>
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:4px;">Available To</label>
                    <input type="time" name="available_to" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" />
                </div>
            </div>
            <div style="margin-top:10px; display:flex; gap:12px; flex-wrap:wrap;">
                @php($days=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'])
                @foreach($days as $d)
                    <label style="display:flex; gap:6px; align-items:center;">
                        <input type="checkbox" name="preferred_days[]" value="{{ $d }}" /> {{ $d }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset style="border:1px solid #eee; border-radius:8px; padding:12px;">
            <legend style="padding:0 8px; color:#374151;">Other Preferences</legend>
            <div class="grid-responsive" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:4px;">Gender Preference</label>
                    <select name="gender_pref" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <option value="any">Any gender</option>
                        <option value="male">Male only</option>
                        <option value="female">Female only</option>
                    </select>
                    <div style="color:#6b7280; font-size:0.8rem; margin-top:2px;">
                        Your gender: <strong>{{ ucfirst($user->gender ?? 'Not specified') }}</strong>
                    </div>
                </div>
                <div>
                    <label for="location" style="display:block; font-weight:600; margin-bottom:4px;">Location (Cebu, Philippines)</label>
                    <input type="text" id="location" name="location" placeholder="Enter your location in Cebu" class="w-100" style="padding:10px; border:1px solid #ddd; border-radius:6px;" list="location-suggestions" autocomplete="off" />
                    <datalist id="location-suggestions"></datalist>
                    <div class="wrap-anywhere" style="color:#6b7280; font-size:0.8rem; margin-top:2px;">
                        Start typing to see Cebu city and barangay suggestions
                    </div>
                    @error('location')<div style="color:#e53e3e; font-size:0.875rem;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:4px;">Session Type</label>
                    <select name="session_type" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <option value="any">Any</option>
                        <option value="online">Online</option>
                        <option value="onsite">On-site</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <div>
            <button type="submit" style="padding:10px 14px; background:#2563eb; color:#fff; border:none; border-radius:6px;">Post Trade</button>
            <a href="{{ route('trades.matches') }}" style="margin-left:8px;">See Matches</a>
        </div>
    </form>



    <script>
        // Skill category selection with debugging
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('looking_skill_category');
            const skillSelect = document.getElementById('looking_skill_id');
            const allOptions = Array.from(skillSelect.options);

            console.log('🔧 Skill selection initialized');
            console.log('📊 Total skill options:', allOptions.length);
            console.log('📋 All options:', allOptions.map(opt => ({value: opt.value, category: opt.getAttribute('data-category'), text: opt.textContent})));

            categorySelect.addEventListener('change', function() {
                const selectedCategory = this.value;
                console.log('🎯 Category selected:', selectedCategory);

                skillSelect.innerHTML = '<option value="">Select a skill</option>';

                if (selectedCategory) {
                    skillSelect.disabled = false;
                    let addedCount = 0;

                    allOptions.forEach(option => {
                        if (!option.value) return; // skip placeholder
                        const optionCategory = option.getAttribute('data-category');
                        console.log('🔍 Checking option:', option.textContent, 'Category:', optionCategory);

                        if (optionCategory === selectedCategory) {
                            skillSelect.appendChild(option.cloneNode(true));
                            addedCount++;
                            console.log('✅ Added skill:', option.textContent);
                        }
                    });

                    console.log('📈 Added', addedCount, 'skills for category:', selectedCategory);

                    if (addedCount === 0) {
                        skillSelect.innerHTML = '<option value="">No skills found for this category</option>';
                        console.log('⚠️ No skills found for category:', selectedCategory);
                    }
                } else {
                    skillSelect.disabled = true;
                    console.log('🔒 Skill select disabled');
                }
            });
        });

        // Location suggestions for Cebu
        const locationInput = document.getElementById('location');
        const locationSuggestions = document.getElementById('location-suggestions');

        locationInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                locationSuggestions.innerHTML = '';
                return;
            }

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
                .catch(error => {
                    console.error('Error fetching location suggestions:', error);
                });
        });
    </script>
</main>
@endsection


