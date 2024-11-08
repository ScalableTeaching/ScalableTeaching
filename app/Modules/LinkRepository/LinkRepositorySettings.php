<?php

namespace App\Modules\LinkRepository;

use App\Models\Task;
use App\Modules\Settings;
use GrahamCampbell\GitLab\GitLabManager;

class LinkRepositorySettings extends Settings
{
    public ?string $repo = null;

    public function validationRules(Task $task): array
    {
        return [
            'repo' => ['required'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function additionalValues(): array
    {
        if ($this->repo == null)
        {
            return [];
        }

        try
        {
            $gitlabManager = app(GitLabManager::class);
            $project = $gitlabManager->projects()->show(explode("/", $this->repo)[4]);

            return [
                'repoName' => $project['name_with_namespace'],
            ];
        } catch (\Exception $ex)
        {
            return [
                'repo' => null,
            ];
        }

    }


}
