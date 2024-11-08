<?php

namespace App\Console\Commands;

use Gitlab\ResultPager;
use GrahamCampbell\GitLab\GitLabManager;
use Illuminate\Console\Command;

/**
 * This should be avoided to run as much as possible, but can help speed up fixing an issue, or managing project/repo settings or screw ups.
 */
class FixLiveProjects extends Command
{
    protected $signature = "fix:live-projects";

    protected $description = "This commands aim to provide a template for easily modifying/fixing all projects that is live, and has already been cloned.";

    /**
     * Remember to setup fix as a valid connection in `gitlab.php` config file.
     * It should be a personal access token generated for that specific course, with full API perms.
     * @return void
     */
    public function handle()
    {
        $courseGitlabGroupId = 12337;

        $manager = app(GitLabManager::class);
        $pager = new ResultPager($manager->connection('fix'));
        try
        {
            $projects = $pager->fetchAll($manager->connection('fix')->groups(), 'projects', [$courseGitlabGroupId]);
        } catch (\Exception $e)
        {
            $this->error("Did not fetch all: {$e->getMessage()}");

            return;
        }

        foreach ($projects as $project)
        {
            try
            {
                // Do something with the project here.
                $this->info("Successfully made action for project {$project['name']}");
            } catch (\Exception)
            {
                $this->error("FAILED action for project {$project['name']}");
            }
        }
    }
}
