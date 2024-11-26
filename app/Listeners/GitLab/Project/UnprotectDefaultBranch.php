<?php

namespace App\Listeners\GitLab\Project;

use App\Events\ProjectCreated;
use Exception;
use GrahamCampbell\GitLab\GitLabManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UnprotectDefaultBranch implements ShouldQueue
{
    public int $delay = 5;

    public int $tries = 5;

    /**
     * @var int[]
     */
    public array $backoff = [10, 30];

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ProjectCreated $event
     * @return void
     * @throws Exception
     */
    public function handle(ProjectCreated $event): void
    {
        if ( ! $event->project->task->isCodeTask()) return;

        Log::info("Unprotecting default branch for project {$event->project->id}");

        $gitLabManager = app(GitLabManager::class);

        $project = $gitLabManager->projects()->show($event->project->gitlab_project_id);
        if($project['import_error'] != null || $project['import_status'] != 'finished')
            throw new Exception("Import not fully done yet.");

        $gitLabManager->repositories()->unprotectBranch($event->project->gitlab_project_id, $project['default_branch']);
    }
}
