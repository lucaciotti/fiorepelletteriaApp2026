<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProcessType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcessTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProcessType');
    }

    public function view(AuthUser $authUser, ProcessType $processType): bool
    {
        return $authUser->can('View:ProcessType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProcessType');
    }

    public function update(AuthUser $authUser, ProcessType $processType): bool
    {
        return $authUser->can('Update:ProcessType');
    }

    public function delete(AuthUser $authUser, ProcessType $processType): bool
    {
        return $authUser->can('Delete:ProcessType');
    }

    public function restore(AuthUser $authUser, ProcessType $processType): bool
    {
        return $authUser->can('Restore:ProcessType');
    }

    public function forceDelete(AuthUser $authUser, ProcessType $processType): bool
    {
        return $authUser->can('ForceDelete:ProcessType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProcessType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProcessType');
    }

    public function replicate(AuthUser $authUser, ProcessType $processType): bool
    {
        return $authUser->can('Replicate:ProcessType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProcessType');
    }

}