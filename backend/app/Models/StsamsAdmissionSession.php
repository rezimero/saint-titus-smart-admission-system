<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StsamsAdmissionSession extends Model
{
    protected $table = 'stsams_admission_sessions';
    protected $fillable = ['name', 'is_open', 'opens_on', 'closes_on'];
    protected function casts(): array
    {
        return ['is_open' => 'boolean', 'opens_on' => 'date', 'closes_on' => 'date'];
    }

    public function acceptsApplications(): bool
    {
        $today = now()->startOfDay();
        return $this->is_open
            && ($this->opens_on === null || $this->opens_on->lte($today))
            && ($this->closes_on === null || $this->closes_on->gte($today));
    }
}
