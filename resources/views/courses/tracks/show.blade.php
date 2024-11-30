@extends('master')

@section('content')
    <div class="px-6 pt-4 container mx-auto">
        @include('courses.partials.tabs')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 xl:grid-cols-4">
            <div class="grid gap-4 lg:col-span-2 xl:col-span-3 grid-cols-1 xl:grid-cols-2 ">
                @if($track->immediateChildren->count() > 0)
                    <div class="flex flex-row h-44 w-full xl:col-span-2">
                        <div class="flex bg-lime-green-700 rounded-l-lg shadow-md border-r-4 border-lime-green-800">
                            <div class="flex-1 -mr-2 flex flex-col pl-4 justify-center" style="max-width: 11rem">
                                <span class="text-white text-lg font-light">Track</span>
                                <span class="text-white text-3xl font-semibold -mt-2 ">{{ $track->name }}</span>
                            </div>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="fill-current text-white h-20 w-20 -ml-2 -mr-4" viewBox="0 0 24 24"
                                     >
                                    <path
                                            d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="bg-lime-green-400 flex-1 flex gap-4 flex-row rounded-r-lg shadow-md p-4">
                            @foreach($track->immediateChildren as $index => $subTrack)
                                <div class="flex-1 flex flex-col justify-between">
                                    <div class="flex flex-col">
                                        <span class="text-white text-lg font-light">Track</span>
                                        <span
                                                class="text-white text-3xl font-semibold -mt-2 ">{{ $subTrack->name ?? $index + 1 }}</span>
                                    </div>
                                    <div>
                                        @if($subTrack->isOn(auth()->user()))
                                            <div class="flex text-lime-green-800 items-center mb-2">
                                                <span>
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                         class="fill-current h-5 w-5 mr-1"
                                                         viewBox="0 0 24 24"
                                                         style="transform: ;msFilter:;">
                                                        <path
                                                                d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path>
                                                        <path
                                                                d="M9.999 13.587 7.7 11.292l-1.412 1.416 3.713 3.705 6.706-6.706-1.414-1.414z"></path>
                                                    </svg>
                                                </span>
                                                <span>You are on this track</span>
                                            </div>
                                        @endif
                                        <a href="{{ route('courses.tracks.show', [$course->id, $subTrack->id]) }}"
                                           class="bg-lime-green-700 w-full flex rounded-lg text-center items-center text-white py-1 hover:bg-lime-green-800 transition-colors justify-center">Open</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($tasks->count() == 0 && $track->parent_id != null)
                    <h1 class="dark:text-gray-400 w-full text-center mt-12 col-span-2 text-xl">No tasks available. Check
                        back
                        later.</h1>
                @endif
                @foreach($tasks as $task)
                    @include('courses.partials.task', ['task' => $task])
                @endforeach
            </div>
            <div>
                @can('manage', $course)
                    <div class="flex justify-between mb-4 gap-4">
                        <a href="{{ route('courses.manage.tracks.assign', [$course, $track]) }}"
                           class="bg-lime-green-500 text-white rounded-lg px-4 py-2.5 text-center block flex-grow hover:bg-lime-green-600 transition-colors">Assign tasks</a>
                        <a href="{{ route('courses.manage.tracks.create', [$course, 'parent' => $track->id]) }}"
                             class="bg-lime-green-500 text-white rounded-lg px-4 py-2.5 text-center block flex-grow hover:bg-lime-green-600 transition-colors">Create subtrack</a>
                    </div>
                @endcan
                <div class="bg-gray-200 dark:bg-gray-600 shadow-md rounded-md py-2 px-2">
                    <div class="flex">
                        <span class="mr-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 fill-current dark:text-white"
                                 viewBox="0 0 24 24"
                                 style="transform: ;msFilter:;">
                                <path
                                        d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path>
                                <path
                                        d="M11 11h2v6h-2zm0-4h2v2h-2z"></path>
                            </svg>
                        </span>
                        <span class="font-semibold dark:text-white">What are tracks?</span>
                    </div>
                    <div class="text-sm dark:text-gray-300 mt-2">
                        <p>Tracks allow you to choose specific pathways you'd like to take. Each pathway have different
                            tasks, and may lead to new tracks. <span class="font-semibold">Be aware</span>, that once
                            you
                            start a project within a track, you wont be able to pick another track.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
