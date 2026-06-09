<?php

namespace App\Events\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A content item was published (CMS/Knowledge/Research) — D-038. */
class ContentPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Model $content) {}
}
