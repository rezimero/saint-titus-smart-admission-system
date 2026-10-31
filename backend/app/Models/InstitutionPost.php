<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionPost extends Model
{
    protected $table = 'stsams_web_posts';
    protected $fillable = ['category', 'title', 'slug', 'summary', 'body', 'is_published', 'published_at', 'event_starts_at', 'event_location', 'created_by'];
    protected function casts(): array { return ['is_published'=>'boolean','published_at'=>'datetime','event_starts_at'=>'datetime']; }
}
