<?php

use App\Http\Controllers\Course\Management\OverviewController;
use App\Http\Controllers\Course\Management\UserManagementController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseTrackController;
use App\Http\Controllers\GradingController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Course\Management\TaskController as TaskManagementController;
use App\Models\Course;

Route::get('/', [CourseController::class, 'index'])->name('index');
Route::get('{course}/enroll', [CourseController::class, 'showEnroll'])->name('enroll');
Route::get('create', [CourseController::class, 'create'])->name('create')->can('create', Course::class);
Route::post('/', [CourseController::class, 'store'])->name('store')->can('store', Course::class);

Route::group(['prefix' => '{course}', 'middleware' => ['can:view,course']], function() {
    Route::get('/', [CourseController::class, 'show'])->name('show');

    Route::group(['prefix' => 'tasks', 'as' => 'tasks.'], function() {
        Route::get('{task}', [TaskController::class, 'show'])->name('show');
        Route::get('{task}/next-exercise', [TaskController::class, 'nextExercise'])->name('next-exercise');
        Route::get('{task}/projects/{project}', [TaskController::class, 'showProject'])->name('showProject')->middleware('can:view,project');
        Route::get('{task}/projects/{project}/download', [ProjectController::class, 'download'])->name('downloadProject')->middleware('can:download,project');
        Route::post('{task}/projects/{project}/mark-complete', [ProjectController::class, 'markComplete'])->name('mark-complete');
        Route::get('{task}/projects/{project}/validate', [ProjectController::class, 'validateProject'])->name('validateProject')->middleware('can:validate,project');

        Route::prefix('{task}/projects/{project}/editor')->controller(ProjectController::class)->group(function() {
            Route::get('/', 'showEditor')->name('show-editor')->can('accessCode', ['project']);
            Route::get('tree', 'showTree')->name('show-tree')->can('accessCode', ['project']);
            Route::get('file', 'showFile')->name('show-file')->can('accessCode', ['project']);
            Route::get('comments', 'comments')->name('comments')->can('accessCode', ['project']);
            Route::post('comments', 'storeComment')->name('store-comment')->can('createFeedbackComment', ['project', 'projectDownload']);
            Route::put('comments/{projectFeedbackComment}', 'updateComment')->name('update-comment')->can('updateFeedbackComment', ['project', 'projectDownload', 'projectFeedbackComment']);
            Route::delete('comments/{projectFeedbackComment}', 'deleteComment')->name('delete-comment')->can('updateFeedbackComment', ['project', 'projectDownload', 'projectFeedbackComment']);
            Route::post('feedback', 'submitFeedback')->name('submit-feedback')->can('accessCode', ['project']);
            Route::put('comments/{projectFeedbackComment}/mark-as', 'markComment')->name('mark-comment')->can('markFeedbackComment', ['project', 'projectDownload', 'projectFeedbackComment']);
        });
        Route::post('{task}/create-project', [TaskController::class, 'doCreateProject'])->name('createProject');
    });

    Route::get('tracks/{track}', [CourseTrackController::class, 'show'])->name('tracks.show');

    Route::group(['prefix' => 'groups', 'as' => 'groups.'], function() {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::post('/', [GroupController::class, 'create'])->name('create')->middleware('can:createGroup,course');
        Route::delete('{group}', [GroupController::class, 'destroy'])->name('destroy')->middleware('can:delete,group');
        Route::post('{group}/invite-user', [GroupController::class, 'inviteUser'])->name('invite')->middleware(['can:invite,group', 'throttle:30']);
        Route::post('{group}/leave', [GroupController::class, 'leave'])->name('leave')->middleware(['can:leave,group']);

        Route::get('{group}/invitation/{groupInvitation}/{action}', [GroupController::class, 'respondToInvite'])->name('respondInvite')
            ->can('respondInvite,group,groupInvitation')->where('action', 'accept|decline');
        Route::delete('{group}/invitation/{groupInvitation}', [GroupController::class, 'deleteInvite'])->name('invitations.delete')
            ->middleware('can:delete,groupInvitation');
        Route::delete('{group}/members/{user}', [GroupController::class, 'removeMember'])->name('removeMember')
            ->middleware('can:removeMember,group,user');
    });

    Route::group(['prefix' => 'manage', 'as' => 'manage.'], function() {
        Route::get('/', [OverviewController::class, 'index'])->name('index')->can('manage,course');
        Route::post('tasks', [OverviewController::class, 'store'])->name('storeTask')->middleware('can:manage,course');

        Route::controller(UserManagementController::class)->middleware('can:manage,course')->group(function() {
            Route::get('enrolment', 'enrolment')->name('enrolment.index');
            Route::put('update-role', 'updateRole')->name('update-role');
            Route::delete('kick-user', 'kickUser')->name('kick-user');
            Route::get('activity', 'activity')->name('activity.index');

            Route::get('groups', 'groups')->name('groups.index');
            Route::post('groups', 'createGroup')->name('groups.create');
            Route::get('groups/{group}', 'showGroup')->name('groups.show')->can('view,group');
            Route::delete('groups/{group}', 'deleteGroup')->name('groups.delete')->can('delete,group');
            Route::put('groups/{group}', 'updateGroup')->name('groups.update')->can('update,group');
            Route::post('groups/{group}/members', 'addGroupMember')->name('groups.add-member')->can('addMember,group');
            Route::delete('groups/{group}/members', 'removeGroupMember')->name('groups.remove-member')->can('removeMemberAsAdmin,group');
            Route::put('groups', 'updateGroupSettings')->name('groups.update-settings');
            //Route::get('roles', [UserManagementController::class, 'roles'])->name('roles');
        });

        Route::controller(TaskManagementController::class)->middleware('can:manage,course')->group(function() {
            Route::get('exercises', 'exercises')->name('exercises.index');
            Route::put('exercises/reorganize', 'reorganizeExercises')->name('exercises.reorganize');
        });

        Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('editTask')->middleware('can:manage,course');
        Route::patch('tasks/{task}/edit', [TaskController::class, 'update'])->name('updateTask')->middleware('can:manage,course');
        Route::get('tasks/{task}/toggle-visibility', [TaskController::class, 'toggleVisibility'])->name('toggleVisibility')->middleware('can:manage,course');
        Route::get('tasks/{task}/refresh-readme', [TaskController::class, 'refreshReadme'])->name('refreshReadme')->middleware('can:manage,course');
        Route::post('add-teacher', [CourseController::class, 'addTeacher'])->name('addTeacher')->middleware('can:manage,course');
        Route::get('teachers/{teacher}/remove', [CourseController::class, 'removeTeacher'])->name('removeTeacher')->middleware('can:manage,course');

        Route::group(['prefix' => 'grading', 'as' => 'grading.', 'middleware' => 'can:grade,course'], function() {
            Route::get('/', [GradingController::class, 'index'])->name('index');
            Route::put('users/{user}', [GradingController::class, 'updateGrading'])->name('updateGrading');
            Route::get('tasks/{task}', [GradingController::class, 'taskInfo'])->name('task-info');
            Route::post('{grade}/set-selected', [GradingController::class, 'setSelected'])->name('set-selected');
        });

        Route::prefix('tracks')->as('tracks.')->controller(CourseTrackController::class)->middleware('can:manage,course')->group(function() {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('create', 'store')->name('store');
            Route::delete('{track}', 'destroy')->name('destroy');
            Route::get("{track}/assign", 'assign')->name('assign');
            Route::post("{track}/assign", 'doAssign')->name('doAssign');
            Route::delete('{track}/assign/{task}', 'unassign')->name('unassign');
        });
    });
});
