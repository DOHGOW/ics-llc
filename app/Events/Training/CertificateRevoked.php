<?php

namespace App\Events\Training;

use App\Models\Training\Certificate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A certificate was revoked — HIGH-sensitivity credential event (D-058/D-059). */
class CertificateRevoked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Certificate $certificate,
        public string $reason,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
