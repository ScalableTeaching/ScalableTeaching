<?php

namespace App\Http\Controllers;

use App\Exceptions\PipelineException;
use App\Models\Casts\SubTask;
use App\Models\Enums\PipelineStatusEnum;
use App\Models\Pipeline;
use App\Models\Project;
use App\WebhookTypes;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function reporter(): WebhookTypes|string
    {
        abort_unless(request()->hasHeader('X-Gitlab-Token'), 400, 'No GitLab token supplied');
        abort_unless(request()->hasHeader('X-GitLab-Event'), 400, 'GitLab event missing');
        abort_unless(request()->has('project_id') || request()->has('project.id'), 400, 'Project ID missing');
        abort_unless(Project::isCorrectToken(request('project_id', request('project.id')), request()->header('X-Gitlab-Token')), 400, 'Token mismatch');

        return match (WebhookTypes::tryFrom(request()->header('X-GitLab-Event')))
        {
            WebhookTypes::Pipeline => $this->pipeline(),
            WebhookTypes::Push     => $this->push(),
            default                => "ignored",
        };
    }

    private function pipeline(): string
    {
        Log::info("Pipeline event received");
        /** @var Project|null $project */
        $project = Project::firstWhere('gitlab_project_id', request('project.id'));
        abort_if($project == null, 400, "Project not found");
        $startedAt = Carbon::parse(\request('object_attributes.created_at'))->setTimezone(config('app.timezone'));
        abort_if(Pipeline::isOutsideTimeFrame($startedAt, $project), 400, 'Pipeline could not be processed as it is not within the timeframe of the task.');

        $pipeline = $project->pipelines()->firstWhere('pipeline_id', request('object_attributes.id'));
        if($pipeline == null)
            $pipeline = $this->createPipeline($project);

        $tracking = (new Collection($project->task->sub_tasks->all()))->mapWithKeys(fn(SubTask $task) => [$task->getId() => strtolower($task->getName())]);
        $builds = new Collection(request('builds'));
        $succeedingBuilds = $builds->filter(fn($build) => $tracking->contains(strtolower($build['name'])) && $build['status'] == 'success');
        try
        {
            $pipelineStatus = PipelineStatusEnum::tryFrom(request('object_attributes.status'));
            if ($pipelineStatus == null)
            {
                return "SKIPPED"; // Skip pipeline events we don't care about.
            }

            $pipeline->process(
                startedAt: $startedAt,
                status: $pipelineStatus,
                duration: request('object_attributes.duration') ?? null,
                queueDuration: request('object_attributes.queued_duration') ?? null,
                succeedingBuilds: $succeedingBuilds->pluck('name')->toArray()
            );
        } catch(PipelineException)
        {
            abort(400, 'Pipeline could not be processed as it is not within the timeframe of the task.');
        }

        return "OK";
    }

    /**
     * @param Project $project
     * @return Pipeline
     */
    private function createPipeline(Project $project): Pipeline
    {
        Log::info("Creating pipeline for project {$project->id} with status " . request('object_attributes.status'));
        /** @var Pipeline $pipeline */
        $pipeline = $project->pipelines()->create([
            'pipeline_id'    => request('object_attributes.id'),
            'status'         => request('object_attributes.status'),
            'sha'            => request('object_attributes.sha') ?? null,
            'user_name'      => request('user.username'),
            'duration'       => request('object_attributes.duration') ?? null,
            'queue_duration' => request('object_attributes.queued_duration') ?? null,
            'created_at'     => Carbon::parse(request('object_attributes.created_at'))->setTimezone(config('app.timezone')),
        ]);

        return $pipeline;
    }

    private function push(): string
    {
        /** @var Project $project */
        $project = Project::firstWhere('gitlab_project_id', request('project.id'));
        abort_if($project == null, 404);

        if ($this->isBranchDeletionPush())
        {
            Log::info("Skipping branch deletion push event for project {$project->id} on branch " . request('ref'));

            return "SKIPPED";
        }

        $newProjectPush = [
            'before_sha' => request('before'),
            'after_sha'  => request('after'),
            'ref'        => request('ref'),
            'username'   => request('user_username'),
        ];

        if (request()->has('commits'))
        {
            $lastCommit = request('commits')[0];
            $lastCommitTimestamp = $lastCommit['timestamp'] ?? null;
            if ($lastCommitTimestamp != null)
            {
                $newProjectPush['created_at'] = $lastCommitTimestamp;
            }
        }

        $project->pushes()->create($newProjectPush);

        return "OK";
    }

    /**
     * When deleting a branch, it triggers a push event that will have the after key set to pure 0's and checkout_sha = null and no commits.
     * Criteria taken from https://gitlab.com/gitlab-org/gitlab/-/issues/25305 and manual investigation.
     * @return bool whether the push event is a branch deletion
     */
    private function isBranchDeletionPush(): bool
    {
        return request('checkout_sha') == null && intval(request('after')) == 0 && request('total_commits_count') == 0;
    }
}
