<?php

use App\Models\Course;
use App\Models\CourseTrack;
use App\Models\Task;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function() {
    $this->course = Course::factory()->create();
    $this->track = CourseTrack::factory()->for($this->course)->create();
    $this->task = Task::factory()->for($this->course)->create();

    $this->user = UserFactory::new()->hasAttached($this->course)->create();
    $this->professor = UserFactory::new()->hasAttached($this->course, ['role' => 'teacher'])->create();
});

it('does not allow students to hit the task unassignment route', function() {
    actingAs($this->user);
    delete(route('courses.manage.tracks.unassign', [$this->course, $this->track, $this->task]))
        ->assertStatus(403);
});

it('does not allow professors to unassign a task from a track not associated with their course', function() {
    actingAs($this->professor);
    $course = Course::factory()->create();
    delete(route('courses.manage.tracks.unassign', [$course, $this->track, $this->task]))
        ->assertStatus(403);
});

it('does not allow professors to unassign a task not in their course from a track in a course that is not theirs', function() {
    actingAs($this->professor);
    $course = Course::factory()->create();
    $track = CourseTrack::factory()->for($course)->create();
    $task = Task::factory()->for($course)->create();
    delete(route('courses.manage.tracks.unassign', [$course, $track, $task]))
        ->assertStatus(403);
});

it('allows a professor to unassign a task from a track within their course', function() {
    actingAs($this->professor);
    $this->task->track()->associate($this->track);
    $this->task->save();

    delete(route('courses.manage.tracks.unassign', [$this->course, $this->track, $this->task]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();
});
