<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stsams_admission_applications', function (Blueprint $table): void {
            $table->foreignId('assigned_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->index(['assigned_reviewer_id', 'status'], 'stsams_reviewer_work_queue');
        });
        Schema::table('stsams_application_documents', function (Blueprint $table): void {
            $table->string('verification_status', 16)->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->index(['application_id', 'verification_status'], 'stsams_doc_review_queue');
        });
        Schema::table('stsams_web_contents', function (Blueprint $table): void {
            $table->longText('pending_body')->nullable();
            $table->foreignId('pending_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('stsams_operations_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 48);
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id', 'created_at'], 'stsams_ops_subject');
            $table->index(['actor_id', 'created_at'], 'stsams_ops_actor');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('stsams_operations_audits');
        Schema::table('stsams_web_contents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pending_by');
            $table->dropColumn('pending_body');
        });
        Schema::table('stsams_application_documents', function (Blueprint $table): void {
            $table->dropIndex('stsams_doc_review_queue');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['verification_status', 'verified_at', 'verification_note']);
        });
        Schema::table('stsams_admission_applications', function (Blueprint $table): void {
            $table->dropIndex('stsams_reviewer_work_queue');
            $table->dropConstrainedForeignId('assigned_reviewer_id');
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn('assigned_at');
        });
    }
};
