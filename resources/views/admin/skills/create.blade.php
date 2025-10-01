@extends('layouts.app')

@section('content')
<main style="padding:32px;">
    <h1 style="font-size:2rem; margin-bottom:1rem;">Add Skill</h1>

    @if($errors->any())
        <div id="error-message" style="background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:12px 16px; border-radius:6px; margin-bottom:16px; position:relative;">
            <button onclick="closeErrorMessage()" style="position:absolute; top:8px; right:8px; background:none; border:none; color:#dc2626; font-size:18px; cursor:pointer; padding:0; width:20px; height:20px; display:flex; align-items:center; justify-content:center;">×</button>
            <div style="font-weight:600; margin-bottom:8px; padding-right:20px;">⚠️ Please fix the following errors:</div>
            <ul style="margin:0; padding-left:20px; padding-right:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background:#fff; padding:16px; border-radius:8px; box-shadow:0 2px 8px #eee;">
        <div style="background:#f0f9ff; border:1px solid #0ea5e9; border-radius:6px; padding:12px; margin-bottom:16px;">
            <p style="margin:0; color:#0c4a6e; font-size:0.875rem;">
                <strong>Note:</strong> Skill names will be automatically formatted (proper case, single spaces). Duplicate skills are not allowed.
            </p>
        </div>
        <form method="POST" action="{{ route('admin.skill.store') }}" style="display:grid; gap:12px; max-width:540px;">
            @csrf
            <div>
                <label for="name" style="display:block; font-weight:600; margin-bottom:4px;">Skill Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required style="width:100%; padding:10px; border:1px solid {{ $errors->has('name') ? '#dc2626' : '#ddd' }}; border-radius:6px;" placeholder="e.g., Web Development" />
                @error('name')<div style="color:#dc2626; font-size:0.875rem; margin-top:4px; font-weight:500;">⚠️ {{ $message }}</div>@enderror
            </div>
            <div>
                <label for="category" style="display:block; font-weight:600; margin-bottom:4px;">Category</label>
                <input id="category" name="category" type="text" value="{{ old('category') }}" required style="width:100%; padding:10px; border:1px solid {{ $errors->has('category') ? '#dc2626' : '#ddd' }}; border-radius:6px;" placeholder="e.g., IT" />
                @error('category')<div style="color:#dc2626; font-size:0.875rem; margin-top:4px; font-weight:500;">⚠️ {{ $message }}</div>@enderror
            </div>
            <div>
                <button type="submit" style="padding:10px 16px; background:#2563eb; color:#fff; border:none; border-radius:6px; cursor:pointer;">Save</button>
                <a href="{{ route('admin.skills.index') }}" style="margin-left:8px;">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
function closeErrorMessage() {
    const errorMessage = document.getElementById('error-message');
    if (errorMessage) {
        errorMessage.style.display = 'none';
    }
}
</script>

@endsection


