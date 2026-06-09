<?php

namespace App\Events\Startup;

use App\Models\Startup\Startup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A startup was created → ONE-WAY CRM lead capture (D-053; founder never sees the lead). */
class StartupCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Startup $startup) {}
}
