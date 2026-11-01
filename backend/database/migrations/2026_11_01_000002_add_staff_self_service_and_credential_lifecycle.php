<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('password_changed_at')->nullable();
        });
        Schema::table('staff_profiles', function (Blueprint $table): void {
            $table->string('phone', 30)->nullable();
            $table->string('job_title', 120)->nullable();
        });

        // Existing ordinary staff accounts also need a first password rotation.
        // Explicitly exclude superadmin accounts and all applicant-only users.
        $staffRoleIds = DB::table('roles')->whereIn('name', [
            'admin', 'admission_officer', 'staff', 'website_editor',
        ])->pluck('id');
        $superRoleIds = DB::table('roles')->where('name', 'superadmin')->pluck('id');
        if ($staffRoleIds->isNotEmpty()) {
            $staffIds = DB::table('model_has_roles')
                ->where('model_type', \App\Models\User::class)
                ->whereIn('role_id', $staffRoleIds)
                ->pluck('model_id');
            $superIds = DB::table('model_has_roles')
                ->where('model_type', \App\Models\User::class)
                ->whereIn('role_id', $superRoleIds)
                ->pluck('model_id');
            DB::table('users')->whereIn('id', $staffIds->diff($superIds)->values())
                ->update(['must_change_password' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'job_title']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['must_change_password', 'password_changed_at']);
        });
    }
};
