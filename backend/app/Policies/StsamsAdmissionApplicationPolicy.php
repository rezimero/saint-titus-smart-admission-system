<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StsamsAdmissionApplication;
use App\Models\User;

final class StsamsAdmissionApplicationPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('applicant') && $user->can('applications.create');
    }

    public function view(User $user, StsamsAdmissionApplication $application): bool
    {
        return $application->user_id === $user->id || $this->viewAny($user);
    }

    public function viewAny(User $user): bool
    {
        return $user->can('applications.review') || $user->can('applications.approve') || $user->can('applications.reject');
    }

    public function update(User $user, StsamsAdmissionApplication $application): bool
    {
        return $application->user_id === $user->id && $application->status === 'draft';
    }

    public function submit(User $user, StsamsAdmissionApplication $application): bool
    {
        return $this->update($user, $application);
    }

    public function review(User $user, StsamsAdmissionApplication $application): bool
    {
        return $user->can('applications.review') && $application->status === 'submitted';
    }

    public function decide(User $user, StsamsAdmissionApplication $application): bool
    {
        return ($user->can('applications.approve') || $user->can('applications.reject'))
            && $application->status === 'under_review';
    }
}
