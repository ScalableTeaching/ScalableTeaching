<?php

use App\Models\Course;
use App\Models\Enums\PipelineStatusEnum;
use App\Models\Pipeline;
use App\Models\Project;
use App\Models\Task;

it('ensures a failing pipeline is not upgradable', function() {
    $pipeline = new Pipeline();
    $pipeline->status = PipelineStatusEnum::Failed;

    expect($pipeline->isUpgradable(PipelineStatusEnum::Pending))->toBeFalse();
    expect($pipeline->isUpgradable(PipelineStatusEnum::Success))->toBeTrue();
    expect($pipeline->isUpgradable(PipelineStatusEnum::Running))->toBeFalse();
});

it('ensures a succeeding pipeline is upgradable', function() {
    $pipeline = new Pipeline();
    $pipeline->status = PipelineStatusEnum::Success;

    expect($pipeline->isUpgradable(PipelineStatusEnum::Pending))->toBeFalse();
    expect($pipeline->isUpgradable(PipelineStatusEnum::Failed))->toBeTrue();
    expect($pipeline->isUpgradable(PipelineStatusEnum::Running))->toBeFalse();
});

it('ensures a pending pipeline is upgradable to running, success and failed', function() {
    $pipeline = new Pipeline();
    $pipeline->status = PipelineStatusEnum::Pending;

    expect($pipeline->isUpgradable(PipelineStatusEnum::Success))->toBeTrue();
    expect($pipeline->isUpgradable(PipelineStatusEnum::Failed))->toBeTrue();
    expect($pipeline->isUpgradable(PipelineStatusEnum::Running))->toBeTrue();
});

it('ensures a running pipeline is upgradable to success and failed', function() {
    $pipeline = new Pipeline();
    $pipeline->status = PipelineStatusEnum::Running;

    expect($pipeline->isUpgradable(PipelineStatusEnum::Success))->toBeTrue();
    expect($pipeline->isUpgradable(PipelineStatusEnum::Failed))->toBeTrue();
    expect($pipeline->isUpgradable(PipelineStatusEnum::Pending))->toBeFalse();
});

it('skips jobs that are successful but not tracked', function() {
    \Illuminate\Support\Facades\Log::spy();

    /** @var Project $project */
    $project = Project::factory()->for(Task::factory(['starts_at' => now()->subSeconds(10), 'ends_at' => now()->addSeconds(10)])->for(Course::factory()))->create();

    $pipeline = new Pipeline();
    $pipeline->status = PipelineStatusEnum::Running;
    $pipeline->project = $project;

    \Illuminate\Support\Facades\Log::shouldReceive('debug')->once();
    $pipeline->process(
        startedAt: now(),
        status: PipelineStatusEnum::Success,
        duration: 10,
        queueDuration: 5,
        succeedingBuilds: ['test 11 equals [10, 1]']
    );
});

it('does not skip jobs that are successful and tracked', function() {
    \Illuminate\Support\Facades\Log::spy();


    $subTasks = createSubTasks([
        ['title' => 'test 11 Equals [10, 1]', 'points' => 50],
    ]);
    /** @var Project $project */
    $project = Project::factory()->for(Task::factory(['sub_tasks' => $subTasks, 'starts_at' => now()->subSeconds(10), 'ends_at' => now()->addSeconds(10)])->for(Course::factory()))->create();

    $pipeline = Pipeline::factory()->running()->for($project)->create();

    \Illuminate\Support\Facades\Log::shouldReceive('debug')->never();
    $pipeline->process(
        startedAt: now(),
        status: PipelineStatusEnum::Success,
        duration: 10,
        queueDuration: 5,
        succeedingBuilds: ['test 11 equals [10, 1]']
    );
});

