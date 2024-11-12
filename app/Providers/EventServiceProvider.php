<?php

namespace App\Providers;

use App\Events\ProjectCreated;
use App\Listeners\GitLab\Project\DisableForking;
use App\Listeners\GitLab\Project\RegisterWebhook;
use App\Listeners\GitLab\Project\UnprotectDefaultBranch;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ProjectCreated::class => [
            DisableForking::class,
            RegisterWebhook::class,
            UnprotectDefaultBranch::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
    }
}
