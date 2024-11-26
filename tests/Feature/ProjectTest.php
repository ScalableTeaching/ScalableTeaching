<?php

use App\Events\ProjectCreated;
use App\Listeners\GitLab\Project\DisableForking;
use App\Jobs\Project\RefreshMemberAccess;
use App\Listeners\GitLab\Project\RegisterWebhook;
use App\Listeners\GitLab\Project\UnprotectDefaultBranch;
use App\Models\Course;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ensures the ProjectCreated event is fired', function() {
    Event::fake();
    Project::factory()->for(Task::factory()->for(Course::factory()))->create();

    Event::assertDispatched(ProjectCreated::class);
});

it('ensures the RefreshMemberAccess job is not fired when a project is created without any owner', function() {
    Queue::fake();
    Project::factory()->for(Task::factory()->for(Course::factory()))->create();


    Queue::assertNotPushed(RefreshMemberAccess::class);
});

it('ensures the RefreshMemberAccess job is fired when a project is created with a user', function() {
    Queue::fake();
    Project::factory()->for(User::factory(), 'ownable')->for(Task::factory()->for(Course::factory()))->create();


    Queue::assertPushed(RefreshMemberAccess::class);
});

it('ensures the DisableForking event is fired when ProjectCreated', function() {
    Event::fake();
    Project::factory()->for(Task::factory()->for(Course::factory()))->create();

    Event::assertListening(ProjectCreated::class, DisableForking::class);
});

it('ensures the RegisterWebhook event is fired when ProjectCreated', function() {
    Event::fake();
    Project::factory()->for(Task::factory()->for(Course::factory()))->create();

    Event::assertListening(ProjectCreated::class, RegisterWebhook::class);
});

it('ensures the UnprotectDefaultBranch event is fired when ProjectCreated is triggered', function() {
    Event::fake();
    Project::factory()->for(Task::factory()->for(Course::factory()))->create();

    Event::assertListening(ProjectCreated::class, UnprotectDefaultBranch::class);
});
