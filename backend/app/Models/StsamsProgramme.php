<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StsamsProgramme extends Model
{
    protected $table = 'stsams_programmes';
    protected $fillable = ['code', 'name', 'description', 'is_active', 'admission_track'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function documentRequirements(): HasMany { return $this->hasMany(StsamsDocumentRequirement::class, 'programme_id'); }
}
