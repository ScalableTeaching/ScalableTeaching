<?php

namespace App\Modules\MarkAsDone;

use App\Modules\AutomaticGrading\AutomaticGrading;
use App\Modules\LinkRepository\LinkRepository;
use App\Modules\Module;
use App\Modules\SubtaskGradingAndFeedback\SubtaskGradingAndFeedback;
use App\Modules\Subtasks\Subtasks;
use App\Modules\Template\Template;

class MarkAsDone extends Module
{
    protected string $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-lime-green-300">
  <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
</svg>';
    protected string $name = "Mark As Done";

    protected string $description = "Task can be marked as done by participants. Useful for small tasks and exercises where there is no hand-in.";

    protected array $conflicts = [
        AutomaticGrading::class,
        SubtaskGradingAndFeedback::class,
        Subtasks::class,
    ];

    protected array $dependencies = [Template::class];
}
