<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionContent extends Model
{
    protected $table = 'stsams_web_contents';
    protected $fillable = ['section', 'content_key', 'body', 'updated_by'];

}
