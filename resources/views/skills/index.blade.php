@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 fw-bold text-dark mb-2">Skills Directory</h1>
                <p class="text-muted">Explore and manage available skills</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('skills.history') }}" class="btn btn-outline-primary">
                    <i class="fas fa-history me-2"></i>My History
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('skills.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search Skills</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name or description..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name</option>
                            <option value="category" {{ request('sort') == 'category' ? 'selected' : '' }}>Category</option>
                            <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Date Added</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Order</label>
                        <select name="order" class="form-select">
                            <option value="asc" {{ request('order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                            <option value="desc" {{ request('order') == 'desc' ? 'selected' : '' }}>Descending</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Skills Grid -->
        <div class="row g-4">
            @forelse($skills as $skill)
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1">{{ $skill->name }}</h5>
                                <span class="badge bg-primary">{{ $skill->category }}</span>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('skills.show', $skill->skill_id) }}">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    @auth
                                        @if(Auth::user()->skills()->where('skill_id', $skill->skill_id)->exists())
                                        <li>
                                            <form action="{{ route('skills.remove-from-profile', $skill->skill_id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-minus-circle me-2"></i>Remove from Profile
                                                </button>
                                            </form>
                                        </li>
                                        @else
                                        <li>
                                            <form action="{{ route('skills.add-to-profile', $skill->skill_id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="fas fa-plus-circle me-2"></i>Add to Profile
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                    @endauth
                                </ul>
                            </div>
                        </div>

                        @if($skill->difficulty_level)
                        <div class="mb-2">
                            <small class="text-muted">Difficulty: </small>
                            <span class="badge bg-{{ 
                                $skill->difficulty_level == 'beginner' ? 'success' : 
                                ($skill->difficulty_level == 'intermediate' ? 'warning' : 
                                ($skill->difficulty_level == 'advanced' ? 'danger' : 'dark')) 
                            }}">
                                {{ ucfirst($skill->difficulty_level) }}
                            </span>
                        </div>
                        @endif

                        @if($skill->description)
                        <p class="card-text text-muted flex-grow-1">
                            {{ Str::limit($skill->description, 120) }}
                        </p>
                        @endif

                        @if($skill->prerequisites)
                        <div class="mb-3">
                            <small class="text-muted">
                                <strong>Prerequisites:</strong> {{ Str::limit($skill->prerequisites, 80) }}
                            </small>
                        </div>
                        @endif

                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-users me-1"></i>
                                    {{ $skill->users()->count() }} users have this skill
                                </small>
                                @auth
                                    @if(Auth::user()->skills()->where('skill_id', $skill->skill_id)->exists())
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>In Your Profile
                                    </span>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Skills Found</h4>
                    <p class="text-muted mb-4">
                        @if(request()->hasAny(['search', 'category']))
                            No skills match your current filters. Try adjusting your search criteria.
                        @else
                            No skills have been added yet. Be the first to add a skill!
                        @endif
                    </p>
                    @if(request()->hasAny(['search', 'category']))
                    <a href="{{ route('skills.index') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                    @endif
                    <a href="{{ route('skills.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Skill
                    </a>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($skills->hasPages())
        <div class="d-flex justify-content-center mt-5">
            {{ $skills->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
