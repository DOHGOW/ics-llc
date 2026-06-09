<?php

namespace App\Notifications\Core;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password reset notification (D-041 / AF-2).
 *
 * INTENTIONALLY does NOT implement ShouldQueue — authentication-critical mail
 * is delivered IMMEDIATELY (never via the delayed queue). It is sent through the
 * application's default mailer, which is the `failover` transport (Brevo →
 * fallback SMTP), satisfying AF-2 / D-039 SPOF-04.
 */
class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @return array<int,string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expire = (int) config('auth.passwords.users.expire', 60);

        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject(__('Reset your ICS account password'))
            ->line(__('You are receiving this email because a password reset was requested for your account.'))
            ->action(__('Reset Password'), $url)
            ->line(__('This password reset link will expire in :count minutes.', ['count' => $expire]))
            ->line(__('If you did not request a password reset, no further action is required.'));
    }
}
