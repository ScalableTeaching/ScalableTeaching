<?php

use App\Models\Course;
use App\Models\CourseTrack;
use App\Models\Task;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function() {
    $this->course = Course::factory()->create();

    $this->user = UserFactory::new()->hasAttached($this->course)->create();
    $this->professor = UserFactory::new()->hasAttached($this->course, ['role' => 'teacher'])->create();
});

it('allows a professor to access the create a track page', function() {
    actingAs($this->professor);
    get("/courses/{$this->course->id}/manage/tracks/create")
        ->assertStatus(200);
});

it('does not allow a student to access the create a track page', function() {
    actingAs($this->user);
    get("/courses/{$this->course->id}/manage/tracks/create")
        ->assertStatus(403);
});

it('does not allow a student to post to the create track route', function() {
    actingAs($this->user);
    post("/courses/{$this->course->id}/manage/tracks/create")
        ->assertStatus(403);
});

it('validates the name field', function() {
    actingAs($this->professor);
    post("/courses/{$this->course->id}/manage/tracks/create", [
        'name' => '',
    ])->assertSessionHasErrors(['name']);

    post("/courses/{$this->course->id}/manage/tracks/create", [
        'name'        => 'Hello World',
        'description' => 'abcd',
    ])->assertSessionHasNoErrors();
});

it('allows an empty description', function() {
    actingAs($this->professor);
    post(route('courses.manage.tracks.create', [$this->course]), [
        'name'        => 'Hello World',
        'description' => '',
    ])->assertSessionHasNoErrors();

    post(route('courses.manage.tracks.create', [$this->course]), [
        'name' => 'Hello World',
    ])->assertSessionHasNoErrors();
});

it('creates a track', function() {
    actingAs($this->professor);
    post("/courses/{$this->course->id}/manage/tracks/create", [
        'name'        => 'Hello World',
        'description' => 'abcd',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('course_tracks', [
        'name'        => 'Hello World',
        'description' => 'abcd',
    ]);
});

it('creates a track with a parent', function() {
    actingAs($this->professor);
    post("/courses/{$this->course->id}/manage/tracks/create", [
        'name'        => 'Hello World',
        'description' => 'abcd',
    ])->assertSessionHasNoErrors();

    $track = CourseTrack::where('name', 'Hello World')->first();

    post("/courses/{$this->course->id}/manage/tracks/create", [
        'name'        => 'With Parent',
        'description' => 'abcd',
        'parent'      => $track->id,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('course_tracks', [
        'name'        => 'With Parent',
        'description' => 'abcd',
        'parent_id'   => $track->id,
    ]);
});

it('does not allow creating a track with a parent that does not exist', function() {
    actingAs($this->professor);
    post("/courses/{$this->course->id}/manage/tracks/create", [
        'name'        => 'Hello World',
        'description' => 'abcd',
    ])->assertSessionHasNoErrors();

    post("/courses/{$this->course->id}/manage/tracks/create", [
        'name'        => 'With Parent',
        'description' => 'abcd',
        'parent'      => 1234,
    ])->assertSessionHasErrors(['parent']);
});

it('does not allow creating a track for a course that does not exist', function() {
    actingAs($this->professor);
    post("/courses/1234/manage/tracks/create", [
        'name'        => 'Hello World',
        'description' => 'abcd',
    ])->assertStatus(404);
});

it('does not allow creating a track for a course that the user is not attached to', function() {
    actingAs($this->professor);
    $course = Course::factory()->create();
    post("/courses/{$course->id}/manage/tracks/create", [
        'name'        => 'Hello World',
        'description' => 'abcd',
    ])->assertStatus(403);
});
