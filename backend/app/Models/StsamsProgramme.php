<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StsamsProgramme extends Model
{
    protected $table = 'stsams_programmes';
    protected $fillable = ['code', 'name', 'description', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
