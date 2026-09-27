<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorStorageFile;
use App\Security\Permission;

class VendorStorageFilePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return $user->hasPermission(Permission::PLATFORM_VENDORS);
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->vendor_id !== null && $user->hasPermission(Permission::STORAGE_VIEW);
    }

    public function view(User $user, VendorStorageFile $file): bool
    {
        return (int) $user->vendor_id === (int) $file->vendor_id
            && $user->hasPermission(Permission::STORAGE_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->vendor_id !== null && $user->hasPermission(Permission::STORAGE_MANAGE);
    }

    public function delete(User $user, VendorStorageFile $file): bool
    {
        return (int) $user->vendor_id === (int) $file->vendor_id
            && $user->hasPermission(Permission::STORAGE_MANAGE);
    }
}
