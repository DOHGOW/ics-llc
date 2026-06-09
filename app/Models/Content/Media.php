<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CMS Media asset (content_media). `alt_text` is required for images at the
 * controller (WCAG 1.1.1 / W1c-2). Not lifecycle content — it tracks `uploaded_by`
 * (stamped by the controller), not the created_by/updated_by authorship pair.
 */
class Media extends Model
{
    use SoftDeletes;

    protected $table = 'content_media';

    protected $fillable = [
        'tenant_id', 'type', 'file_path', 'original_name', 'mime_type', 'size_kb', 'alt_text', 'uploaded_by',
    ];
}
