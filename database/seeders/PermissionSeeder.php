<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission catalogue (D-021 / D-044).
 *
 * Canonical naming: {module}.{resource}.{action}  (D-044). This file is the code
 * encoding of PERMISSION_MATRIX.md; on any discrepancy, PERMISSION_MATRIX governs
 * and the difference is reconciled here (Governance §7).
 */
class PermissionSeeder extends Seeder
{
    /** @return array<int,string> */
    public static function catalogue(): array
    {
        return array_merge(
            // 1 — Platform Administration
            [
                'platform.config.read', 'platform.config.update',
                'platform.users.create', 'platform.users.read.all', 'platform.users.update.all',
                'platform.users.deactivate', 'platform.users.delete',
                'platform.roles.manage', 'platform.audit.read', 'platform.audit.export',
                'platform.tenants.manage',
            ],
            // 2 — Authentication & Profile (self-service)
            [
                'auth.login', 'auth.logout',
                'profile.read.own', 'profile.update.own', 'profile.data.export',
                'profile.delete.own', 'profile.mfa.manage',
                'notifications.read.own', 'notifications.preferences',
            ],
            // 3 — Corporate Website / CMS
            [
                'cms.pages.create', 'cms.pages.read', 'cms.pages.update', 'cms.pages.delete', 'cms.pages.publish',
                'cms.articles.create', 'cms.articles.update', 'cms.articles.publish', 'cms.articles.delete',
                'cms.media.upload', 'cms.media.delete', 'cms.menu.manage', 'cms.read.public',
            ],
            // 4 — CRM (D-053: assignment-scoped read.own vs full-pipeline read.all, W1d-4)
            [
                'crm.accounts.create', 'crm.accounts.read.all', 'crm.accounts.read.own', 'crm.accounts.update', 'crm.accounts.delete',
                'crm.contacts.create', 'crm.contacts.read.all', 'crm.contacts.read.own', 'crm.contacts.update', 'crm.contacts.delete',
                'crm.leads.create', 'crm.leads.read.all', 'crm.leads.read.own', 'crm.leads.update', 'crm.leads.delete', 'crm.leads.qualify.ai',
                'crm.opportunities.create', 'crm.opportunities.read.all', 'crm.opportunities.read.own', 'crm.opportunities.update', 'crm.opportunities.delete',
                'crm.proposals.create', 'crm.proposals.generate.ai', 'crm.proposals.read', 'crm.proposals.update',
                'crm.contracts.create', 'crm.contracts.read.all', 'crm.contracts.update', 'crm.contracts.delete',
                'crm.activities.create', 'crm.activities.read.all', 'crm.activities.read.own', 'crm.reports.view', 'crm.reports.export',
            ],
            // 5 — Client Portal
            [
                'client.projects.read.own', 'client.projects.manage',
                'client.milestones.read', 'client.milestones.manage',
                'client.deliverables.read.own', 'client.deliverables.manage', 'client.deliverables.download',
                'client.invoices.read.own', 'client.invoices.download',
                'client.tickets.create', 'client.tickets.read.own', 'client.tickets.reply', 'client.tickets.manage',
                'client.users.manage.own',
            ],
            // 6 — Training Institute
            [
                'training.courses.create', 'training.courses.read.all', 'training.courses.update',
                'training.courses.delete', 'training.courses.publish', 'training.courses.read.catalogue',
                'training.enrollments.create', 'training.enrollments.read.all', 'training.enrollments.read.own',
                'training.lessons.access.enrolled', 'training.assessments.submit', 'training.assessments.grade',
                'training.certificates.issue', 'training.certificates.read.own', 'training.certificates.verify',
                'training.instructors.manage', 'training.reports.view',
            ],
            // 7 — Opportunity Marketplace
            [
                'marketplace.listings.create', 'marketplace.listings.read.all', 'marketplace.listings.read.published',
                'marketplace.listings.update.own', 'marketplace.listings.delete.own',
                'marketplace.listings.approve', 'marketplace.listings.reject',
                'marketplace.applications.create', 'marketplace.applications.read.own',
                'marketplace.applications.read.all', 'marketplace.reports.view',
            ],
            // 8 — Partner Portal
            [
                'partner.profiles.create', 'partner.profiles.read.all', 'partner.profiles.read.own',
                'partner.profiles.update', 'partner.profiles.approve',
                'partner.referrals.create', 'partner.referrals.read.all', 'partner.referrals.read.own',
                'partner.agreements.manage', 'partner.agreements.read.own',
                'partner.tiers.manage', 'partner.reports.view',
            ],
            // 9 — Startup Hub
            [
                'startup.profiles.create', 'startup.profiles.read.all', 'startup.profiles.read.own',
                'startup.profiles.update.own', 'startup.profiles.delete',
                'startup.milestones.create', 'startup.milestones.update', 'startup.milestones.delete',
                'startup.team.manage', 'startup.mentors.manage', 'startup.programs.manage',
                'startup.reports.view', 'startup.assessment.ai',
            ],
            // 10 — Knowledge Center (tiered, D-036)
            [
                'knowledge.tier1.read', 'knowledge.tier2.read', 'knowledge.tier3.read',
                'knowledge.tier4.read', 'knowledge.tier5.read',
                'knowledge.articles.create', 'knowledge.articles.update.own', 'knowledge.articles.publish',
                'knowledge.articles.delete', 'knowledge.bookmarks.manage.own', 'knowledge.ratings.create',
                'knowledge.downloads.access', 'knowledge.search.ai', 'knowledge.reports.view',
            ],
            // 11 — Research Center (tiered, D-034)
            [
                'research.tier1.read', 'research.tier2.read', 'research.tier3.read',
                'research.tier4.read', 'research.tier5.read',
                'research.publications.create', 'research.publications.update', 'research.publications.publish',
                'research.publications.delete', 'research.downloads.access', 'research.citations.generate',
                'research.reports.view',
            ],
            // 12 — AI Services (D-029)
            [
                'ai.website.assistant', 'ai.crm.lead.qualify', 'ai.crm.proposal.generate',
                'ai.training.recommend', 'ai.knowledge.search', 'ai.research.assist',
                'ai.marketplace.match', 'ai.startup.readiness', 'ai.digital.maturity',
                'ai.content.draft', 'ai.usage.view', 'ai.usage.manage',
            ],
            // 13 — Community Module (D-035)
            [
                'community.directory.read', 'community.profile.create.own', 'community.profile.update.own',
                'community.profile.delete.own', 'community.profile.verify', 'community.profile.suspend',
                'community.skills.endorse', 'community.profile.read.all',
            ],
            // 14 — Billing & Subscriptions (D-031)
            [
                'billing.plans.manage', 'billing.invoices.create', 'billing.invoices.read.all',
                'billing.invoices.read.own', 'billing.invoices.download',
                'billing.payments.read.all', 'billing.payments.read.own',
                'billing.subscriptions.manage', 'billing.subscriptions.read.own', 'billing.subscriptions.cancel.own',
                'billing.reports.view', 'billing.reports.export',
            ],
            // 15 — Analytics & Data Warehouse (D-025/D-032)
            [
                'analytics.executive.dashboard', 'analytics.crm.reports', 'analytics.training.reports',
                'analytics.marketplace.reports', 'analytics.partner.reports', 'analytics.content.reports',
                'analytics.finance.reports', 'analytics.warehouse.read', 'analytics.export',
            ],
        );
    }

    public function run(): void
    {
        foreach (self::catalogue() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        App::make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
