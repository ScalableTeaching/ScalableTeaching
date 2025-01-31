<?php

namespace App\Http\Controllers;

use App\Models\Casts\SubTask;
use App\Models\Course;
use App\Models\Enums\FeedbackCommentStatus;
use App\Models\Enums\GradeEnum;
use App\Models\Enums\PipelineStatusEnum;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Pipeline;
use App\Models\Project;
use App\Models\ProjectDownload;
use App\Models\ProjectFeedback;
use App\Models\ProjectFeedbackComment;
use App\Models\Task;
use App\Models\User;
use App\ProjectStatus;
use Carbon\CarbonInterval;
use Domain\Files\Directory;
use Domain\Files\Highlight;
use Domain\Files\IsChangeable;
use Gitlab\Exception\RuntimeException;
use GrahamCampbell\GitLab\GitLabManager;
use GraphQL\Client;
use GraphQL\SchemaObject\RepositoryTreeArgumentsObject;
use GraphQL\SchemaObject\RootProjectsArgumentsObject;
use GraphQL\SchemaObject\RootQueryObject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    /**
     * @param Project $project
     * @return Collection<int, array{status:PipelineStatusEnum,run_time:string,queued_for:string,ran:string,ran_date:string,sub_tasks:Collection<int,array{name:string,completed:bool}>,user_name:string}>
     */
    public function builds(Project $project): Collection
    {
        return $project->pipelines()->with('subTasks')->latest()->get()->append('prettySubTasks')->map(fn(Pipeline $job) => [
            'status'     => $job->status,
            'run_time'   => CarbonInterval::seconds($job->duration)->forHumans(),
            'queued_for' => CarbonInterval::seconds($job->queue_duration)->forHumans(),
            'ran'        => $job->updated_at->diffForHumans(),
            'ran_date'   => $job->updated_at->toDateTimeString(),
            'sub_tasks'  => $job->pretty_sub_tasks,
            'user_name'  => $job->user_name,
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function reset(GitLabManager $gitLabManager, Project $project): string
    {
        abort_unless($project->status == ProjectStatus::Active, 400);
        \DB::transaction(function() use ($gitLabManager, $project) {
            $found = $project->gitlab_project_id != null;
            try
            {
                // Be aware if passed in value is null, then it will return all projects and therefore not throwing.
                $gitLabManager->projects()->show($project->gitlab_project_id);
            } catch(RuntimeException $runtimeException)
            {
                $found = $runtimeException->getCode() != 404;
            }

            if($found)
                $gitLabManager->projects()->remove($project->gitlab_project_id);


            $project->delete();

            /** @var ?Grade $grade */
            $grade = Grade::where("task_id", "=", $project->task->id)
                ->where("source_type", "=", User::class)
                ->where("source_id", "=", auth()->id())->first();
            if ($grade)
            {
                $grade->delete();
            }

        });

        return "OK";
    }

    public function markComplete(Course $course, Task $task, Project $project): string|Response
    {

        if( ! $task->isMarkAsCompleteTask())
            return response('Bad request', 400);
        if(Grade::where(['task_id' => $task->id, 'user_id' => auth()->id()])->exists())
            return response('Bad request', 400);

        $foundProject = $project->owners()->find(auth()->id());

        if ( ! $foundProject)
        {
            return response('Forbidden', 403);
        }

        $project->setProjectStatus(ProjectStatus::Finished);

        return "OK";
    }

    public function migrate(Project $project, Group $group): string
    {
        $activeUsers = $project->task->projectsForUsers($group->members)->reject(function(Project $currentProject) use ($project) {
            return $currentProject->id == $project->id;
        })->map(function(Project $project) {
            return $project->owners()->pluck('name');
        })->flatten();

        abort_if($activeUsers->count() > 0, 409, "The following members have already started a project: "
            . $activeUsers->join(", ") . '<br><br>They will need to delete their project for the group to start the project.');

        $project->ownable()->associate($group)->save();

        \App\Jobs\Project\RefreshMemberAccess::dispatch($project);

        return "ok";
    }

    public function refreshAccess(Project $project): string
    {
        \App\Jobs\Project\RefreshMemberAccess::dispatch($project);

        return "ok";
    }

    public function download(Course $course, Task $task, Project $project, GitLabManager $gitLabManager): StreamedResponse
    {
        $sha = $project->final_commit_sha;
        abort_if($sha == null, 404);

        return response()->streamDownload(function() use ($sha, $project, $gitLabManager) {
            echo $gitLabManager->repositories()->archive($project->gitlab_project_id, [
                'sha' => $sha,
            ], 'zip');
        }, "$project->repo_name.zip");
    }

    /**
     * @throws \Exception
     */
    public function validateProject(Course $course, Task $task, Project $project): RedirectResponse
    {
        if($project->final_commit_sha == null)
        {
            return redirect()->back()->withErrors('Can\'t validate this project as it isn\' finished yet');
        }

        $files = $project->task->protectedFiles;
        $directories = $files->groupBy('directory');
        $errors = [];
        foreach($directories as $directory => $files)
        {
            $rootObject = new RootQueryObject();
            $rootObject->selectProjects((new RootProjectsArgumentsObject())
                ->setIds(["gid://gitlab/Project/$project->gitlab_project_id"])
                ->setFirst(1))
                ->selectNodes()
                ->selectRepository()
                ->selectTree((new RepositoryTreeArgumentsObject())->setPath(trim($directory, '/'))->setRef($project->final_commit_sha))
                ->selectBlobs()
                ->selectNodes()
                ->selectName()
                ->selectSha();
            $client = new Client('https://gitlab.sdu.dk/api/graphql', ["Authorization" => 'Bearer ' . env('GITLAB_ACCESS_TOKEN')]);
            $projects = $client->runQuery($rootObject->getQuery())->getResults()->data->projects->nodes; // @phpstan-ignore-line

            if(count($projects) == 0)
            {
                throw new \Exception("Project with id $project->id wasn't found.");
            }

            $repoFiles = collect($projects[0]->repository->tree->blobs->nodes);
            foreach($files as $file)
            {
                $lookFor = $file->baseName;
                $found = $repoFiles->firstWhere('name', $lookFor);
                if($found == null)
                {
                    $errors[] = "The file \"{$file->path}\" is missing.";
                    continue;
                }

                $shaValues = new Collection($file->sha_values);
                $shaIntact = $shaValues->contains($found->sha);
                if( ! $shaIntact)
                    $errors[] = "The file \"{$file->path}\" has been altered! Expected file to have sha value one of [{$shaValues->join(', ')}] but got $found->sha.";
            }
        }

        $project->update([
            'validated_at'      => now(),
            'validation_errors' => $errors,
        ]);

        return redirect()->back();
    }

    public function showEditor(Course $course, Task $task, Project $project, ProjectDownload $projectDownload): View
    {
        /** @var ProjectFeedback|null $feedback */
        $feedback = $project->feedback()->where('user_id', auth()->id())->orWhere('sha', $projectDownload->ref)->first(); // todo, this should probably be based on SHA

        if($feedback == null)
            return view('tasks.editor')->with('context', 'view');
        $context = match (true)
        {
            $project->owners()->contains(fn(User $user) => $user->is(auth()->user())) => 'recipient',
            $feedback->reviewed == false                                              => 'pre-submission',
            $feedback->reviewed                                                       => 'submitted'
        };
        $delegation = $feedback->taskDelegation;
        $subTaskStatus = null;
        if($delegation->grading)
        {
            $achievedPoints = $project->subTasks->pluck('points', 'sub_task_id');
            $comments = $project->subTaskComments->pluck('text', 'sub_task_id');
            $subTaskStatus = $task->sub_tasks->all()->map(fn(SubTask $subTask) => [
                'id'        => $subTask->getId(),
                'name'      => $subTask->getName(),
                'group'     => $subTask->getGroup(),
                'maxPoints' => $subTask->getPoints(),
                'comment'   => $comments->get($subTask->getId()),
                'points'    => $achievedPoints->get($subTask->getId()) ?? null,
            ])->groupBy('group')->map(fn($tasks, $groupName) => [
                'group_name' => $groupName,
                'tasks'      => $tasks,
            ])->values(); // we convert to an ordinary array as we don't want JS to sort the output json based on keys
        }

        return view('tasks.editor')->with('context', $context)->with('delegation', $delegation)->with('subtasks', $subTaskStatus);
    }

    public function showTree(Course $course, Task $task, Project $project): Directory
    {
        $projectDownload = $project->download;
        $tree = $projectDownload->fileTree()->trim();
        $changes = $project->changes()->where('from', $project->task->current_sha)->where('to', $projectDownload->ref)->first()?->changes;
        if($changes != null)
        {
            $filesChanged = array_column($changes, 'file');
            $tree->traverse(function(IsChangeable $item) use ($filesChanged) {
                $path = str_replace('/', '\/', preg_quote($item->path()));// @phpstan-ignore-line

                foreach($filesChanged as $file) // @phpstan-ignore-line
                {
                $pathMatches = ! ($path == '') && preg_match("/^$path/i", $file) === 1;
                    if($pathMatches)
                    {

                        $item->setChanged(true);
                        break;
                    }
                }
            });
        }

        return $tree;
    }

    public function showFile(Course $course, Task $task, Project $project): Response|array
    {
        $projectDownload = $project->download;
        $contents = $projectDownload->file(\request('path'));
        $processedLines = (new Highlight(\request('path')))->code($contents);
        if($processedLines == null)
            return response("Can't be opened", 400);

        return [
            ...pathinfo(\request('path')),
            'full'  => \request('path'),
            'lines' => $processedLines,
        ];
    }

    /**
     * @param Course $course
     * @param Task $task
     * @param Project $project
     * @param ProjectDownload $projectDownload
     * @return Collection<int,ProjectFeedbackComment>
     */
    public function comments(Course $course, Task $task, Project $project, ProjectDownload $projectDownload): Collection
    {
        $isOwner = $project->owners()->contains(fn(User $user) => $user->is(auth()->user()));
        $feedbackIds = $isOwner ? $project->feedback()->reviewed()->pluck('id') : $project->feedback()->where('user_id', auth()->id())->pluck('id');

        $query = ProjectFeedbackComment::whereIn('project_feedback_id', $feedbackIds)
            ->orderBy('filename')
            ->orderBy('line');

        if(\request()->has('file'))
            $query->where('filename', \request('file'));

        if($isOwner)
        {
            $query->where('status', FeedbackCommentStatus::Approved);
            $query->select(['id', 'project_feedback_id', 'filename', 'line', 'marked_as', 'comment', 'created_at', 'updated_at']);
        } else
        {
            $query->select(['id', 'project_feedback_id', 'filename', 'line', 'status', 'reviewer_feedback', 'comment', 'created_at', 'updated_at']);
        }

        return $query->get();
    }

    public function storeComment(Course $course, Task $task, Project $project, ProjectDownload $projectDownload): ProjectFeedbackComment
    {
        $validated = \request()->validate([
            'comment' => ['string', 'required'],
            'file'    => ['string', 'required'],
            'line'    => ['numeric', 'required'],
        ]);

        /** @var ?ProjectFeedback $feedback */
        $feedback = $project->feedback()->where('user_id', auth()->id())->first(); // todo, this should probably be based on SHA
        abort_if($feedback == null, 400, 'This user is not able to make feedback on this project.');

        return $feedback->comments()->create([
            'filename' => $validated['file'],
            'comment'  => $validated['comment'],
            'line'     => $validated['line'],
        ]);
    }

    public function deleteComment(Course $course, Task $task, Project $project, ProjectDownload $projectDownload, ProjectFeedbackComment $projectFeedbackComment): string
    {
        $projectFeedbackComment->delete();

        return "ok";
    }

    public function updateComment(Course $course, Task $task, Project $project, ProjectDownload $projectDownload, ProjectFeedbackComment $projectFeedbackComment): string
    {
        $projectFeedbackComment->update([
            'comment' => \request('comment'),
        ]);

        $projectFeedbackComment->refresh();

        return $projectFeedbackComment;
    }

    public function submitFeedback(Course $course, Task $task, Project $project, ProjectDownload $projectDownload): string
    {
        $validated = \request()->validate([
            'general' => [],
            'grade'   => [],
            'tasks'   => [],
        ]);
        /** @var ?ProjectFeedback $feedback */
        $feedback = $project->feedback()->where('user_id', auth()->id())->first(); // todo, this should probably be based on SHA
        abort_if($feedback == null, 400, 'This user is not able to make feedback on this project.');
        $feedback->comments()->update([
            'status' => $feedback->taskDelegation->is_moderated ? FeedbackCommentStatus::Pending : FeedbackCommentStatus::Approved,
        ]);

        if(\request()->has('general') && Str::of(\request('general'))->isNotEmpty())
        {
            $feedback->comments()->create([
                'comment' => Str::of($validated['general'])->trim(),
                'status'  => $feedback->taskDelegation->is_moderated ? FeedbackCommentStatus::Pending : FeedbackCommentStatus::Approved,
            ]);
        }
        $feedback->update(['reviewed' => true]);

        if($feedback->taskDelegation->grading)
        {
            $feedback->project->owners()->each(function(User $user) use ($feedback, $validated, $task) {
                Grade::create([
                    'task_id'     => $task->id,
                    'value'       => $validated['grade'] == 'approve' ? GradeEnum::Passed : GradeEnum::Failed,
                    'source_type' => $feedback::class,
                    'source_id'   => $feedback->id,
                    'user_id'     => $user->id,
                ]);
            });
            $tasks = (new Collection(request('tasks')))->map(fn($group) => $group['tasks'])->flatten(1);
            $tasksToCreate = $tasks->reject(fn($task) => $task['points'] === null)->map(fn($task) => [
                'source_type' => $feedback::class,
                'source_id'   => $feedback->id,
                'points'      => $task['points'],
                'sub_task_id' => $task['id'],
            ]);
            $commentsToCreate = $tasks->reject(fn($task) => $task['comment'] === null)->map(fn($task) => [
                'text'        => $task['comment'],
                'sub_task_id' => $task['id'],
                'author_id'   => auth()->id(),
            ]);
            $feedback->project->subTasks()->delete();
            $feedback->project->subTaskComments()->delete();
            $feedback->project->subTasks()->createMany($tasksToCreate);
            $feedback->project->subTaskComments()->createMany($commentsToCreate);

            $feedback->project->update(['status' => ProjectStatus::Finished]);
        }

        return "ok";
    }

    public function markComment(Course $course, Task $task, Project $project, ProjectDownload $projectDownload, ProjectFeedbackComment $projectFeedbackComment): string
    {
        $projectFeedbackComment->marked_as = \request('mark');
        $projectFeedbackComment->save();

        return "ok";
    }
}
