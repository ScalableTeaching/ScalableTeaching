<?php

use App\Models\Casts\SubTask;
use App\Models\Course;
use App\Models\Enums\PipelineStatusEnum;
use App\Models\Pipeline;
use App\Models\Project;
use App\Models\Task;
use App\Modules\AutomaticGrading\AutomaticGrading;
use App\Modules\AutomaticGrading\AutomaticGradingSettings;
use App\Modules\AutomaticGrading\AutomaticGradingType;
use App\ProjectStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;
use function Pest\testDirectory;

uses(RefreshDatabase::class);

beforeEach(function() {
    /** @var Task $task */
    $task = Task::factory([
        'sub_tasks'       => [
            new SubTask('test 11 equals [10, 1]', '11 Equals [10, 1]'),
            new SubTask('test 9 equals [5,2,2]', '9 Equals [5,2,2]'),
        ],
        'starts_at'       => Carbon::create(2022, 1, 21),
        'ends_at'         => Carbon::create(2022, 2, 3),
    ])->for(Course::factory())->make();


    $task->module_configuration->addModule(AutomaticGrading::class);
    $settings = new AutomaticGradingSettings();
    $settings->gradingType = AutomaticGradingType::PIPELINE_SUCCESS->value;
    $task->module_configuration->update(AutomaticGrading::class, $settings, $task);
    $task->save();


    $this->project = Project::factory()->for($task)->createQuietly();
    $this->pipelinePendingRequest = json_decode(file_get_contents(testDirectory('Feature/GitLab/Stubs/Pipeline1.json')), true);
    $this->pipelinePendingRequest['project']['id'] = $this->project->gitlab_project_id;
    $this->pipelineRunningRequest = json_decode(file_get_contents(testDirectory('Feature/GitLab/Stubs/Pipeline2.json')), true);
    $this->pipelineRunningRequest['project']['id'] = $this->project->gitlab_project_id;
    $this->pipelineFailedRequest = json_decode(file_get_contents(testDirectory('Feature/GitLab/Stubs/Pipeline3.json')), true);
    $this->pipelineFailedRequest['project']['id'] = $this->project->gitlab_project_id;
    $this->pipelineSucceedingRequest = json_decode(file_get_contents(testDirectory('Feature/GitLab/Stubs/Pipeline4.json')), true);
    $this->pipelineSucceedingRequest['project']['id'] = $this->project->gitlab_project_id;
});


function sendPendingPipeline(): Pipeline
{
    postJson(route('reporter'), test()->pipelinePendingRequest, [
        'X-Gitlab-Event' => 'Pipeline Hook',
        'X-Gitlab-Token' => Project::token(test()->project),
    ]);

    $project = Project::firstWhere('gitlab_project_id', test()->pipelinePendingRequest['project']['id']);

    return $project->pipelines()->first();
}

function sendRunningPipeline(): Pipeline
{
    postJson(route('reporter'), test()->pipelineRunningRequest, [
        'X-Gitlab-Event' => 'Pipeline Hook',
        'X-Gitlab-Token' => Project::token(test()->project),
    ]);

    $project = Project::firstWhere('gitlab_project_id', test()->pipelineRunningRequest['project']['id']);

    return $project->pipelines()->first();
}

function sendFailedPipeline(): ?Pipeline
{
    postJson(route('reporter'), test()->pipelineFailedRequest, [
        'X-Gitlab-Event' => 'Pipeline Hook',
        'X-Gitlab-Token' => Project::token(test()->project),
    ]);

    $project = Project::firstWhere('gitlab_project_id', test()->pipelineFailedRequest['project']['id']);

    return $project->pipelines()->first();
}

function sendSucceedingPipeline(): Pipeline
{
    postJson(route('reporter'), test()->pipelineSucceedingRequest, [
        'X-Gitlab-Event' => 'Pipeline Hook',
        'X-Gitlab-Token' => Project::token(test()->project),
    ]);

    $project = Project::firstWhere('gitlab_project_id', test()->pipelineSucceedingRequest['project']['id']);

    return $project->pipelines()->first();
}

it('only accepts requests with correct GitLab headers', function() {
    postJson(route('reporter'))->assertStatus(400);
    postJson(route('reporter'), [], ['X-Gitlab-Event' => 'test', 'X-Gitlab-Token' => Project::token($this->project)])->assertStatus(400);
    postJson(route('reporter'), ['project_id' => 22], ['X-Gitlab-Token' => Project::token($this->project)])->assertStatus(400);
    postJson(route('reporter'), ['project_id' => 22], ['X-Gitlab-Event' => 'test'])->assertStatus(400);

    postJson(route('reporter'), $this->pipelinePendingRequest, [
        'X-Gitlab-Event' => 'ok',
        'X-Gitlab-Token' => Project::token($this->project),
    ])->assertStatus(200);
});

it('rejects requests that are past the due date of the task', function() {
    $this->project->task->update([
        'ends_at' => Carbon::create(2022, 1, 26),
    ]);

    postJson(route('reporter'))->assertStatus(400);
    postJson(route('reporter'), [], ['X-Gitlab-Event' => 'test', 'X-Gitlab-Token' => Project::token($this->project)])->assertStatus(400);
    postJson(route('reporter'), ['project_id' => 22], ['X-Gitlab-Token' => Project::token($this->project)])->assertStatus(400);
    postJson(route('reporter'), ['project_id' => 22], ['X-Gitlab-Event' => 'test'])->assertStatus(400);

    postJson(route('reporter'), $this->pipelinePendingRequest, [
        'X-Gitlab-Event' => 'Pipeline Hook',
        'X-Gitlab-Token' => Project::token($this->project),
    ])->assertStatus(400);

    assertDatabaseMissing('pipelines', ['project_id' => $this->project->id]);
});

it('rejects requests that are before the start date of the task', function() {
    $this->project->task->update([
        'starts_at' => Carbon::create(2022, 2, 1),
    ]);

    postJson(route('reporter'))->assertStatus(400);
    postJson(route('reporter'), [], ['X-Gitlab-Event' => 'test', 'X-Gitlab-Token' => Project::token($this->project)])->assertStatus(400);
    postJson(route('reporter'), ['project_id' => 22], ['X-Gitlab-Token' => Project::token($this->project)])->assertStatus(400);
    postJson(route('reporter'), ['project_id' => 22], ['X-Gitlab-Event' => 'test'])->assertStatus(400);

    postJson(route('reporter'), $this->pipelinePendingRequest, [
        'X-Gitlab-Event' => 'Pipeline Hook',
        'X-Gitlab-Token' => Project::token($this->project),
    ])->assertStatus(400);


    assertDatabaseMissing('pipelines', ['project_id' => $this->project->id]);
});

it('stores a pipeline request', function() {
    sendPendingPipeline();

    assertDatabaseHas('pipelines', [
        'status'     => PipelineStatusEnum::Pending,
        'project_id' => $this->project->id,
        'sha'        => 'bccf22832b61fb7232a1f5bd7dbd96184d5d28b4',
    ]);
});

it('processes a pending pipeline request', function() {
    $pipeline = sendPendingPipeline();

    expect($pipeline->pipeline_id)->toBe($this->pipelinePendingRequest['object_attributes']['id']);
    expect($pipeline->status)->toBe(PipelineStatusEnum::from($this->pipelinePendingRequest['object_attributes']['status']));
    expect($pipeline->user_name)->toBe($this->pipelinePendingRequest['user']['username']);
    expect($pipeline->queue_duration)->toBe($this->pipelinePendingRequest['object_attributes']['queued_duration']);
});

it('converts timestamps to the current timezone', function() {
    $pipeline = sendPendingPipeline();

    $expectedTime = Carbon::parse($this->pipelinePendingRequest['object_attributes']['created_at'])->setTimezone(config('app.timezone'));
    expect($pipeline->created_at->toDateTimeString())->toBe($expectedTime->toDateTimeString());
});


/*
 * todo neat feature in the future
it('requests info about pipelines that have gone stale', function ()
{

});*/

it('processes a running pipeline', function() {
    $pipeline = sendRunningPipeline();

    expect($pipeline->pipeline_id)->toBe($this->pipelineRunningRequest['object_attributes']['id']);
    expect($pipeline->status)->toBe(PipelineStatusEnum::from($this->pipelineRunningRequest['object_attributes']['status']));
    expect($pipeline->user_name)->toBe($this->pipelineRunningRequest['user']['username']);
    expect($pipeline->queue_duration)->toBe($this->pipelineRunningRequest['object_attributes']['queued_duration']);
});

it('processes a failing pipeline', function() {
    $pipeline = sendFailedPipeline();

    expect($pipeline->pipeline_id)->toBe($this->pipelineFailedRequest['object_attributes']['id']);
    expect($pipeline->status)->toBe(PipelineStatusEnum::from($this->pipelineFailedRequest['object_attributes']['status']));
    expect($pipeline->user_name)->toBe($this->pipelineFailedRequest['user']['username']);
    expect($pipeline->queue_duration)->toBe($this->pipelineFailedRequest['object_attributes']['queued_duration']);
});

it('processes a succeeding pipeline', function() {
    $pipeline = sendSucceedingPipeline();

    expect($pipeline->pipeline_id)->toBe($this->pipelineSucceedingRequest['object_attributes']['id']);
    expect($pipeline->status)->toBe(PipelineStatusEnum::from($this->pipelineSucceedingRequest['object_attributes']['status']));
    expect($pipeline->user_name)->toBe($this->pipelineSucceedingRequest['user']['username']);
    expect($pipeline->queue_duration)->toBe($this->pipelineSucceedingRequest['object_attributes']['queued_duration']);
});

it('ensures subtasks completion isn\'t overwritten should they fail in the future', function() {
    sendFailedPipeline();
    $this->pipelineRunningRequest['builds'][0]['status'] = 'failed';
    $pipeline = sendRunningPipeline();
    expect($pipeline->project->subTasks()->where('sub_task_id', 2)->exists())->toBeTrue();
    expect($pipeline->project->subTasks()->where('sub_task_id', 1)->exists())->toBeFalse();
});

it('marks one subtask as complete when one build succeeds', function() {
    $pipeline = sendFailedPipeline();

    expect($pipeline->project->subTasks()->where('sub_task_id', 2)->exists())->toBeTrue();
    expect($pipeline->project->subTasks()->where('sub_task_id', 1)->exists())->toBeFalse();
});

it('marks the task as complete when all builds succeeds', function() {
    $pipeline = sendSucceedingPipeline();

    expect($pipeline->project->subTasks()->where('sub_task_id', 2)->exists())->toBeTrue();
    expect($pipeline->project->subTasks()->where('sub_task_id', 1)->exists())->toBeTrue();

    $pipeline->project->refresh();
    expect($pipeline->project->status)->toBe(ProjectStatus::Finished);
});

it('ensures pending and running pipelines don\'t overwrite a finished pipeline', function() {
    sendSucceedingPipeline();
    $pipeline = sendPendingPipeline();

    expect($pipeline->project->status)->toBe(ProjectStatus::Finished);
    expect($pipeline->status)->toBe(PipelineStatusEnum::Success);
});

it('ensures pending pipelines don\'t overwrite a running or finished pipeline', function() {
    sendRunningPipeline();
    $pipeline = sendPendingPipeline();

    expect($pipeline->project->status)->toBe(ProjectStatus::Active);
    expect($pipeline->status)->toBe(PipelineStatusEnum::Running);
});

it('ensures pending pipelines gets updated to a running pipeline', function() {
    $pipeline = sendPendingPipeline();
    sendRunningPipeline();

    $pipeline->refresh();
    expect($pipeline->project->status)->toEqual(ProjectStatus::Active);
    expect($pipeline->status)->toEqual(PipelineStatusEnum::Running);
});

it('ensures failing pipelines changes the project', function() {
    $pipeline = sendPendingPipeline();
    sendFailedPipeline();

    $pipeline->refresh();
    expect($pipeline->project->status)->toBe(ProjectStatus::Active);
    expect($pipeline->status)->toBe(PipelineStatusEnum::Failed);
});

it('ensures running pipelines gets updated to a failed status', function() {
    $pipeline = sendPendingPipeline();
    sendFailedPipeline();

    $pipeline->refresh();
    expect($pipeline->project->status)->toBe(ProjectStatus::Active);
    expect($pipeline->status)->toBe(PipelineStatusEnum::Failed);
});

it('ensures failing pipelines can be updated to a succeeding', function() {
    $pipeline = sendFailedPipeline();
    sendSucceedingPipeline();

    $pipeline->refresh();
    expect($pipeline->project->status)->toEqual(ProjectStatus::Finished);
    expect($pipeline->status)->toEqual(PipelineStatusEnum::Success);
});
