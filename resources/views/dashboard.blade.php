<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="bg-black shadow-lg rounded-lg p-6 max-w-7xl mx-auto">
            <h3 class="text-white text-2xl font-bold mb-6">
                {{ __('Welcome back,') }} {{ Auth::user()->name }}!
            </h3>

            <!-- Search & Filter -->
            <div class="flex items-center justify-between">
                <!-- Search Bar -->
                <form action="{{ route('dashboard') }}" method="get" class="flex items-center justify-center w-1/4">
                    <input type="text" class="w-full p-2 rounded-l-lg bg-gray-800 text-white" name="search"
                        value="{{ request('search') }}" placeholder="Search jobs...">
                    <button type="submit"
                        class="bg-indigo-500 text-white p-2 rounded-r-lg border border-indigo-500">Search</button>

                    @if (request('filter'))
                        <input type="hidden" name="filter" value="{{ request('filter') }}">
                    @endif

                    @if (request('search'))
                        <a href="{{ route('dashboard', ['filter' => request('filter')]) }}"
                            class="text-white p-2 rounded-lg">
                            Clear
                        </a>
                    @endif
                </form>

                <!-- Filter -->
                <div class="flex space-x-2">
                    <a href="{{ route('dashboard', ['filter' => 'Full-Time', 'search' => request('search')]) }}"
                        class="bg-indigo-500 text-white p-2 rounded-lg">Full-Time</a>
                    <a href="{{ route('dashboard', ['filter' => 'Remote', 'search' => request('search')]) }}"
                        class="bg-indigo-500 text-white p-2 rounded-lg">Remote</a>
                    <a href="{{ route('dashboard', ['filter' => 'Hybrid', 'search' => request('search')]) }}"
                        class="bg-indigo-500 text-white p-2 rounded-lg">Hybrid</a>
                    <a href="{{ route('dashboard', ['filter' => 'Contract', 'search' => request('search')]) }}"
                        class="bg-indigo-500 text-white p-2 rounded-lg">Contract</a>

                    @if (request('filter'))
                        <a href="{{ route('dashboard', ['search' => request('search')]) }}"
                            class="text-white p-2 rounded-lg">
                            Clear
                        </a>
                    @endif
                </div>
            </div>

            <!-- Job Listings -->
            <div class="space-y-4 mt-6">
                <!-- Job Item -->
                @forelse ($jobs as $job)
                    <div class="border-b border-white/10 pb-4 flex justify-between items-center">
                        <div>
                            <a href="{{ route('job-vacancies.show', $job->id) }}"
                                class="text-lg font-semibold text-blue-400 hover:underline">{{ $job->title }}</a>
                            <p class="text-sm text-white">{{ $job->company->name }} - {{$job->location}}</p>
                            <p class="text-sm text-white">${{ number_format($job->salary, 2)}}/ yearly</p>
                        </div>
                        <span class="bg-blue-500 text-white p-2 rounded-lg">{{ $job->type }}</span>
                    </div>
                @empty
                    <p class="text-white text-2xl font-bold">No Jobs found.</p>
                @endforelse
            </div>
            <!-- Pagination -->
            <div class="mt-6">
                {{ $jobs->links() }}
            </div>
        </div>
</x-app-layout>