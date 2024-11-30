<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseTrack;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CourseTrackController extends Controller
{

    public function index(Course $course) : View
    {
        $tracks = $course->tracks()->get();

        $tracks->map(function(CourseTrack $track) {
            $track->depth = $track->getDepthAttribute();
        });

        return view('courses.tracks.index', [
            'course' => $course,
            'tracks' => $tracks,
        ]);
    }

    public function show(Course $course, CourseTrack $track) : View
    {
        $tasks = $course->tasks()->where('track_id', $track->id)->where('is_visible', true)->get()->map(fn(Task $task) => [
            'details' => $task,
            'project' => $task->currentProjectForUser(auth()->user()),
        ]);

        $trackBreadCrumbs = $track->path()
            ->mapWithKeys(fn($track, $index) => [$track->name => route('courses.tracks.show', [$course, $track])])
            ->reverse();

        return view('courses.tracks.show', [
            'course'      => $course,
            'track'       => $track,
            'tasks'       => $tasks,
            'breadcrumbs' => [
                'Courses'                => route('courses.index'),
                $course->name            => route('courses.show', $course),
                ...$trackBreadCrumbs,
            ],
        ]);
    }

    public function create(Request $request, Course $course) : View
    {
        Log::info("Create course track");

        $parent = $course->tracks()->where('id', $request->query('parent'))->first();

        return view('courses.tracks.create', [
            'course' => $course,
            'parent' => $parent,
        ]);
    }

    public function store(Request $request, Course $course) : RedirectResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255|min:1',
            'description'   => 'nullable|string',
            'parent'        => 'nullable|exists:course_tracks,id',
        ]);

        /** @var CourseTrack $track */

        $track = $course->tracks()->make([
            'name'        => $validated['name'],
            'description' => $validated['description'],
        ]);

        if (array_key_exists('parent', $validated))
        {
            $parent = $course->tracks()->where('id', $validated['parent'])->first();
            if ($parent == null)
            {
                flash()->addError("Parent track does not exist!");

                return redirect()->route('courses.manage.tracks.index', $course);
            }

            $track->parent()->associate($validated['parent']);
        }

        $track->save();

        flash()->addSuccess("Track created successfully");

        return redirect()->route('courses.manage.tracks.index', [$course, $track]);
    }

    public function destroy(Course $course, CourseTrack $track) : RedirectResponse
    {
        if ($track->children()->count() > 0)
        {
            flash()->addError("Track has sub tracks, remove them first!");

            return redirect()->route('courses.manage.tracks.index', [$course]);
        }

        Log::info("Deleting course track $track->id");

        $track->tasks()->each(function(Task $task) {
            $task->track()->dissociate();
            $task->save();
        });

        $track->delete();

        flash()->addSuccess("Track deleted successfully");

        return redirect()->route('courses.manage.tracks.index', $course);
    }

    public function assign(Course $course, CourseTrack $track) : View
    {
        $tasks = $course->tasks()->where('track_id', null)->get();

        return view('courses.tracks.assign-tasks', [
            'course' => $course,
            'track'  => $track,
            'tasks'  => $tasks,
        ]);
    }

    public function doAssign(Request $request, Course $course, CourseTrack $track) : RedirectResponse
    {
        if ($course->id != $track->course_id)
        {
            abort(403);
        }

        $validated = $request->validate([
            'tasks'   => 'required|array',
            'tasks.*' => 'exists:tasks,id',
        ]);

        $selectedTasks = $course->tasks()->whereIn('id', $validated['tasks'])->getQuery();
        if ($selectedTasks->count() != count($validated['tasks']))
        {
            flash()->addError("Some tasks do not exist");

            return redirect()->route('courses.manage.tracks.assign', [$course, $track]);
        }

        if ($selectedTasks->clone()->where('track_id', '!=', null)->count() > 0)
        {
            flash()->addError("Some tasks are already assigned to a track");

            return redirect()->route('courses.manage.tracks.assign', [$course, $track]);
        }


        $selectedTasks->get()->each(function (Task $task) use ($track) {
            $task->track()->associate($track);
            $task->save();
        });

        flash()->addSuccess("Tasks assigned to track successfully");

        return redirect()->route('courses.manage.tracks.index', $course);
    }

    public function unassign(Course $course, CourseTrack $track, Task $task) : RedirectResponse
    {
        if ($course->id != $track->course_id)
        {
            abort(403);
        }

        if ($task->track_id != $track->id)
        {
            abort(403);
        }

        $task->track()->dissociate();
        $task->save();

        flash()->addSuccess("Task unassigned from track successfully");

        return redirect()->route('courses.manage.tracks.assign', [$course, $track]);
    }
}
