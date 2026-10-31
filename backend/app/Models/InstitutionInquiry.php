<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionInquiry extends Model
{
    protected $table = 'stsams_web_inquiries';
    protected $fillable = ['name', 'email', 'subject', 'message', 'status'];

}
