<?php

namespace App\Notifications\Core;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * High-sensitivity security alert (R-7). Sent to the security team on sensitive
 * lifecycle actions (role grants/revokes, deactivations, suspensions, deletions,
 * Super Admin escalations).
 *
 * INTENTIONALLY NOT ShouldQueue — security alerting must be timely and is routed
 * through the failover mailer (immediate, with SMTP fallback — AF-2 / D-039).
 */
class SecurityAlertNotification extends Notification
{
    /** @param array<string,mixed> $context */
    public function __construct(
        public string $summary,
        public array $context = [],
    ) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('[ICS Security] '.$this->summary)
            ->line('A high-sensitivity lifecycle action occurred on the ICS platform:')
            ->line($this->summary);

        foreach ($this->context as $key => $value) {
            $message->line(sprintf('%s: %s', $key, is_scalar($value) ? (string) $value : json_encode($value)));
        }

        return $message->line('Review the audit trail (sensitivity = high) for full detail.');
    }
}
