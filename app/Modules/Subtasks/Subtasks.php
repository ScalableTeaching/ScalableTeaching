<?php

namespace App\Modules\Subtasks;

use App\Modules\LinkRepository\LinkRepository;
use App\Modules\MarkAsDone\MarkAsDone;
use App\Modules\Module;
use App\Modules\Template\Template;
use Route;

class Subtasks extends Module
{
    protected string $name = "Subtasks";
    protected string $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-lime-green-300">
  <path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd" />
  <path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd" />
</svg>
';
    protected string $description = "Specify smaller subtasks that participants can complete. This module requires either the \"Automatic Grading\" or \"Subtask Grading and Feedback\" module installed, to allow grading subtasks.";

    protected array $dependencies = [LinkRepository::class, Template::class];

    protected array $conflicts = [MarkAsDone::class];

    public static function configRoutes(): void
    {
        Route::get('/', [SubtasksController::class, 'subTasks'])->name('subTasks');
        Route::post('/', [SubtasksController::class, 'saveSubTasks'])->name('save-subTasks');
        Route::get('export-results', [SubtasksController::class, 'exportResults'])->name('export');
        Route::get('task-completion', [SubtasksController::class, 'studentTaskCompletion'])->name('task-completion');
        Route::get('task-completion/aggregate', [SubtasksController::class, 'aggregatedTaskCompletion'])->name('aggregate-task-completion');
    }
}
