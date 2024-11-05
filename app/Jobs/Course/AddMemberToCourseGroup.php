<?php

namespace App\Jobs\Course;

use Domain\GitLab\Definitions\GitLabUserAccessLevelEnum;
use GrahamCampbell\GitLab\GitLabManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddMemberToCourseGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public int $gitlabUser, public int $groupId, public ?int $level)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        app(GitLabManager::class)->groups()->addMember($this->groupId, $this->gitlabUser, $this->level ?? GitLabUserAccessLevelEnum::REPORTER);
    }
}
