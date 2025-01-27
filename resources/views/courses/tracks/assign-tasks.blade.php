@extends('master')

@section('content')
    <div class="px-6 pt-6 container mx-auto">
        <form action="{{ route('courses.manage.tracks.doAssign', [$course, $track]) }}" method="post">
            @csrf
            <div
                class="py-4 px-6 lg:max-w-xl container mx-auto flex flex-col bg-white border dark:border-gray-700 shadow dark:bg-gray-800 rounded-md">
                <h2 class="text-2xl font-bold mb-2 text-lime-green-700 dark:text-lime-green-400">Assigning tasks to track "{{$track->name}}"</h2>
                <p class="text-gray-500">Hold <code>ctrl</code> or <code>cmd</code> to do a multi select.</p>
                <div class="mb-4">
                    <label for="tasks" class="text-sm text-lime-green-700 dark:text-gray-300">
                        Tasks*
                    </label>
                    <select id="course-track-tasks" name="tasks[]" multiple required
                            class=" bg-gray-50 flex-grow border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-green-400  block w-full p-2.5 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200">
                        @foreach($tasks as $task)
                            <option value="{{ $task->id }}">{{ $task->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($errors->any())
                    <div class="text-red-800 dark:text-red-500 text-sm font-semibold">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <button
                    class="flex-shrink-0 mt-3 bg-white hover:bg-lime-green-500 text-lime-green-700 font-semi-bold hover:text-white py-2 px-4 border border-lime-green-500 hover:border-transparent rounded-lg"
                    type="submit">
                    Assign
                </button>
            </div>
        </form>

        <div class="mt-6 py-4 px-6 lg:max-w-xl container mx-auto flex flex-col bg-white border dark:border-gray-700 shadow dark:bg-gray-800 rounded-md">
            <h2 class="text-xl font-bold mb-2 text-lime-green-700 dark:text-lime-green-400">Currently Assigned Tasks</h2>
            @if($track->tasks->isEmpty())
                <p class="text-gray-400 text-sm">No tasks are currently assigned to this track.</p>
            @else
                <ul class="list-disc pl-5">
                    @foreach($track->tasks as $task)
                        <li class="flex justify-between items-center mb-2">
                            <span class="text-gray-800 dark:text-gray-200">{{ $task->name }}</span>
                            <form action="{{ route('courses.manage.tracks.unassign', [$course, $track, $task]) }}" method="post">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-white bg-red-500 hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-3 py-1.5 text-center dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-800">Unassign</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
