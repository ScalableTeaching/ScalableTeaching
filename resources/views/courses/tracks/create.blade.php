@extends('master')

@section('content')
    <div class="px-6 pt-6 container mx-auto">
        <form action="{{ route('courses.manage.tracks.store', $course) }}" method="post">
            @csrf
            <div
                class="py-4 px-6 lg:max-w-xl container mx-auto flex flex-col bg-white border dark:border-gray-700 shadow dark:bg-gray-800 rounded-md">
                <h2 class="text-2xl font-bold mb-2 text-lime-green-700 dark:text-lime-green-400">Creating course track for course "{{$course->name}}"</h2>
                @isset($parent)
                    <h3 class="text-lg font-medium mb-2 text-black dark:text-gray-400">Parent track: <span class="font-bold">{{ $parent->name }}</span></h3>
                @endisset
                <div class="mb-4">
                    <label for="name" class="text-sm text-lime-green-700 dark:text-gray-300">
                        Name*
                    </label>
                    <input value="{{ old('name') }}" id="course-track-name" type="text" name="name" placeholder="Point Giving Activity 1" required
                           class=" bg-gray-50 flex-grow border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-green-400  block w-full p-2.5 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"/>
                </div>
                <label for="description" class="text-sm text-lime-green-700 dark:text-gray-300">
                    Description
                </label>
                <textarea id="course-track-description" name="description" placeholder="A more thorough explanation of the track..."
                       class=" bg-gray-50 flex-grow border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-green-400  block w-full p-2.5 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200">{{ old('description') }}</textarea>
                <input type="hidden" name="parent" value="@isset($parent){{$parent->id}}@else{{null}}@endisset"/>
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
                    Create
                </button>
            </div>
        </form>
    </div>
@endsection
