<?php

namespace Tests\Support;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;

/**
 * Test-only org-owned model used to exercise the isolation framework (Wave 1a)
 * without depending on a real business module. Backed by the `iso_fixtures` table
 * created in the test's setUp.
 */
class IsoFixture extends Model
{
    use BelongsToAccount;

    protected $table = 'iso_fixtures';

    protected $guarded = [];

    public $timestamps = false;
}
