@extends('master')

@section('content')
    <div class="container flex gap-x-20 px-6 mx-auto pt-10">
        <div class="flex-auto w-1/2">
            @if($courses->count() == 0)
                <div class="pb-3 rounded-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center">
                    <div class="px-6 pt-5">
                        <h1 class="text-black dark:text-white text-lg font-semibold">
                            All courses
                        </h1>
                        <P class="text-black dark:text-gray-500 text-medium">
                            You currently have no courses active
                        </P>
                    </div>
                </div>
            @else
                <div class="rounded-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center">
                    <div class="px-6 pt-5">
                        <h1 class="text-black dark:text-white text-lg font-semibold">
                            All courses
                        </h1>
                    </div>
                    <div class="pb-5 px-6 w-full">
                        @foreach($courses as $course)
                            <ol>
                                <li>
                                    <a href="{{ route('courses.show', $course) }}"
                                       class="flex items-center mb-1 text-gray-900 dark:text-white hover:text-gray-400 justify-between">
                                        {{ $course->name }}
                                        <p class="text-gray-500 dark:text-gray-600 text-right">
                                            {{ $course->updated_at?->diffForHumans() }}
                                        </p>
                                    </a>
                                </li>
                            </ol>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($exercises->count() == 0)
                <div
                    class="overflow-auto mt-10 mb-5 pb-3 rounded-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center">
                    <div class="px-6 pt-5">
                        <h1 class="text-black dark:text-white text-lg font-semibold">
                            Exercises
                        </h1>
                        <p class="text-black dark:text-gray-500 text-medium">
                            You currently have no exercises active
                        </p>
                    </div>
                </div>
            @elseif($exercises->count() < 4)
                <div
                    class="overflow-auto mt-10 mb-5 pb-3 rounded-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center">
                    <div class="px-6 pt-5">
                        <h1 class="text-black dark:text-white text-lg font-semibold">
                            Exercises
                        </h1>
                    </div>
                    @foreach($exercises as $exercise)
                        <ol class="w-full">
                            <li class="py-0.5 mx-6 mt-5 bg-white dark:bg-gray-750 rounded-lg">
                                <div class="m-4">
                                    <a href="{{ route('courses.tasks.show', [$exercise->course_id, $exercise]) }}"
                                       class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $exercise->name }}
                                    </a>
                                    <p class="text-base font-normal text-gray-500 dark:text-gray-400">
                                        {{ strip_tags(Str::words($exercise->description, 20)) }}</p>
                                </div>
                            </li>
                        </ol>
                    @endforeach
                </div>
            @else
                <div
                    class="overflow-auto exercise-assignments mt-10 mb-5 pb-3 rounded-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center">
                    <div class="px-6 pt-5">
                        <h1 class="text-black dark:text-white text-lg font-semibold">
                            Exercises
                        </h1>
                    </div>
                    @foreach($exercises as $exercise)
                        <ol class="w-full">
                            <li class="py-0.5 mx-6 mt-5 bg-white dark:bg-gray-750 rounded-lg">
                                <div class="m-4">
                                    <a href="{{ route('courses.tasks.show', [$exercise->course_id, $exercise]) }}"
                                       class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $exercise->name }}
                                    </a>
                                    <p class="text-base font-normal text-gray-500 dark:text-gray-400">
                                        {{ strip_tags(Str::words($exercise->description, 20)) }}</p>
                                </div>
                            </li>
                        </ol>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="flex-auto w-1/2">
            @if($awaitingFeedback->count() > 0)
                <ul class="flex flex-wrap text-sm font-medium text-center text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    <li class="mr-2">
                        <a href="?" aria-current="page"
                           class="@if(request('feedback') != 'completed') text-lime-green-800 bg-gray-300  dark:bg-gray-800 dark:text-lime-green-500 @else hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-900 dark:hover:text-gray-300 @endif  inline-block p-4 rounded-t-lg">Awaiting
                            feedback ({{ $awaitingFeedback->count() }})</a>
                    </li>
                    <li class="mr-2">
                        <a href="?feedback=completed"
                           class="@if(request('feedback') == 'completed')  text-lime-green-800 bg-gray-300  dark:bg-gray-800 dark:text-lime-green-500 @else hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-900 dark:hover:text-gray-300 @endif inline-block p-4 rounded-t-lg ">Completed
                            feedback</a>
                    </li>
                </ul>
                <div
                    class="overflow-auto mb-5 py-3 rounded-b-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center max-h-96">
                    <div class="px-6">
                        <table class="table w-full mt-2">
                            <tbody>
                            @foreach(request('feedback') == 'completed' ? $completedFeedback : $awaitingFeedback as $feedback)
                                <tr>
                                    <td class="text-lime-green-600 dark:text-lime-green-300 font-medium text-xs pr-2"><a
                                            href="{{ route('courses.tasks.show-editor', [$feedback->project->task->course, $feedback->project->task, $feedback->project]) }}"
                                            target="_blank">{{ $feedback->pseudonym }}</a></td>
                                    <td class="text-gray-800 dark:text-gray-300 text-xs">{{ $feedback->project->task->course->name }}
                                        , {{ $feedback->project->task->name }}</td>
                                    @if($feedback->taskDelegation->deadline_at->isPast())
                                        <td class="text-gray-900 dark:text-gray-400 text-xs text-right">{{ $feedback->taskDelegation->deadline_at->diffForHumans() }}</td>
                                    @else
                                        <td class="text-gray-900 dark:text-gray-400 text-xs text-right">Due
                                            in {{ $feedback->taskDelegation->deadline_at->diffForHumans() }}</td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if($notDownloadedYetCount > 0)
                            <p class="text-sm text-center text-lime-green-800 mt-3 font-bold">
                                {{ $notDownloadedYetCount  }} {{ Str::of("task")->plural($notDownloadedYetCount) }} is
                                still waiting to download. Please check back later...
                            </p>
                        @endif
                    </div>
                </div>
            @endif
            @if($tasks->count() == 0)
                <div
                    class="overflow-auto mb-5 pb-3 rounded-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center">
                    <div class="px-6 pt-5">
                        <h1 class="text-black dark:text-white text-lg font-semibold">
                            Active assignments
                        </h1>
                        <p class="text-black dark:text-gray-500 text-medium">
                            You currently have no assignments active
                        </p>
                    </div>
                </div>
            @elseif($tasks->count() < 5)
                <div
                    class="overflow-auto mb-5 pb-3 rounded-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center">
                    <div class="px-6 pt-5">
                        <h1 class="text-black dark:text-white text-lg font-semibold">
                            Active assignments
                        </h1>
                    </div>
                    @foreach($tasks as $task)
                        <ol class="w-full">
                            <li class="py-0.5 m-6 mt-5 bg-white dark:bg-gray-750 rounded-lg">
                                <div class="m-4">
                                    <a href="{{ route('courses.tasks.show', [$task->course_id, $task]) }}"
                                       class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $task->name }}
                                    </a>
                                    <time
                                        class="flex text-sm font-normal leading-none text-lime-green-400 dark:text-lime-green-400 justify-between">
                                        {{ $task->starts_at }} - {{ $task->ends_at }}
                                        <time
                                            class="text-sm font-normal leading-none text-gray-400 dark:text-gray-500">
                                            ({{ $task->ends_at->diffForHumans() }})
                                        </time>
                                    </time>
                                    <p class="text-base font-normal text-gray-500 dark:text-gray-400">
                                        {{ strip_tags(Str::words($task->description, 30)) }}</p>
                                </div>
                            </li>
                        </ol>
                    @endforeach
                </div>
            @else
                <div
                    class="overflow-auto dashboard-assignments mb-5 pb-3 rounded-lg shadow bg-gray-300 dark:bg-gray-800 place-items-center">
                    <div class="px-6 pt-5">
                        <h1 class="text-black dark:text-white text-lg font-semibold">
                            Active assignments
                        </h1>
                    </div>
                    @foreach($tasks as $task)
                        <ol class="w-full">
                            <li class="py-0.5 m-6 mt-5 bg-white dark:bg-gray-750 rounded-lg">
                                <div class="m-4">
                                    <a href="{{ route('courses.tasks.show', [$task->course_id, $task]) }}"
                                       class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $task->name }}
                                    </a>
                                    <time
                                        class="flex text-sm font-normal leading-none text-lime-green-400 dark:text-lime-green-400 justify-between">
                                        {{ $task->starts_at }} - {{ $task->ends_at }}
                                        <time
                                            class="text-sm font-normal leading-none text-gray-400 dark:text-gray-500">
                                            ({{ $task->ends_at->diffForHumans() }})
                                        </time>
                                    </time>
                                    <p class="text-base font-normal text-gray-500 dark:text-gray-400">
                                        {{ strip_tags(Str::words($task->description, 30)) }}</p>
                                </div>
                            </li>
                        </ol>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
