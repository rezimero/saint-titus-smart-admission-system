<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stsams_programmes', function (Blueprint $table): void {
            $table->string('admission_track', 32)->default('general');
        });
        Schema::table('stsams_admission_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('application_fee_kobo')->nullable();
        });
        Schema::table('stsams_application_documents', function (Blueprint $table): void {
            $table->string('scan_status', 16)->default('unscanned');
            $table->char('sha256', 64)->nullable();
        });
        Schema::create('stsams_document_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('programme_id')->constrained('stsams_programmes')->cascadeOnDelete();
            $table->string('document_type', 32);
            $table->string('requirement', 16); // required, optional, conditional, not_applicable
            $table->timestamps();
            $table->unique(['programme_id', 'document_type'], 'stsams_requirement_per_programme');
        });
        Schema::create('stsams_application_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('stsams_admission_applications')->restrictOnDelete();
            $table->string('reference', 80)->unique();
            $table->string('gateway', 32)->default('paystack');
            $table->string('status', 24)->default('initiated')->index();
            $table->unsignedBigInteger('amount_kobo');
            $table->char('currency', 3)->default('NGN');
            $table->string('payer_email', 254);
            $table->string('gateway_transaction_id', 48)->nullable()->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stsams_application_payments');
        Schema::dropIfExists('stsams_document_requirements');
        Schema::table('stsams_application_documents', function (Blueprint $table): void {
            $table->dropColumn(['scan_status', 'sha256']);
        });
        Schema::table('stsams_admission_sessions', fn (Blueprint $table) => $table->dropColumn('application_fee_kobo'));
        Schema::table('stsams_programmes', fn (Blueprint $table) => $table->dropColumn('admission_track'));
    }
};
