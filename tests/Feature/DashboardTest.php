<?php

use App\Models\Course;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);


beforeEach(function() {
    $this->oldCourse = Course::factory()->create([
        'name'       => 'old course',
        'created_at' => date('2022-11-10'),
    ]);

    $this->newCourse = Course::factory()->create([
        'name'       => 'new course',
        'created_at' => date('2022-11-13'),
    ]);

    $this->oldExercise = Task::factory()->exercise()->for($this->oldCourse)->create([
        'name'       => 'old exercise',
        'created_at' => date('2022-11-10'),
    ]);

    $this->newExercise = Task::factory()->exercise()->for($this->oldCourse)->create([
        'name'       => 'new exercise',
        'created_at' => date('2022-11-13'),
    ]);
});

it('tests courses are ordered by desc', function () {
    $student = User::factory()->hasAttached([$this->oldCourse, $this->newCourse])->create();
    actingAs($student);

    $this->assertEquals('new course', $student->courses()->orderBy('created_at', 'desc')->first()->name);
});

it('tests exercises are ordered by desc', function () {
    $student = User::factory()->hasAttached($this->oldCourse)->create();
    actingAs($student);

    $this->assertEquals('new exercise', Task::exercises()->whereIn('course_id', $this->oldCourse->pluck('id'))->orderBy('created_at', 'desc')->visible()->first()->name);
});
