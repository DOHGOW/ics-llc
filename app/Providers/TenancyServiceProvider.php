<?php

namespace App\Providers;

use App\Authorization\Scopes\TenantScope;
use App\Models\Client\ClientProject;
use App\Models\Client\Ticket;
use App\Models\Community\CommunityProfile;
use App\Models\Content\Article;
use App\Models\Content\ContentEngagementEvent;
use App\Models\Content\Media;
use App\Models\Content\Page;
use App\Models\Crm\Account;
use App\Models\Crm\Activity;
use App\Models\Crm\Contact;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Knowledge\KnowledgeCategory;
use App\Models\Knowledge\KnowledgeResource;
use App\Models\Marketplace\ListingReport;
use App\Models\Marketplace\MarketplaceApplication;
use App\Models\Marketplace\MarketplaceListing;
use App\Models\Partner\PartnerAgreement;
use App\Models\Partner\PartnerProfile;
use App\Models\Partner\PartnerReferral;
use App\Models\Research\ResearchAuthor;
use App\Models\Research\ResearchCategory;
use App\Models\Research\ResearchPublication;
use App\Models\Startup\ProgramCohort;
use App\Models\Startup\ProgramEvent;
use App\Models\Startup\Startup;
use App\Models\Startup\StartupProgram;
use App\Models\Training\Certificate;
use App\Models\Training\Enrollment;
use App\Models\Training\Instructor;
use App\Models\Training\Lesson;
use App\Models\Training\TrainingCourse;
use App\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

/**
 * TenantScope activation (D-076/D-077). CENTRALIZED + ADDITIVE: applies TenantScope (the global
 * tenant filter) + tenant_id create-stamping to the registered tenant-scoped PARENT models —
 * WITHOUT editing the models or touching any existing access-control family. This $tenantScoped
 * array is the single auditable list of what is tenant-scoped (finding F parents).
 *
 * Composition: TenantScope sits ABOVE AccountScope (tenant > account > user, D-050 #4). Children
 * inherit tenancy via their AccountScope/parent (W2-1) and are NOT listed here.
 *
 * Deliberate exclusions (NOT auto-scoped): core_users (authentication queries run before the tenant
 * is resolved — auto-scoping would fail-close login), core_audit_logs (append-only forensic record;
 * reads scoped explicitly), core_tenants (it IS the tenant). Their tenancy is enforced explicitly.
 *
 * Register in bootstrap/providers.php.
 *
 * @var array<int,class-string>
 */
class TenancyServiceProvider extends ServiceProvider
{
    private array $tenantScoped = [
        Page::class,
        Article::class,
        Media::class,
        ContentEngagementEvent::class,
        Account::class,
        Contact::class,
        Lead::class,
        Opportunity::class,
        Activity::class,
        ClientProject::class,
        Ticket::class,
        PartnerProfile::class,
        PartnerReferral::class,
        PartnerAgreement::class,
        KnowledgeCategory::class,
        KnowledgeArticle::class,
        KnowledgeResource::class,
        ResearchCategory::class,
        ResearchAuthor::class,
        ResearchPublication::class,
        Instructor::class,
        TrainingCourse::class,
        Lesson::class,
        Enrollment::class,
        Certificate::class,
        CommunityProfile::class,
        MarketplaceListing::class,
        MarketplaceApplication::class,
        ListingReport::class,
        Startup::class,
        StartupProgram::class,
        ProgramCohort::class,
        ProgramEvent::class,
    ];

    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        foreach ($this->tenantScoped as $modelClass) {
            $modelClass::addGlobalScope(new TenantScope);

            $modelClass::creating(function ($model) {
                if ($model->tenant_id === null) {
                    $model->tenant_id = app(TenantContext::class)->id()
                        ?? (int) config('ics.tenancy.default_tenant_id', 1);
                }
            });
        }
    }
}
