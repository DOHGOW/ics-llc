<?php

namespace App\Listeners\Audit;

use App\Audit\AuditCategory;
use App\Audit\AuditSensitivity;
use App\Events\Billing\SubscriptionStateChanged;
use App\Events\Community\ProfileStatusChanged as CommunityProfileStatusChanged;
use App\Events\Community\ProfileVerified;
use App\Events\Content\ContentArchived;
use App\Events\Content\ContentPublished;
use App\Events\Core\AccountApproved;
use App\Events\Core\AccountDeactivated;
use App\Events\Core\AccountDeletionRequested;
use App\Events\Core\AccountLocked;
use App\Events\Core\AccountReactivated;
use App\Events\Core\AccountSuspended;
use App\Events\Core\DataExportRequested;
use App\Events\Core\PasswordChanged;
use App\Events\Core\RoleAssigned;
use App\Events\Core\RoleRevoked;
use App\Events\Core\UserLoggedIn;
use App\Events\Core\UserLoggedOut;
use App\Events\Core\UserRegistered;
use App\Events\Crm\CrmAccountDeleted;
use App\Events\Crm\CrmRecordAssigned;
use App\Events\Crm\LeadConverted;
use App\Events\Crm\LeadStageChanged;
use App\Events\Crm\OpportunityStageChanged;
use App\Events\Marketplace\ApplicationStatusChanged;
use App\Events\Marketplace\ListingReportResolved;
use App\Events\Marketplace\ListingReviewed;
use App\Events\Membership\MembershipEntitlementChanged;
use App\Events\Portal\AgreementSigned;
use App\Events\Portal\CommissionPaid;
use App\Events\Portal\CommissionRecorded;
use App\Events\Portal\DeliverableStatusChanged;
use App\Events\Portal\PartnerProfileStatusChanged;
use App\Events\Portal\ProjectStatusChanged;
use App\Events\Portal\ReferralStageChanged;
use App\Events\Portal\TicketResolved;
use App\Events\Program\EventActivity;
use App\Events\Program\ParticipationChanged;
use App\Events\Program\ProgramGovernanceChanged;
use App\Events\Startup\OwnershipChanged;
use App\Events\Startup\OwnershipTransferred;
use App\Events\Startup\StartupStatusChanged;
use App\Events\Tenant\TenantLifecycleChanged;
use App\Events\Training\AssessmentGraded;
use App\Events\Training\CertificateIssued;
use App\Events\Training\CertificateRevoked;
use App\Events\Training\CourseCompleted;
use App\Events\Training\EnrollmentCreated;
use App\Events\Training\InstructorApproved;
use App\Models\Core\User;
use App\Services\Audit\AuditService;
use Illuminate\Events\Dispatcher;

/**
 * Writes the immutable audit trail for all core security events (D-006 / D-046).
 *
 * SYNCHRONOUS by design — does NOT implement ShouldQueue. The audit trail must be
 * written reliably in-request; we never risk losing a security record to a queue
 * failure. Sensitivity (incl. "all Super Admin actions = high") is resolved inside
 * AuditService.
 */
class AuditEventSubscriber
{
    public function __construct(private readonly AuditService $audit) {}

    public function handleUserRegistered(UserRegistered $e): void
    {
        $this->audit->log('REGISTERED', 'core', AuditCategory::USER_MANAGEMENT,
            $e->user->id, $this->roleOf($e->user), User::class, $e->user->id,
            null, ['source' => $e->registrationSource], $e->ipAddress);
    }

    public function handleLoggedIn(UserLoggedIn $e): void
    {
        $this->audit->log('LOGIN', 'auth', AuditCategory::AUTHENTICATION,
            $e->user->id, $this->roleOf($e->user), User::class, $e->user->id,
            null, null, $e->ipAddress, $e->userAgent);
    }

    public function handleLoggedOut(UserLoggedOut $e): void
    {
        $this->audit->log('LOGOUT', 'auth', AuditCategory::AUTHENTICATION,
            $e->user->id, $this->roleOf($e->user), User::class, $e->user->id,
            null, null, $e->ipAddress);
    }

    public function handlePasswordChanged(PasswordChanged $e): void
    {
        $this->audit->log('PASSWORD_CHANGED', 'auth', AuditCategory::AUTHENTICATION,
            $e->user->id, $this->roleOf($e->user), User::class, $e->user->id,
            null, null, $e->ipAddress);
    }

    public function handleAccountLocked(AccountLocked $e): void
    {
        $this->audit->log('ACCOUNT_LOCKED', 'auth', AuditCategory::SECURITY_CONFIG,
            null, null, User::class, null,
            null, ['email' => $e->email, 'attempts' => $e->attempts], $e->ipAddress, $e->userAgent);
    }

    public function handleAccountDeactivated(AccountDeactivated $e): void
    {
        $this->audit->log('ACCOUNT_DEACTIVATED', 'core', AuditCategory::USER_MANAGEMENT,
            $e->actorId, $e->actorRole, User::class, $e->user->id,
            null, ['reason' => $e->reason], $e->ipAddress);
    }

    public function handleAccountApproved(AccountApproved $e): void
    {
        $this->audit->log('ACCOUNT_APPROVED', 'core', AuditCategory::USER_MANAGEMENT,
            $e->actorId, $e->actorRole, User::class, $e->user->id,
            ['status' => 'pending'], ['status' => 'active'], $e->ipAddress);
    }

    public function handleAccountSuspended(AccountSuspended $e): void
    {
        $this->audit->log('ACCOUNT_SUSPENDED', 'core', AuditCategory::USER_MANAGEMENT,
            $e->actorId, $e->actorRole, User::class, $e->user->id,
            null, ['reason' => $e->reason], $e->ipAddress);
    }

    public function handleAccountReactivated(AccountReactivated $e): void
    {
        $this->audit->log('ACCOUNT_REACTIVATED', 'core', AuditCategory::USER_MANAGEMENT,
            $e->actorId, $e->actorRole, User::class, $e->user->id,
            null, ['status' => 'active'], $e->ipAddress);
    }

    public function handleDataExportRequested(DataExportRequested $e): void
    {
        $this->audit->log('DATA_EXPORT_REQUESTED', 'core', AuditCategory::DATA_PRIVACY,
            $e->user->id, $this->roleOf($e->user), User::class, $e->user->id,
            null, null, $e->ipAddress);
    }

    public function handleAccountDeletionRequested(AccountDeletionRequested $e): void
    {
        $this->audit->log('ACCOUNT_DELETION_REQUESTED', 'core', AuditCategory::DATA_PRIVACY,
            $e->user->id, $this->roleOf($e->user), User::class, $e->user->id,
            null, null, $e->ipAddress);
    }

    public function handleRoleAssigned(RoleAssigned $e): void
    {
        $this->audit->log('ROLE_ASSIGNED', 'authorization', AuditCategory::ROLE_ASSIGNMENT,
            $e->actorId, $e->actorRole, User::class, $e->target->id,
            ['previous_role' => $e->previousRole], ['role' => $e->role], $e->ipAddress);
    }

    public function handleRoleRevoked(RoleRevoked $e): void
    {
        $this->audit->log('ROLE_REVOKED', 'authorization', AuditCategory::ROLE_ASSIGNMENT,
            $e->actorId, $e->actorRole, User::class, $e->target->id,
            ['role' => $e->role], null, $e->ipAddress);
    }

    public function handleContentPublished(ContentPublished $e): void
    {
        $actor = auth()->user() ?? optional(request())->user();
        $this->audit->log('CONTENT_PUBLISHED', $this->contentModuleOf($e->content), AuditCategory::CONTENT_MANAGEMENT,
            $actor?->id, $actor ? $this->roleOf($actor) : null,
            $e->content::class, $e->content->getKey(),
            null, ['slug' => $e->content->slug ?? null], optional(request())->ip());
    }

    public function handleContentArchived(ContentArchived $e): void
    {
        $actor = auth()->user() ?? optional(request())->user();
        $this->audit->log('CONTENT_ARCHIVED', $this->contentModuleOf($e->content), AuditCategory::CONTENT_MANAGEMENT,
            $actor?->id, $actor ? $this->roleOf($actor) : null,
            $e->content::class, $e->content->getKey(),
            null, ['slug' => $e->content->slug ?? null], optional(request())->ip());
    }

    /**
     * W3-2: derive the audit module from the content itself (cms/knowledge/research)
     * instead of hard-coding 'cms'. ContentAccessible exposes contentModule().
     */
    private function contentModuleOf(object $content): string
    {
        return method_exists($content, 'contentModule') ? $content->contentModule() : 'cms';
    }

    public function handleLeadStageChanged(LeadStageChanged $e): void
    {
        $this->audit->log('LEAD_STAGE_CHANGED', 'crm', AuditCategory::CRM_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->lead::class, $e->lead->getKey(),
            ['stage' => $e->fromStage], ['stage' => $e->toStage], optional(request())->ip());
    }

    public function handleOpportunityStageChanged(OpportunityStageChanged $e): void
    {
        $this->audit->log('OPPORTUNITY_STAGE_CHANGED', 'crm', AuditCategory::CRM_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->opportunity::class, $e->opportunity->getKey(),
            ['stage' => $e->fromStage], ['stage' => $e->toStage], optional(request())->ip());
    }

    public function handleCrmRecordAssigned(CrmRecordAssigned $e): void
    {
        $this->audit->log('CRM_RECORD_ASSIGNED', 'crm', AuditCategory::CRM_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->record::class, $e->record->getKey(),
            ['assigned_to' => $e->previousAssignee], ['assigned_to' => $e->newAssignee], optional(request())->ip());
    }

    public function handleLeadConverted(LeadConverted $e): void
    {
        $this->audit->log('LEAD_CONVERTED', 'crm', AuditCategory::CRM_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->lead::class, $e->lead->getKey(),
            null, ['opportunity_id' => $e->opportunity->getKey()], optional(request())->ip());
    }

    public function handleCrmAccountDeleted(CrmAccountDeleted $e): void
    {
        $this->audit->log('CRM_ACCOUNT_DELETED', 'crm', AuditCategory::CRM_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->account::class, $e->account->getKey(),
            ['name' => $e->account->name], null, optional(request())->ip());
    }

    // ── Wave 2 Portal events (PORTAL_MANAGEMENT, D-056) ───────────────────────
    public function handleProjectStatusChanged(ProjectStatusChanged $e): void
    {
        $this->audit->log('PROJECT_STATUS_CHANGED', 'client_portal', AuditCategory::PORTAL_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->project::class, $e->project->getKey(),
            ['status' => $e->fromStatus], ['status' => $e->toStatus], optional(request())->ip());
    }

    public function handleDeliverableStatusChanged(DeliverableStatusChanged $e): void
    {
        $this->audit->log('DELIVERABLE_STATUS_CHANGED', 'client_portal', AuditCategory::PORTAL_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->deliverable::class, $e->deliverable->getKey(),
            ['status' => $e->fromStatus], ['status' => $e->toStatus], optional(request())->ip());
    }

    public function handleTicketResolved(TicketResolved $e): void
    {
        $this->audit->log('TICKET_RESOLVED', 'client_portal', AuditCategory::PORTAL_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->ticket::class, $e->ticket->getKey(),
            null, ['status' => $e->ticket->status], optional(request())->ip());
    }

    public function handlePartnerProfileStatusChanged(PartnerProfileStatusChanged $e): void
    {
        // Suspension/termination are HIGH-sensitivity (D-056).
        $high = in_array($e->toStatus, ['suspended', 'terminated'], true) ? AuditSensitivity::HIGH : null;

        $this->audit->log('PARTNER_PROFILE_STATUS_CHANGED', 'partner_portal', AuditCategory::PORTAL_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->profile::class, $e->profile->getKey(),
            ['status' => $e->fromStatus], ['status' => $e->toStatus], optional(request())->ip(), null, null, $high);
    }

    public function handleReferralStageChanged(ReferralStageChanged $e): void
    {
        $this->audit->log('REFERRAL_STAGE_CHANGED', 'partner_portal', AuditCategory::PORTAL_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->referral::class, $e->referral->getKey(),
            ['stage' => $e->fromStage], ['stage' => $e->toStage], optional(request())->ip());
    }

    public function handleCommissionRecorded(CommissionRecorded $e): void
    {
        $this->audit->log('COMMISSION_RECORDED', 'partner_portal', AuditCategory::PORTAL_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->referral::class, $e->referral->getKey(),
            null, ['amount' => $e->amount, 'currency' => $e->currency], optional(request())->ip(),
            null, null, AuditSensitivity::HIGH); // D-056 financial = HIGH
    }

    public function handleCommissionPaid(CommissionPaid $e): void
    {
        $this->audit->log('COMMISSION_PAID', 'partner_portal', AuditCategory::PORTAL_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->referral::class, $e->referral->getKey(),
            null, ['paid_at' => optional($e->referral->commission_paid_at)->toIso8601String()],
            optional(request())->ip(), null, null, AuditSensitivity::HIGH);
    }

    public function handleAgreementSigned(AgreementSigned $e): void
    {
        $this->audit->log('AGREEMENT_SIGNED', 'partner_portal', AuditCategory::PORTAL_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->agreement::class, $e->agreement->getKey(),
            null, ['title' => $e->agreement->title], optional(request())->ip(),
            null, null, AuditSensitivity::HIGH); // D-056 agreement = HIGH
    }

    // ── Wave 4a Training events (TRAINING_MANAGEMENT, D-058) ──────────────────
    public function handleEnrollmentCreated(EnrollmentCreated $e): void
    {
        $this->audit->log('ENROLLMENT_CREATED', 'training', AuditCategory::TRAINING_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->enrollment::class, $e->enrollment->getKey(),
            null, ['course_id' => $e->enrollment->course_id], optional(request())->ip());
    }

    public function handleCourseCompleted(CourseCompleted $e): void
    {
        $this->audit->log('COURSE_COMPLETED', 'training', AuditCategory::TRAINING_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->enrollment::class, $e->enrollment->getKey(),
            null, ['course_id' => $e->enrollment->course_id], optional(request())->ip());
    }

    public function handleCertificateIssued(CertificateIssued $e): void
    {
        // D-058: certificate issuance is HIGH-sensitivity (credential integrity).
        $this->audit->log('CERTIFICATE_ISSUED', 'training', AuditCategory::TRAINING_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->certificate::class, $e->certificate->getKey(),
            null, ['number' => $e->certificate->certificate_number], optional(request())->ip(),
            null, null, AuditSensitivity::HIGH);
    }

    public function handleCertificateRevoked(CertificateRevoked $e): void
    {
        $this->audit->log('CERTIFICATE_REVOKED', 'training', AuditCategory::TRAINING_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->certificate::class, $e->certificate->getKey(),
            null, ['number' => $e->certificate->certificate_number, 'reason' => $e->reason],
            optional(request())->ip(), null, null, AuditSensitivity::HIGH);
    }

    public function handleAssessmentGraded(AssessmentGraded $e): void
    {
        $this->audit->log('ASSESSMENT_GRADED', 'training', AuditCategory::TRAINING_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->submission::class, $e->submission->getKey(),
            null, ['score' => $e->submission->score, 'passed' => $e->submission->passed], optional(request())->ip());
    }

    public function handleInstructorApproved(InstructorApproved $e): void
    {
        $this->audit->log('INSTRUCTOR_APPROVED', 'training', AuditCategory::TRAINING_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->instructor::class, $e->instructor->getKey(),
            null, ['user_id' => $e->instructor->user_id], optional(request())->ip());
    }

    // ── Wave 4b Community governance events (COMMUNITY_MANAGEMENT, D-058) ──────
    public function handleProfileVerified(ProfileVerified $e): void
    {
        $this->audit->log('PROFILE_VERIFIED', 'community', AuditCategory::COMMUNITY_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->profile::class, $e->profile->getKey(),
            null, ['profile_type' => $e->profile->profile_type], optional(request())->ip());
    }

    public function handleCommunityProfileStatusChanged(CommunityProfileStatusChanged $e): void
    {
        // Suspension/hiding are moderation — HIGH when removing visibility.
        $high = in_array($e->toStatus, ['suspended', 'hidden'], true) ? AuditSensitivity::HIGH : null;

        $this->audit->log('COMMUNITY_PROFILE_STATUS_CHANGED', 'community', AuditCategory::COMMUNITY_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->profile::class, $e->profile->getKey(),
            ['status' => $e->fromStatus], ['status' => $e->toStatus], optional(request())->ip(), null, null, $high);
    }

    // ── Wave 4c Marketplace events (MARKETPLACE_MANAGEMENT, D-058/D-060) ───────
    public function handleListingReviewed(ListingReviewed $e): void
    {
        // Removal of a published listing is HIGH (moderation against live content).
        $high = $e->action === 'removed' ? AuditSensitivity::HIGH : null;

        $this->audit->log('LISTING_'.strtoupper($e->action), 'marketplace', AuditCategory::MARKETPLACE_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->listing::class, $e->listing->getKey(),
            null, ['action' => $e->action, 'notes' => $e->notes], optional(request())->ip(), null, null, $high);
    }

    public function handleApplicationStatusChanged(ApplicationStatusChanged $e): void
    {
        $this->audit->log('APPLICATION_STATUS_CHANGED', 'marketplace', AuditCategory::MARKETPLACE_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->application::class, $e->application->getKey(),
            ['status' => $e->fromStatus], ['status' => $e->toStatus], optional(request())->ip());
    }

    public function handleListingReportResolved(ListingReportResolved $e): void
    {
        $this->audit->log('LISTING_REPORT_RESOLVED', 'marketplace', AuditCategory::MARKETPLACE_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->report::class, $e->report->getKey(),
            null, ['resolution' => $e->resolution, 'listing_id' => $e->report->listing_id], optional(request())->ip());
    }

    // ── Wave 5A Startup Hub events (STARTUP_MANAGEMENT, D-062/D-064) ───────────
    public function handleOwnershipTransferred(OwnershipTransferred $e): void
    {
        // H-2/D-064: ownership transfer is HIGH.
        $this->audit->log('STARTUP_OWNERSHIP_TRANSFERRED', 'startup', AuditCategory::STARTUP_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->startup::class, $e->startup->getKey(),
            ['founder_id' => $e->fromFounderId], ['founder_id' => $e->toFounderId],
            optional(request())->ip(), null, null, AuditSensitivity::HIGH);
    }

    public function handleOwnershipChanged(OwnershipChanged $e): void
    {
        // D-064: founder ownership changes HIGH. Amounts are NOT recorded (C-1 confidentiality).
        $this->audit->log('STARTUP_OWNERSHIP_CHANGED', 'startup', AuditCategory::STARTUP_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->startup::class, $e->startup->getKey(),
            null, null, optional(request())->ip(), null, null, AuditSensitivity::HIGH);
    }

    public function handleStartupStatusChanged(StartupStatusChanged $e): void
    {
        // verified/suspended/reactivated = HIGH; graduated = normal (audited).
        $high = in_array($e->action, ['verified', 'suspended', 'reactivated'], true) ? AuditSensitivity::HIGH : null;

        $this->audit->log('STARTUP_'.strtoupper($e->action), 'startup', AuditCategory::STARTUP_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->startup::class, $e->startup->getKey(),
            null, ['action' => $e->action], optional(request())->ip(), null, null, $high);
    }

    // ── Wave 5B Program events (PROGRAM_MANAGEMENT, D-066) ─────────────────────
    public function handleParticipationChanged(ParticipationChanged $e): void
    {
        // D-066: forced removal + graduation reversal are HIGH.
        $high = in_array($e->action, ['removed', 'graduation_reversed'], true) ? AuditSensitivity::HIGH : null;

        $this->audit->log('PROGRAM_PARTICIPATION_'.strtoupper($e->action), 'program', AuditCategory::PROGRAM_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->enrollment::class, $e->enrollment->getKey(),
            null, ['action' => $e->action, 'program_type' => $e->programType, 'reason' => $e->reason],
            optional(request())->ip(), null, null, $high);
    }

    public function handleProgramGovernanceChanged(ProgramGovernanceChanged $e): void
    {
        // D-066: suspend/reinstate/terminate are HIGH; closure/archival audited normal.
        $high = in_array($e->action, ['program_suspended', 'program_reinstated', 'program_terminated'], true)
            ? AuditSensitivity::HIGH : null;

        $this->audit->log(strtoupper($e->action), 'program', AuditCategory::PROGRAM_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->program::class, $e->program->getKey(),
            null, ['action' => $e->action, 'type' => $e->program->type, 'cohort_id' => $e->cohortId],
            optional(request())->ip(), null, null, $high);
    }

    public function handleSubscriptionStateChanged(SubscriptionStateChanged $e): void
    {
        // D-085: HIGH for override/refund/chargeback/admin-cancel/reactivate/etc. (carrier-set).
        $this->audit->log('SUBSCRIPTION_'.strtoupper($e->action), 'billing', AuditCategory::BILLING_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->subscription::class, $e->subscription->getKey(),
            null, ['action' => $e->action, 'status' => $e->subscription->status, 'plan_id' => $e->subscription->plan_id],
            optional(request())->ip(), null, $e->subscription->tenant_id, $e->high ? AuditSensitivity::HIGH : null);
    }

    public function handleMembershipEntitlementChanged(MembershipEntitlementChanged $e): void
    {
        // D-081: MANUAL entitlement grant/removal is HIGH (carrier-set). Membership-specific overlay
        // on the billing stream (MEMBERSHIP_MANAGEMENT), carrying the subscription's tenant.
        $this->audit->log('MEMBERSHIP_'.strtoupper($e->action), 'membership', AuditCategory::MEMBERSHIP_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->subscription::class, $e->subscription->getKey(),
            null, ['action' => $e->action, 'plan_id' => $e->subscription->plan_id, 'user_id' => $e->subscription->user_id],
            optional(request())->ip(), null, $e->subscription->tenant_id, $e->high ? AuditSensitivity::HIGH : null);
    }

    public function handleTenantLifecycleChanged(TenantLifecycleChanged $e): void
    {
        // FT: ALL tenant mutations are HIGH-sensitivity (approved).
        $this->audit->log('TENANT_'.strtoupper($e->action), 'tenant', AuditCategory::TENANT_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->tenant::class, $e->tenant->getKey(),
            null, ['action' => $e->action, 'slug' => $e->tenant->slug], optional(request())->ip(),
            null, $e->tenant->getKey(), AuditSensitivity::HIGH);
    }

    public function handleEventActivity(EventActivity $e): void
    {
        // D-068: overrides / showcase-access revocation are HIGH; others normal. Reuses
        // PROGRAM_MANAGEMENT (no new category) with the event type as context.
        $high = in_array($e->action, ['score_override', 'readiness_override', 'showcase_access_revoked'], true)
            ? AuditSensitivity::HIGH : null;

        $this->audit->log('PROGRAM_EVENT_'.strtoupper($e->action), 'program', AuditCategory::PROGRAM_MANAGEMENT,
            $e->actorId, $e->actorRole, $e->event::class, $e->event->getKey(),
            null, ['action' => $e->action, 'event_type' => $e->event->type, 'program_type' => $e->programType],
            optional(request())->ip(), null, null, $high);
    }

    private function roleOf(User $user): ?string
    {
        return $user->getRoleNames()->first();
    }

    /**
     * @return array<class-string,string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            UserRegistered::class => 'handleUserRegistered',
            UserLoggedIn::class => 'handleLoggedIn',
            UserLoggedOut::class => 'handleLoggedOut',
            PasswordChanged::class => 'handlePasswordChanged',
            AccountLocked::class => 'handleAccountLocked',
            AccountApproved::class => 'handleAccountApproved',
            AccountSuspended::class => 'handleAccountSuspended',
            AccountReactivated::class => 'handleAccountReactivated',
            AccountDeactivated::class => 'handleAccountDeactivated',
            DataExportRequested::class => 'handleDataExportRequested',
            AccountDeletionRequested::class => 'handleAccountDeletionRequested',
            RoleAssigned::class => 'handleRoleAssigned',
            RoleRevoked::class => 'handleRoleRevoked',
            ContentPublished::class => 'handleContentPublished',
            ContentArchived::class => 'handleContentArchived',
            LeadStageChanged::class => 'handleLeadStageChanged',
            OpportunityStageChanged::class => 'handleOpportunityStageChanged',
            CrmRecordAssigned::class => 'handleCrmRecordAssigned',
            LeadConverted::class => 'handleLeadConverted',
            CrmAccountDeleted::class => 'handleCrmAccountDeleted',
            ProjectStatusChanged::class => 'handleProjectStatusChanged',
            DeliverableStatusChanged::class => 'handleDeliverableStatusChanged',
            TicketResolved::class => 'handleTicketResolved',
            PartnerProfileStatusChanged::class => 'handlePartnerProfileStatusChanged',
            ReferralStageChanged::class => 'handleReferralStageChanged',
            CommissionRecorded::class => 'handleCommissionRecorded',
            CommissionPaid::class => 'handleCommissionPaid',
            AgreementSigned::class => 'handleAgreementSigned',
            EnrollmentCreated::class => 'handleEnrollmentCreated',
            CourseCompleted::class => 'handleCourseCompleted',
            CertificateIssued::class => 'handleCertificateIssued',
            CertificateRevoked::class => 'handleCertificateRevoked',
            AssessmentGraded::class => 'handleAssessmentGraded',
            InstructorApproved::class => 'handleInstructorApproved',
            ProfileVerified::class => 'handleProfileVerified',
            CommunityProfileStatusChanged::class => 'handleCommunityProfileStatusChanged',
            ListingReviewed::class => 'handleListingReviewed',
            ApplicationStatusChanged::class => 'handleApplicationStatusChanged',
            ListingReportResolved::class => 'handleListingReportResolved',
            OwnershipTransferred::class => 'handleOwnershipTransferred',
            OwnershipChanged::class => 'handleOwnershipChanged',
            StartupStatusChanged::class => 'handleStartupStatusChanged',
            ParticipationChanged::class => 'handleParticipationChanged',
            ProgramGovernanceChanged::class => 'handleProgramGovernanceChanged',
            EventActivity::class => 'handleEventActivity',
            TenantLifecycleChanged::class => 'handleTenantLifecycleChanged',
            SubscriptionStateChanged::class => 'handleSubscriptionStateChanged',
            MembershipEntitlementChanged::class => 'handleMembershipEntitlementChanged',
        ];
    }
}
