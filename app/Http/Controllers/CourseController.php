<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index() : View
    {
        $courses = auth()->user()->courses->map(function($course) {

            $tasks = $course->tasks->map(function(Task $task) {
                $project = $task->currentProjectForUser(auth()->user());

                return [
                    'task'    => $task,
                    'project' => $project,
                    'status'  => $project?->status,
                ];
            });

            $deadline = $tasks->sort(function($a, $b) {
                $startsAtA = Carbon::parse($a['task']->starts_at);
                $endsAtA = Carbon::parse($a['task']->ends_at);
                $startsAtB = Carbon::parse($b['task']->starts_at);
                $endsAtB = Carbon::parse($b['task']->ends_at);

                if(now()->between($startsAtA, $endsAtA))
                    return -1;
                if(now()->between($startsAtB, $endsAtB))
                    return 1;

                if($endsAtA->isPast() || $endsAtB->isPast())
                    return 1;

                return now()->diffInSeconds($endsAtA) > now()->diffInSeconds($endsAtB) ? 1 : -1;
            })->first();

            return [
                'id'            => $course->id,
                'name'          => $course->name,
                'taskCount'     => $tasks->count(),
                'completed'     => $tasks->where('status', 'finished')->count(),
                'next_deadline' => $deadline != null ? $deadline['task']->ends_at : null,
            ];
        });

        return view('courses.index', [
            'courses'     => $courses,
            'bg'          => 'bg-gray-100 dark:bg-gray-700',
            'breadcrumbs' => [
                'Courses' => null,
            ],
        ]);
    }

    public function show(Course $course) : View
    {
        $tasks = $course->tasks()->whereNull('track_id')->where('is_visible', true)->get()->map(fn(Task $task) => [
            'details' => $task,
            'project' => $task->currentProjectForUser(auth()->user()),
        ]);

        $exerciseGroups = $tasks->filter(fn($task) => $task['details']->type == 'exercise')->groupBy(fn($task) => $task['details']->grouped_by);
        $assignments = $tasks->filter(fn($task) => $task['details']->type == 'assignment');

        $taskCount = $course->tasks()->count();

        return view('courses.show', [
            'course'         => $course,
            'bg'             => 'bg-gray-50 dark:bg-gray-600',
            'taskCount'      => $taskCount,
            'breadcrumbs'    => [
                'Courses'     => route('courses.index'),
                $course->name => null,
            ],
            'exerciseGroups' => $exerciseGroups,
            'assignments'    => $assignments,
        ]);
    }

    public function showManage(Course $course) : View
    {
        return view('courses.manage.index', [
            'course'      => $course,
            'breadcrumbs' => [
                'Courses'     => route('courses.index'),
                $course->name => null,
            ],
        ]);
    }

    public function addTeacher(Course $course) : RedirectResponse
    {
        $validated = \Validator::make(\request()->all(), [
            'teacher' => ['required', 'exists:users,id'],
        ])->validateWithBag('teachers');

        $user = User::find($validated['teacher']);
        $course->teachers()->syncWithoutDetaching([$user->id => ['role' => 'teacher']]);

        return redirect()->back();
    }

    public function removeTeacher(Course $course, User $teacher) : RedirectResponse
    {
        if($teacher->id == auth()->id())
            return redirect()->back()->withErrors('You can\'t remove yourself.', 'teachers');

        $course->teachers()->detach($teacher);

        return redirect()->back();
    }

    public function showEnroll(Course $course) : RedirectResponse | View
    {
        if($course->enroll_token != request('token'))
            return redirect()->route('home')->withError('Invalid course token');

        if($course->members()->where(['user_id' => auth()->id()])->exists())
            return redirect()->route('courses.show', [$course->id]);

        if( ! request()->has('confirm'))
            return view('courses.enroll-dialog', compact('course'));

        $course->members()->attach(auth()->id(), ['role' => 'student']);

        return redirect()->route('courses.show', [$course->id]);
    }
}
