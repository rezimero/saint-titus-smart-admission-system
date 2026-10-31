<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionMedia extends Model
{
    protected $table = 'stsams_web_media';
    protected $fillable = ['kind', 'title', 'alt_text', 'file_path', 'is_published', 'uploaded_by'];
    protected function casts(): array { return ['is_published'=>'boolean']; }
}
