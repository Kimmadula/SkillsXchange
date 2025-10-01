<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trade Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Debug Information -->
                    <div class="mb-6 p-4 bg-yellow-100 border border-yellow-400 rounded">
                        <h3 class="font-bold text-yellow-800">Debug Information:</h3>
                        <p><strong>Trade ID:</strong> {{ $trade->id ?? 'Not found' }}</p>
                        <p><strong>Trade Status:</strong> {{ $trade->status ?? 'Not found' }}</p>
                        <p><strong>User ID:</strong> {{ $user->id ?? 'Not found' }}</p>
                        <p><strong>Offering User:</strong> {{ $trade->offeringUser->firstname ?? 'Not found' }}</p>
                        <p><strong>Offering Skill:</strong> {{ $trade->offeringSkill->name ?? 'Not found' }}</p>
                        <p><strong>Looking Skill:</strong> {{ $trade->lookingSkill->name ?? 'Not found' }}</p>
                    </div>
                    
                    <!-- Trade Header -->
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">Trade Details</h1>
                        <p class="text-gray-600">Review the details of this trade opportunity</p>
                    </div>

                    <!-- Trade Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        
                        <!-- Offering User -->
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900 mb-4">Offering</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="font-medium text-gray-700">User:</span>
                                    <span class="text-gray-900">{{ $trade->offeringUser->firstname }} {{ $trade->offeringUser->lastname }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Skill:</span>
                                    <span class="text-gray-900">{{ $trade->offeringSkill->name }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Category:</span>
                                    <span class="text-gray-900">{{ $trade->offeringSkill->category }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Looking For -->
                        <div class="bg-green-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-green-900 mb-4">Looking For</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="font-medium text-gray-700">Skill:</span>
                                    <span class="text-gray-900">{{ $trade->lookingSkill->name }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Category:</span>
                                    <span class="text-gray-900">{{ $trade->lookingSkill->category }}</span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <em>Anyone with this skill can request this trade</em>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trade Details -->
                    <div class="bg-gray-50 p-6 rounded-lg mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Trade Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="font-medium text-gray-700">Start Date:</span>
                                <span class="text-gray-900">{{ \Carbon\Carbon::parse($trade->start_date)->format('M d, Y') }}</span>
                            </div>
                            @if($trade->end_date)
                            <div>
                                <span class="font-medium text-gray-700">End Date:</span>
                                <span class="text-gray-900">{{ \Carbon\Carbon::parse($trade->end_date)->format('M d, Y') }}</span>
                            </div>
                            @endif
                            @if($trade->available_from)
                            <div>
                                <span class="font-medium text-gray-700">Available From:</span>
                                <span class="text-gray-900">{{ $trade->available_from }}</span>
                            </div>
                            @endif
                            @if($trade->available_to)
                            <div>
                                <span class="font-medium text-gray-700">Available To:</span>
                                <span class="text-gray-900">{{ $trade->available_to }}</span>
                            </div>
                            @endif
                            <div>
                                <span class="font-medium text-gray-700">Gender Preference:</span>
                                <span class="text-gray-900 capitalize">{{ $trade->gender_pref }}</span>
                            </div>
                            @if($trade->location)
                            <div>
                                <span class="font-medium text-gray-700">Location:</span>
                                <span class="text-gray-900">{{ $trade->location }}</span>
                            </div>
                            @endif
                        </div>
                        
                        @if($trade->preferred_days)
                        <div class="mt-4">
                            <span class="font-medium text-gray-700">Preferred Days:</span>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach($trade->preferred_days as $day)
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{{ $day }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Trade Status -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-gray-700">Status:</span>
                                <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    @if($trade->status === 'active') bg-green-100 text-green-800
                                    @elseif($trade->status === 'completed') bg-blue-100 text-blue-800
                                    @elseif($trade->status === 'cancelled') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($trade->status) }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                Created: {{ $trade->created_at->format('M d, Y g:i A') }}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between">
                        <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Back to Dashboard
                        </a>
                        
                        @if($user->id !== $trade->user_id && $trade->status === 'open')
                        <form action="{{ route('trades.request', $trade->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Request This Trade
                            </button>
                        </form>
                        @elseif($user->id === $trade->user_id)
                        <span class="text-gray-500 text-sm">This is your trade</span>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
