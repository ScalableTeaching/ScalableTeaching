<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GitLabOAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\EnsureUserIsSystemAdmin;
use App\Models\User;
use Badcow\PhraseGenerator\PhraseGenerator;
use Illuminate\Support\Facades\Route;

// If you can't find the routes in these files, then take a look in the module controllers if's a route related to a module.

Route::get('/', [DashboardController::class, 'index'])->name('home');

Route::get('start', [HomeController::class, 'start'])->middleware('auth')->name('start');

Route::get('status', [HomeController::class, 'status'])->middleware('auth')->name('status');

Route::prefix('surveys')->as('surveys.')->middleware('auth')->controller(SurveyController::class)->group(function() {
    Route::get('/', 'index')->name('index');
    Route::get('all', 'all')->name('all');
    Route::get('{survey}', 'details')->name('details');
    Route::get('{survey}/export', 'export')->name('export');
});

Route::group(['prefix' => 'projects/{project}', 'as' => 'projects.', 'middleware' => ['auth']], function() {
    Route::get('builds', [ProjectController::class, 'builds'])->middleware('can:view,project');
    Route::get('reset', [ProjectController::class, 'reset'])->middleware('can:view,project');
    Route::post('migrate/{group}', [ProjectController::class, 'migrate'])->middleware(['can:migrate,project,group', 'throttle:5']);
    Route::post('refresh-access', [ProjectController::class, 'refreshAccess'])->middleware(['can:refreshAccess,project', 'throttle:5']);

    Route::controller(SurveyController::class)->prefix('surveys/{survey}')->group(function() {
        Route::post('/', 'projectSurvey')->can('answer,survey,project');
    });
});


Route::get('random-name', function() {
    return PhraseGenerator::generate();
})->middleware('auth');

if(app()->environment('local'))
{
    Route::get('impersonate/{user}', function($user) {
        auth()->login(User::findOrFail($user));

        return "authed";
    });
}

Route::controller(GitLabOAuthController::class)->prefix('login')->middleware('guest')->group(function() {
    Route::get('/', 'login')->name('login');
    Route::get('callback', 'callback')->name('callback');
});

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => [EnsureUserIsSystemAdmin::class]], function() {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::post('add-professor', [AdminController::class, 'addProfessor'])->name('add-professor');
    Route::get('professors/{user}/remove', [AdminController::class, 'removeProfessor'])->name('remove-professor');
    Route::get('professors/{user}/toggle-promotion', [AdminController::class, 'togglePromotion'])->name('toggle-promotion');
});


Route::get('logout', function() {
    auth()->logout();

    return redirect()->route('home');
})->name("logout");
