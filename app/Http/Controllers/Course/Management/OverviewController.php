<?php

namespace App\Http\Controllers\Course\Management;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enums\CorrectionType;
use App\Models\Task;
use Domain\Analytics\Graph\DataSets\BarDataSet;
use Domain\Analytics\Graph\DataSets\LineDataSet;
use Domain\Analytics\Graph\Graph;
use Domain\SourceControl\SourceControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(Course $course): View
    {
        /** @var Collection<string,int> $exerciseEngagement */
        $exerciseEngagement = $course->task_engagement;
        $userEngagementGraph = $exerciseEngagement == null ? null : new Graph(
            $exerciseEngagement->keys(),
            new BarDataSet("Engagement %", $exerciseEngagement->values(), "#4F535B"),
        );


        /** @var Collection<string,int> $enrolmentPerDay */
        $enrolmentPerDay = $course->enrolment_per_day;
        $enrolmentPerDayGraph = new Graph(
            $enrolmentPerDay->keys(),
            new LineDataSet("Enrolled in total", $enrolmentPerDay->values(), "#266ab0", true)
        );

        $activities = $course->activities()->take(10)->get();

        return view('courses.manage.index', [
            'course'              => $course,
            'userEngagementGraph' => $userEngagementGraph,
            'enrolmentGraph'      => $enrolmentPerDayGraph,
            'activities'          => $activities,
        ]);
    }


    /**
     * @throws \Throwable
     */
    public function store(Course $course, Request $request, SourceControl $sourceControl): array|RedirectResponse
    {
        $validated = request()->validate([
            'name'  => 'required',
            'group' => ['string', 'nullable'],
        ]);

        // TODO: Move this into the module space, where when LinkRepository module is installed, then set up this gitlab group.
        // TODO: This requires a bit of a refactor, to add a new way to only trigger it on new installs and uninstalls, but not on loads from database.
        // Create a Gitlab sub-group for each task.
        $gitlabGroup = $sourceControl->createGroup($validated['name'], [
            "parent_id" => $course->gitlab_group_id,
        ]);

        /** @var Task $task */
        $task = $course->tasks()->create([
            'name'              => $validated['name'],
            'gitlab_group_id'   => $gitlabGroup->id,
            'grouped_by'        => $request->has('group') ? $validated['group'] : null,
            'correction_type'   => CorrectionType::None, //TODO: This is added to remove the "builds" tab in a task overview. This will be removed when looking at the Build Tracking module.
        ]);

        return [
            'id'    => $task->id,
            'route' => route('courses.tasks.admin.preferences', [$course->id, $task->id]),
        ];
    }
}
