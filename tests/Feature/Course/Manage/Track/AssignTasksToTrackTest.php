<?php

use App\Models\Course;
use App\Models\CourseTrack;
use App\Models\Task;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function() {
    $this->course = Course::factory()->create();
    $this->track = CourseTrack::factory()->for($this->course)->create();
    $this->task = Task::factory()->for($this->course)->create();

    $this->user = UserFactory::new()->hasAttached($this->course)->create();
    $this->professor = UserFactory::new()->hasAttached($this->course, ['role' => 'teacher'])->create();
});

it('does not allow students to access the task assignment page', function() {
    actingAs($this->user);
    get(route('courses.manage.tracks.assign', [$this->course, $this->track]))
        ->assertStatus(403);
});

it('allows professors to access the task assignment page', function() {
    actingAs($this->professor);
    get(route('courses.manage.tracks.assign', [$this->course, $this->track]))
        ->assertStatus(200);
});

it('does not allow students to post to the task assignment route', function() {
    actingAs($this->user);
    post(route('courses.manage.tracks.assign', [$this->course, $this->track]))
        ->assertStatus(403);
});

it('validates the tasks field', function() {
    actingAs($this->professor);
    post(route('courses.manage.tracks.assign', [$this->course, $this->track]), [
        'tasks' => '',
    ])->assertSessionHasErrors(['tasks']);

    post(route('courses.manage.tracks.assign', [$this->course, $this->track]), [
        'tasks' => ['Hello World'],
    ])->assertSessionHasErrors(['tasks.*']);

    post(route('courses.manage.tracks.assign', [$this->course, $this->track]), [
        'tasks' => [$this->task->id],
    ])->assertSessionHasNoErrors();
});

it('allows a professor to assign tasks to a track', function() {
    actingAs($this->professor);
    post(route('courses.manage.tracks.assign', [$this->course, $this->track]), [
        'tasks' => [$this->task->id],
    ])->assertSessionHasNoErrors()
    ->assertRedirectToRoute('courses.manage.tracks.index', [$this->course]);

    $this->assertDatabaseHas('tasks', [
        'id'       => $this->task->id,
        'track_id' => $this->track->id,
    ]);
});

it('does not allow a professor to assign a task within the course to a track that does not belong to the course', function() {
    actingAs($this->professor);
    $course = Course::factory()->create();
    $track = CourseTrack::factory()->for($course)->create();
    post(route('courses.manage.tracks.assign', [$this->course, $track]), [
        'tasks' => [$this->task->id],
    ])->assertStatus(403);

    post(route('courses.manage.tracks.assign', [$course, $this->track]), [
        'tasks' => [$this->task->id],
    ])->assertStatus(403);
});

it('does not allow a professor to assign a task that is not part of the course to a track', function() {
    actingAs($this->professor);
    $course = Course::factory()->create();
    $task = Task::factory()->for($course)->create();
    $res = post(route('courses.manage.tracks.assign', [$this->course, $this->track]), [
        'tasks' => [$task->id],
    ])->assertRedirectToRoute('courses.manage.tracks.assign', [$this->course, $this->track]);
});

it('does not allow a professor to assign a task that is already assigned to a track', function() {
    actingAs($this->professor);
    $this->task->track()->associate($this->track);
    $this->task->save();

    post(route('courses.manage.tracks.assign', [$this->course, $this->track]), [
        'tasks' => [$this->task->id],
    ])->assertRedirectToRoute('courses.manage.tracks.assign', [$this->course, $this->track]);
});


