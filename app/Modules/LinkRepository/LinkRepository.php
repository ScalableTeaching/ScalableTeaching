<?php

namespace App\Modules\LinkRepository;

use App\Modules\MarkAsDone\MarkAsDone;
use App\Modules\Module;
use App\Modules\Settings;

class LinkRepository extends Module
{
    protected string $name = 'Link Repository';
    protected string $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" class="w-6 h-6 text-lime-green-300">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                            </svg>';
    protected string $description = 'Links this repository to a GitLab repo.';

    protected function loadSettings(): ?Settings
    {
        return new LinkRepositorySettings();
    }

    public function isEnabled(LinkRepositorySettings|Settings|null $settings): bool
    {
        if($settings instanceof LinkRepositorySettings)
            return $settings->repo != "";

        return false;
    }
}
