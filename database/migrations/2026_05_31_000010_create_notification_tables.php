<?php

/*
|--------------------------------------------------------------------------
| Migration: create_notification_tables        (Task T-3.7)
|--------------------------------------------------------------------------
| Purpose:       Notification infrastructure (D-022):
|                - notifications: Laravel in-app/database notifications.
|                - notify_preferences: per-user channel toggles.
|                - notify_push_subscriptions: Web Push (PWA) endpoints.
| Decision IDs:  D-022 (multi-channel notifications), D-005 (PWA push).
| Security:      `notifications.data` may contain PII — access scoped to the
|                owner. Push keys (`p256dh`, `auth`) are sensitive credentials;
|                protect and never expose. WhatsApp channel defaults OFF
|                (cost control, COST-03).
| Dependencies:  notify_preferences.user_id & notify_push_subscriptions.user_id
|                -> core_users (cascade on delete).
| Extension pts: Channels (mail/whatsapp/database) toggle per user/type; push
|                activated with the PWA (D-005); additional channels added
|                without schema change to `notifications`.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notify_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('core_users')->cascadeOnDelete();
            $table->string('notification_type');
            $table->boolean('mail_enabled')->default(true);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->boolean('database_enabled')->default(true);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['user_id', 'notification_type'], 'uk_notify_prefs');
        });

        Schema::create('notify_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('core_users')->cascadeOnDelete();
            $table->text('endpoint');
            $table->text('p256dh');
            $table->string('auth');
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notify_push_subscriptions');
        Schema::dropIfExists('notify_preferences');
        Schema::dropIfExists('notifications');
    }
};
