@extends('courses.manage.master')

@section('manageContent')
    <div class="p-6 container mx-auto bg-white dark:bg-gray-600 shadow rounded-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-medium mb-2 text-lime-green-700 dark:text-lime-green-400">Course tracks</h2>
                <p class="text-gray-400 text-sm">Create tracks to organize your course activities.</p>
            </div>

            <a href="{{ route('courses.manage.tracks.create', $course) }}"
               class="text-white bg-lime-green-500 hover:bg-lime-green-600 focus:ring-4 focus:outline-none focus:ring-lime-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-lime-green-500 dark:hover:bg-lime-green-600 dark:focus:ring-lime-green-800">Create track</a>
        </div>
        <div class="mt-4">
            @if($tracks->isEmpty())
                <p class="text-gray-400 text-sm">No tracks have been created for this course.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($tracks as $track)
                        <div
                            class="shadow hover:shadow-md border dark:border-gray-800 rounded-xl p-4 bg-white relative overflow-hidden dark:bg-gray-800">
                            <a href="{{ route('courses.tracks.show', [$course, $track]) }}"
                               class="w-full h-full block flex flex-col justify-between">
                                <div class="w-full">
                                    <p class="text-gray-800 dark:text-white text-xl font-medium">
                                        {{ $track->name }}
                                    </p>
                                    <p class="text-gray-400 text-sm mb-2">
                                        Depth: {{ $track->depth }}
                                    </p>
                                    @unless($track->description == null)
                                        <p class="text-lime-green-600 text-xs font-medium mb-2">
                                            {{ $track->description }}
                                        </p>
                                    @endunless
                                </div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-gray-400 mt-2 text-sm">
                                                {{ $track->tasks->count() }} activities
                                            </p>
                                            <p class="text-gray-400 mt-2 text-sm">
                                                {{ $track->immediateChildren->count() }} subtracks
                                            </p>
                                        </div>
                                        <form method="post" action="{{ route('courses.manage.tracks.destroy', [$course, $track]) }}">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-white bg-red-500 hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-800">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
    </div>
@endsection
