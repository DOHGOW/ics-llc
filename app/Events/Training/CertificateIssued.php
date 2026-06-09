<?php

namespace App\Events\Training;

use App\Models\Training\Certificate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A certificate was issued — HIGH-sensitivity credential event (D-058). */
class CertificateIssued
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Certificate $certificate,
        public ?int $actorId = null,
        public ?string $actorRole = null,
    ) {}
}
