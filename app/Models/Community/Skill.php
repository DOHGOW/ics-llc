<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;

/** Community skill (reference data). */
class Skill extends Model
{
    protected $table = 'community_skills';

    protected $fillable = ['name', 'slug', 'category'];
}
