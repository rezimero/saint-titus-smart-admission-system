<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stsams_web_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section', 40);
            $table->string('content_key', 100);
            $table->longText('body')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['section', 'content_key']);
        });
        Schema::create('stsams_web_posts', function (Blueprint $table) {
            $table->id();
            $table->string('category', 24);
            $table->string('title', 180);
            $table->string('slug', 220)->unique();
            $table->text('summary');
            $table->longText('body');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('event_starts_at')->nullable();
            $table->string('event_location', 180)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_published', 'category', 'published_at']);
        });
        Schema::create('stsams_web_media', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 24);
            $table->string('title', 160);
            $table->string('alt_text', 200);
            $table->string('file_path');
            $table->boolean('is_published')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['kind', 'is_published']);
        });
        Schema::create('stsams_web_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 140);
            $table->string('email', 190);
            $table->string('subject', 190);
            $table->text('message');
            $table->string('status', 16)->default('new');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stsams_web_inquiries');
        Schema::dropIfExists('stsams_web_media');
        Schema::dropIfExists('stsams_web_posts');
        Schema::dropIfExists('stsams_web_contents');
    }
};
