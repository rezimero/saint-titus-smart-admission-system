<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StsamsDocumentRequirement extends Model
{
    protected $table = 'stsams_document_requirements';
    protected $fillable = ['programme_id', 'document_type', 'requirement'];
}
