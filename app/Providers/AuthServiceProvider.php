<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\Observation;
use App\Policies\AssessmentPolicy;
use App\Policies\ObservationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Assessment::class => AssessmentPolicy::class,
        Observation::class => ObservationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
