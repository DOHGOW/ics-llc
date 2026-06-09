<?php

namespace Database\Seeders;

use App\Authorization\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role → Permission mapping (D-021 / D-044). Code encoding of PERMISSION_MATRIX.md.
 *
 * - Platform Super Admin is intentionally given NO explicit permissions: it is
 *   granted everything by Gate::before (AuthServiceProvider). This also means new
 *   permissions are covered automatically.
 * - Platform Admin receives ALL permissions EXCEPT the Super-Admin-only set.
 * - All other roles are explicit and least-privilege.
 *
 * A conformance test (T-10.1) asserts this mapping equals PERMISSION_MATRIX; on any
 * discrepancy, PERMISSION_MATRIX governs and is reconciled here (Governance §7).
 */
class RolePermissionSeeder extends Seeder
{
    /** Permissions reserved to Super Admin only (excluded from Platform Admin). */
    private const SUPER_ONLY = [
        'platform.config.update',
        'platform.tenants.manage',
        'research.tier5.read',
        'ai.usage.manage',
    ];

    public function run(): void
    {
        $self = [
            'auth.login', 'auth.logout',
            'profile.read.own', 'profile.update.own', 'profile.data.export',
            'profile.delete.own', 'profile.mfa.manage',
            'notifications.read.own', 'notifications.preferences',
        ];

        $publicContent = [
            'cms.read.public',
            'knowledge.tier1.read', 'knowledge.tier2.read', 'knowledge.search.ai',
            'knowledge.downloads.access', 'knowledge.bookmarks.manage.own', 'knowledge.ratings.create',
            'research.tier1.read', 'research.tier2.read', 'research.downloads.access', 'research.citations.generate',
            'ai.website.assistant',
        ];

        $community = [
            'community.directory.read', 'community.profile.create.own',
            'community.profile.update.own', 'community.profile.delete.own', 'community.skills.endorse',
        ];

        $marketplaceUser = [
            'marketplace.listings.read.published',
            'marketplace.applications.create', 'marketplace.applications.read.own',
        ];

        $learner = [
            'training.courses.read.catalogue', 'training.certificates.verify',
            'training.certificates.read.own', 'training.enrollments.create',
            'training.enrollments.read.own', 'training.lessons.access.enrolled',
            'training.assessments.submit', 'ai.training.recommend', 'ai.knowledge.search',
        ];

        $authBase = array_merge($self, $publicContent, $community, $marketplaceUser);

        $map = [];

        // Super Admin — none (Gate::before grants all).
        $map[Roles::SUPER_ADMIN] = [];

        // Platform Admin — everything except SUPER_ONLY.
        $map[Roles::PLATFORM_ADMIN] = array_values(array_diff(
            PermissionSeeder::catalogue(),
            self::SUPER_ONLY
        ));

        // ICS Staff — CRM.
        $map[Roles::ICS_CRM] = array_merge($authBase, $learner, [
            'knowledge.tier3.read', 'knowledge.tier4.read', 'knowledge.tier5.read',
            'research.tier3.read', 'research.tier4.read',
            'crm.accounts.create', 'crm.accounts.read.all', 'crm.accounts.read.own', 'crm.accounts.update', 'crm.accounts.delete',
            'crm.contacts.create', 'crm.contacts.read.all', 'crm.contacts.read.own', 'crm.contacts.update', 'crm.contacts.delete',
            'crm.leads.create', 'crm.leads.read.all', 'crm.leads.read.own', 'crm.leads.update', 'crm.leads.delete', 'crm.leads.qualify.ai',
            'crm.opportunities.create', 'crm.opportunities.read.all', 'crm.opportunities.read.own', 'crm.opportunities.update', 'crm.opportunities.delete',
            'crm.proposals.create', 'crm.proposals.generate.ai', 'crm.proposals.read', 'crm.proposals.update',
            'crm.contracts.create', 'crm.contracts.read.all', 'crm.contracts.update', 'crm.contracts.delete',
            'crm.activities.create', 'crm.activities.read.all', 'crm.activities.read.own', 'crm.reports.view', 'crm.reports.export',
            'client.projects.read.own', 'client.projects.manage', 'client.milestones.read', 'client.milestones.manage',
            'client.deliverables.read.own', 'client.deliverables.manage', 'client.deliverables.download',
            'client.invoices.read.own', 'client.invoices.download',
            'client.tickets.create', 'client.tickets.read.own', 'client.tickets.reply', 'client.tickets.manage',
            'marketplace.listings.create',
            'partner.profiles.read.all', 'partner.profiles.approve', 'partner.referrals.read.all', 'partner.agreements.manage', 'partner.reports.view',
            'startup.profiles.read.all', 'startup.milestones.create', 'startup.milestones.update', 'startup.milestones.delete',
            'startup.mentors.manage', 'startup.programs.manage', 'startup.reports.view', 'startup.assessment.ai',
            'billing.invoices.create', 'billing.invoices.read.all', 'billing.payments.read.all',
            'analytics.crm.reports', 'analytics.partner.reports',
            'ai.crm.lead.qualify', 'ai.crm.proposal.generate', 'ai.digital.maturity',
            'ai.startup.readiness', 'ai.research.assist', 'ai.marketplace.match',
        ]);

        // ICS Staff — Training.
        $map[Roles::ICS_TRAINING] = array_merge($authBase, $learner, [
            'knowledge.tier3.read', 'knowledge.tier4.read', 'knowledge.tier5.read',
            'research.tier3.read', 'research.tier4.read',
            'training.courses.create', 'training.courses.read.all', 'training.courses.update',
            'training.courses.delete', 'training.courses.publish',
            'training.enrollments.read.all', 'training.assessments.grade',
            'training.certificates.issue', 'training.instructors.manage', 'training.reports.view',
            'knowledge.articles.create', 'knowledge.articles.publish',
            'analytics.training.reports',
            'ai.content.draft', 'ai.research.assist',
        ]);

        // ICS Staff — Content.
        $map[Roles::ICS_CONTENT] = array_merge($authBase, [
            'knowledge.tier3.read', 'knowledge.tier4.read', 'knowledge.tier5.read',
            'research.tier3.read', 'research.tier4.read',
            'cms.pages.create', 'cms.pages.read', 'cms.pages.update', 'cms.pages.delete', 'cms.pages.publish',
            'cms.articles.create', 'cms.articles.update', 'cms.articles.publish', 'cms.articles.delete',
            'cms.media.upload', 'cms.media.delete', 'cms.menu.manage',
            'knowledge.articles.create', 'knowledge.articles.update.own', 'knowledge.articles.publish',
            'knowledge.articles.delete', 'knowledge.reports.view',
            'research.publications.create', 'research.publications.update', 'research.publications.publish',
            'research.reports.view',
            'analytics.content.reports',
            'ai.content.draft', 'ai.research.assist',
        ]);

        // Client Admin.
        $map[Roles::CLIENT_ADMIN] = array_merge($authBase, $learner, [
            'knowledge.tier3.read',
            'client.projects.read.own', 'client.milestones.read', 'client.deliverables.read.own',
            'client.deliverables.download', 'client.invoices.read.own', 'client.invoices.download',
            'client.tickets.create', 'client.tickets.read.own', 'client.tickets.reply', 'client.users.manage.own',
            'billing.invoices.read.own', 'billing.payments.read.own',
            'ai.digital.maturity',
        ]);

        // Partner Admin.
        $map[Roles::PARTNER_ADMIN] = array_merge($authBase, $learner, [
            'knowledge.tier4.read', 'research.tier3.read',
            'partner.profiles.read.own', 'partner.profiles.update',
            'partner.referrals.create', 'partner.referrals.read.own', 'partner.agreements.read.own',
            'partner.reports.view',
            'marketplace.listings.create', 'marketplace.listings.update.own', 'marketplace.listings.delete.own',
            'billing.invoices.read.own', 'billing.payments.read.own',
        ]);

        // Government Agency Representative (D-044: Tier-4 knowledge REMOVED).
        $map[Roles::GOV_REP] = array_merge($authBase, $learner, [
            'research.tier3.read',
            'marketplace.listings.create', 'marketplace.listings.update.own', 'marketplace.listings.delete.own',
        ]);

        // Vendor.
        $map[Roles::VENDOR] = array_merge($self, $publicContent, $community, $marketplaceUser, [
            'marketplace.listings.create', 'marketplace.listings.update.own', 'marketplace.listings.delete.own',
            'billing.invoices.read.own', 'billing.payments.read.own',
        ]);

        // Startup Founder.
        $map[Roles::STARTUP_FOUNDER] = array_merge($authBase, $learner, [
            'startup.profiles.create', 'startup.profiles.read.own', 'startup.profiles.update.own',
            'startup.milestones.create', 'startup.milestones.update', 'startup.milestones.delete',
            'startup.team.manage', 'startup.reports.view', 'startup.assessment.ai',
            'ai.startup.readiness', 'ai.marketplace.match',
        ]);

        // Startup Team Member.
        $map[Roles::STARTUP_MEMBER] = array_merge($authBase, $learner, [
            'startup.profiles.read.own', 'startup.milestones.create',
        ]);

        // Trainer / Instructor (no enrolment; teaches).
        $map[Roles::TRAINER] = array_merge($self, $publicContent, $community, [
            'training.courses.create', 'training.courses.read.all', 'training.courses.update',
            'training.assessments.grade', 'training.certificates.read.own', 'training.certificates.verify',
            'knowledge.articles.create',
            'ai.content.draft', 'ai.training.recommend',
        ]);

        // Student / Trainee.
        $map[Roles::STUDENT] = array_merge($authBase, $learner, [
            'research.tier2.read',
        ]);

        $this->apply($map);

        App::make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @param array<string,array<int,string>> $map */
    private function apply(array $map): void
    {
        foreach ($map as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');

            // Resolve to existing permissions (guards against typos vs the catalogue).
            $resolved = Permission::whereIn('name', array_unique($permissions))
                ->where('guard_name', 'web')
                ->get();

            $role->syncPermissions($resolved);
        }
    }
}
