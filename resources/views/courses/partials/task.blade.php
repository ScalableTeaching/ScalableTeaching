<a href="{{ route(auth()->user()->can('viewDashboard', $task['details']) ? 'courses.tasks.admin.index' : 'courses.tasks.show', [$task['details']->course->id, $task['details']->id]) }}" class="hover:shadow-md shadow rounded-lg mb-4 max-h-fit    ">
    <div @class([
        'px-8 py-4 mx-auto bg-white dark:bg-gray-600 rounded-lg']) class="">
        <div class="flex items-center justify-between">
            <span class="text-xl font-bold text-gray-700 dark:text-white">{{ $task['details']['name'] }}</span>
            @if($task['details']->grade() != null)
                <span @class([
                    'px-3 py-1 text-sm font-bold text-gray-100 transform rounded',
                    'bg-lime-green-600' => $task['details']->grade()->value == \App\Models\Enums\GradeEnum::Passed,
                    'bg-red-500' => $task['details']->grade()->value == \App\Models\Enums\GradeEnum::Failed,
                ])>
                    @switch($task['details']->grade()->value)
                        @case(\App\Models\Enums\GradeEnum::Passed)
                        Passed
                        @break
                        @case(\App\Models\Enums\GradeEnum::Failed)
                        Failed
                        @break
                        @default
                    @endswitch
                </span>
            @endif
        </div>
        <div class="flex items-end justify-between mt-4">
            <div class="flex gap-2 text-lime-green-600 dark:text-lime-green-400 text-sm">
                <div class="flex flex-col">
                    <span class="font-bold">{{ $task['details']->starts_at?->toDateString() }}</span>
                    <span class="text-xs text-gray-400">{{ $task['details']->starts_at?->toTimeString() }}</span>
                </div>
                -
                <div class="flex flex-col">
                    <span class="font-bold">{{ $task['details']->ends_at?->toDateString() }}</span>
                    <span class="text-xs text-gray-400">{{ $task['details']->ends_at?->toTimeString() }}</span>
                </div>
            </div>

            <div class="flex items-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                @if ($task['details']->starts_at?->isFuture())
                    <span>{{ $task['details']->starts_at?->diffForHumans() }}</span>
                @else
                    <span>{{ $task['details']->ends_at?->diffForHumans() }}</span>
                @endif
            </div>
        </div>
    </div>
</a>
