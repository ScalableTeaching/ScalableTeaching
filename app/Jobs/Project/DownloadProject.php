<?php

namespace App\Jobs\Project;

use App\Models\ProjectDownload;
use GrahamCampbell\GitLab\GitLabManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

class DownloadProject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * @var int[]
     */
    public array $backoff = [60, 120, 300, 600];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly ProjectDownload $download)
    {
    }

    public function handle(): void
    {
        if ($this->download->downloaded_at != null) {
            return;
        }

        $gitLabManager = app(GitLabManager::class);

        $archiveContent = $gitLabManager->repositories()->archive($this->download->project->project_id, [
            'sha' => $this->download->ref,
        ], 'zip');

        $fileLocation = "tasks/{$this->download->project->task_id}/projects/{$this->download->project_id}_{$this->download->ref}.zip";
        Storage::disk('local')->put($fileLocation, $archiveContent);

        $this->download->update([
            'location' => $fileLocation,
            'downloaded_at' => now(),
        ]);
    }
}
