<?php

use App\Models\Casts\SubTask;
use App\Models\Course;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use function Pest\Laravel\assertDatabaseCount;
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
    ])->for(Course::factory())->createQuietly();


    $this->project = Project::factory()->for($task)->createQuietly();

});

function sendPushEvent(mixed $pushEvent): TestResponse
{
    return postJson(route('reporter'), $pushEvent, [
        'X-GitLab-Token' => Project::token(test()->project->gitlab_project_id),
        'X-GitLab-Event' => 'Push Hook',
    ]);
}



it('skips branch deletion push event', function() {
    $branchDeletionPush = json_decode(file_get_contents(testDirectory('Feature/GitLab/Stubs/Pushes/branch_deletion_push.json')), true);
    $branchDeletionPush['project']['id'] = $this->project->gitlab_project_id;
    $branchDeletionPush['project_id'] = $this->project->gitlab_project_id;

    $res = sendPushEvent($branchDeletionPush);

    $res->assertSeeText("SKIPPED");
});

it('succeeds with a regular push event', function() {
    $regularPush = json_decode(file_get_contents(testDirectory('Feature/GitLab/Stubs/Pushes/regular_push.json')), true);
    $regularPush['project']['id'] = $this->project->gitlab_project_id;
    $regularPush['project_id'] = $this->project->gitlab_project_id;

    $res = sendPushEvent($regularPush);

    $res->assertOk();

    assertDatabaseCount('project_pushes', 1);
});
