<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stsams_admission_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('programme_id')->constrained('stsams_programmes')->restrictOnDelete();
            $table->foreignId('admission_session_id')->constrained('stsams_admission_sessions')->restrictOnDelete();
            $table->string('reference', 32)->nullable()->unique();
            $table->string('status', 24)->default('draft')->index();
            $table->string('phone', 32)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('prior_school', 200)->nullable();
            $table->string('qualification', 160)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'programme_id', 'admission_session_id'], 'stsams_application_one_per_programme');
            $table->index(['admission_session_id', 'status'], 'stsams_application_queue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stsams_admission_applications');
    }
};
