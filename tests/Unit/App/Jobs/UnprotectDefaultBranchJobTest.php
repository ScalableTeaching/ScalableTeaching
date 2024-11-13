<?php

use App\Events\ProjectCreated;
use App\Listeners\GitLab\Project\UnprotectDefaultBranch;
use App\Models\Course;
use App\Models\Project;
use App\Models\Task;
use App\Modules\LinkRepository\LinkRepository;
use Carbon\Carbon;
use GrahamCampbell\GitLab\GitLabManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function() {

    /** @var Task $task */
    $task = Task::factory([
        'starts_at' => Carbon::create(2022, 8, 8, 12),
        'ends_at'   => Carbon::create(2022, 8, 24, 23, 59),
    ])->for(Course::factory())->make();

    installLinkRepositoryModule($task);
    $task->save();
    $this->task = $task;

    $this->project = Project::factory([
        "task_id" => $task,
    ])->createQuietly();
});

it('should skip if the project is not a code task', function() {

    $this->project->task->module_configuration->uninstall($this->project->task->module_configuration->resolveModule(LinkRepository::class));
    $this->project->task->save();

    $job = new UnprotectDefaultBranch();
    $job->handle(new ProjectCreated($this->project));

    $this->mock(GitLabManager::class, function (MockInterface $mock) {
        $mock->shouldNotHaveBeenCalled();
    });
});


it('should throw an exception if the project import is not finished', function() {

    $this->project->gitlab_project_id = 1;
    $this->project->save();

    $this->mock(GitLabManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('projects->show')->once()->andReturn([
            'import_error'  => 'error',
            'import_status' => 'finished',
        ]);
    });

    $this->expectException(Exception::class);

    $job = new UnprotectDefaultBranch();
    $job->handle(new ProjectCreated($this->project));
});

it('should unprotect the default branch', function() {

    $this->project->gitlab_project_id = 1;
    $this->project->save();

    $this->mock(GitLabManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('projects->show')->once()->andReturn([
            'import_error'   => null,
            'import_status'  => 'finished',
            'default_branch' => 'master',
        ]);

        $mock->shouldReceive('repositories->unprotectBranch')->once()->with(1, 'master');
    });

    $job = new UnprotectDefaultBranch();
    $job->handle(new ProjectCreated($this->project));
});


