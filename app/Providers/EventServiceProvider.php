<?php

namespace App\Providers;

use App\Events\Community\ConsultantProfileCreated;
use App\Events\Startup\StartupCreated;
use App\Listeners\Audit\AuditEventSubscriber;
use App\Listeners\Crm\CaptureConsultantLead;
use App\Listeners\Crm\CaptureStartupLead;
use App\Listeners\Security\SecurityAlertSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Event registration (D-027). Register in bootstrap/providers.php.
 *
 * AuditEventSubscriber wires every core security event (E-CORE-*) to the
 * append-only audit trail. SecurityAlertSubscriber raises high-sensitivity alerts
 * (R-7). Non-audit listeners (welcome email, etc.) are registered by their modules.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<int,class-string>
     */
    protected $subscribe = [
        AuditEventSubscriber::class,
        SecurityAlertSubscriber::class,
    ];

    /**
     * Module event→listener bindings (non-audit).
     *
     * @var array<class-string,array<int,class-string>>
     */
    protected $listen = [
        // ONE-WAY Community → CRM lead capture (W4b-3 / D-053).
        ConsultantProfileCreated::class => [CaptureConsultantLead::class],
        // ONE-WAY Startup Hub → CRM lead capture (D-053 / H-3).
        StartupCreated::class => [CaptureStartupLead::class],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false; // explicit registration only
    }
}
