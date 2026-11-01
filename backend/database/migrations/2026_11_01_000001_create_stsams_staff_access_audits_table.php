<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stsams_staff_access_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 40);
            $table->json('before_access')->nullable();
            $table->json('after_access');
            $table->timestamps();
            $table->index(['staff_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stsams_staff_access_audits');
    }
};
