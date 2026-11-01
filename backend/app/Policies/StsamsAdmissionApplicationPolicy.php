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
        return $application->user_id === $user->id
            || $user->canAny(['applications.view_all', 'applications.offer_admission', 'applications.reject'])
            || ($user->can('applications.review')
                && in_array($application->status, ['submitted', 'under_review'], true)
                && ($application->assigned_reviewer_id === null || (int) $application->assigned_reviewer_id === $user->id));
    }
    public function viewAny(User $user): bool
    {
        return $user->canAny(['applications.view_all', 'applications.review',
            'applications.offer_admission', 'applications.reject']);
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
        return ($user->hasRole('superadmin') || ($user->can('applications.review') && (int) $application->assigned_reviewer_id === $user->id))
            && $application->status === 'submitted';
    }
    public function offer(User $user, StsamsAdmissionApplication $application): bool
    {
        return $user->can('applications.offer_admission') && $application->status === 'under_review';
    }
    public function reject(User $user, StsamsAdmissionApplication $application): bool
    {
        return $user->can('applications.reject') && $application->status === 'under_review';
    }
}
