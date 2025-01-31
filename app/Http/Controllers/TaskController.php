<?php

namespace App\Http\Controllers;

use App\Models\Casts\SubTask;
use App\Models\Course;
use App\Models\ProjectFeedback;
use App\Models\Group;
use App\Models\Project;
use App\Models\ProjectSubTaskComment;
use App\Models\Task;
use App\ProjectStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Domain\Analytics\Graph\DataSets\BarDataSet;
use Domain\Analytics\Graph\Graph;
use GrahamCampbell\GitLab\GitLabManager;
use Http\Client\Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function show(Course $course, Task $task): RedirectResponse|View
    {
        abort_if( ! $task->is_visible && auth()->user()->cannot('manage', $course), 401);
        if ( ! $task->starts_at || ! $task->ends_at)
        {
            return redirect()->route('courses.tasks.admin.preferences', [$course->id, $task->id]);
        }

        $project = $task->currentProjectForUser(auth()->user());

        return $this->showProject($course, $task, $project);
    }

    public function showProject(Course $course, Task $task, ?Project $project): View
    {
        $myGroups = $course->groups()
            ->whereRelation('members', 'user_id', auth()->id())
            ->latest()
            ->pluck('name', 'id');
        $startDay = $task->starts_at->format("j/n");
        $endDay = $task->ends_at->format("j/n");
        $percent = number_format(now()->diffInSeconds($task->starts_at) / $task->starts_at->diffInSeconds($task->ends_at) * 100, 2);
        $progress = min($percent, 100);
        $timeLeft = $task->ends_at->isPast() ? '' : $task->ends_at->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE, false, 2) . ' left';
        $myBuilds = $project == null ? new Collection() : $project->dailyBuilds();
        $dailyBuilds = $task->dailyBuilds(true);

        $project?->append('is_missed');
        $completedSubTasks = $project?->subTasks->keyBy('sub_task_id');
        $completedSubTaskComments = $project?->subTaskComments()->with('author')->get()->map(fn(ProjectSubTaskComment $comment) => [
            'sub_task_id' => $comment->sub_task_id,
            'author'      => $comment->author->name,
            'text'        => $comment->text,
        ])->groupBy('sub_task_id');

        $subTasks = $task->sub_tasks->all()->groupBy('group')->map(fn(\Illuminate\Support\Collection $subTasks, $group) => [
            'group' => $group,
            'tasks' => $subTasks->map(fn(SubTask $subTask) => [
                'name'           => $subTask->getDisplayName(),
                'pointsAcquired' => $completedSubTasks?->has($subTask->getId()) ? $completedSubTasks->get($subTask->getId())->points ?? 1 : null,
                'comments'       => $completedSubTaskComments?->has($subTask->getId()) ? $completedSubTaskComments->get($subTask->getId()) : [],
                'points'         => $subTask->getPoints(),
                'required'       => $subTask->isRequired() ?? true,
                'group'          => $subTask->getGroup(),
            ]),
        ])->values();

        $survey = $task->survey()?->load(['fields' => function(HasMany $query) {
            $query->where('type', '!=', 'environment');
        }, 'fields.items']);

        $dailyBuildsGraph = new Graph(
            $dailyBuilds->keys(),
            new BarDataSet("Total", $dailyBuilds->subtractByKey($myBuilds), "#6B7280"),
            new BarDataSet("You", $myBuilds->values(), "#7BB026")
        );

        $gradeDelegations = $project?->status == ProjectStatus::Finished && $project ? $project->feedback()->with('user')->get()
            ->reject(fn(ProjectFeedback $gradeDelegation) => $gradeDelegation->taskDelegation->is_anonymous)
            ->map(fn(ProjectFeedback $gradeDelegation) => [
                'by'         => $gradeDelegation->user->name,
                'identifier' => $gradeDelegation->pseudonym,
            ]) : null;

        $newProjectRoute = route('courses.tasks.createProject', [$course->id, $task->id]);

        $download = $project?->download;
        $codeRoute = $download != null ? route('courses.tasks.show-editor', [$course, $task, $project, $download]) : null;


        return view('tasks.show', [
            'course'               => $course,
            'task'                 => $task->setHidden(['markdown_description']),
            'isTemplateTask'       => $task->isTemplateTask(),
            'isMarkAsCompleteTask' => $task->isMarkAsCompleteTask(),
            'bg'                   => 'bg-gray-50 dark:bg-gray-600',
            'project'              => $project,
            'subTasks'             => count($subTasks) > 0
                ? ['list' => $subTasks, 'gradeDelegations' => $gradeDelegations]
                : null,
            'progress'        => [
                'startDay' => $startDay,
                'endDay'   => $endDay,
                'percent'  => $progress,
                'timeLeft' => $timeLeft,
                'ended'    => $task->ends_at->isPast(),
            ],
            'pushes'          => $project?->pushes()
                    ->where('created_at', '<=', $task->ends_at)
                    ->pluck('created_at') ?? [],
            'codeRoute'       => $codeRoute,
            'track'           => $task->track,
            'builds'          => $dailyBuilds,
            'myBuilds'        => $myBuilds,
            'buildGraph'      => $dailyBuildsGraph,
            'newProjectRoute' => $newProjectRoute,
            'availableGroups' => $myGroups,
            'survey'          => [
                'details'   => $survey,
                'submitted' => $project != null && ($survey?->isAnswered(auth()->user(), $task) ?? false),
                'deadline'  => [
                    'forHumans' => $survey?->pivot->deadline->diffForHumans(),
                    'date'      => $survey?->pivot->deadline->toDateTimeString(),
                ],
                'can'       => [
                    'view'   => $survey == null ? false : auth()->user()->can('view', [$survey, $project]),
                    'answer' => $survey == null ? false : auth()->user()->can('answer', [$survey, $project]),
                ],
            ],
            'breadcrumbs'     => [
                'Courses'     => route('courses.index'),
                $course->name => route('courses.show', $course->id),
                $task->name   => null,
            ],
        ]);
    }

    public function doCreateProject(Course $course, Task $task, GitLabManager $gitLabManager): string
    {
        $isSolo = request('as', 'solo') == 'solo';
        $group = $isSolo ? null : Group::findOrFail(request('as'));

        abort_if( ! $isSolo && ! auth()->user()->can('canStartProject', $group), 401, "You don't have access to this project.");
        abort_if( ! $task->canStart($isSolo ? auth()->user() : $group, $message), 410, $message);

        $owner = $isSolo ? auth()->user() : $group;
        try
        {
            $task->createProject($owner);
        } catch (\Exception|Exception $e)
        {
            Log::error("Failed creating project: " . $e->getMessage());

            return abort(500, "Couldn't create project, try again later.");
        }

        return "OK";
    }

    public function edit(Course $course, Task $task): View
    {
        $breadcrumbs = [
            'Courses'     => route('courses.index'),
            $course->name => null,
        ];

        return view('courses.manage.editTask', compact('course', 'task', 'breadcrumbs'));
    }

    public function update(Course $course, Task $task): RedirectResponse
    {
        $validated = request()->validateWithBag('task', [
            'name'        => 'required',
            'description' => 'required',
            'from'        => ['required', 'date', 'before:to'],
            'to'          => ['required', 'date', 'after:from'],
            'start-time'  => ['required', 'date_format:H:i'],
            'end-time'    => ['required', 'date_format:H:i'],
        ]);

        $task->update([
            'name'              => $validated['name'],
            'short_description' => $validated['description'],
            'starts_at'         => Carbon::parse($validated['from'] . " " . $validated['start-time']),
            'ends_at'           => Carbon::parse($validated['to'] . " " . $validated['end-time']),
        ]);

        return redirect()->back()->with('success-task', 'The changes were saved.');
    }

    public function toggleVisibility(Course $course, Task $task): RedirectResponse
    {
        $task->is_visible = ! $task->is_visible;
        $task->save();

        return redirect()->back()->with('success-task', 'The visibility was updated.');
    }

    public function refreshReadme(Course $course, Task $task): RedirectResponse
    {
        try
        {
            $task->reloadDescriptionFromRepo();
        } catch(\Exception $exception)
        {
        }

        return redirect()->back()->with('success-task', 'The readme was updated.');
    }

    public function nextExercise(Course $course, Task $task): array
    {
        $nextExercise = $course->tasks()->where('order', '>', $task->order)->orderBy('order')->first();

        return [
            'route' => $nextExercise != null ? route('courses.tasks.show', [$course, $nextExercise]) : null,
        ];
    }
}
