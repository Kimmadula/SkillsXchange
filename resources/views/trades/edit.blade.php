@extends('layouts.chat')

@section('content')
<div style="padding:16px; max-width:800px; margin:0 auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h1 style="font-size:1.25rem; margin:0;">Edit Post</h1>
        <a href="{{ route('trades.manage') }}" style="padding:8px 12px; background:#6b7280; color:#fff; text-decoration:none; border-radius:6px;">Back</a>
    </div>

    @if($errors->any())
        <div style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:10px 12px; border-radius:6px; margin-bottom:12px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('trades.update', $trade) }}" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
        @csrf
        @method('PUT')

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Offering Skill</label>
                <select name="offering_skill_id" required style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
                    @foreach($skills as $skill)
                        <option value="{{ $skill->skill_id }}" {{ $trade->offering_skill_id == $skill->skill_id ? 'selected' : '' }}>{{ $skill->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Looking Skill</label>
                <select name="looking_skill_id" required style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
                    @foreach($skills as $skill)
                        <option value="{{ $skill->skill_id }}" {{ $trade->looking_skill_id == $skill->skill_id ? 'selected' : '' }}>{{ $skill->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Start Date</label>
                <input type="date" name="start_date" value="{{ \Illuminate\Support\Carbon::parse($trade->start_date)->format('Y-m-d') }}" required style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;" />
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">End Date</label>
                <input type="date" name="end_date" value="{{ $trade->end_date ? \Illuminate\Support\Carbon::parse($trade->end_date)->format('Y-m-d') : '' }}" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;" />
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Location</label>
                <input type="text" name="location" value="{{ $trade->location }}" placeholder="City/Area (optional)" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;" />
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Session Type</label>
                <select name="session_type" required style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
                    <option value="any" {{ $trade->session_type==='any' ? 'selected' : '' }}>Any</option>
                    <option value="online" {{ $trade->session_type==='online' ? 'selected' : '' }}>Online</option>
                    <option value="onsite" {{ $trade->session_type==='onsite' ? 'selected' : '' }}>Onsite</option>
                </select>
            </div>
        </div>

        <div style="margin-top:16px; display:flex; gap:8px;">
            <button type="submit" style="padding:10px 14px; background:#1e40af; color:#fff; border:none; border-radius:6px; font-weight:600;">Save Changes</button>
            <a href="{{ route('trades.manage') }}" style="padding:10px 14px; background:#6b7280; color:#fff; text-decoration:none; border-radius:6px;">Cancel</a>
        </div>
    </form>
</div>
@endsection


