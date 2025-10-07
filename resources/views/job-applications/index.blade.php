<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('My Applications') }}
        </h2>
    </x-slot>

    <!-- Validate Session -->
    @if (session('success'))
        <div class="w-full bg-indigo-600 text-white p-4 rounded-md mb-2">
            {{ session('success') }}
        </div>
    @endif

    <div class="py-12">
        <div class="bg-black shadow-lg rounded-lg p-6 max-w-7xl mx-auto space-y-4">
            @forelse ($jobApplications as $jobApplication)
                <div class="bg-gray-900 p-4 rounded-lg">
                    <h3 class="text-lg font-bold text-white">{{ $jobApplication->jobVacancy->title }}</h3>
                    <p class="text-sm">{{ $jobApplication->jobVacancy->company->name }}</p>
                    <p>{{ $jobApplication->jobVacancy->location }}</p>

                    <div class="flex items-center justify-between">
                        <p class="text-sm">{{ $jobApplication->created_at->format('d M Y') }}</p>
                        <p class="px-3 py-1 bg-blue-600 text-white rounded-md">{{ $jobApplication->jobVacancy->type }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span>Applied with: {{ $jobApplication->resume->filename }}</span>
                        <a href="{{ Storage::disk('cloud')->url($jobApplication->resume->fileUri) }}" target="_blank"
                            class="text-indigo-500 hover:text-indigo-600">View Resume </a>
                    </div>

                    <div class="flex flex-start flex-col gap-2">
                        <div class="flex items-center gap-2">
                            @php
                                $status = $jobApplication->status;
                                $statusClasses = match ($status) {
                                    'pending' => 'bg-yellow-500',
                                    'accepted' => 'bg-green-500',
                                    'rejected' => 'bg-red-500'
                                }; 
                            @endphp
                            <p class="text-sm p-2 w-fit {{ $statusClasses }} rounded-md mt-4">
                                Status: {{ $jobApplication->status }}
                            </p>
                            <p class="text-sm p-2 w-fit bg-indigo-500 rounded-md mt-4">
                                Score: {{ $jobApplication->aiGeneratedScore }}
                            </p>
                        </div>

                        <h4 class="text-md font-bold">AI Feedback:</h4>
                        <p class="text-sm">{{ $jobApplication->aiGeneratedFeedback }}</p>
                    </div>
                </div>
            @empty
                <div class="bg-gray-900 p-4 rounded-lg">
                    <p class="text-gray-400">No applications found.</p>
                </div>
            @endforelse
        </div>
        <div class="mt-4">
            {{ $jobApplications->links() }}
        </div>
    </div>


</x-app-layout>