<?php

namespace App\Console\Commands;

use App\Models\Enums\CorrectionType;
use App\Models\Project;
use App\Models\Task;
use App\ProjectStatus;
use Illuminate\Console\Command;

class MarkExpiredProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:mark-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Goes through all of the tasks and marks the ones that are overdue.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        $tasks = Task::where('is_visible', true)->get();
        /** @var Task $task */
        foreach ($tasks as $task)
        {
            $this->info("Marking tasks under [{$task->course->name}] $task->name");
            $overDueTime = $task->ends_at->addMinutes(5);

            if ( ! now()->isAfter($overDueTime))
            {
                $this->info('Not expired yet.');
                continue;
            }

            $count = $task->projects()->where('status', 'active')->withTrashed()->each(function(Project $project) {
                $project->setProjectStatus(ProjectStatus::Overdue);
            });
            $this->info("Marked $count projects as overdue.");
        }

        return 0;
    }
}
