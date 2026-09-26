<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffProfile extends Model
{
    protected $fillable = [
        'user_id',
        'staff_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
