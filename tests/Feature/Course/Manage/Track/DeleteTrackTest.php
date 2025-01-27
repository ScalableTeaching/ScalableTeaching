<?php

use App\Models\Course;
use App\Models\CourseTrack;
use App\Models\Task;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

uses(RefreshDatabase::class);

beforeEach(function() {
    $this->course = Course::factory()->create();
    $this->track = CourseTrack::factory()->for($this->course)->create();

    $this->user = UserFactory::new()->hasAttached($this->course)->create();
    $this->professor = UserFactory::new()->hasAttached($this->course, ['role' => 'teacher'])->create();
});

it('does not allow a student to delete a track', function() {
    actingAs($this->user);
    delete("/courses/{$this->course->id}/manage/tracks/{$this->track->id}")
        ->assertStatus(403);
});

it('does allow a professor to delete a track', function() {
    actingAs($this->professor);
    delete("/courses/{$this->course->id}/manage/tracks/{$this->track->id}")
        ->assertRedirect(route('courses.manage.tracks.index', $this->course));
});

it('returns 404 when trying to delete a track that does not exist', function() {
    actingAs($this->professor);
    delete("/courses/{$this->course->id}/manage/tracks/12341")
        ->assertStatus(404);
});

it('returns 403 when trying to delete a track from another course', function() {
    actingAs($this->professor);
    $course = Course::factory()->create();
    $track = CourseTrack::factory()->for($course)->create();
    delete("/courses/{$course->id}/manage/tracks/{$track->id}")
        ->assertStatus(403);
});

it('should unassign all tasks from the track', function() {
    actingAs($this->professor);
    $task = Task::factory()->for($this->course)->create();
    $task->track()->associate($this->track);
    $task->save();
    $this->assertDatabaseHas('tasks', [
        'track_id' => $this->track->id,
    ]);

    delete("/courses/{$this->course->id}/manage/tracks/{$this->track->id}")
        ->assertRedirect(route('courses.manage.tracks.index', $this->course));

    $this->assertDatabaseMissing('tasks', [
        'track_id' => $this->track->id,
    ]);
});

it('should return 400 when trying to delete a track with sub tracks', function() {
    actingAs($this->professor);
    $subTrack = CourseTrack::factory()->for($this->course)->create(['parent_id' => $this->track->id]);
    delete("/courses/{$this->course->id}/manage/tracks/{$this->track->id}")
        ->assertRedirect(route('courses.manage.tracks.index', $this->course));

    $this->assertDatabaseHas('course_tracks', [
        'id' => $this->track->id,
    ]);
});


